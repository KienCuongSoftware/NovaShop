<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'gateway')) {
                $table->string('gateway', 30)->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('payments', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('payments', 'response_payload')) {
                $table->json('response_payload')->nullable()->after('failure_reason');
            }
            if (!Schema::hasColumn('payments', 'error_code')) {
                $table->string('error_code', 80)->nullable()->after('response_payload');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $drops = [];
            foreach (['gateway', 'failure_reason', 'response_payload', 'error_code'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $drops[] = $col;
                }
            }
            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};

