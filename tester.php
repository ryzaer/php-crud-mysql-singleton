<?php
require_once "Database/Engine.php";
require_once "Database/BlobHelper.php";

use Database\Engine;

$pdo = Engine::connect([
    'user' => 'root',
    'pass' => '123',
    'dbname' => 'db_ident',
    'host' => 'localhost',     // opsional, default 'localhost'
    'port' => '3306',          // opsional, default '3306'
    'type' => 'mysql'          // opsional, default 'mysql'
]);
$stmt = $pdo->prepare("select * from tbbiodata limit 1");
$stmt->execute();
var_dump($stmt->fetchAll(PDO::FETCH_ASSOC));