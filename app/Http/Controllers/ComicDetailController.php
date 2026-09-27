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

            return Inertia::render('Comic/Show', [
                'comic' => $detail->toArray(),
            ]);
        } catch (ComicNotFoundException) {
            abort(404, "Komik [{$slug}] tidak ditemukan.");
        } catch (ComicProviderException $e) {
            Log::error("Gagal mengambil detail komik [{$slug}]: {$e->getMessage()}", $e->context());

            throw new HttpException(503, 'Layanan komik sementara tidak dapat diakses. Silakan coba kembali sesaat lagi.');
        }
    }
}
