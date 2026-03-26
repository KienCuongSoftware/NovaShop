<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\Product;
use App\Observers\ProductObserver;
use App\Services\CatalogCache;
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
        Product::observe(ProductObserver::class);

        Category::saved(fn () => CatalogCache::forgetCategoryTree());
        Category::deleted(fn () => CatalogCache::forgetCategoryTree());

        FlashSale::saved(fn () => CatalogCache::forgetFlashWelcome());
        FlashSaleItem::saved(fn () => CatalogCache::forgetFlashWelcome());
        FlashSaleItem::deleted(fn () => CatalogCache::forgetFlashWelcome());

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
            /** @var \App\Models\User $u */
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
