<?php
// File: Database/Crud.php

namespace Database;

use PDO;
use PDOException;

class Crud {
    private PDO $pdo;
    public string $format = 'tmp|mp4|webm|mp3|ogg|aac|zip|png|gif|jpeg|jpg|bmp|svg|pdf';

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function insert(string $table, array $data, bool $useBlob = false): int {
        $keys = array_keys($data);
        $placeholders = array_map(fn($k) => ":$k", $keys);
        $sql = "INSERT INTO `$table` (" . implode(',', $keys) . ") VALUES (" . implode(',', $placeholders) . ")";

        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $key => $value) {
            $param = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;

            if ($useBlob && BlobHelper::isBlobFile($value, $this->format)) {
                $content = BlobHelper::readFile($value);
                $stmt->bindValue(":$key", $content, PDO::PARAM_LOB);
            } else {
                $stmt->bindValue(":$key", $value, $param);
            }
        }

        $stmt->execute();
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $table, array $data, array $where, bool $useBlob = false): bool {
        $setParts = [];
        foreach ($data as $key => $_) {
            $setParts[] = "$key = :$key";
        }
        $whereParts = [];
        foreach ($where as $key => $_) {
            $whereParts[] = "$key = :w_$key";
        }

        $sql = "UPDATE `$table` SET " . implode(',', $setParts) . " WHERE " . implode(' AND ', $whereParts);
        $stmt = $this->pdo->prepare($sql);

        foreach ($data as $key => $value) {
            $param = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            if ($useBlob && BlobHelper::isBlobFile($value, $this->format)) {
                $value = BlobHelper::readFile($value);
                $stmt->bindValue(":$key", $value, PDO::PARAM_LOB);
            } else {
                $stmt->bindValue(":$key", $value, $param);
            }
        }

        foreach ($where as $key => $value) {
            $param = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":w_$key", $value, $param);
        }

        return $stmt->execute();
    }

    public function delete(string $table, array $where): bool {
        $parts = [];
        foreach ($where as $key => $_) {
            $parts[] = "$key = :$key";
        }
        $sql = "DELETE FROM `$table` WHERE " . implode(' AND ', $parts);
        $stmt = $this->pdo->prepare($sql);

        foreach ($where as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        return $stmt->execute();
    }

    public function select(string $table, ?string $where = null, ?string $order = null, ?int $limit = null, ?string $columns = '*'): array {
        $sql = "SELECT $columns FROM `$table`";
        if ($where) $sql .= " WHERE $where";
        if ($order) $sql .= " ORDER BY $order";
        if ($limit !== null) $sql .= " LIMIT $limit";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(string $table, ?string $where = null): int {
        $sql = "SELECT COUNT(*) FROM `$table`";
        if ($where) $sql .= " WHERE $where";
        return (int) $this->pdo->query($sql)->fetchColumn();
    }
}