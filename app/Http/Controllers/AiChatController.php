<?php

namespace App\Http\Controllers;

use App\Models\AiChatMessage;
use App\Services\OpenAiChatService;
use App\Services\ProductSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AiChatController extends Controller
{
    private const SESSION_KEY = 'ai_chat_messages';

    private const MAX_HISTORY_MESSAGES = 24;

    private const MAX_TOOL_ROUNDS = 5;

    private const SYSTEM_PROMPT = <<<'PROMPT'
Bạn là trợ lý ảo của cửa hàng trực tuyến NovaShop (Việt Nam). Trả lời ngắn gọn, thân thiện, bằng tiếng Việt.

Khi khách hỏi về sản phẩm cụ thể, gợi ý mua, "có bán ... không", tìm đồ theo nhu cầu, hoặc so sánh mặt hàng — bạn PHẢI gọi công cụ `search_products` với tham số `query` là từ khóa tìm kiếm ngắn bằng tiếng Việt (có thể thử 1–2 lần với từ khóa khác nếu lần đầu không có kết quả).

Chỉ mô tả giá, tên sản phẩm, tồn kho dựa trên dữ liệu JSON trả về từ công cụ. Không bịa sản phẩm. Nếu công cụ trả về danh sách rỗng, nói rõ là hiện không tìm thấy và gợi ý khách đổi từ khóa hoặc xem trang danh mục.

Với câu hỏi chung (đặt hàng, ship, đổi trả, thanh toán) không cần gọi công cụ trừ khi khách kết hợp hỏi sản phẩm.
PROMPT;

    /** @return array<int, array{type: string, function: array{name: string, description: string, parameters: array<string, mixed>}}> */
    private function productToolDefinitions(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'search_products',
                    'description' => 'Tìm sản phẩm đang bán trên NovaShop theo từ khóa (tên, loại hàng, mô tả ngắn). Trả về danh sách thật từ database.',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'query' => [
                                'type' => 'string',
                                'description' => 'Từ khóa tiếng Việt, ví dụ: quần túi hộp, áo thun nam, tai nghe bluetooth',
                            ],
                        ],
                        'required' => ['query'],
                    ],
                ],
            ],
        ];
    }

    public function index()
    {
        return view('ai-chat.index');
    }

    public function history(Request $request)
    {
        $user = $request->user() ?: Auth::user();
        if (! $user) {
            return response()->json(['messages' => []]);
        }

        try {
            $rows = AiChatMessage::query()
                ->where('user_id', $user->id)
                ->orderBy('id')
                ->limit(100)
                ->get();
        } catch (\Throwable) {
            return response()->json(['messages' => []]);
        }

        return response()->json([
            'messages' => $rows->map(fn (AiChatMessage $m) => [
                'role' => $m->role,
                'content' => $m->content,
                'products' => $m->products ?? [],
            ])->values()->all(),
        ]);
    }

    public function send(Request $request, OpenAiChatService $openAi, ProductSearchService $productSearch)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        if (! config('services.openai.api_key')) {
            return response()->json([
                'error' => 'Chưa cấu hình OPENAI_API_KEY. Thêm khóa vào file .env và chạy lại.',
            ], 503);
        }

        $user = $request->user() ?: Auth::user();

        if ($user) {
            $historyForApi = [];
            try {
                $historyForApi = AiChatMessage::query()
                    ->where('user_id', $user->id)
                    ->orderByDesc('id')
                    ->limit(self::MAX_HISTORY_MESSAGES)
                    ->get()
                    ->reverse()
                    ->values()
                    ->map(fn (AiChatMessage $m) => [
                        'role' => $m->role,
                        'content' => $m->content,
                    ])
                    ->all();
            } catch (\Throwable $e) {
                Log::warning('AI chat: không đọc được lịch sử DB', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $messages = array_merge(
                [['role' => 'system', 'content' => self::SYSTEM_PROMPT]],
                $historyForApi,
                [['role' => 'user', 'content' => $validated['message']]],
            );
        } else {
            $history = session(self::SESSION_KEY, []);
            $history[] = ['role' => 'user', 'content' => $validated['message']];

            if (count($history) > self::MAX_HISTORY_MESSAGES) {
                $history = array_slice($history, -self::MAX_HISTORY_MESSAGES);
            }

            $messages = array_merge(
                [['role' => 'system', 'content' => self::SYSTEM_PROMPT]],
                $history
            );
        }

        $reply = '';
        $productsPayload = [];

        try {
            if (config('services.openai.product_tools')) {
                [$reply, $productsPayload] = $this->runChatWithProductTools($openAi, $productSearch, $messages);
            } else {
                $data = $openAi->chatCompletion(['messages' => $messages]);
                $reply = trim((string) data_get($data, 'choices.0.message.content', ''));
            }
        } catch (\Throwable) {
            return response()->json([
                'error' => 'Không thể kết nối AI. Vui lòng thử lại sau.',
            ], 502);
        }

        if ($reply === '' && $productsPayload !== []) {
            $reply = 'Dưới đây là một số sản phẩm gợi ý trên NovaShop:';
        }

        if ($reply === '') {
            return response()->json([
                'error' => 'AI không trả lời được. Vui lòng thử lại.',
            ], 502);
        }

        if ($user) {
            try {
                DB::transaction(function () use ($user, $validated, $reply, $productsPayload) {
                    AiChatMessage::query()->create([
                        'user_id' => $user->id,
                        'role' => 'user',
                        'content' => $validated['message'],
                        'products' => null,
                    ]);
                    AiChatMessage::query()->create([
                        'user_id' => $user->id,
                        'role' => 'assistant',
                        'content' => $reply,
                        'products' => $productsPayload !== [] ? $productsPayload : null,
                    ]);
                });
            } catch (\Throwable $e) {
                Log::warning('AI chat: không lưu được lịch sử DB', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $this->pruneAiChatMessages($user->id);
            } catch (\Throwable $e) {
                Log::warning('AI chat: prune lịch sử thất bại', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        } else {
            $history = session(self::SESSION_KEY, []);
            $history[] = ['role' => 'user', 'content' => $validated['message']];
            $history[] = ['role' => 'assistant', 'content' => $reply];
            if (count($history) > self::MAX_HISTORY_MESSAGES) {
                $history = array_slice($history, -self::MAX_HISTORY_MESSAGES);
            }
            session([self::SESSION_KEY => $history]);
        }

        return response()->json([
            'reply' => $reply,
            'products' => $productsPayload,
        ]);
    }

    private function pruneAiChatMessages(int $userId): void
    {
        $max = max(40, (int) config('services.openai.chat_history_max_rows', 200));
        $keepIds = AiChatMessage::query()
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->limit($max)
            ->pluck('id');

        if ($keepIds->isEmpty()) {
            return;
        }

        AiChatMessage::query()
            ->where('user_id', $userId)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    /**
     * @param  array<int, array<string, mixed>>  $messages
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function runChatWithProductTools(
        OpenAiChatService $openAi,
        ProductSearchService $productSearch,
        array $messages
    ): array {
        $tools = $this->productToolDefinitions();
        $lastProducts = [];

        for ($round = 0; $round < self::MAX_TOOL_ROUNDS; $round++) {
            $data = $openAi->chatCompletion([
                'messages' => $messages,
                'tools' => $tools,
                'tool_choice' => 'auto',
            ]);

            $choice = $data['choices'][0] ?? null;
            if (! is_array($choice)) {
                break;
            }

            $msg = $choice['message'] ?? [];
            $finish = $choice['finish_reason'] ?? 'stop';

            if ($finish === 'tool_calls' && ! empty($msg['tool_calls'])) {
                $messages[] = $msg;

                foreach ($msg['tool_calls'] as $tc) {
                    if (! is_array($tc)) {
                        continue;
                    }
                    $fn = (string) data_get($tc, 'function.name', '');
                    $argsRaw = data_get($tc, 'function.arguments', '{}');
                    $args = is_string($argsRaw) ? json_decode($argsRaw, true) : (is_array($argsRaw) ? $argsRaw : []);
                    if (! is_array($args)) {
                        $args = [];
                    }
                    $toolCallId = (string) ($tc['id'] ?? '');

                    if ($fn === 'search_products') {
                        $q = trim((string) ($args['query'] ?? ''));
                        $lastProducts = $productSearch->searchProductsForChat($q, (int) config('services.openai.chat_product_limit', 8));
                        $toolContent = json_encode([
                            'products' => $lastProducts,
                            'count' => count($lastProducts),
                        ], JSON_UNESCAPED_UNICODE);
                    } else {
                        $toolContent = json_encode(['error' => 'unknown_tool'], JSON_UNESCAPED_UNICODE);
                    }

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCallId,
                        'content' => $toolContent,
                    ];
                }

                continue;
            }

            $reply = trim((string) ($msg['content'] ?? ''));

            return [$reply, $lastProducts];
        }

        return ['Xin lỗi, xử lý hơi lâu. Bạn thử hỏi lại ngắn gọn hơn nhé.', $lastProducts];
    }

    public function clear(Request $request)
    {
        $user = $request->user() ?: Auth::user();

        if ($user) {
            try {
                AiChatMessage::query()->where('user_id', $user->id)->delete();
            } catch (\Throwable $e) {
                Log::warning('AI chat: xóa lịch sử DB thất bại', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        session()->forget(self::SESSION_KEY);

        return response()->json(['ok' => true]);
    }
}
