<?php

declare(strict_types=1);

namespace App\Exceptions\ComicProvider;

use Throwable;

class UpstreamTimeoutException extends ComicProviderException
{
    public function __construct(
        string $message = 'Koneksi ke penyedia komik mengalami batas waktu (timeout).',
        int $code = 504,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
