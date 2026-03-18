<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('status', 20)->default('active'); // active, ended, scheduled
            $table->timestamps();

            $table->index(['start_time', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sales');
    }
};
