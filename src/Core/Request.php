<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Abstraccion de la peticion HTTP entrante.
 */
final class Request
{
    private string $method;
    private string $path;
    /** @var array<string, mixed> */
    private array $query;
    /** @var array<string, mixed> */
    private array $body;
    /** @var array<string, string> */
    private array $params = [];

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     */
    public function __construct(string $method, string $path, array $query = [], array $body = [])
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->query = $query;
        $this->body = $body;
    }

    public static function capture(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Permite que clientes limitados envien PUT/DELETE via _method.
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper((string) $_POST['_method']);
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        return new self($method, self::normalizePath($path), $_GET, self::parseBody());
    }

    /** @return array<string, mixed> */
    private static function parseBody(): array
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            if (trim($raw) === '') {
                return [];
            }

            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    private static function normalizePath(string $path): string
    {
        // Cuando se sirve desde XAMPP en un subdirectorio, elimina el prefijo
        // del script (ej. /GestionEventosESPOLBackend-LP/public).
        //
        // El servidor embebido (php -S ... server.php) se excluye: ahi PHP pone
        // en SCRIPT_NAME la ruta solicitada y no el script, de modo que
        // dirname() devolveria el primer segmento de la URL (/api) y lo
        // recortaria, dejando /health y rompiendo todas las rutas.
        if (PHP_SAPI !== 'cli-server') {
            $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
            $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

            if ($base !== '' && $base !== '.' && str_starts_with($path, $base)) {
                $path = substr($path, strlen($base));
            }
        }

        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    /** @return mixed */
    public function query(string $key, $default = null)
    {
        $value = $this->query[$key] ?? $default;

        return is_string($value) && trim($value) === '' ? $default : $value;
    }

    /** @return array<string, mixed> */
    public function allQuery(): array
    {
        return $this->query;
    }

    /** @return mixed */
    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function body(): array
    {
        return $this->body;
    }

    /** @param array<string, string> $params */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, ?string $default = null): ?string
    {
        return $this->params[$key] ?? $default;
    }
}
