<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Exceptions\HttpException;
use Throwable;

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
     * Condiciones del catalogo, compartidas entre `catalogo()` y
     * `contarCatalogo()`: el total tiene que salir de exactamente los mismos
     * filtros que la pagina, o la paginacion del frontend contaria mal.
     *
     * @param array<string, mixed> $filtros
     * @return array{0: array<int, string>, 1: array<string, mixed>}
     */
    private function condicionesCatalogo(array $filtros): array
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

        return [$where, $bindings];
    }

    /**
     * Catalogo filtrable por categoria, rango de fechas, texto y disponibilidad.
     *
     * @param array<string, mixed> $filtros
     * @return array<int, array<string, mixed>>
     */
    public function catalogo(array $filtros = []): array
    {
        [$where, $bindings] = $this->condicionesCatalogo($filtros);

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

    /**
     * Total de eventos que cumplen los mismos filtros que `catalogo()`, sin
     * `LIMIT`/`OFFSET`. Mismo patron que `Feedback::contarPorEvento`.
     *
     * @param array<string, mixed> $filtros
     */
    public function contarCatalogo(array $filtros = []): int
    {
        [$where, $bindings] = $this->condicionesCatalogo($filtros);

        $sql = 'SELECT COUNT(*) AS total FROM eventos e';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $registro = $this->selectOne($sql, $bindings);

        return (int) ($registro['total'] ?? 0);
    }

    /** @return array<string, mixed>|null */
    public function detalle(int $id): ?array
    {
        return $this->selectOne(self::SELECT_BASE . ' WHERE e.id = :id', ['id' => $id]);
    }

    /**
     * Crea un evento validando que la categoria indicada exista
     * (misma idea que Inscripcion::registrar valida sus reglas antes de escribir).
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function crear(array $datos): array
    {
        if (!$this->categoriaExiste((int) $datos['categoria_id'])) {
            throw HttpException::badRequest('La categoria indicada no existe.');
        }

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
     * Corre dentro de una transaccion con bloqueo de fila (SELECT ... FOR UPDATE),
     * igual que Inscripcion::registrar / cancelar, para que el ajuste de aforo no
     * quede desactualizado si llega una inscripcion al mismo tiempo.
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>|null
     */
    public function actualizar(int $id, array $datos): ?array
    {
        if (isset($datos['categoria_id']) && !$this->categoriaExiste((int) $datos['categoria_id'])) {
            throw HttpException::badRequest('La categoria indicada no existe.');
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'SELECT cupos_maximos, cupos_disponibles FROM eventos WHERE id = :id FOR UPDATE'
            );
            $stmt->execute(['id' => $id]);
            $actual = $stmt->fetch();

            if ($actual === false) {
                throw HttpException::notFound('El evento que intenta actualizar no existe.');
            }

            $permitidos = ['titulo', 'descripcion', 'categoria_id', 'ubicacion', 'fecha_evento', 'organizador', 'imagen_url', 'estado'];
            $sets = [];
            $bindings = ['id' => $id];

            foreach ($permitidos as $campo) {
                if (array_key_exists($campo, $datos)) {
                    $sets[] = sprintf('%s = :%s', $campo, $campo);
                    $bindings[$campo] = $datos[$campo];
                }
            }

            // El aforo se ajusta conservando la cantidad ya inscrita, calculada
            // con los valores que acabamos de leer bajo bloqueo de fila.
            if (array_key_exists('cupos_maximos', $datos)) {
                $inscritos = (int) $actual['cupos_maximos'] - (int) $actual['cupos_disponibles'];

                if ((int) $datos['cupos_maximos'] < $inscritos) {
                    throw HttpException::conflict(
                        sprintf('El aforo no puede ser menor que las %d inscripciones ya registradas.', $inscritos)
                    );
                }

                $sets[] = 'cupos_maximos = :cupos_maximos';
                $sets[] = 'cupos_disponibles = :cupos_disponibles';
                $bindings['cupos_maximos'] = $datos['cupos_maximos'];
                $bindings['cupos_disponibles'] = (int) $datos['cupos_maximos'] - $inscritos;
            }

            if ($sets !== []) {
                $sets[] = 'updated_at = NOW()';

                $this->execute(
                    sprintf('UPDATE eventos SET %s WHERE id = :id', implode(', ', $sets)),
                    $bindings
                );
            }

            $this->db->commit();

            return $this->detalle($id);
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    private function categoriaExiste(int $categoriaId): bool
    {
        return $this->selectOne('SELECT 1 FROM categorias WHERE id = :id', ['id' => $categoriaId]) !== null;
    }
}
