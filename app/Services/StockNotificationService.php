<?php

namespace App\Services;

use App\Mail\ProductBackInStockMail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockNotificationSubscription;
use Illuminate\Support\Facades\Mail;

class StockNotificationService
{
    public function notifyVariantAvailable(ProductVariant $variant): void
    {
        $subs = StockNotificationSubscription::query()
            ->where('product_id', $variant->product_id)
            ->where('product_variant_id', $variant->id)
            ->whereNull('notified_at')
            ->with('user')
            ->get();

        foreach ($subs as $sub) {
            $this->sendForSubscription($sub, $variant->product, $variant);
        }
    }

    public function notifySimpleProductAvailable(Product $product): void
    {
        if ($product->hasVariants()) {
            return;
        }

        $subs = StockNotificationSubscription::query()
            ->where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->whereNull('notified_at')
            ->with('user')
            ->get();

        foreach ($subs as $sub) {
            $this->sendForSubscription($sub, $product, null);
        }
    }

    protected function sendForSubscription(
        StockNotificationSubscription $sub,
        Product $product,
        ?ProductVariant $variant
    ): void {
        if ($variant) {
            $variant->loadMissing('attributeValues.attribute');
        }

        $email = $sub->email ?: $sub->user?->email;
        if (!$email) {
            return;
        }

        try {
            Mail::to($email)->send(new ProductBackInStockMail($product, $variant, $sub->user));
        } catch (\Throwable $e) {
            report($e);

            return;
        }

        $sub->forceFill(['notified_at' => now()])->save();
    }
}
