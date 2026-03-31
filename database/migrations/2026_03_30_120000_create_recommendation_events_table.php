<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recommendation_events')) {
            return;
        }

        Schema::create('recommendation_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->string('variant', 8)->default('v1')->index();
            $table->string('event_type', 32)->index(); // impression|click|add_to_cart|purchase
            $table->string('source', 32)->default('suggested')->index();
            $table->string('session_id', 128)->nullable()->index();
            $table->string('route_name', 128)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['variant', 'event_type', 'created_at']);
            $table->index(['product_id', 'event_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_events');
    }
};
