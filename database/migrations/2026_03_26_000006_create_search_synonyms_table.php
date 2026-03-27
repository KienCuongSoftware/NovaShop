<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('search_synonyms')) {
            return;
        }

        Schema::create('search_synonyms', function (Blueprint $table) {
            $table->id();
            $table->string('keyword', 255)->index();
            $table->string('synonym', 255)->index();
            $table->timestamps();

            $table->unique(['keyword', 'synonym'], 'uq_search_synonyms_keyword_synonym');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_synonyms');
    }
};

