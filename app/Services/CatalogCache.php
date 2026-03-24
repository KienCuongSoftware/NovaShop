<?php

namespace App\Services;

use App\Models\Category;
use App\Models\FlashSale;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;

/**
 * Read-through cache for storefront catalog data (category tree, flash sale context).
 * Invalidated from model hooks in AppServiceProvider when data changes.
 */
class CatalogCache
{
    public const CATEGORY_TREE_KEY = 'novashop.categories.root_tree_v1';

    public const CATEGORY_TREE_TTL = 600;

    /** Time-sensitive: short TTL so flash boundaries stay roughly correct. */
    public const FLASH_WELCOME_KEY = 'novashop.flash_sale.welcome_context_v1';

    public const FLASH_TTL = 45;

    public static function forgetCategoryTree(): void
    {
        Cache::forget(self::CATEGORY_TREE_KEY);
    }

    public static function forgetFlashWelcome(): void
    {
        Cache::forget(self::FLASH_WELCOME_KEY);
    }

    /**
     * @return EloquentCollection<int, Category>
     */
    public static function rootCategoryTree(): EloquentCollection
    {
        /** @var EloquentCollection<int, Category> $tree */
        $tree = Cache::remember(self::CATEGORY_TREE_KEY, self::CATEGORY_TREE_TTL, function () {
            return Category::roots()->with('children.children')->orderBy('name')->get();
        });

        return $tree;
    }

    /**
     * @return array{activeFlashSale: ?FlashSale, todaySlots: EloquentCollection<int, FlashSale>}
     */
    public static function flashSaleWelcomeContext(): array
    {
        return Cache::remember(self::FLASH_WELCOME_KEY, self::FLASH_TTL, function () {
            $activeFlashSale = FlashSale::getCurrentOrNext();
            $todaySlots = $activeFlashSale
                ? FlashSale::whereDate('start_time', $activeFlashSale->start_time->toDateString())->orderBy('start_time')->get()
                : FlashSale::getTodaySlots();

            return [
                'activeFlashSale' => $activeFlashSale,
                'todaySlots' => $todaySlots,
            ];
        });
    }
}
