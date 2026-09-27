<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\ComicProviderInterface;
use App\Exceptions\ComicProvider\ComicProviderException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SearchController extends Controller
{
    public function __construct(
        protected ComicProviderInterface $comicProvider
    ) {}

    /**
     * Search comics by title/keyword.
     */
    public function __invoke(Request $request): Response
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = trim($validated['q'] ?? '');
        $page = (int) ($validated['page'] ?? 1);

        if ($query === '') {
            return Inertia::render('Comic/Search', [
                'query' => '',
                'comics' => [
                    'items' => [],
                    'current_page' => 1,
                    'has_next_page' => false,
                    'has_prev_page' => false,
                    'total_pages' => 0,
                ],
            ]);
        }

        try {
            $comicPage = $this->comicProvider->search($query, $page);

            return Inertia::render('Comic/Search', [
                'query' => $query,
                'comics' => $comicPage->toArray(),
            ]);
        } catch (ComicProviderException $e) {
            Log::error("Gagal melakukan pencarian komik untuk query '{$query}': {$e->getMessage()}", $e->context());

            throw new HttpException(503, 'Layanan pencarian sementara mengalami gangguan. Silakan coba kembali.');
        }
    }
}
