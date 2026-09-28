<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Comic;
use App\Models\ReadingHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingProgressController extends Controller
{
    /**
     * Upsert reading progress for authenticated user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'comic_slug' => ['required', 'string', 'max:255'],
            'chapter_key' => ['required', 'string', 'max:191'],
            'chapter_number' => ['nullable', 'string', 'max:50'],
            'last_image_index' => ['nullable', 'integer', 'min:0'],
            'progress_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'completed' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $comic = Comic::where('slug', $validated['comic_slug'])->first();
        if (! $comic) {
            $comic = Comic::firstOrCreate(
                ['slug' => $validated['comic_slug']],
                [
                    'title' => ucwords(str_replace('-', ' ', $validated['comic_slug'])),
                    'comic_type' => 'unknown',
                ]
            );
        }

        $progressPercent = (float) $validated['progress_percent'];
        $isCompleted = ! empty($validated['completed']) || $progressPercent >= 95.0;

        /** @var ReadingHistory $history */
        $history = ReadingHistory::updateOrCreate(
            [
                'user_id' => $user->id,
                'comic_id' => $comic->id,
            ],
            [
                'chapter_key' => $validated['chapter_key'],
                'chapter_number' => $validated['chapter_number'] ?? preg_replace('/[^0-9.]/', '', $validated['chapter_key']) ?: '1',
                'last_image_index' => $validated['last_image_index'] ?? null,
                'progress_percent' => $progressPercent,
                'read_at' => now(),
                'started_at' => now(),
                'completed_at' => $isCompleted ? now() : null,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $history->id,
                'comic_id' => $history->comic_id,
                'chapter_key' => $history->chapter_key,
                'chapter_number' => $history->chapter_number,
                'last_image_index' => $history->last_image_index,
                'progress_percent' => (float) $history->progress_percent,
                'completed_at' => $history->completed_at?->toISOString(),
            ],
        ]);
    }
}
