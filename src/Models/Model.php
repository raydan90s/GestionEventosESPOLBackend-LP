<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Modelo base: expone la conexion PDO y helpers de consulta reutilizables.
 */
abstract class Model
{
    protected PDO $db;

    /** Nombre de la tabla en PostgreSQL. */
    protected string $table = '';

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /**
     * @param array<string, mixed> $bindings
     * @return array<int, array<string, mixed>>
     */
    protected function select(string $sql, array $bindings = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->fetchAll();
    }

    /**
     * @param array<string, mixed> $bindings
     * @return array<string, mixed>|null
     */
    protected function selectOne(string $sql, array $bindings = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string, mixed> $bindings
     */
    protected function execute(string $sql, array $bindings = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->rowCount();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        return $this->selectOne(sprintf('SELECT * FROM %s WHERE id = :id', $this->table), ['id' => $id]);
    }

    public function exists(int $id): bool
    {
        return $this->selectOne(sprintf('SELECT 1 FROM %s WHERE id = :id', $this->table), ['id' => $id]) !== null;
    }

    public function delete(int $id): bool
    {
        return $this->execute(sprintf('DELETE FROM %s WHERE id = :id', $this->table), ['id' => $id]) > 0;
    }
}
