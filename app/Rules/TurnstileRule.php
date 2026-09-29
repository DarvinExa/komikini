<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\Security\TurnstileService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class TurnstileRule implements ValidationRule
{
    public function __construct(
        protected ?TurnstileService $turnstileService = null
    ) {
        $this->turnstileService = $turnstileService ?? app(TurnstileService::class);
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $token = is_string($value) ? $value : null;
        $ip = request()->ip();

        if (! $this->turnstileService->verify($token, $ip)) {
            $fail('Verifikasi keamanan Turnstile gagal. Silakan coba lagi.');
        }
    }
}
