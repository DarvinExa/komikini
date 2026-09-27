<?php

declare(strict_types=1);

namespace App\Exceptions\ComicProvider;

use Throwable;

class UpstreamUnavailableException extends ComicProviderException
{
    public function __construct(
        string $message = 'Layanan penyedia komik sementara tidak tersedia.',
        int $code = 503,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
