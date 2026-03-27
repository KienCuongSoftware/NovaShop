<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ProductReviewRejectedMail;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminProductReviewController extends Controller
{
    public function index(Request $request)
    {
        $reviews = ProductReview::query()
            ->where('is_approved', false)
            ->with(['user', 'product', 'images'])
            ->latest()
            ->paginate(20);

        return view('admin.product_reviews.index', compact('reviews'));
    }

    public function approve(ProductReview $review): RedirectResponse
    {
        $review->is_approved = true;
        $review->approved_at = now();
        $review->rejected_at = null;
        $review->rejection_reason = null;
        $review->save();

        return redirect()->route('admin.product-reviews.index')->with('success', 'Đã duyệt đánh giá.');
    }

    public function reject(Request $request, ProductReview $review): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $review->loadMissing(['user', 'product']);

        $review->is_approved = false;
        $review->approved_at = null;
        $review->rejected_at = now();
        $review->rejection_reason = $validated['reason'] ?? null;
        $review->save();

        try {
            if ($review->user?->email) {
                Mail::to($review->user->email)->send(new ProductReviewRejectedMail($review));
            }
        } catch (\Throwable $e) {
            Log::warning('Send ProductReviewRejectedMail failed', [
                'review_id' => $review->id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.product-reviews.index')->with('success', 'Đã từ chối đánh giá.');
    }
}

