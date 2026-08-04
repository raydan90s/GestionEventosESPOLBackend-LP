<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Comentarios de cada evento
 * (RF: Escribir comentario / Ver comentarios - Eimmy Ochoa).
 */
final class Comentario extends Model
{
    protected string $table = 'comentarios';

    /**
     * @return array<int, array<string, mixed>>
     */
    public function porEvento(int $eventoId, int $limite = 50, int $offset = 0): array
    {
        $limite = max(1, min(100, $limite));
        $offset = max(0, $offset);

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
            ['evento_id' => $eventoId]
        );
    }

    public function totalPorEvento(int $eventoId): int
    {
        $row = $this->selectOne(
            'SELECT COUNT(*) AS total FROM comentarios WHERE evento_id = :evento_id',
            ['evento_id' => $eventoId]
        );

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @param array<string, mixed> $datos
     * @return array<string, mixed>
     */
    public function crear(int $eventoId, array $datos): array
    {
        $stmt = $this->db->prepare(
            'INSERT INTO comentarios (evento_id, autor, contenido)
             VALUES (:evento_id, :autor, :contenido)
             RETURNING id, evento_id, autor, contenido, created_at'
        );

        $stmt->execute([
            'evento_id' => $eventoId,
            'autor'     => $datos['autor'],
            'contenido' => $datos['contenido'],
        ]);

        /** @var array<string, mixed> $comentario */
        $comentario = $stmt->fetch();

        return $comentario;
    }
}
