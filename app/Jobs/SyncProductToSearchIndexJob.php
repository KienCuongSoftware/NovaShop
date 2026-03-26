<?php

namespace App\Jobs;

use App\Services\ProductSearchService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncProductToSearchIndexJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $productId,
        public string $action = 'upsert'
    ) {}

    public function handle(ProductSearchService $searchService): void
    {
        if ($this->action === 'delete') {
            $searchService->deleteProductById($this->productId);

            return;
        }

        $searchService->upsertProductById($this->productId);
    }
}

