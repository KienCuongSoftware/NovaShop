<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'products' => Product::count(),
            'orders' => Order::count(),
            'users' => User::where('is_admin', false)->count(),
            'categories' => Category::count(),
            'revenue' => (int) Order::where('status', Order::STATUS_COMPLETED)->sum('total_amount'),
        ];

        $revenueByDayLabels = [];
        $revenueByDayValues = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $revenueByDayLabels[] = $date->format('d/m');
            $revenueByDayValues[] = (float) Order::query()
                ->where('status', Order::STATUS_COMPLETED)
                ->whereDate('created_at', $date)
                ->sum('total_amount');
        }

        $topSkuRows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', Order::STATUS_COMPLETED)
            ->whereNull('orders.deleted_at')
            ->whereNull('order_items.deleted_at')
            ->selectRaw('order_items.product_id, order_items.product_variant_id, SUM(order_items.quantity) as qty_sold')
            ->groupBy('order_items.product_id', 'order_items.product_variant_id')
            ->orderByDesc('qty_sold')
            ->limit(10)
            ->get();

        $productIds = $topSkuRows->pluck('product_id')->unique()->filter()->all();
        $variantIds = $topSkuRows->pluck('product_variant_id')->filter()->unique()->all();
        $productsById = $productIds !== []
            ? Product::query()->withTrashed()->whereIn('id', $productIds)->get()->keyBy('id')
            : collect();
        $variantsById = $variantIds !== []
            ? ProductVariant::query()->withTrashed()->whereIn('id', $variantIds)->get()->keyBy('id')
            : collect();

        $topSkus = $topSkuRows->map(function ($row) use ($productsById, $variantsById) {
            $product = $productsById->get($row->product_id);
            $variant = $row->product_variant_id ? $variantsById->get($row->product_variant_id) : null;
            $name = $product?->name ?? ('Sản phẩm #'.$row->product_id);
            $sku = $variant && $variant->sku ? $variant->sku : null;

            return [
                'product_id' => (int) $row->product_id,
                'product_variant_id' => $row->product_variant_id ? (int) $row->product_variant_id : null,
                'name' => $name,
                'sku' => $sku,
                'qty_sold' => (int) $row->qty_sold,
            ];
        });

        $from30 = Carbon::today()->subDays(29)->startOfDay();
        $orders30Query = Order::query()->where('created_at', '>=', $from30);
        $cancelRate30Total = (clone $orders30Query)->count();
        $cancelRate30Cancelled = (clone $orders30Query)->where('status', Order::STATUS_CANCELLED)->count();
        $cancelRate30Pct = $cancelRate30Total > 0
            ? round(100 * $cancelRate30Cancelled / $cancelRate30Total, 2)
            : null;

        $cancelRateAllTotal = Order::count();
        $cancelRateAllCancelled = Order::where('status', Order::STATUS_CANCELLED)->count();
        $cancelRateAllPct = $cancelRateAllTotal > 0
            ? round(100 * $cancelRateAllCancelled / $cancelRateAllTotal, 2)
            : null;

        $ordersByStatus = Order::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $statusLabels = Order::statusLabels();
        $chartStatusLabels = [];
        $chartStatusData = [];
        $chartStatusColors = [
            'unpaid' => '#ffc107',
            'payment_failed' => '#fd7e14',
            'pending' => '#17a2b8',
            'processing' => '#007bff',
            'shipping' => '#6f42c1',
            'awaiting_delivery' => '#20c997',
            'completed' => '#28a745',
            'cancelled' => '#6c757d',
            'return_refund' => '#e83e8c',
        ];
        $chartStatusColorsOrdered = [];
        foreach (['pending', 'processing', 'shipping', 'completed', 'cancelled', 'unpaid'] as $key) {
            $chartStatusLabels[] = $statusLabels[$key] ?? $key;
            $chartStatusData[] = $ordersByStatus[$key] ?? 0;
            $chartStatusColorsOrdered[] = $chartStatusColors[$key] ?? '#6c757d';
        }

        $last30Days = [];
        $last30DaysLabels = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $last30DaysLabels[] = $date->format('d/m');
            $last30Days[] = Order::whereDate('created_at', $date)->count();
        }

        $recentOrders = Order::with('user:id,name')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard.index', compact(
            'stats',
            'chartStatusLabels',
            'chartStatusData',
            'chartStatusColorsOrdered',
            'last30DaysLabels',
            'last30Days',
            'recentOrders',
            'revenueByDayLabels',
            'revenueByDayValues',
            'topSkus',
            'cancelRate30Total',
            'cancelRate30Cancelled',
            'cancelRate30Pct',
            'cancelRateAllTotal',
            'cancelRateAllCancelled',
            'cancelRateAllPct'
        ));
    }
}
