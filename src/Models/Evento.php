<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Eventos del catalogo (RF: Crear evento / Ver catalogo de eventos - Juliana Burgos).
 */
final class Evento extends Model
{
    protected string $table = 'eventos';

    private const SELECT_BASE = '
        SELECT e.id,
               e.titulo,
               e.descripcion,
               e.categoria_id,
               c.nombre        AS categoria_nombre,
               e.ubicacion,
               e.fecha_evento,
               e.cupos_maximos,
               e.cupos_disponibles,
               (e.cupos_maximos - e.cupos_disponibles) AS inscritos,
               e.organizador,
               e.imagen_url,
               e.estado,
               e.created_at
          FROM eventos e
          INNER JOIN categorias c ON c.id = e.categoria_id';

    /**
     * Catalogo filtrable por categoria, rango de fechas, texto y disponibilidad.
     *
     * @param array<string, mixed> $filtros
     * @return array<int, array<string, mixed>>
     */
    public function catalogo(array $filtros = []): array
    {
        $where = [];
        $bindings = [];

        if (!empty($filtros['categoria_id'])) {
            $where[] = 'e.categoria_id = :categoria_id';
            $bindings['categoria_id'] = (int) $filtros['categoria_id'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = 'e.fecha_evento >= :fecha_desde';
            $bindings['fecha_desde'] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = 'e.fecha_evento <= :fecha_hasta';
            $bindings['fecha_hasta'] = $filtros['fecha_hasta'];
        }

        if (!empty($filtros['q'])) {
            // Un solo marcador: PDO sin emulacion no admite repetir :q en la consulta.
            $where[] = "(e.titulo || ' ' || COALESCE(e.descripcion, '') || ' ' || e.ubicacion) ILIKE :q";
            $bindings['q'] = '%' . $filtros['q'] . '%';
        }

        if (!empty($filtros['estado'])) {
            $where[] = 'e.estado = :estado';
            $bindings['estado'] = $filtros['estado'];
        }

        if (!empty($filtros['solo_disponibles'])) {
            $where[] = 'e.cupos_disponibles > 0';
        }

        if (!empty($filtros['solo_proximos'])) {
            $where[] = 'e.fecha_evento >= NOW()';
        }

        $sql = self::SELECT_BASE;

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY e.fecha_evento ASC';

        $limite = isset($filtros['limite']) ? max(1, min(100, (int) $filtros['limite'])) : 50;
        $offset = isset($filtros['offset']) ? max(0, (int) $filtros['offset']) : 0;
        $sql .= sprintf(' LIMIT %d OFFSET %d', $limite, $offset);

        return $this->select($sql, $bindings);
    }

    /** @return array<string, mixed>|null */
    public function detalle(int $id): ?array
    {
        return $this->selectOne(self::SELECT_BASE . ' WHERE e.id = :id', ['id' => $id]);
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function crear(array $datos): array
    {
        $sql = 'INSERT INTO eventos
                    (titulo, descripcion, categoria_id, ubicacion, fecha_evento,
                     cupos_maximos, cupos_disponibles, organizador, imagen_url, estado)
                VALUES
                    (:titulo, :descripcion, :categoria_id, :ubicacion, :fecha_evento,
                     :cupos_maximos, :cupos_disponibles, :organizador, :imagen_url, :estado)
                RETURNING id';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'titulo'            => $datos['titulo'],
            'descripcion'       => $datos['descripcion'] ?? null,
            'categoria_id'      => $datos['categoria_id'],
            'ubicacion'         => $datos['ubicacion'],
            'fecha_evento'      => $datos['fecha_evento'],
            'cupos_maximos'     => $datos['cupos_maximos'],
            'cupos_disponibles' => $datos['cupos_maximos'],
            'organizador'   => $datos['organizador'] ?? null,
            'imagen_url'    => $datos['imagen_url'] ?? null,
            'estado'        => $datos['estado'] ?? 'activo',
        ]);

        $id = (int) $stmt->fetchColumn();

        /** @var array<string, mixed> $evento */
        $evento = $this->detalle($id);

        return $evento;
    }

    /**
     * Actualiza solo los campos presentes en $datos.
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>|null
     */
    public function actualizar(int $id, array $datos): ?array
    {
        $permitidos = ['titulo', 'descripcion', 'categoria_id', 'ubicacion', 'fecha_evento', 'organizador', 'imagen_url', 'estado'];
        $sets = [];
        $bindings = ['id' => $id];

        foreach ($permitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $sets[] = sprintf('%s = :%s', $campo, $campo);
                $bindings[$campo] = $datos[$campo];
            }
        }

        // El aforo se ajusta conservando la cantidad ya inscrita.
        // Se usan dos marcadores distintos porque PDO no permite repetir un
        // parametro con nombre cuando las consultas preparadas no se emulan.
        if (array_key_exists('cupos_maximos', $datos)) {
            $sets[] = 'cupos_disponibles = :nuevo_aforo - (cupos_maximos - cupos_disponibles)';
            $sets[] = 'cupos_maximos = :cupos_maximos';
            $bindings['nuevo_aforo'] = $datos['cupos_maximos'];
            $bindings['cupos_maximos'] = $datos['cupos_maximos'];
        }

        if ($sets === []) {
            return $this->detalle($id);
        }

        $sets[] = 'updated_at = NOW()';

        $this->execute(
            sprintf('UPDATE eventos SET %s WHERE id = :id', implode(', ', $sets)),
            $bindings
        );

        return $this->detalle($id);
    }

    public function totalInscritos(int $id): int
    {
        $row = $this->selectOne('SELECT COUNT(*) AS total FROM inscripciones WHERE evento_id = :id', ['id' => $id]);

        return (int) ($row['total'] ?? 0);
    }
}
