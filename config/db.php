<?php
// config/db.php

$host = 'localhost';
$db   = 'election_system';
$user = 'root';
$pass = 'Sultan@3141';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In a real app, log error and show user-friendly message
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
