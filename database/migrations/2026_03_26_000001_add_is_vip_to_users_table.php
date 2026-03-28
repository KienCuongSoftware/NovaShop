<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'is_vip')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_vip')->default(false)->after('is_admin');
            $table->index('is_vip');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'is_vip')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_vip']);
            $table->dropColumn('is_vip');
        });
    }
};

