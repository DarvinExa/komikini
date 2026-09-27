<?php

declare(strict_types=1);

namespace App\Exceptions\ComicProvider;

use Throwable;

class MalformedUpstreamResponseException extends ComicProviderException
{
    public function __construct(
        string $message = 'Format respons dari penyedia komik tidak sesuai spesifikasi.',
        int $code = 502,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
