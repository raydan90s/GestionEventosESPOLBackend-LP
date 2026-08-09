<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Config;
use App\Core\Request;

/**
 * Registro de peticiones para desarrollo.
 *
 * El servidor embebido de PHP (php -S ... server.php) no escribe ninguna linea
 * de acceso para las peticiones que resuelve el router script: solo muestra
 * "Accepted" y "Closing", sin decir que endpoint se pidio. Bajo Apache/XAMPP
 * pasa algo parecido, porque el access_log queda en los logs de Apache y no en
 * la consola donde se trabaja.
 *
 * Este middleware imprime una linea por peticion con metodo, ruta, estado,
 * duracion y cuerpo, para poder seguir el trafico del frontend en vivo.
 *
 * Se activa con LOG_REQUESTS en el .env; si no se define, sigue a APP_DEBUG.
 * Nunca deberia quedar encendido en produccion: el cuerpo de una inscripcion
 * lleva el correo y el telefono del estudiante.
 */
final class RequestLogger
{
    /** Claves cuyo valor se enmascara en el log. */
    private const CLAVES_SENSIBLES = ['password', 'contrasena', 'token', 'secret', 'api_key', 'authorization'];

    /** Longitud maxima del cuerpo antes de recortarlo. */
    private const MAX_CUERPO = 500;

    /**
     * Programa el registro de la peticion actual.
     *
     * La linea se emite al terminar el script, no al empezar, para poder
     * incluir el codigo de estado y la duracion. Se usa register_shutdown_function
     * porque tambien se ejecuta cuando la respuesta acaba en un exit(), como
     * hace Cors con las peticiones de verificacion previa (preflight).
     */
    public static function start(Request $request): void
    {
        if (!(bool) Config::get('app.log_requests', false)) {
            return;
        }

        $inicio = microtime(true);

        register_shutdown_function(static function () use ($request, $inicio): void {
            $estado = http_response_code();

            self::escribir(sprintf(
                '[%s] %-6s %s %6.1fms  %s%s',
                date('H:i:s'),
                $request->method(),
                is_int($estado) ? (string) $estado : '???',
                (microtime(true) - $inicio) * 1000,
                self::ruta($request),
                self::cuerpo($request)
            ));
        });
    }

    /** Ruta con su cadena de consulta, tal como llego. */
    private static function ruta(Request $request): string
    {
        $query = $request->allQuery();

        return $query === []
            ? $request->path()
            : $request->path() . '?' . http_build_query($query);
    }

    /** Cuerpo JSON de la peticion, enmascarado y recortado. */
    private static function cuerpo(Request $request): string
    {
        $body = $request->body();

        if ($body === []) {
            return '';
        }

        foreach ($body as $clave => $valor) {
            if (in_array(strtolower((string) $clave), self::CLAVES_SENSIBLES, true)) {
                $body[$clave] = '***';
            }
        }

        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            return '  body=<no serializable>';
        }

        if (mb_strlen($json) > self::MAX_CUERPO) {
            $json = mb_substr($json, 0, self::MAX_CUERPO) . '...(recortado)';
        }

        return '  body=' . $json;
    }

    /**
     * Escribe en la salida de error.
     *
     * Con php -S eso es la terminal donde corre el servidor; bajo Apache va al
     * error log del servidor. Si el flujo no se puede abrir, se cae a error_log().
     */
    private static function escribir(string $linea): void
    {
        $stream = @fopen('php://stderr', 'w');

        if ($stream === false) {
            error_log($linea);

            return;
        }

        fwrite($stream, $linea . PHP_EOL);
        fclose($stream);
    }
}
