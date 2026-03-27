<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'stock_reserved_expires_at')) {
                $table->timestamp('stock_reserved_expires_at')->nullable()->after('lng');
            }
            if (! Schema::hasColumn('orders', 'stock_reserved_released_at')) {
                $table->timestamp('stock_reserved_released_at')->nullable()->after('stock_reserved_expires_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'stock_reserved_released_at')) {
                $table->dropColumn('stock_reserved_released_at');
            }
            if (Schema::hasColumn('orders', 'stock_reserved_expires_at')) {
                $table->dropColumn('stock_reserved_expires_at');
            }
        });
    }
};

