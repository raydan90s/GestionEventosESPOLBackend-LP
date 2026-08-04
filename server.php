<?php

declare(strict_types=1);

/**
 * Router para el servidor embebido de PHP:
 *
 *   php -S localhost:8000 server.php
 *
 * Sirve los archivos estaticos que existen en /public y envia el resto de
 * peticiones al front controller, igual que hace .htaccess en Apache/XAMPP.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$publicPath = __DIR__ . '/public';
$requested = $publicPath . str_replace('/', DIRECTORY_SEPARATOR, $uri);

if ($uri !== '/' && is_file($requested)) {
    return false; // Deja que el servidor embebido sirva el archivo tal cual.
}

require $publicPath . '/index.php';
