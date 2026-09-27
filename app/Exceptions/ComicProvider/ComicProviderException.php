<?php

declare(strict_types=1);

namespace App\Exceptions\ComicProvider;

use RuntimeException;
use Throwable;

class ComicProviderException extends RuntimeException
{
    public function __construct(
        string $message = 'Terjadi kesalahan pada penyedia komik.',
        int $code = 0,
        ?Throwable $previous = null,
        protected array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get contextual error information for structured logging.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
