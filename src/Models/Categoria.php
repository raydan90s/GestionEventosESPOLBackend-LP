<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Categorias preestablecidas para clasificar la oferta de eventos.
 */
final class Categoria extends Model
{
    protected string $table = 'categorias';

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->select(
            'SELECT c.id,
                    c.nombre,
                    c.descripcion,
                    COUNT(e.id) AS total_eventos
               FROM categorias c
               LEFT JOIN eventos e ON e.categoria_id = c.id AND e.estado = \'activo\'
              GROUP BY c.id, c.nombre, c.descripcion
              ORDER BY c.nombre'
        );
    }
}
