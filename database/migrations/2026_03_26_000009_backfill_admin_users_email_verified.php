<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }
        if (! Schema::hasColumn('users', 'is_admin') || ! Schema::hasColumn('users', 'email_verified_at')) {
            return;
        }

        DB::statement("
            UPDATE users
            SET
                email_verified_at = COALESCE(email_verified_at, CURRENT_TIMESTAMP),
                email_verification_otp = NULL,
                email_verification_otp_expires_at = NULL
            WHERE is_admin = 1
        ");
    }

    public function down(): void
    {
        //
    }
};

