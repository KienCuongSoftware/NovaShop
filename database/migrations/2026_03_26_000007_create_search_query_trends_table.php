<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('search_query_trends')) {
            return;
        }

        Schema::create('search_query_trends', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 255)->unique();
            $table->unsignedInteger('count')->default(0);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_query_trends');
    }
};

