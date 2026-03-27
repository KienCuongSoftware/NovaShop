<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_reviews')) {
            return;
        }

        Schema::table('product_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('product_reviews', 'is_approved')) {
                $table->boolean('is_approved')->default(false)->after('is_verified');
            }
            if (! Schema::hasColumn('product_reviews', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('is_approved');
            }
            if (! Schema::hasColumn('product_reviews', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('approved_at');
            }
            if (! Schema::hasColumn('product_reviews', 'rejection_reason')) {
                $table->string('rejection_reason', 500)->nullable()->after('rejected_at');
            }
            $table->index(['product_id', 'is_approved'], 'idx_product_reviews_product_approved');
        });

        if (! Schema::hasTable('product_review_images')) {
            Schema::create('product_review_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_review_id')->constrained('product_reviews')->cascadeOnDelete();
                $table->string('path');
                $table->unsignedTinyInteger('sort')->default(0);
                $table->timestamps();
                $table->index(['product_review_id', 'sort'], 'idx_pri_review_sort');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_review_images')) {
            Schema::dropIfExists('product_review_images');
        }

        if (! Schema::hasTable('product_reviews')) {
            return;
        }

        Schema::table('product_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('product_reviews', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
            if (Schema::hasColumn('product_reviews', 'rejected_at')) {
                $table->dropColumn('rejected_at');
            }
            if (Schema::hasColumn('product_reviews', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('product_reviews', 'is_approved')) {
                $table->dropColumn('is_approved');
            }
        });
    }
};

