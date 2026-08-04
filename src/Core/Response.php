<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Emisor de respuestas JSON.
 */
final class Response
{
    /** @param mixed $data */
    public static function json($data, int $status = 200, array $headers = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    /** @param mixed $data */
    public static function success($data, int $status = 200, ?string $message = null): void
    {
        $payload = ['ok' => true];

        if ($message !== null) {
            $payload['message'] = $message;
        }

        $payload['data'] = $data;

        self::json($payload, $status);
    }

    /** @param array<string, string[]> $errors */
    public static function error(string $message, int $status = 400, array $errors = []): void
    {
        $payload = [
            'ok'      => false,
            'message' => $message,
        ];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        self::json($payload, $status);
    }

    public static function noContent(): void
    {
        http_response_code(204);
    }
}
