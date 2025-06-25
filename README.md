# 📘 DatabaseEngine - PDO CRUD Extension for PHP 7.4+

`DatabaseEngine` adalah class PHP yang mewarisi `PDO` dan menambahkan fungsi CRUD siap pakai seperti `insert`, `update`, `delete`, `select`, `count`, dan `createTable`, serta mendukung penyimpanan file BLOB secara langsung dari path file.

---

## 🚀 Fitur Utama

- Extends `PDO`, sehingga semua method asli tetap bisa digunakan (`prepare`, `query`, dll).
- Method CRUD langsung tersedia: `insert`, `update`, `delete`, `select`, `count`.
- Dukungan penyimpanan file ke database (LONGBLOB) otomatis.
- Format file yang didukung bisa dikustomisasi.
- `select` mendukung `LIKE` dan `OR` yang aman dengan bind parameter.
- `createTable` untuk membuat tabel dengan parameter array.
- Kompatibel dengan PHP 7.4+.

---

## 🧱 Struktur

```
/Database
├── DatabaseEngine.php   ← Class utama (extends PDO)
```

---

## ⚙️ Konfigurasi Koneksi

```php
use Database\DatabaseEngine;

$config = [
    'user' => 'root',
    'pass' => '123',
    'dbname' => 'DBriza',
    'host' => 'localhost',     // opsional, default 'localhost'
    'port' => '3306',          // opsional, default '3306'
    'type' => 'mysql'          // opsional, default 'mysql'
];

$pdo = DatabaseEngine::connect($config);
```

---

## ✨ Method CRUD

### ➕ `insert(string $table, array $data, bool $blob = false): int`

Insert data ke tabel.

**Contoh:**

```php
$id = $pdo->insert('users', [
    'name' => 'Riza',
    'avatar' => '/path/to/image.jpg' // akan dibaca otomatis jika $blob = true
], true);
```

---

### 🔁 `update(string $table, array $data, array $where, bool $blob = false): bool`

Update data berdasarkan kondisi `where`.

**Contoh:**

```php
$pdo->update('users', [
    'name' => 'Updated Name'
], [
    'id' => 1
]);
```

---

### ❌ `delete(string $table, array $where): bool`

Hapus data berdasarkan kondisi.

**Contoh:**

```php
$pdo->delete('users', ['id' => 5]);
```

---

### 📄 `select(string $table, array $where = [], ?string $order = null, ?int $limit = null, string $columns = '*', bool $useLike = false, array $orWhere = []): array`

Ambil data dari tabel dengan dukungan `LIKE` dan `OR` yang aman.

**Contoh:**

```php
// Select sederhana
$data = $pdo->select('users', ['role' => 'admin'], 'id DESC', 10);

// Select dengan LIKE dan OR
$data = $pdo->select('users', ['name' => 'John'], null, null, '*', true, ['email' => 'gmail.com']);
```

---

### 🔢 `count(string $table, array $where = []): int`

Hitung jumlah record berdasarkan kondisi.

**Contoh:**

```php
$total = $pdo->count('users', ['role' => 'admin']);
```

---

### 🏗️ `createTable(string $table, array $columns, string $engine = 'InnoDB', string $charset = 'utf8mb4'): bool`

Membuat tabel baru dengan definisi kolom berbasis array.

**Contoh:**

```php
$pdo->createTable('users', [
    'id' => 'INT(11) AUTO_INCREMENT PRIMARY KEY',
    'name' => 'VARCHAR(255) NOT NULL',
    'email' => 'VARCHAR(255) NOT NULL',
    'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP'
]);
```

---

## 📦 Format File BLOB

Default: `tmp|mp4|webm|mp3|ogg|aac|zip|png|gif|jpeg|jpg|bmp|svg|pdf`

**Ubah dengan:**

```php
$pdo->format = 'jpg|png|pdf';
```

---

## 🧪 BLOB Helper

```php
$isValid = Database\BlobHelper::isBlobFile('image.jpg', $pdo->format); // true
$content = Database\BlobHelper::readFile('image.jpg'); // isi binary file
```

---

## 🧼 Catatan

- Jika file path tidak valid, field akan diisi kosong.
- Tidak ada validasi size file — pastikan Anda tidak melebihi batas max\_packet\_size MySQL dan memory\_limit PHP.

---

## 📚 Lisensi

MIT License – bebas digunakan dan dimodifikasi.