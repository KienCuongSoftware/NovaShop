<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coupons')) {
            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            if (! Schema::hasColumn('coupons', 'user_segment')) {
                $table->string('user_segment', 32)->default('all')->after('category_id');
                $table->index('user_segment');
            }
            if (! Schema::hasColumn('coupons', 'first_order_only')) {
                $table->boolean('first_order_only')->default(false)->after('user_segment');
            }
            if (! Schema::hasColumn('coupons', 'min_completed_orders')) {
                $table->unsignedInteger('min_completed_orders')->nullable()->after('first_order_only');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('coupons')) {
            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            if (Schema::hasColumn('coupons', 'min_completed_orders')) {
                $table->dropColumn('min_completed_orders');
            }
            if (Schema::hasColumn('coupons', 'first_order_only')) {
                $table->dropColumn('first_order_only');
            }
            if (Schema::hasColumn('coupons', 'user_segment')) {
                $table->dropIndex(['user_segment']);
                $table->dropColumn('user_segment');
            }
        });
    }
};

