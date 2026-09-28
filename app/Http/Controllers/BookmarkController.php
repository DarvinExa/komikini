<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Comic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    /**
     * Toggle bookmark state for the given comic.
     */
    public function toggle(Request $request, string $slug): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }

        $comic = Comic::firstOrCreate(
            ['slug' => $slug],
            [
                'title' => ucwords(str_replace('-', ' ', $slug)),
                'comic_type' => 'unknown',
            ]
        );

        $bookmark = Bookmark::where('user_id', $user->id)
            ->where('comic_id', $comic->id)
            ->first();

        if ($bookmark) {
            $bookmark->delete();
            $isBookmarked = false;
            $message = 'Komik berhasil dihapus dari bookmark.';
        } else {
            Bookmark::create([
                'user_id' => $user->id,
                'comic_id' => $comic->id,
            ]);
            $isBookmarked = true;
            $message = 'Komik berhasil ditambahkan ke bookmark.';
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_bookmarked' => $isBookmarked,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }
}
