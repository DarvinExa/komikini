<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ComicProviderInterface;
use App\Exceptions\ComicProvider\ComicProviderException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        protected ComicProviderInterface $comicProvider
    ) {}

    /**
     * Display the Komikini discovery homepage.
     */
    public function __invoke(Request $request): Response
    {
        $error = null;
        $recommended = [];
        $popular = [];
        $latest = null;
        $genres = [];

        try {
            $recommended = $this->comicProvider->recommended()->toArray();
            $popular = $this->comicProvider->popular()->toArray();
            $latest = $this->comicProvider->latest(1)->toArray();
            $genres = $this->comicProvider->genres()->toArray();
        } catch (ComicProviderException $e) {
            Log::warning("Gagal mengambil data katalog homepage: {$e->getMessage()}", $e->context());
            $error = 'Sebagian konten upstream sedang mengalami gangguan. Kami menyajikan konten yang tersedia.';
        }

        return Inertia::render('Home', [
            'recommended' => $recommended,
            'popular' => $popular,
            'latest' => $latest,
            'genres' => array_slice($genres, 0, 15),
            'errorMessage' => $error,
        ]);
    }
}
