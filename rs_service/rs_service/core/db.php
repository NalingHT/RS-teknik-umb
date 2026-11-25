<?php
// Koneksi database tunggal MySQLi (utf8mb4) dan konfigurasi global
ini_set('display_errors', '0');
error_reporting(E_ALL);

function get_config(): array {
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }
    return $config;
}

function get_db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        $cfg = get_config()['db'];
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name']);
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// Tangkap exception tak terduga agar respons selalu JSON rapi
set_exception_handler(function (Throwable $e) {
    $logMsg = sprintf("[%s] %s in %s:%d\n", date('Y-m-d H:i:s'), $e->getMessage(), $e->getFile(), $e->getLine());
    @file_put_contents(__DIR__ . '/../logs/error.log', $logMsg, FILE_APPEND);

    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $msg = get_config()['debug'] ? $e->getMessage() : 'Terjadi kesalahan internal pada server';
    echo json_encode([
        'status'  => 'error',
        'message' => $msg,
        'errors'  => []
    ], JSON_UNESCAPED_UNICODE);
    exit;
});