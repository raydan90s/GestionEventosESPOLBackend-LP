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
    // Se sirve aqui mismo en vez de devolver false: "devolver false" delega
    // el archivo en el servidor embebido, pero este lo busca relativo a su
    // propio docroot -la carpeta desde la que se invoco "php -S", no
    // necesariamente "public/"-, y con el comando documentado (ejecutado
    // desde la raiz del proyecto) esos dos caminos no coinciden. Servirlo
    // a mano no depende de con que docroot se haya arrancado el comando.
    header('Content-Type: ' . (mime_content_type($requested) ?: 'application/octet-stream'));
    header('Content-Length: ' . (string) filesize($requested));
    readfile($requested);

    return true;
}

require $publicPath . '/index.php';
