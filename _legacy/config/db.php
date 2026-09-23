<?php
// Configuration & Database Connection

if (!defined('BASE_URL')) {
    define('BASE_URL', '/rms/');
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'rms_db';
$db_port = 3306;

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host={$db_host};port={$db_port};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    
    // Automatically create database if it doesn't exist yet
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$db_name}`");
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
