<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('orders')->where('status', 'pending_payment')->update(['status' => 'unpaid']);
    }

    public function down(): void
    {
        DB::table('orders')->where('status', 'unpaid')->where('payment_method', 'paypal')->update(['status' => 'pending_payment']);
    }
};
