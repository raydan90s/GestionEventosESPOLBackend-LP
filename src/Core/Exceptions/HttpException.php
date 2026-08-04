<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Error de aplicacion que se traduce directamente a un codigo HTTP.
 */
class HttpException extends RuntimeException
{
    private int $statusCode;

    public function __construct(int $statusCode, string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public static function notFound(string $message = 'Recurso no encontrado'): self
    {
        return new self(404, $message);
    }

    public static function conflict(string $message): self
    {
        return new self(409, $message);
    }

    public static function badRequest(string $message): self
    {
        return new self(400, $message);
    }
}
