<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'birthday')) {
            Schema::table('users', function (Blueprint $table) {
                $table->date('birthday')->nullable()->after('email');
            });
        }

        if (! Schema::hasTable('coupons')) {
            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            if (! Schema::hasColumn('coupons', 'birthday_only')) {
                $table->boolean('birthday_only')->default(false)->after('first_order_only');
            }
            if (! Schema::hasColumn('coupons', 'birthday_window_days')) {
                $table->unsignedTinyInteger('birthday_window_days')->default(7)->after('birthday_only');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'birthday')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('birthday');
            });
        }

        if (! Schema::hasTable('coupons')) {
            return;
        }

        Schema::table('coupons', function (Blueprint $table) {
            if (Schema::hasColumn('coupons', 'birthday_window_days')) {
                $table->dropColumn('birthday_window_days');
            }
            if (Schema::hasColumn('coupons', 'birthday_only')) {
                $table->dropColumn('birthday_only');
            }
        });
    }
};
