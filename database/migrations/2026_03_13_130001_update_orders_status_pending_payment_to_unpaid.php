<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }
        DB::table('orders')->where('status', 'pending_payment')->update(['status' => 'unpaid']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }
        DB::table('orders')->where('status', 'unpaid')->where('payment_method', 'paypal')->update(['status' => 'pending_payment']);
    }
};
