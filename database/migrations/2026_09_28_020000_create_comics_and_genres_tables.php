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
        Schema::create('comics', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('alternative_title')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('comic_type')->default('unknown')->index();
            $table->string('publication_status')->nullable();
            $table->text('synopsis')->nullable();
            $table->json('upstream_payload')->nullable();
            $table->timestamp('last_synced_at')->nullable()->index();
            $table->timestamps();

            $table->index('slug');
        });

        Schema::create('genres', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('comic_genre', function (Blueprint $table) {
            $table->foreignId('comic_id')->constrained('comics')->cascadeOnDelete();
            $table->foreignId('genre_id')->constrained('genres')->cascadeOnDelete();
            $table->primary(['comic_id', 'genre_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comic_genre');
        Schema::dropIfExists('genres');
        Schema::dropIfExists('comics');
    }
};
