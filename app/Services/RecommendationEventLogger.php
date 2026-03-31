<?php

namespace App\Services;

use App\Models\Order;
use App\Models\RecommendationEvent;
use App\Models\User;

class RecommendationEventLogger
{
    public const EVENT_IMPRESSION = 'impression';
    public const EVENT_CLICK = 'click';
    public const EVENT_ADD_TO_CART = 'add_to_cart';
    public const EVENT_PURCHASE = 'purchase';

    public function logImpressions(?User $user, string $variant, array $productIds, array $meta = []): void
    {
        $productIds = array_values(array_unique(array_map('intval', array_filter($productIds))));
        if (empty($productIds)) {
            return;
        }

        $now = now();
        $rows = [];
        foreach ($productIds as $pid) {
            $rows[] = [
                'user_id' => $user?->id,
                'product_id' => $pid,
                'variant' => $this->normalizeVariant($variant),
                'event_type' => self::EVENT_IMPRESSION,
                'source' => 'suggested',
                'session_id' => $this->safeSessionId(),
                'route_name' => request()->route()?->getName(),
                'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        RecommendationEvent::query()->insert($rows);
    }

    public function logClick(?User $user, int $productId, string $variant, array $meta = []): void
    {
        $this->logSingle(self::EVENT_CLICK, $user, $productId, $variant, $meta);
    }

    public function logAddToCart(?User $user, int $productId, string $variant, array $meta = []): void
    {
        $this->logSingle(self::EVENT_ADD_TO_CART, $user, $productId, $variant, $meta);
    }

    public function logPurchaseForOrder(Order $order, string $defaultVariant = 'v1', array $variantByProduct = []): void
    {
        $order->loadMissing('items');
        foreach ($order->items as $item) {
            $pid = (int) ($item->product_id ?? 0);
            if ($pid <= 0) {
                continue;
            }
            $variant = (string) ($variantByProduct[$pid] ?? $defaultVariant);
            $this->logSingle(self::EVENT_PURCHASE, $order->user, $pid, $variant, [
                'order_id' => $order->id,
                'quantity' => (int) $item->quantity,
                'price' => (int) $item->price,
                'payment_method' => $order->payment_method,
            ]);
        }
    }

    protected function logSingle(string $eventType, ?User $user, int $productId, string $variant, array $meta = []): void
    {
        RecommendationEvent::query()->create([
            'user_id' => $user?->id,
            'product_id' => $productId > 0 ? $productId : null,
            'variant' => $this->normalizeVariant($variant),
            'event_type' => $eventType,
            'source' => 'suggested',
            'session_id' => $this->safeSessionId(),
            'route_name' => request()->route()?->getName(),
            'meta' => $meta,
        ]);
    }

    protected function normalizeVariant(string $variant): string
    {
        return in_array($variant, [RecommendationService::VARIANT_V1, RecommendationService::VARIANT_V2], true)
            ? $variant
            : RecommendationService::VARIANT_V1;
    }

    protected function safeSessionId(): ?string
    {
        try {
            return session()->getId();
        } catch (\Throwable) {
            return null;
        }
    }
}
