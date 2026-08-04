<?php

declare(strict_types=1);

namespace App\Core\Exceptions;

/**
 * Agrupa los errores de validacion de un formulario/payload (HTTP 422).
 */
final class ValidationException extends HttpException
{
    /** @var array<string, string[]> */
    private array $errors;

    /** @param array<string, string[]> $errors */
    public function __construct(array $errors, string $message = 'Los datos enviados no son validos')
    {
        parent::__construct(422, $message);
        $this->errors = $errors;
    }

    /** @return array<string, string[]> */
    public function errors(): array
    {
        return $this->errors;
    }
}
