<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_reviews') || Schema::hasColumn('product_reviews', 'variant_classification')) {
            return;
        }
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->string('variant_classification', 255)->nullable()->after('content');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_reviews') || ! Schema::hasColumn('product_reviews', 'variant_classification')) {
            return;
        }
        Schema::table('product_reviews', function (Blueprint $table) {
            $table->dropColumn('variant_classification');
        });
    }
};
