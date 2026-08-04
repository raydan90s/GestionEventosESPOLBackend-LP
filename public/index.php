<?php

declare(strict_types=1);

/**
 * Punto de entrada unico de la API (front controller).
 *
 * Todas las peticiones HTTP pasan por aqui: Apache/XAMPP las redirige mediante
 * .htaccess y el servidor embebido de PHP mediante server.php.
 */

use App\Core\App;
use App\Core\Autoloader;

$basePath = dirname(__DIR__);

require $basePath . '/src/Core/Autoloader.php';

Autoloader::register('App', $basePath . '/src');

(new App($basePath))->boot()->run();
