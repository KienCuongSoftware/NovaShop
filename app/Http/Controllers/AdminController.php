<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
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

        return view('admin.dashboard', compact(
            'stats',
            'chartStatusLabels',
            'chartStatusData',
            'chartStatusColorsOrdered',
            'last30DaysLabels',
            'last30Days',
            'recentOrders'
        ));
    }
}
