<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'bilgipaylas');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', '/soc');
define('SITE_NAME', 'BilgiPaylaş');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}
