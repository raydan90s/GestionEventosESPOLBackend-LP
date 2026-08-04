<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Exceptions\HttpException;
use PDO;
use Throwable;

/**
 * Inscripciones y control de aforo
 * (RF: Registrar inscripcion / Ver asistentes - Diego Parrales).
 */
final class Inscripcion extends Model
{
    protected string $table = 'inscripciones';

    /**
     * Registra una inscripcion validando la disponibilidad de cupos en tiempo real.
     *
     * Se ejecuta dentro de una transaccion con bloqueo de fila (SELECT ... FOR UPDATE)
     * para que dos peticiones simultaneas no puedan tomar el mismo ultimo cupo.
     *
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function registrar(int $eventoId, array $datos): array
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'SELECT id, titulo, estado, fecha_evento, cupos_maximos, cupos_disponibles
                   FROM eventos
                  WHERE id = :evento_id
                  FOR UPDATE'
            );
            $stmt->execute(['evento_id' => $eventoId]);
            $evento = $stmt->fetch();

            if ($evento === false) {
                throw HttpException::notFound('El evento solicitado no existe.');
            }

            if ($evento['estado'] !== 'activo') {
                throw HttpException::conflict('El evento no admite inscripciones porque no esta activo.');
            }

            if (strtotime((string) $evento['fecha_evento']) < time()) {
                throw HttpException::conflict('El evento ya se realizo; no se aceptan nuevas inscripciones.');
            }

            if ((int) $evento['cupos_disponibles'] <= 0) {
                throw HttpException::conflict('No quedan cupos disponibles para este evento.');
            }

            $duplicado = $this->db->prepare(
                'SELECT 1 FROM inscripciones WHERE evento_id = :evento_id AND LOWER(correo) = LOWER(:correo)'
            );
            $duplicado->execute(['evento_id' => $eventoId, 'correo' => $datos['correo']]);

            if ($duplicado->fetchColumn() !== false) {
                throw HttpException::conflict('Este correo ya se encuentra inscrito en el evento.');
            }

            $insert = $this->db->prepare(
                'INSERT INTO inscripciones (evento_id, nombre_estudiante, matricula, correo, telefono)
                 VALUES (:evento_id, :nombre_estudiante, :matricula, :correo, :telefono)
                 RETURNING id, evento_id, nombre_estudiante, matricula, correo, telefono, created_at'
            );
            $insert->execute([
                'evento_id'         => $eventoId,
                'nombre_estudiante' => $datos['nombre_estudiante'],
                'matricula'         => $datos['matricula'] ?? null,
                'correo'            => $datos['correo'],
                'telefono'          => $datos['telefono'] ?? null,
            ]);

            /** @var array<string, mixed> $inscripcion */
            $inscripcion = $insert->fetch();

            $update = $this->db->prepare(
                'UPDATE eventos
                    SET cupos_disponibles = cupos_disponibles - 1,
                        updated_at = NOW()
                  WHERE id = :evento_id AND cupos_disponibles > 0
              RETURNING cupos_disponibles'
            );
            $update->execute(['evento_id' => $eventoId]);
            $restantes = $update->fetchColumn();

            if ($restantes === false) {
                throw HttpException::conflict('No quedan cupos disponibles para este evento.');
            }

            $this->db->commit();

            $inscripcion['evento_titulo'] = $evento['titulo'];
            $inscripcion['cupos_restantes'] = (int) $restantes;

            return $inscripcion;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }

    /**
     * Listado de asistentes de un evento para el control logistico.
     *
     * @return array<int, array<string, mixed>>
     */
    public function asistentesPorEvento(int $eventoId, ?string $busqueda = null): array
    {
        $sql = 'SELECT id, nombre_estudiante, matricula, correo, telefono, created_at AS fecha_inscripcion
                  FROM inscripciones
                 WHERE evento_id = :evento_id';
        $bindings = ['evento_id' => $eventoId];

        if ($busqueda !== null && trim($busqueda) !== '') {
            $sql .= " AND (nombre_estudiante || ' ' || COALESCE(matricula, '') || ' ' || correo) ILIKE :q";
            $bindings['q'] = '%' . trim($busqueda) . '%';
        }

        $sql .= ' ORDER BY created_at ASC';

        return $this->select($sql, $bindings);
    }

    /**
     * Cancela una inscripcion y devuelve el cupo al evento.
     */
    public function cancelar(int $inscripcionId): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('DELETE FROM inscripciones WHERE id = :id RETURNING evento_id');
            $stmt->execute(['id' => $inscripcionId]);
            $eventoId = $stmt->fetchColumn();

            if ($eventoId === false) {
                $this->db->rollBack();

                return false;
            }

            $this->execute(
                'UPDATE eventos
                    SET cupos_disponibles = LEAST(cupos_disponibles + 1, cupos_maximos),
                        updated_at = NOW()
                  WHERE id = :evento_id',
                ['evento_id' => $eventoId]
            );

            $this->db->commit();

            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}
