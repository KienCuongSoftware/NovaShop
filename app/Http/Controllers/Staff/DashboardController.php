<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductReview;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function dashboard(): View
    {
        $pendingReviews = ProductReview::query()->where('is_approved', false)->count();
        $ordersToday = Order::query()->whereDate('created_at', today())->count();

        $chartLast7Labels = [];
        $chartLast7Counts = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = Carbon::today()->subDays($i);
            $chartLast7Labels[] = $d->format('d/m');
            $chartLast7Counts[] = (int) Order::query()->whereDate('created_at', $d)->count();
        }

        $chartStatusLabels = [];
        $chartStatusData = [];
        foreach (Order::tabStatusKeys() as $key) {
            $chartStatusLabels[] = Order::statusLabel($key);
            $chartStatusData[] = (int) Order::query()->where('status', $key)->count();
        }

        return view('staff.dashboard.index', compact(
            'pendingReviews',
            'ordersToday',
            'chartLast7Labels',
            'chartLast7Counts',
            'chartStatusLabels',
            'chartStatusData'
        ));
    }
}
