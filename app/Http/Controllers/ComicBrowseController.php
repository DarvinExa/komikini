<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ComicProviderInterface;
use App\Enums\ComicType;
use App\Exceptions\ComicProvider\ComicProviderException;
use App\Services\Comic\ComicEnricher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ComicBrowseController extends Controller
{
    public function __construct(
        protected ComicProviderInterface $comicProvider
    ) {}

    /**
     * Browse latest released comics with pagination.
     */
    public function latest(Request $request): Response
    {
        $page = max(1, (int) $request->input('page', 1));

        try {
            $comicPage = $this->comicProvider->latest($page);

            return Inertia::render('Comic/Browse', [
                'title' => 'Komik Rilis Terbaru',
                'description' => 'Daftar rilisan chapter komik terbaru dan terhangat diurutkan berdasarkan waktu.',
                'comics' => ComicEnricher::enrichPage($comicPage->toArray()),
                'type' => null,
                'genre' => null,
            ]);
        } catch (ComicProviderException $e) {
            Log::error("Gagal browse latest comics: {$e->getMessage()}", $e->context());

            throw new HttpException(503, 'Gagal memuat daftar komik terbaru.');
        }
    }

    /**
     * Browse comics by specific type (manga, manhwa, manhua) with pagination.
     */
    public function byType(Request $request, string $type): Response
    {
        $type = strtolower(trim($type));
        $comicType = ComicType::fromUpstream($type);

        if ($comicType === ComicType::UNKNOWN) {
            abort(404, "Tipe komik '{$type}' tidak ditemukan.");
        }

        $page = max(1, (int) $request->input('page', 1));

        try {
            $comicPage = $this->comicProvider->byType($comicType, $page);

            return Inertia::render('Comic/Browse', [
                'title' => 'Katalog Komik '.ucfirst($comicType->value),
                'description' => "Jelajahi seluruh koleksi komik {$comicType->value} terlengkap di Komikini.",
                'comics' => ComicEnricher::enrichPage($comicPage->toArray()),
                'type' => $comicType->value,
                'genre' => null,
            ]);
        } catch (ComicProviderException $e) {
            Log::error("Gagal browse comics by type [{$type}]: {$e->getMessage()}", $e->context());

            throw new HttpException(503, "Gagal memuat katalog {$type}.");
        }
    }

    /**
     * Display all available genres directory.
     */
    public function genres(): Response
    {
        try {
            $genres = $this->comicProvider->genres();

            return Inertia::render('Comic/Genres', [
                'genres' => $genres->toArray(),
            ]);
        } catch (ComicProviderException $e) {
            Log::error("Gagal memuat daftar genre: {$e->getMessage()}", $e->context());

            throw new HttpException(503, 'Gagal memuat daftar genre.');
        }
    }

    /**
     * Browse comics by genre with pagination.
     */
    public function byGenre(Request $request, string $slug): Response
    {
        $slug = trim($slug);
        $page = max(1, (int) $request->input('page', 1));

        try {
            $comicPage = $this->comicProvider->byGenre($slug, $page);

            return Inertia::render('Comic/Browse', [
                'title' => 'Genre: '.ucfirst(str_replace('-', ' ', $slug)),
                'description' => "Daftar komik dalam kategori genre {$slug}.",
                'comics' => ComicEnricher::enrichPage($comicPage->toArray()),
                'type' => null,
                'genre' => $slug,
            ]);
        } catch (ComicProviderException $e) {
            Log::error("Gagal browse comics by genre [{$slug}]: {$e->getMessage()}", $e->context());

            throw new HttpException(503, "Gagal memuat komik untuk genre {$slug}.");
        }
    }
}
