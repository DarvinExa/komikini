<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TurnstileService
{
    protected ?string $secretKey;

    public function __construct(?string $secretKey = null)
    {
        $this->secretKey = $secretKey ?? (string) config('services.turnstile.secret_key', '');
    }

    /**
     * Check if Turnstile verification is actively configured.
     */
    public function isEnabled(): bool
    {
        return ! empty($this->secretKey);
    }

    /**
     * Verify a Turnstile response token with Cloudflare API.
     * When not configured (empty secret), passes adaptively.
     */
    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        // Adaptive bypass when credentials are not configured in environment
        if (! $this->isEnabled()) {
            return true;
        }

        if (empty($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(4)
                ->connectTimeout(2)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $this->secretKey,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]);

            if ($response->successful()) {
                return (bool) $response->json('success', false);
            }

            Log::warning('Turnstile verification request failed with HTTP status '.$response->status());

            return false;
        } catch (Throwable $e) {
            Log::error('Turnstile verification error: '.$e->getMessage());

            // Fail-closed on active configuration error
            return false;
        }
    }
}
