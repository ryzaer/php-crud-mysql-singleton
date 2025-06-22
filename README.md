# 📘 PHP PDO CRUD Singletone Extension (v7.4+)

`PHP PDO CRUD Singletone` adalah class PHP yang mewarisi `PDO` dan menambahkan fungsi CRUD siap pakai seperti `insert`, `update`, `delete`, `select`, dan `count`, serta mendukung penyimpanan file BLOB secara langsung dari path file.

---

## 🚀 Fitur Utama

- Extends `PDO`, sehingga semua method asli tetap bisa digunakan (`prepare`, `query`, dll).
- Method CRUD langsung tersedia: `insert`, `update`, `delete`, `select`, `count`.
- Dukungan penyimpanan file ke database (LONGBLOB) otomatis.
- Format file yang didukung bisa dikustomisasi.
- Kompatibel dengan PHP 7.4+.

---

## 🧱 Struktur

```
/Database
├── Engine.php       ← Class utama (extends PDO)
├── BlobHelper.php   ← Helper baca & validasi file BLOB
```

---

## ⚙️ Konfigurasi Koneksi

```php
use Database\Engine;

$config = [
    'user' => 'root',
    'pass' => '123',
    'dbname' => 'DBriza',
    'host' => 'localhost',     // opsional, default 'localhost'
    'port' => '3306',          // opsional, default '3306'
    'type' => 'mysql'          // opsional, default 'mysql'
];

$pdo = Engine::connect($config);
```

---

## ✨ Method CRUD

### ➕ `insert(string $table, array $data): int`

Insert data ke tabel.

**Contoh:**

```php
$id = $pdo->insert('users', [
    'name' => 'Riza',
    'avatar' => '/path/to/image.jpg' // akan terbaca sebagai string
], true);

atau 

$id = $pdo->blob()->insert('users', [
    'name' => 'Riza',
    'avatar' => '/path/to/image.jpg' // akan terbaca sebagai file
], true);
```

---

### 🔁 `update(string $table, array $data, array $where): bool`

Update data berdasarkan kondisi `where`.

**Contoh:**

```php
$pdo->blob()->update('users', [
    'name' => 'Updated Name',
    'avatar' => '/path/to/image.jpg' // akan terupdate sebagai file
], [
    'id' => 1
]);

atau 

$pdo->update('users', [
    'name' => 'Updated Name',
    'avatar' => '/path/to/image.jpg' // akan terupdate sebagai string
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

### 📄 `select(string $table, ?string $where = null, ?string $order = null, ?int $limit = null, ?string $columns = '*'): array`

Ambil data dari tabel dengan filter opsional.

**Contoh:**

```php
$data = $pdo->select('users', 'age > 25', 'id DESC', 10);
```

---

### 🔢 `count(string $table, ?string $where = null): int`

Hitung jumlah record.

**Contoh:**

```php
$total = $pdo->count('users', 'role = "admin"');
```

---

## 📦 Format File BLOB

Default: `tmp|mp4|webm|mp3|ogg|aac|zip|png|gif|jpeg|jpg|bmp|svg|pdf`

**Ubah dengan:**

```php
$pdo->format = 'jpg|png|pdf';
```

---

## 🧪 Testing File BLOB

```php
$isValid = BlobHelper::isBlobFile('image.jpg', $pdo->format); // true
$content = BlobHelper::readFile('image.jpg'); // isi binary
```

---

## 🧼 Catatan

- Jika file path tidak valid, field akan diisi kosong.
- Tidak ada validasi size file — pastikan Anda tidak melebihi batas max\_packet\_size MySQL dan memory\_limit PHP.

---

## 📚 Lisensi

MIT License – bebas digunakan dan dimodifikasi.