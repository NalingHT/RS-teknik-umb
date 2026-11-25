<?php
// Helper pencatatan audit_log ke database dan error.log

function write_audit(?int $idPengguna, string $aksi, string $modul, string $detail = ''): void {
    try {
        $db = get_db();
        $ip = get_client_ip();
        $stmt = $db->prepare("INSERT INTO audit_log (id_pengguna, aksi, modul, detail, ip_address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('issss', $idPengguna, $aksi, $modul, $detail, $ip);
        $stmt->execute();
    } catch (Throwable $e) {
        write_error_log('Gagal menulis audit_log: ' . $e->getMessage());
    }
}

function write_error_log(string $message): void {
    $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $message);
    @file_put_contents(__DIR__ . '/../logs/error.log', $line, FILE_APPEND);
}