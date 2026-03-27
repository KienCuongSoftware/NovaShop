<?php

namespace App\Services;

use App\Models\SearchSynonym;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductSearchService
{
    /**
     * Lấy synonyms theo keyword (DB-backed).
     * Ví dụ: keyword="iphone" => ["iphone 11", "apple iphone", ...]
     */
    public function getSynonyms(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        // Normalize to lower for matching (database rows should ideally be stored lower-case).
        $key = mb_strtolower($keyword);

        return SearchSynonym::query()
            ->where('keyword', $key)
            ->pluck('synonym')
            ->filter(fn ($s) => is_string($s) && trim($s) !== '')
            ->values()
            ->all();
    }

    public function isEnabled(): bool
    {
        return (bool) config('services.elasticsearch.enabled')
            && filled(config('services.elasticsearch.host'))
            && filled(config('services.elasticsearch.index'));
    }

    /**
     * Tìm kiếm sản phẩm bằng Elasticsearch và trả về danh sách product IDs theo thứ tự relevance.
     * Trả về null nếu ES tắt hoặc lỗi (để caller fallback DB LIKE).
     */
    public function searchProductIds(string $keyword, ?int $categoryId = null, int $limit = 500): ?array
    {
        if (! $this->isEnabled() || $keyword === '') {
            return null;
        }

        $synonyms = $this->getSynonyms($keyword);
        $terms = array_values(array_unique(array_merge([$keyword], $synonyms)));

        $host = rtrim((string) config('services.elasticsearch.host'), '/');
        $index = (string) config('services.elasticsearch.index');
        $url = "{$host}/{$index}/_search";

        $should = array_map(function (string $term) {
            return [
                'multi_match' => [
                    'query' => $term,
                    'fields' => ['name^3', 'description', 'category_name', 'brand_name'],
                    'fuzziness' => 'AUTO',
                ],
            ];
        }, $terms);

        $boolQuery = [
            'should' => $should,
            'minimum_should_match' => 1,
        ];

        if ($categoryId !== null) {
            $boolQuery['must'] = [['term' => ['category_id' => $categoryId]]];
        }

        try {
            $response = Http::timeout((int) config('services.elasticsearch.timeout', 2))
                ->acceptJson()
                ->post($url, [
                    'size' => $limit,
                    '_source' => false,
                    'query' => ['bool' => $boolQuery],
                ]);

            if (! $response->successful()) {
                Log::warning('Elasticsearch search request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $hits = $response->json('hits.hits', []);

            return collect($hits)
                ->map(fn ($hit) => (int) data_get($hit, '_id'))
                ->filter(fn (int $id) => $id > 0)
                ->values()
                ->all();
        } catch (\Throwable $e) {
            Log::warning('Elasticsearch search exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Reindex toàn bộ sản phẩm active lên Elasticsearch.
     */
    public function reindexAllProducts(): void
    {
        if (! $this->isEnabled()) {
            throw new \RuntimeException('Elasticsearch is not enabled/configured.');
        }

        $host = rtrim((string) config('services.elasticsearch.host'), '/');
        $index = (string) config('services.elasticsearch.index');

        Http::timeout(10)->put("{$host}/{$index}", [
            'mappings' => [
                'properties' => [
                    'name' => ['type' => 'text'],
                    'description' => ['type' => 'text'],
                    'category_id' => ['type' => 'integer'],
                    'category_name' => ['type' => 'text'],
                    'brand_name' => ['type' => 'text'],
                    'price' => ['type' => 'double'],
                    'is_active' => ['type' => 'boolean'],
                ],
            ],
        ]);

        Product::query()
            ->with(['category', 'brand'])
            ->where('is_active', true)
            ->chunkById(200, function ($products) use ($host, $index) {
                $lines = [];

                foreach ($products as $product) {
                    $lines[] = json_encode(['index' => ['_index' => $index, '_id' => (string) $product->id]], JSON_UNESCAPED_UNICODE);
                    $lines[] = json_encode([
                        'name' => (string) $product->name,
                        'description' => (string) ($product->description ?? ''),
                        'category_id' => (int) $product->category_id,
                        'category_name' => (string) ($product->category->name ?? ''),
                        'brand_name' => (string) ($product->brand->name ?? ''),
                        'price' => (float) $product->price,
                        'is_active' => (bool) $product->is_active,
                    ], JSON_UNESCAPED_UNICODE);
                }

                if (empty($lines)) {
                    return;
                }

                $body = implode("\n", $lines)."\n";
                Http::timeout(10)
                    ->withBody($body, 'application/x-ndjson')
                    ->post("{$host}/_bulk?refresh=true");
            });
    }

    /**
     * Index 1 product; nếu không active hoặc đã bị xóa mềm thì xóa khỏi index.
     */
    public function upsertProductById(int $productId): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $product = Product::with(['category', 'brand'])->withTrashed()->find($productId);
        if (! $product || $product->trashed() || ! $product->is_active) {
            $this->deleteProductById($productId);

            return;
        }

        $host = rtrim((string) config('services.elasticsearch.host'), '/');
        $index = (string) config('services.elasticsearch.index');
        $url = "{$host}/{$index}/_doc/{$productId}";

        Http::timeout((int) config('services.elasticsearch.timeout', 2))
            ->acceptJson()
            ->put($url, [
                'name' => (string) $product->name,
                'description' => (string) ($product->description ?? ''),
                'category_id' => (int) $product->category_id,
                'category_name' => (string) ($product->category->name ?? ''),
                'brand_name' => (string) ($product->brand->name ?? ''),
                'price' => (float) $product->price,
                'is_active' => (bool) $product->is_active,
            ]);
    }

    public function deleteProductById(int $productId): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        $host = rtrim((string) config('services.elasticsearch.host'), '/');
        $index = (string) config('services.elasticsearch.index');
        $url = "{$host}/{$index}/_doc/{$productId}";

        try {
            Http::timeout((int) config('services.elasticsearch.timeout', 2))
                ->acceptJson()
                ->delete($url);
        } catch (\Throwable $e) {
            Log::warning('Elasticsearch delete exception', ['message' => $e->getMessage(), 'product_id' => $productId]);
        }
    }

    /**
     * Gợi ý autocomplete bằng prefix trên name.
     */
    public function suggest(string $keyword, int $limit = 8): ?array
    {
        $keyword = trim($keyword);
        if (! $this->isEnabled() || $keyword === '') {
            return null;
        }

        $synonyms = $this->getSynonyms($keyword);
        $terms = array_values(array_unique(array_merge([$keyword], $synonyms)));
        $terms = array_slice($terms, 0, 4); // tránh gọi quá nhiều query tới ES

        $host = rtrim((string) config('services.elasticsearch.host'), '/');
        $index = (string) config('services.elasticsearch.index');
        $url = "{$host}/{$index}/_search";

        try {
            $byId = [];
            foreach ($terms as $term) {
                $response = Http::timeout((int) config('services.elasticsearch.timeout', 2))
                    ->acceptJson()
                    ->post($url, [
                        'size' => $limit,
                        '_source' => ['name', 'price'],
                        'query' => [
                            'bool' => [
                                'must' => [[
                                    'match_phrase_prefix' => [
                                        'name' => [
                                            'query' => $term,
                                        ],
                                    ],
                                ]],
                            ],
                        ],
                    ]);

                if (! $response->successful()) {
                    continue;
                }

                $hits = $response->json('hits.hits', []);
                foreach ($hits as $hit) {
                    $id = (int) data_get($hit, '_id');
                    if ($id <= 0) {
                        continue;
                    }
                    $name = (string) data_get($hit, '_source.name', '');
                    if ($name === '') {
                        continue;
                    }

                    if (! isset($byId[$id])) {
                        $byId[$id] = [
                            'id' => $id,
                            'name' => $name,
                            'price' => (float) data_get($hit, '_source.price', 0),
                        ];
                    }
                }
            }

            return array_values($byId);
        } catch (\Throwable $e) {
            Log::warning('Elasticsearch suggest exception', ['message' => $e->getMessage()]);

            return null;
        }
    }
}

