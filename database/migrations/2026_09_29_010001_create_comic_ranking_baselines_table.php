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
        Schema::create('comic_ranking_baselines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comic_id')->unique()->constrained('comics')->cascadeOnDelete();
            $table->unsignedBigInteger('qualified_views')->default(0);
            $table->unsignedBigInteger('unique_readers')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comic_ranking_baselines');
    }
};
