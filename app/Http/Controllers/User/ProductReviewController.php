<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductReviewImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $user = Auth::user();

        if (! Order::userHasDeliveredPurchase((int) $user->id, (int) $product->id)) {
            return redirect()
                ->route('products.show', $product)
                ->with('error', 'Chỉ khách đã mua và nhận hàng thành công mới được đánh giá sản phẩm này.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'content' => 'required|string|max:2000',
            'variant_classification' => 'nullable|string|max:255',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $existing = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('user_id', $user->id)
            ->first();

        $isVerified = Order::userHasDeliveredPurchase((int) $user->id, (int) $product->id);

        $isApproved = $isVerified;
        $now = now();

        $review = $existing ?: new ProductReview();
        $review->product_id = $product->id;
        $review->user_id = $user->id;

        $review->rating = (int) $validated['rating'];
        $review->title = $validated['title'] ?? null;
        $review->content = $validated['content'];
        $review->variant_classification = $validated['variant_classification'] ?? null;

        $review->is_verified = $isVerified;
        $review->is_approved = $isApproved;
        $review->approved_at = $isApproved ? $now : null;
        $review->rejected_at = null;
        $review->rejection_reason = null;

        $review->save();

        if ($request->hasFile('images')) {
            $review->images()->delete();

            $sort = 0;
            foreach ($request->file('images', []) as $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }

                $storedPath = $file->store('review_images', 'public');
                if (! $storedPath) {
                    continue;
                }

                ProductReviewImage::query()->create([
                    'product_review_id' => $review->id,
                    'path' => $storedPath,
                    'sort' => $sort++,
                ]);
            }
        }

        $message = $isApproved
            ? 'Cảm ơn bạn! Đánh giá của bạn đã được đăng.'
            : 'Cảm ơn bạn! Đánh giá của bạn đang chờ duyệt.';

        return redirect()->route('products.show', $product)->with('success', $message);
    }
}
