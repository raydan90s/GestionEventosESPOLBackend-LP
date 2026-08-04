<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Autoloader PSR-4 minimo.
 *
 * Evita la dependencia de Composer: el proyecto es una API RESTful nativa en PHP,
 * asi que resolvemos las clases mapeando el namespace raiz a un directorio base.
 */
final class Autoloader
{
    /** @var array<string, string> prefijo de namespace => directorio base */
    private static array $prefixes = [];

    public static function register(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        self::$prefixes[$prefix] = rtrim($baseDir, DIRECTORY_SEPARATOR . '/') . DIRECTORY_SEPARATOR;

        static $registered = false;
        if (!$registered) {
            spl_autoload_register([self::class, 'load']);
            $registered = true;
        }
    }

    private static function load(string $class): void
    {
        foreach (self::$prefixes as $prefix => $baseDir) {
            if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
                continue;
            }

            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

            if (is_file($file)) {
                require $file;
                return;
            }
        }
    }
}
