<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ComicProviderInterface;
use App\Enums\ComicType;
use App\Exceptions\ComicProvider\ChapterNotFoundException;
use App\Exceptions\ComicProvider\ComicNotFoundException;
use App\Exceptions\ComicProvider\ComicProviderException;
use App\Jobs\RecordQualifiedView;
use App\Models\Comic;
use App\Services\Comic\ImageUrlValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class ChapterReaderController extends Controller
{
    public function __construct(
        protected ComicProviderInterface $comicProvider,
        protected ImageUrlValidator $imageValidator
    ) {}

    /**
     * Display the chapter reader.
     */
    public function show(Request $request, string $slug, string $chapter): Response
    {
        $slug = trim($slug);
        $chapter = trim($chapter);

        try {
            $payload = $this->comicProvider->chapter($slug, $chapter);

            // Re-validate and sanitize all image URLs against the server allowlist
            $sanitizedImages = [];
            foreach ($payload->images as $imageUrl) {
                $sanitized = $this->imageValidator->sanitize($imageUrl);
                if ($sanitized !== null) {
                    $sanitizedImages[] = $sanitized;
                }
            }

            // Find or initialize local comic record
            /** @var Comic|null $comic */
            $comic = Comic::where('slug', $slug)->first();
            if (! $comic) {
                try {
                    $detail = $this->comicProvider->detail($slug);
                    $comic = Comic::upsertFromDetail($detail);
                } catch (Throwable $e) {
                    try {
                        $comic = Comic::firstOrCreate(
                            ['slug' => $slug],
                            [
                                'title' => ucwords(str_replace('-', ' ', $slug)),
                                'comic_type' => ComicType::UNKNOWN->value,
                            ]
                        );
                    } catch (Throwable) {
                        $comic = new Comic([
                            'slug' => $slug,
                            'title' => ucwords(str_replace('-', ' ', $slug)),
                            'comic_type' => ComicType::UNKNOWN->value,
                        ]);
                    }
                }
            }

            // Asynchronously dispatch qualified view recording without blocking response
            $visitorHash = hash('sha256', ($request->ip() ?? '127.0.0.1').'|'.($request->userAgent() ?? ''));
            $userId = $request->user()?->id;

            if ($comic->id) {
                try {
                    RecordQualifiedView::dispatch(
                        $comic->id,
                        $payload->chapterKey,
                        $userId,
                        $visitorHash,
                        now()
                    );
                } catch (Throwable $e) {
                    Log::warning("Gagal dispatch RecordQualifiedView untuk komik [{$slug}] chapter [{$chapter}]: {$e->getMessage()}");
                }
            }

            $chapterData = $payload->toArray();
            $chapterData['images'] = $sanitizedImages;

            $initialIndex = 0;
            if ($request->user()) {
                $history = $request->user()->readingHistories()
                    ->where('comic_id', $comic->id)
                    ->where('chapter_key', $payload->chapterKey)
                    ->first();
                if ($history && $history->last_image_index !== null) {
                    $initialIndex = $history->last_image_index;
                }
            }

            return Inertia::render('Comic/Reader', [
                'comic' => [
                    'id' => $comic->id,
                    'slug' => $comic->slug,
                    'title' => $comic->title,
                ],
                'chapter' => $chapterData,
                'initialIndex' => $initialIndex,
            ]);
        } catch (ChapterNotFoundException) {
            abort(404, "Chapter [{$chapter}] untuk komik [{$slug}] tidak ditemukan.");
        } catch (ComicNotFoundException) {
            abort(404, "Komik [{$slug}] tidak ditemukan.");
        } catch (ComicProviderException $e) {
            Log::error("Gagal memuat chapter [{$slug}/{$chapter}]: {$e->getMessage()}", $e->context());

            throw new HttpException(503, 'Layanan pembaca chapter sedang mengalami gangguan. Silakan coba kembali.');
        }
    }
}
