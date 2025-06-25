<?php

namespace Database;

use PDO;
use PDOException;

class Engine extends PDO
{
    public string $format = 'tmp|mp4|webm|mp3|ogg|aac|zip|png|gif|jpeg|jpg|bmp|svg|pdf';

    public static function connect(array $config): self
    {
        $dsn = sprintf(
            '%s:host=%s;port=%s%s',
            $config['type'] ?? 'mysql',
            $config['host'] ?? 'localhost',
            $config['port'] ?? '3306',
            isset($config['dbname']) ? ';dbname=' . $config['dbname'] : ''
        );

        try {
            $pdo = new self($dsn, $config['user'] ?? '', $config['pass'] ?? '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            die("Connection Error: " . $e->getMessage());
        }
    }

    public function insert(string $table, array $data, bool $blob = false): int
    {
        $keys = array_keys($data);
        $placeholders = array_map(fn($k) => ":$k", $keys);
        $sql = "INSERT INTO `$table` (" . implode(',', $keys) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->prepare($sql);

        foreach ($data as $key => $value) {
            $isBlob = $blob && BlobHelper::isBlobFile($value, $this->format);
            $val = $isBlob ? BlobHelper::readFile($value) : $value;
            $type = $isBlob ? PDO::PARAM_LOB : (is_numeric($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            $stmt->bindValue(":$key", $val, $type);
        }

        $stmt->execute();
        return (int) $this->lastInsertId();
    }

    public function update(string $table, array $data, array $where, bool $blob = false): bool
    {
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "$key = :$key";
        }

        $whereParts = [];
        foreach ($where as $key => $value) {
            $whereParts[] = "$key = :w_$key";
        }

        $sql = "UPDATE `$table` SET " . implode(', ', $setParts) . " WHERE " . implode(' AND ', $whereParts);
        $stmt = $this->prepare($sql);

        foreach ($data as $key => $value) {
            $isBlob = $blob && BlobHelper::isBlobFile($value, $this->format);
            $val = $isBlob ? BlobHelper::readFile($value) : $value;
            $type = $isBlob ? PDO::PARAM_LOB : (is_numeric($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            $stmt->bindValue(":$key", $val, $type);
        }

        foreach ($where as $key => $value) {
            $type = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":w_$key", $value, $type);
        }

        return $stmt->execute();
    }

    public function delete(string $table, array $where): bool
    {
        $whereParts = [];
        foreach ($where as $key => $value) {
            $whereParts[] = "$key = :$key";
        }

        $sql = "DELETE FROM `$table` WHERE " . implode(' AND ', $whereParts);
        $stmt = $this->prepare($sql);

        foreach ($where as $key => $value) {
            $type = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":$key", $value, $type);
        }

        return $stmt->execute();
    }

    public function select(
        string $table,
        array $where = [],
        ?string $order = null,
        ?int $limit = null,
        string $columns = '*',
        bool $useLike = false,
        array $orWhere = []
    ): array {
        $sql = "SELECT $columns FROM `$table`";
        $params = [];
        $conditions = [];

        if (!empty($where)) {
            foreach ($where as $key => $value) {
                if ($useLike) {
                    $conditions[] = "$key LIKE :$key";
                    $params[":$key"] = "%$value%";
                } else {
                    $conditions[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
        }

        if (!empty($orWhere)) {
            $orConditions = [];
            foreach ($orWhere as $key => $value) {
                if ($useLike) {
                    $orConditions[] = "$key LIKE :or_$key";
                    $params[":or_$key"] = "%$value%";
                } else {
                    $orConditions[] = "$key = :or_$key";
                    $params[":or_$key"] = $value;
                }
            }
            if (!empty($orConditions)) {
                $conditions[] = '( ' . implode(' OR ', $orConditions) . ' )';
            }
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if ($order) {
            $sql .= " ORDER BY $order";
        }

        if ($limit !== null) {
            $sql .= " LIMIT $limit";
        }

        $stmt = $this->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(string $table, array $where = []): int
    {
        $sql = "SELECT COUNT(*) FROM `$table`";
        $params = [];
        $conditions = [];

        if (!empty($where)) {
            foreach ($where as $key => $value) {
                $conditions[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function createTable(string $table, array $columns, string $engine = 'InnoDB', string $charset = 'utf8mb4'): bool
    {
        $cols = [];
        foreach ($columns as $name => $definition) {
            $cols[] = "`$name` $definition";
        }

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (" . implode(', ', $cols) . ") ENGINE=$engine DEFAULT CHARSET=$charset";

        return $this->exec($sql) === false ? false : true;
    }
}