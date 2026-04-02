<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/** Thông báo trong app: sản phẩm có hàng lại (đã gửi email). */
class StockAlertInboxController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $user->stockNotificationSubscriptions()
            ->whereNotNull('notified_at')
            ->whereNull('seen_at')
            ->update(['seen_at' => now()]);

        $rows = $user->stockNotificationSubscriptions()
            ->whereNotNull('notified_at')
            ->with(['product', 'productVariant.attributeValues.attribute'])
            ->orderByDesc('notified_at')
            ->paginate(20);

        return view('user.stock-alerts.index', compact('rows'));
    }
}
