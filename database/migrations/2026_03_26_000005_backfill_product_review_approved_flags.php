<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_reviews')) {
            return;
        }
        if (! Schema::hasColumn('product_reviews', 'is_approved') || ! Schema::hasColumn('product_reviews', 'is_verified')) {
            return;
        }

        DB::statement("
            UPDATE product_reviews
            SET
                is_approved = CASE WHEN is_verified = 1 THEN 1 ELSE 0 END,
                approved_at = CASE
                    WHEN is_verified = 1 THEN COALESCE(approved_at, created_at)
                    ELSE NULL
                END,
                rejected_at = NULL,
                rejection_reason = NULL
        ");
    }

    public function down(): void
    {
        //
    }
};

