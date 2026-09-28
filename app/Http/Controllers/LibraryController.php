<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\ReadingHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LibraryController extends Controller
{
    /**
     * Display the user's library (reading histories and bookmarks).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $tab = $request->query('tab', 'riwayat');
        if (! in_array($tab, ['riwayat', 'bookmark'], true)) {
            $tab = 'riwayat';
        }

        $histories = $user->readingHistories()
            ->with('comic')
            ->orderByDesc('read_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (ReadingHistory $h): array => [
                'id' => $h->id,
                'comic_id' => $h->comic_id,
                'comic_slug' => $h->comic->slug ?? '',
                'comic_title' => $h->comic->title ?? ucwords(str_replace('-', ' ', (string) $h->comic_id)),
                'comic_thumbnail' => $h->comic->thumbnail_url ?? null,
                'chapter_key' => $h->chapter_key,
                'chapter_number' => $h->chapter_number,
                'last_image_index' => $h->last_image_index,
                'progress_percent' => (float) $h->progress_percent,
                'read_at' => $h->read_at->toISOString(),
                'completed_at' => $h->completed_at?->toISOString(),
            ]);

        $bookmarks = $user->bookmarks()
            ->with('comic')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Bookmark $b): array => [
                'id' => $b->id,
                'comic_id' => $b->comic_id,
                'comic_slug' => $b->comic->slug ?? '',
                'comic_title' => $b->comic->title ?? ucwords(str_replace('-', ' ', (string) $b->comic_id)),
                'comic_thumbnail' => $b->comic->thumbnail_url ?? null,
                'comic_type' => $b->comic->comic_type ?? 'unknown',
                'created_at' => $b->created_at->toISOString(),
            ]);

        $counts = [
            'histories' => $user->readingHistories()->count(),
            'bookmarks' => $user->bookmarks()->count(),
        ];

        return Inertia::render('Library/Index', [
            'tab' => $tab,
            'histories' => $histories,
            'bookmarks' => $bookmarks,
            'counts' => $counts,
        ]);
    }

    /**
     * Delete an individual reading history entry.
     */
    public function destroyHistory(Request $request, ReadingHistory $history): RedirectResponse
    {
        Gate::authorize('delete', $history);

        $history->delete();

        return back()->with('success', 'Riwayat baca berhasil dihapus.');
    }

    /**
     * Delete all reading history entries for the authenticated user.
     */
    public function clearAllHistory(Request $request): RedirectResponse
    {
        $request->user()->readingHistories()->delete();

        return back()->with('success', 'Semua riwayat baca berhasil dihapus.');
    }

    /**
     * Remove a bookmark entry.
     */
    public function destroyBookmark(Request $request, Bookmark $bookmark): RedirectResponse
    {
        Gate::authorize('delete', $bookmark);

        $bookmark->delete();

        return back()->with('success', 'Bookmark berhasil dihapus.');
    }
}
