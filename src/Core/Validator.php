<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\ValidationException;
use DateTimeImmutable;

/**
 * Validador ligero para los payloads de la API.
 *
 * Uso:
 *   $datos = Validator::make($request->body())
 *       ->required('titulo')->string('titulo', 3, 150)
 *       ->required('cupos_maximos')->integer('cupos_maximos', 1)
 *       ->validated();
 */
final class Validator
{
    /** @var array<string, mixed> */
    private array $data;
    /** @var array<string, string[]> */
    private array $errors = [];
    /** @var array<string, mixed> */
    private array $validated = [];

    /** @param array<string, mixed> $data */
    private function __construct(array $data)
    {
        $this->data = $data;
    }

    /** @param array<string, mixed> $data */
    public static function make(array $data): self
    {
        return new self($data);
    }

    public function required(string $field, ?string $label = null): self
    {
        $value = $this->data[$field] ?? null;

        if ($value === null || (is_string($value) && trim($value) === '') || (is_array($value) && $value === [])) {
            $this->addError($field, sprintf('El campo %s es obligatorio.', $label ?? $field));
        }

        return $this;
    }

    public function string(string $field, int $min = 1, int $max = 255, ?string $label = null): self
    {
        if (!$this->present($field)) {
            return $this;
        }

        $value = $this->data[$field];

        if (!is_string($value)) {
            return $this->addError($field, sprintf('El campo %s debe ser texto.', $label ?? $field));
        }

        $value = trim($value);
        $length = mb_strlen($value);

        if ($length < $min) {
            return $this->addError($field, sprintf('El campo %s debe tener al menos %d caracteres.', $label ?? $field, $min));
        }

        if ($length > $max) {
            return $this->addError($field, sprintf('El campo %s no puede superar %d caracteres.', $label ?? $field, $max));
        }

        $this->validated[$field] = $value;

        return $this;
    }

    public function integer(string $field, ?int $min = null, ?int $max = null, ?string $label = null): self
    {
        if (!$this->present($field)) {
            return $this;
        }

        $value = $this->data[$field];

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return $this->addError($field, sprintf('El campo %s debe ser un numero entero.', $label ?? $field));
        }

        $value = (int) $value;

        if ($min !== null && $value < $min) {
            return $this->addError($field, sprintf('El campo %s debe ser mayor o igual a %d.', $label ?? $field, $min));
        }

        if ($max !== null && $value > $max) {
            return $this->addError($field, sprintf('El campo %s debe ser menor o igual a %d.', $label ?? $field, $max));
        }

        $this->validated[$field] = $value;

        return $this;
    }

    public function email(string $field, ?string $label = null): self
    {
        if (!$this->present($field)) {
            return $this;
        }

        $value = trim((string) $this->data[$field]);

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return $this->addError($field, sprintf('El campo %s debe ser un correo valido.', $label ?? $field));
        }

        $this->validated[$field] = mb_strtolower($value);

        return $this;
    }

    /**
     * Valida una fecha/hora ISO 8601 o "Y-m-d H:i:s".
     */
    public function datetime(string $field, bool $mustBeFuture = false, ?string $label = null): self
    {
        if (!$this->present($field)) {
            return $this;
        }

        $value = trim((string) $this->data[$field]);
        $date = self::parseDate($value);

        if ($date === null) {
            return $this->addError($field, sprintf('El campo %s debe tener un formato de fecha valido (ej. 2026-09-15T14:30).', $label ?? $field));
        }

        if ($mustBeFuture && $date <= new DateTimeImmutable('now')) {
            return $this->addError($field, sprintf('El campo %s debe ser una fecha futura.', $label ?? $field));
        }

        $this->validated[$field] = $date->format('Y-m-d H:i:sP');

        return $this;
    }

    /** @param array<int, string> $options */
    public function in(string $field, array $options, ?string $label = null): self
    {
        if (!$this->present($field)) {
            return $this;
        }

        $value = (string) $this->data[$field];

        if (!in_array($value, $options, true)) {
            return $this->addError($field, sprintf('El campo %s debe ser uno de: %s.', $label ?? $field, implode(', ', $options)));
        }

        $this->validated[$field] = $value;

        return $this;
    }

    /** Campo opcional: si viene vacio no se valida ni se incluye. */
    public function optionalString(string $field, int $max = 255, ?string $label = null): self
    {
        if (!$this->present($field)) {
            return $this;
        }

        return $this->string($field, 0, $max, $label);
    }

    private function present(string $field): bool
    {
        $value = $this->data[$field] ?? null;

        return $value !== null && !(is_string($value) && trim($value) === '');
    }

    private function addError(string $field, string $message): self
    {
        $this->errors[$field][] = $message;
        unset($this->validated[$field]);

        return $this;
    }

    /**
     * @return array<string, mixed>
     * @throws ValidationException
     */
    public function validated(): array
    {
        if ($this->errors !== []) {
            throw new ValidationException($this->errors);
        }

        return $this->validated;
    }

    public static function parseDate(string $value): ?DateTimeImmutable
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
