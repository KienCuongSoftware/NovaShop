<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wishlist_items')) {
            Schema::create('wishlist_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('compare_items')) {
            Schema::create('compare_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('sort_order')->default(0);
                $table->timestamps();
                $table->unique(['user_id', 'product_id']);
            });
        }

        if (! Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code', 64)->unique();
                $table->string('name')->nullable();
                $table->string('discount_type', 16);
                $table->unsignedInteger('discount_value');
                $table->unsignedBigInteger('min_order_amount')->default(0);
                $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('uses_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('carts') && ! Schema::hasColumn('carts', 'coupon_id')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->foreignId('coupon_id')->nullable()->after('user_id')->constrained('coupons')->nullOnDelete();
            });
        }

        if (Schema::hasTable('orders')) {
            if (! Schema::hasColumn('orders', 'coupon_id')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->foreignId('coupon_id')->nullable()->after('user_id')->constrained('coupons')->nullOnDelete();
                });
            }
            if (! Schema::hasColumn('orders', 'discount_amount')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->unsignedBigInteger('discount_amount')->default(0)->after('coupon_id');
                });
            }
        }

        if (! Schema::hasTable('stock_notification_subscriptions')) {
            Schema::create('stock_notification_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
                $table->string('email')->nullable();
                $table->timestamp('notified_at')->nullable();
                $table->timestamp('seen_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'product_id', 'product_variant_id'], 'sns_user_prod_var_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_notification_subscriptions');

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'coupon_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['coupon_id']);
            });
        }
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'discount_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('discount_amount');
            });
        }
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'coupon_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('coupon_id');
            });
        }

        if (Schema::hasTable('carts') && Schema::hasColumn('carts', 'coupon_id')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->dropForeign(['coupon_id']);
                $table->dropColumn('coupon_id');
            });
        }

        Schema::dropIfExists('coupons');
        Schema::dropIfExists('compare_items');
        Schema::dropIfExists('wishlist_items');
    }
};
