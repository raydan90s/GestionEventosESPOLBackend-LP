<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Config;
use App\Core\Request;

/**
 * Cabeceras CORS para que el frontend React pueda consumir la API.
 */
final class Cors
{
    public static function apply(Request $request): void
    {
        /** @var string[] $allowed */
        $allowed = (array) Config::get('cors.allowed_origins', ['*']);
        $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');

        if (in_array('*', $allowed, true)) {
            header('Access-Control-Allow-Origin: *');
        } elseif ($origin !== '' && in_array($origin, $allowed, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', (array) Config::get('cors.allowed_methods', [])));
        header('Access-Control-Allow-Headers: ' . implode(', ', (array) Config::get('cors.allowed_headers', [])));
        header('Access-Control-Max-Age: ' . (string) Config::get('cors.max_age', 86400));

        // La peticion de verificacion previa (preflight) termina aqui.
        if ($request->method() === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
