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
        Schema::create('comic_view_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comic_id')->constrained('comics')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('visitor_hash', 64)->nullable();
            $table->string('chapter_key', 191);
            $table->dateTime('occurred_at');
            $table->boolean('qualified')->default(true);
            $table->timestamp('created_at')->useCurrent();

            // Indexes per DATA_MODEL.md
            $table->index('occurred_at');
            $table->index(['comic_id', 'occurred_at']);
            $table->index(
                ['visitor_hash', 'comic_id', 'chapter_key', 'occurred_at'],
                'cve_visitor_dedup_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comic_view_events');
    }
};
