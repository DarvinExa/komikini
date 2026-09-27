<?php

declare(strict_types=1);

namespace App\Exceptions\ComicProvider;

use Throwable;

class ComicNotFoundException extends ComicProviderException
{
    public function __construct(
        string $message = 'Komik tidak ditemukan.',
        int $code = 404,
        ?Throwable $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
    }
}
