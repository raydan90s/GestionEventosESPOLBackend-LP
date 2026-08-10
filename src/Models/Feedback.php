<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Modelo para las opiniones o comentarios (Eimmy Ochoa).
 */
final class Feedback extends Model
{
    protected string $table = 'comentarios'; // Mantiene la tabla original para no romper la BD

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerListaPorEvento(int $idEvento, int $limite = 50, int $offset = 0): array
    {
        return $this->select(
            sprintf(
                'SELECT id, evento_id, autor, contenido, created_at
                   FROM comentarios
                  WHERE evento_id = :evento_id
                  ORDER BY created_at DESC
                  LIMIT %d OFFSET %d',
                $limite,
                $offset
            ),
            ['evento_id' => $idEvento]
        );
    }

    public function contarPorEvento(int $idEvento): int
    {
        $registro = $this->selectOne(
            'SELECT COUNT(*) AS total FROM comentarios WHERE evento_id = :evento_id',
            ['evento_id' => $idEvento]
        );

        return (int) ($registro['total'] ?? 0);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function insertar(int $idEvento, array $payload): array
    {
        $sentencia = $this->db->prepare(
            'INSERT INTO comentarios (evento_id, autor, contenido)
             VALUES (:evento_id, :autor, :contenido)
             RETURNING id, evento_id, autor, contenido, created_at'
        );

        $sentencia->execute([
            'evento_id' => $idEvento,
            'autor'     => $payload['autor'],
            'contenido' => $payload['contenido'],
        ]);

        /** @var array<string, mixed> $nuevoFeedback */
        $nuevoFeedback = $sentencia->fetch();

        return $nuevoFeedback;
    }
}
