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
    public function obtenerComentariosDeEvento(int $idEvento, int $maxResultados = 50, int $salto = 0): array
    {
        $maxResultados = max(1, min(100, $maxResultados));
        $salto = max(0, $salto);

        return $this->select(
            sprintf(
                'SELECT id, evento_id, autor, contenido, created_at
                   FROM comentarios
                  WHERE evento_id = :evento_id
                  ORDER BY created_at DESC
                  LIMIT %d OFFSET %d',
                $maxResultados,
                $salto
            ),
            ['evento_id' => $idEvento]
        );
    }

    public function contarComentariosDeEvento(int $idEvento): int
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
    public function registrarComentario(int $idEvento, array $payload): array
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

        /** @var array<string, mixed> $nuevoComentario */
        $nuevoComentario = $sentencia->fetch();

        return $nuevoComentario;
    }
}
