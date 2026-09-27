<?php

declare(strict_types=1);

namespace App\Exceptions\ComicProvider;

use Throwable;

class UpstreamRateLimitedException extends ComicProviderException
{
    public function __construct(
        string $message = 'Terlalu banyak permintaan ke penyedia komik.',
        int $code = 429,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
