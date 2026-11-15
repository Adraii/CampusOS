<?php
// app/config/database.php
// Koneksi database terpusat menggunakan PDO Prepared Statements

/**
 * Mendapatkan koneksi database PDO (Singleton pattern sederhana)
 * 
 * @return PDO|null
 */
function getDBConnection(): ?PDO
{
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $configFile = __DIR__ . '/config.local.php';
    if (!file_exists($configFile)) {
        $configFile = __DIR__ . '/config.example.php';
    }

    if (!file_exists($configFile)) {
        error_log("Database Config Error: Berkas konfigurasi database tidak ditemukan.");
        return null;
    }

    $config = require $configFile;
    $db = $config['db'] ?? [];

    $host     = $db['host'] ?? '127.0.0.1';
    $dbname   = $db['dbname'] ?? 'campusos_db';
    $username = $db['username'] ?? 'root';
    $password = $db['password'] ?? '';
    $charset  = $db['charset'] ?? 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $username, $password, $options);
        return $pdo;
    } catch (\PDOException $e) {
        error_log("Database Connection Failed: " . $e->getMessage());
        return null;
    }
}
