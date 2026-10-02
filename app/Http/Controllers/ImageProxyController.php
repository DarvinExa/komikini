<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Comic\ImageUrlValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImageProxyController extends Controller
{
    public function __construct(
        protected ImageUrlValidator $validator
    ) {}

    /**
     * Proxy upstream comic images through the same origin (komikini.my.id)
     * to eliminate ISP DNS64 local IP synthesis (fd00::/8) and Chrome Private Network Access (PNA) blocks.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $rawUrl = $request->query('url');
        if (! is_string($rawUrl) || trim($rawUrl) === '') {
            abort(400, 'Parameter url diperlukan.');
        }

        $url = $this->validator->sanitize($rawUrl);
        if ($url === null) {
            abort(403, 'URL gambar tidak valid atau domain tidak diizinkan.');
        }

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                'Referer' => 'https://komiku.org/',
                'Accept' => 'image/avif,image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
            ])->withOptions([
                'stream' => true,
                'timeout' => 15,
                'connect_timeout' => 5,
                'verify' => false,
            ])->get($url);

            if (! $response->successful()) {
                abort($response->status(), 'Gagal mengambil gambar dari server asal.');
            }

            $contentType = $response->header('Content-Type') ?: 'image/jpeg';
            $contentLength = $response->header('Content-Length');
            $body = $response->toPsrResponse()->getBody();

            $headers = [
                'Content-Type' => $contentType,
                'Cache-Control' => 'public, max-age=2592000, immutable',
                'Access-Control-Allow-Origin' => '*',
                'X-Content-Type-Options' => 'nosniff',
            ];

            if ($contentLength !== null && is_numeric($contentLength)) {
                $headers['Content-Length'] = $contentLength;
            }

            return response()->stream(function () use ($body) {
                while (! $body->eof()) {
                    echo $body->read(8192);
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }, 200, $headers);
        } catch (\Throwable $e) {
            abort(502, 'Koneksi ke server gambar upstream gagal.');
        }
    }
}
