<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->after('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('slug', 255)->nullable()->after('name');
        });

        $this->backfillCategorySlugs();
        $this->backfillProductSlugs();

        Schema::table('categories', function (Blueprint $table) {
            $table->unique('slug');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    protected function backfillCategorySlugs(): void
    {
        $table = (new \App\Models\Category())->getTable();
        $rows = \DB::table($table)->get();
        foreach ($rows as $row) {
            $base = Str::slug($row->name ?: 'category');
            $slug = $base;
            $n = 0;
            while (\DB::table($table)->where('slug', $slug)->where('id', '!=', $row->id)->exists()) {
                $slug = $base . '-' . (++$n);
            }
            \DB::table($table)->where('id', $row->id)->update(['slug' => $slug]);
        }
    }

    protected function backfillProductSlugs(): void
    {
        $table = (new \App\Models\Product())->getTable();
        $rows = \DB::table($table)->get();
        foreach ($rows as $row) {
            $base = Str::slug($row->name ?: 'product');
            $slug = $base;
            $n = 0;
            while (\DB::table($table)->where('slug', $slug)->where('id', '!=', $row->id)->exists()) {
                $slug = $base . '-' . (++$n);
            }
            \DB::table($table)->where('id', $row->id)->update(['slug' => $slug]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
