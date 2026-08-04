<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exceptions\HttpException;

/**
 * Controlador base: utilidades compartidas por todos los controladores.
 */
abstract class Controller
{
    /**
     * Obtiene un parametro de ruta como entero positivo.
     */
    protected function idParam(Request $request, string $name = 'id'): int
    {
        $raw = $request->param($name);

        if ($raw === null || filter_var($raw, FILTER_VALIDATE_INT) === false || (int) $raw < 1) {
            throw HttpException::badRequest(sprintf('El parametro "%s" debe ser un identificador numerico valido.', $name));
        }

        return (int) $raw;
    }

    /** @param mixed $data */
    protected function ok($data, ?string $message = null): void
    {
        Response::success($data, 200, $message);
    }

    /** @param mixed $data */
    protected function created($data, ?string $message = null): void
    {
        Response::success($data, 201, $message);
    }
}
