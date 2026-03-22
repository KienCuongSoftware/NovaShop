<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFour();

        View::composer('layouts.user', function ($view) {
            if (! Auth::check()) {
                $view->with([
                    'navWishlistCount' => 0,
                    'navCompareCount' => 0,
                    'navStockAlertUnread' => 0,
                ]);

                return;
            }
            $u = Auth::user();
            $view->with([
                'navWishlistCount' => $u->wishlistItems()->count(),
                'navCompareCount' => $u->compareItems()->count(),
                'navStockAlertUnread' => $u->stockNotificationSubscriptions()
                    ->whereNotNull('notified_at')
                    ->whereNull('seen_at')
                    ->count(),
            ]);
        });
    }
}
