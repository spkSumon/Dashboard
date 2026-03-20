<?php
namespace App\Core;

use mysqli;
use mysqli_stmt;

/**
 * Database wrapper rond mysqli (vanilla, geen ORM).
 *
 * Doel:
 * - 1 plek waar je connectie wordt gemaakt
 * - helpers om veilig queries uit te voeren met prepared statements
 *
 * Tip: werk ALTIJD met prepared statements om SQL-injectie te vermijden.
 */
class Database {
    private mysqli $conn;

    public function __construct(array $config) {
        $this->conn = new mysqli(
            $config['host'] ?? '127.0.0.1',
            $config['user'] ?? '',
            $config['pass'] ?? '',
            $config['name'] ?? '',
            (int)($config['port'] ?? 3306)
        );

        if ($this->conn->connect_error) {
            throw new \RuntimeException('DB connect error: ' . $this->conn->connect_error);
        }

        $this->conn->set_charset($config['charset'] ?? 'utf8mb4');
    }

    public function conn(): mysqli {
        return $this->conn;
    }

    /**
     * Maak een prepared statement + bind variabelen.
     * $types is bvb "si" (string, int). Als je null meegeeft proberen we types te raden.
     */
    public function prepare(string $sql, ?string $types = null, array $params = []): mysqli_stmt {
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException('Prepare failed: ' . $this->conn->error);
        }

        if (!empty($params)) {
            if ($types === null) {
                $types = '';
                foreach ($params as $p) {
                    if (is_int($p)) $types .= 'i';
                    elseif (is_float($p)) $types .= 'd';
                    else $types .= 's'; // default string (ook voor null)
                }
            }

            // mysqli bind_param verwacht references; daarom deze truc
            $refs = [];
            foreach ($params as $k => $v) {
                $refs[$k] = $params[$k];
            }
            $stmt->bind_param($types, ...$refs);
        }

        return $stmt;
    }

    /** Fetch all rows (associative) */
    public function fetchAll(string $sql, ?string $types = null, array $params = []): array {
        $stmt = $this->prepare($sql, $types, $params);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Fetch 1 row (associative) */
    public function fetchOne(string $sql, ?string $types = null, array $params = []): ?array {
        $rows = $this->fetchAll($sql, $types, $params);
        return $rows[0] ?? null;
    }

    /** Execute INSERT/UPDATE/DELETE */
    public function exec(string $sql, ?string $types = null, array $params = []): int {
        $stmt = $this->prepare($sql, $types, $params);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    public function insertId(): int {
        return (int)$this->conn->insert_id;
    }
}
