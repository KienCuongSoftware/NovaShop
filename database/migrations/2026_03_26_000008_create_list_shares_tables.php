<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('list_shares')) {
            Schema::create('list_shares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->string('type', 20); // wishlist | compare
                $table->string('token', 64)->unique();
                $table->timestamps();

                $table->index(['type', 'token'], 'idx_list_shares_type_token');
            });
        }

        if (! Schema::hasTable('list_share_items')) {
            Schema::create('list_share_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('list_share_id')->constrained('list_shares')->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['list_share_id', 'sort_order'], 'idx_list_share_items_share_sort');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('list_share_items');
        Schema::dropIfExists('list_shares');
    }
};

