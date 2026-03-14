<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class BrandSeeder extends Seeder
{
    /**
     * Xóa các thương hiệu mẫu (user tự thêm).
     */
    public function run(): void
    {
        $sampleSlugs = ['baiko', 'kino-shop', 'baseus', 'anker', 'remax', 'xiaomi', 'samsung', 'apple'];

        $brands = Brand::whereIn('slug', $sampleSlugs)->get();

        foreach ($brands as $brand) {
            Product::where('brand_id', $brand->id)->update(['brand_id' => null]);
            if ($brand->logo && Storage::disk('public')->exists($brand->logo)) {
                Storage::disk('public')->delete($brand->logo);
            }
            $brand->delete();
        }
    }
}
