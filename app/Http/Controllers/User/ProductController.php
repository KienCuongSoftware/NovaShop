<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\CompareItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockNotificationSubscription;
use App\Models\WishlistItem;
use App\Services\RecommendationEventLogger;
use App\Services\ShippingFeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('category')->oldest()->get();

        return view('user.products.index', compact('products'));
    }

    public function show(Product $product, Request $request)
    {
        $product->load(['category.parent.parent', 'brand', 'variants.attributeValues.attribute', 'variants.images']);
        $recSource = trim((string) $request->query('rec_src', ''));
        $recVariant = trim((string) $request->query('rec_variant', ''));
        if ($recSource === 'suggested') {
            app(RecommendationEventLogger::class)->logClick(
                Auth::user(),
                (int) $product->id,
                $recVariant !== '' ? $recVariant : (string) session('rec_ab_variant', 'v1'),
                ['surface' => 'welcome_suggested']
            );
        }
        $recentIds = session('recent_product_ids', []);
        $recentIds = array_filter(array_unique(array_merge([$product->id], $recentIds)));
        session(['recent_product_ids' => array_slice($recentIds, 0, 15)]);
        if ($product->category_id) {
            $catIds = session('recent_category_ids', []);
            $catIds = array_filter(array_unique(array_merge([$product->category_id], $catIds)));
            session(['recent_category_ids' => array_slice($catIds, 0, 5)]);
        }

        $activeFlashSale = \App\Models\FlashSale::active()->with('items')->first();
        $flashItemsByVariantId = [];
        $flashSaleEndTime = null;
        if ($activeFlashSale) {
            $flashSaleEndTime = $activeFlashSale->end_time->toIso8601String();
            foreach ($activeFlashSale->items as $item) {
                $flashItemsByVariantId[$item->product_variant_id] = [
                    'sale_price' => (float) $item->sale_price,
                    'remaining' => $item->remaining,
                ];
            }
        }

        $baseReviewsQuery = \App\Models\ProductReview::query()
            ->where('product_id', $product->id)
            ->where('is_approved', true);

        $ratingFilterRaw = $request->query('rating');
        $ratingFilter = in_array((int) $ratingFilterRaw, [1, 2, 3, 4, 5], true) ? (int) $ratingFilterRaw : null;

        $reviewCount = (int) (clone $baseReviewsQuery)->count();
        $avgRating = $reviewCount > 0 ? (float) (clone $baseReviewsQuery)->avg('rating') : 0.0;
        $avgRating = round($avgRating, 1);

        $reviewDistribution = (clone $baseReviewsQuery)
            ->selectRaw('rating, COUNT(*) as cnt')
            ->groupBy('rating')
            ->pluck('cnt', 'rating');

        $reviewsQuery = (clone $baseReviewsQuery);
        if ($ratingFilter !== null) {
            $reviewsQuery->where('rating', $ratingFilter);
        }

        $reviews = $reviewsQuery
            ->with(['user', 'images'])
            ->orderByDesc('created_at')
            ->paginate(10);

        if ($ratingFilter !== null) {
            $reviews->appends(['rating' => $ratingFilter]);
        }

        if ($request->boolean('reviews_partial')) {
            $myReview = null;
            if (Auth::check()) {
                $myReview = \App\Models\ProductReview::query()
                    ->where('product_id', $product->id)
                    ->where('user_id', Auth::id())
                    ->with('images')
                    ->first();
            }
            $canReviewProduct = Auth::check()
                && Order::userHasDeliveredPurchase((int) Auth::id(), (int) $product->id);

            return view('user.products._reviews_block', compact('product', 'reviewCount', 'avgRating', 'reviewDistribution', 'reviews', 'myReview', 'canReviewProduct'));
        }

        $myReview = null;
        if (Auth::check()) {
            $myReview = \App\Models\ProductReview::query()
                ->where('product_id', $product->id)
                ->where('user_id', Auth::id())
                ->with('images')
                ->first();
        }

        $canReviewProduct = Auth::check()
            && Order::userHasDeliveredPurchase((int) Auth::id(), (int) $product->id);

        $rawIds = DB::table('order_items as oi')
            ->join('order_items as oi2', function ($join) {
                $join->on('oi.order_id', '=', 'oi2.order_id')
                    ->whereColumn('oi2.product_id', '!=', 'oi.product_id');
            })
            ->where('oi.product_id', $product->id)
            ->whereNull('oi.deleted_at')
            ->whereNull('oi2.deleted_at')
            ->select('oi2.product_id', DB::raw('COUNT(*) as pair_count'))
            ->groupBy('oi2.product_id')
            ->orderByDesc('pair_count')
            ->limit(8)
            ->pluck('oi2.product_id');
        $order = $rawIds->all();
        $boughtTogetherProducts = collect();
        if (count($order) > 0) {
            $boughtTogetherProducts = Product::query()
                ->whereIn('id', $order)
                ->where('is_active', true)
                ->get()
                ->sortBy(fn ($p) => array_search($p->id, $order, true))
                ->values();
        }

        $inWishlist = false;
        $onCompare = false;
        $stockSubscribedVariantIds = collect();
        $stockSubscribedSimple = false;
        if (Auth::check()) {
            $inWishlist = WishlistItem::where('user_id', Auth::id())->where('product_id', $product->id)->exists();
            $onCompare = CompareItem::where('user_id', Auth::id())->where('product_id', $product->id)->exists();
            $subs = StockNotificationSubscription::where('user_id', Auth::id())
                ->where('product_id', $product->id)
                ->get(['product_variant_id']);
            $stockSubscribedVariantIds = $subs->pluck('product_variant_id')->filter(fn ($id) => $id !== null)->values();
            $stockSubscribedSimple = $subs->contains(fn ($s) => $s->product_variant_id === null);
        }

        $previewKmForEstimate = (float) config('delivery.preview_assumed_km', 15);
        $previewShippingFee = null;
        $previewDistanceKm = null;
        $previewShippingHint = null;
        if (Auth::check()) {
            /** @var \App\Models\User|null $authUser */
            $authUser = Auth::user();
            $addr = $authUser?->addresses()->orderByDesc('is_default')->orderBy('id')->first();
            if ($addr && $addr->hasCoordinates()) {
                $calc = ShippingFeeService::calculate((float) $addr->lat, (float) $addr->lng);
                $previewShippingFee = $calc['fee'];
                $previewDistanceKm = $calc['distance_km'];
                $previewKmForEstimate = (float) $previewDistanceKm;
            }
        }
        if ($previewShippingFee === null) {
            $calc = ShippingFeeService::calculate(null, null);
            $previewShippingFee = $calc['fee'];
            if ($previewDistanceKm === null) {
                $previewShippingHint = Auth::check()
                    ? 'Thêm tọa độ địa chỉ trên bản đồ để xem phí ship & ngày giao chính xác hơn.'
                    : 'Đăng nhập và lưu địa chỉ giao hàng để ước tính chính xác hơn.';
            }
        }
        [$previewDateFrom, $previewDateTo] = Order::estimatedDeliveryDateRangeFromDistanceKm($previewKmForEstimate, now());
        $productShippingPreview = [
            'fee' => (int) $previewShippingFee,
            'distance_km' => $previewDistanceKm,
            'date_from' => $previewDateFrom,
            'date_to' => $previewDateTo,
            'hint' => $previewShippingHint,
        ];

        return view('user.products.show', compact(
            'product',
            'activeFlashSale',
            'flashItemsByVariantId',
            'flashSaleEndTime',
            'reviewCount',
            'avgRating',
            'reviewDistribution',
            'reviews',
            'myReview',
            'canReviewProduct',
            'boughtTogetherProducts',
            'inWishlist',
            'onCompare',
            'stockSubscribedVariantIds',
            'stockSubscribedSimple',
            'productShippingPreview'
        ));
    }
}
