<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Conexion unica (singleton) a PostgreSQL/Supabase mediante PDO.
 */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        if (!in_array('pgsql', PDO::getAvailableDrivers(), true)) {
            throw new RuntimeException(
                'El driver pdo_pgsql no esta habilitado. Activa extension=pdo_pgsql y extension=pgsql en php.ini.'
            );
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            (string) Config::get('db.host'),
            (string) Config::get('db.port'),
            (string) Config::get('db.name'),
            (string) Config::get('db.sslmode')
        );

        try {
            self::$connection = new PDO(
                $dsn,
                (string) Config::get('db.user'),
                (string) Config::get('db.password'),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException('No se pudo conectar a la base de datos: ' . $e->getMessage(), 0, $e);
        }

        return self::$connection;
    }

    /** Util para el endpoint /api/health. */
    public static function check(): bool
    {
        try {
            self::connection()->query('SELECT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
