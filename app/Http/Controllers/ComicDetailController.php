<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ComicProviderInterface;
use App\Exceptions\ComicProvider\ComicNotFoundException;
use App\Exceptions\ComicProvider\ComicProviderException;
use App\Models\Comic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ComicDetailController extends Controller
{
    public function __construct(
        protected ComicProviderInterface $comicProvider
    ) {}

    /**
     * Display comic detail and chapter list.
     */
    public function show(Request $request, string $slug): Response
    {
        $slug = trim($slug);

        try {
            $detail = $this->comicProvider->detail($slug);

            // Upsert minimum metadata into database
            try {
                Comic::upsertFromDetail($detail);
            } catch (\Throwable $e) {
                Log::error("Gagal melakukan upsert metadata komik [{$slug}]: {$e->getMessage()}");
            }

            $user = $request->user();
            $userHistory = null;
            $isBookmarked = false;

            if ($user) {
                /** @var Comic|null $localComic */
                $localComic = Comic::where('slug', $slug)->first();
                if ($localComic) {
                    $history = $user->readingHistories()->where('comic_id', $localComic->id)->first();
                    if ($history) {
                        $userHistory = [
                            'chapter_key' => $history->chapter_key,
                            'chapter_number' => $history->chapter_number,
                            'last_image_index' => $history->last_image_index,
                            'progress_percent' => (float) $history->progress_percent,
                            'read_at' => $history->read_at->toISOString(),
                        ];
                    }

                    $isBookmarked = $user->bookmarks()->where('comic_id', $localComic->id)->exists();
                }
            }

            return Inertia::render('Comic/Show', [
                'comic' => $detail->toArray(),
                'userHistory' => $userHistory,
                'isBookmarked' => $isBookmarked,
            ]);
        } catch (ComicNotFoundException) {
            abort(404, "Komik [{$slug}] tidak ditemukan.");
        } catch (ComicProviderException $e) {
            Log::error("Gagal mengambil detail komik [{$slug}]: {$e->getMessage()}", $e->context());

            throw new HttpException(503, 'Layanan komik sementara tidak dapat diakses. Silakan coba kembali sesaat lagi.');
        }
    }
}
