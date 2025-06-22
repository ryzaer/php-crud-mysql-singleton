<?php 
namespace Database;

use PDO;
use PDOException;

class Engine extends PDO
{
    public string $format = 'tmp|mp4|jpg|png|pdf';
    private bool $allowBlob = false;

    public static function connect(array $config): self {
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
            // parent::__construct($dsn, $config['user'] ?? '', $config['pass'] ?? '');
            return $pdo;
        } catch (PDOException $e) {
            die("Connection Error: " . $e->getMessage());
        }
    }

    public function blob(){
        $this->allowBlob = true;
        return $this;
    }

    public function insert(string $table, array $data): int {
        $keys = array_keys($data);
        $placeholders = array_map(fn($k) => ":$k", $keys);
        $sql = "INSERT INTO `$table` (" . implode(',', $keys) . ") VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->prepare($sql);

        foreach ($data as $key => $value) {
            $isBlob = $this->allowBlob && BlobHelper::isBlobFile($value, $this->format);
            $val = $isBlob ? BlobHelper::readFile($value) : $value;
            $type = $isBlob ? PDO::PARAM_LOB : (is_numeric($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            $stmt->bindValue(":$key", $val, $type);
        }
        $this->allowBlob = false;
        $stmt->execute();
        return (int) $this->lastInsertId();
    }

    public function update(string $table, array $data, array $where): bool {
        $setParts = [];
        foreach ($data as $key => $_) {
            $setParts[] = "$key = :$key";
        }
        $whereParts = [];
        foreach ($where as $key => $_) {
            $whereParts[] = "$key = :w_$key";
        }

        $sql = "UPDATE `$table` SET " . implode(',', $setParts) . " WHERE " . implode(' AND ', $whereParts);
        $stmt = $this->prepare($sql);

       foreach ($data as $key => $value) {
            $isBlob = $this->allowBlob && BlobHelper::isBlobFile($value, $this->format);
            $val = $isBlob ? BlobHelper::readFile($value) : $value;
            $type = $isBlob ? PDO::PARAM_LOB : (is_numeric($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
            $stmt->bindValue(":$key", $val, $type);
        }

        foreach ($where as $key => $value) {
            $param = is_numeric($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(":w_$key", $value, $param);
        }

        $this->allowBlob = false;

        return $stmt->execute();
    }

    public function delete(string $table, array $where): bool {
        $parts = [];
        foreach ($where as $key => $_) {
            $parts[] = "$key = :$key";
        }
        $sql = "DELETE FROM `$table` WHERE " . implode(' AND ', $parts);
        $stmt = $this->prepare($sql);

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

        $stmt = $this->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function count(string $table, ?string $where = null): int {
        $sql = "SELECT COUNT(*) FROM `$table`";
        if ($where) $sql .= " WHERE $where";
        return (int) $this->query($sql)->fetchColumn();
    }
}
