<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comic_rankings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comic_id')->constrained('comics')->cascadeOnDelete();
            $table->string('period', 20); // daily, weekly, monthly, all_time
            $table->unsignedBigInteger('qualified_views')->default(0);
            $table->unsignedBigInteger('unique_readers')->default(0);
            $table->unsignedInteger('rank_position');
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->unique(['comic_id', 'period']);
            $table->index(['period', 'rank_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comic_rankings');
    }
};
