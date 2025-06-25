<?php

namespace Database;

use PDO;
use PDOException;

class Engine extends PDO
{
    use Connection;

    public $format = 'tmp|mp4|webm|mp3|ogg|aac|zip|png|gif|jpeg|jpg|bmp|svg|pdf';

    public function insert(string $table, array $data, bool $blob = false): int
    {
        $fields = [];
        $placeholders = [];
        $bindValues = [];

        foreach ($data as $key => $value) {
            if ($blob && BlobHelper::isBlobFile($value, $this->format)) {
                $fileContent = BlobHelper::readFile($value);
                if ($fileContent !== null) {
                    $fields[] = $key;
                    $placeholders[] = ":$key";
                    $bindValues[":$key"] = ['value' => $fileContent, 'param' => PDO::PARAM_LOB];
                }
            } elseif (!$blob) {
                $fields[] = $key;
                $placeholders[] = ":$key";
                $bindValues[":$key"] = ['value' => $value, 'param' => is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR];
            }
        }

        $sql = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->prepare($sql);

        foreach ($bindValues as $placeholder => $bind) {
            $stmt->bindValue($placeholder, $bind['value'], $bind['param']);
        }

        $stmt->execute();
        return (int)$this->lastInsertId();
    }

    public function update(string $table, array $data, array $where, bool $blob = false): bool
    {
        $setParts = [];
        $bindValues = [];

        foreach ($data as $key => $value) {
            if ($blob && BlobHelper::isBlobFile($value, $this->format)) {
                $fileContent = BlobHelper::readFile($value);
                if ($fileContent !== null) {
                    $setParts[] = "$key = :$key";
                    $bindValues[":$key"] = ['value' => $fileContent, 'param' => PDO::PARAM_LOB];
                }
            } elseif (!$blob) {
                $setParts[] = "$key = :$key";
                $bindValues[":$key"] = ['value' => $value, 'param' => is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR];
            }
        }

        $whereParts = [];
        foreach ($where as $key => $value) {
            $whereParts[] = "$key = :where_$key";
            $bindValues[":where_$key"] = ['value' => $value, 'param' => is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR];
        }

        $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE " . implode(' AND ', $whereParts);
        $stmt = $this->prepare($sql);

        foreach ($bindValues as $placeholder => $bind) {
            $stmt->bindValue($placeholder, $bind['value'], $bind['param']);
        }

        return $stmt->execute();
    }

    public function delete(string $table, array $where): bool
    {
        $whereParts = [];
        $bindValues = [];

        foreach ($where as $key => $value) {
            $whereParts[] = "$key = :$key";
            $bindValues[":$key"] = ['value' => $value, 'param' => is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR];
        }

        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $whereParts);
        $stmt = $this->prepare($sql);

        foreach ($bindValues as $placeholder => $bind) {
            $stmt->bindValue($placeholder, $bind['value'], $bind['param']);
        }

        return $stmt->execute();
    }

    public function select(string $table, array $where = [], ?string $order = null, ?int $limit = null, string $columns = '*', bool $useLike = false, array $orWhere = []): array
    {
        $whereParts = [];
        $bindValues = [];

        foreach ($where as $key => $value) {
            if ($useLike) {
                $whereParts[] = "$key LIKE CONCAT('%', :$key, '%')";
            } else {
                $whereParts[] = "$key = :$key";
            }
            $bindValues[":$key"] = ['value' => $value, 'param' => PDO::PARAM_STR];
        }

        if (!empty($orWhere)) {
            $orParts = [];
            foreach ($orWhere as $key => $value) {
                if ($useLike) {
                    $orParts[] = "$key LIKE CONCAT('%', :or_$key, '%')";
                } else {
                    $orParts[] = "$key = :or_$key";
                }
                $bindValues[":or_$key"] = ['value' => $value, 'param' => PDO::PARAM_STR];
            }
            $whereParts[] = '( ' . implode(' OR ', $orParts) . ' )';
        }

        $sql = "SELECT $columns FROM $table";
        if (!empty($whereParts)) {
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }
        if ($order) {
            $sql .= " ORDER BY $order";
        }
        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        $stmt = $this->prepare($sql);

        foreach ($bindValues as $placeholder => $bind) {
            $stmt->bindValue($placeholder, $bind['value'], $bind['param']);
        }

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(string $table, array $where = []): int
    {
        $whereParts = [];
        $bindValues = [];

        foreach ($where as $key => $value) {
            $whereParts[] = "$key = :$key";
            $bindValues[":$key"] = ['value' => $value, 'param' => PDO::PARAM_STR];
        }

        $sql = "SELECT COUNT(*) as total FROM $table";
        if (!empty($whereParts)) {
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }

        $stmt = $this->prepare($sql);

        foreach ($bindValues as $placeholder => $bind) {
            $stmt->bindValue($placeholder, $bind['value'], $bind['param']);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int)$result['total'];
    }

    public function createTable(string $table, array $columns, string $engine = 'InnoDB', string $charset = 'utf8mb4'): bool
    {
        $fields = [];
        foreach ($columns as $name => $definition) {
            $fields[] = "`$name` $definition";
        }

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (" . implode(',', $fields) . ") ENGINE=$engine DEFAULT CHARSET=$charset;";

        return $this->exec($sql) !== false;
    }
}

trait Connection
{
    public static function connect(array $config): ?Engine
    {
        $user = $config['user'] ?? '';
        $pass = $config['pass'] ?? '';
        $dbname = $config['dbname'] ?? '';
        $host = $config['host'] ?? 'localhost';
        $port = $config['port'] ?? '3306';
        $type = $config['type'] ?? 'mysql';

        try {
            return new Engine("$type:host=$host;port=$port;dbname=$dbname", $user, $pass);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
}