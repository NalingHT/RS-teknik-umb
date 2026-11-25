<?php
// Endpoint POST auth/ganti_password.php — Mengubah password akun yang sedang login
require_once __DIR__ . '/../../../core/init.php';

require_api_key();
require_methods(['POST']);
$user = require_token();

$body = get_json_body();
$passLama = (string)($body['password_lama'] ?? '');
$passBaru = (string)($body['password_baru'] ?? '');

$v = new Validator();
$v->required($body, ['password_lama', 'password_baru'])
  ->min($body, 'password_baru', 8)
  ->validate();

$db = get_db();
$stmt = $db->prepare("SELECT password FROM pengguna WHERE id_pengguna = ? LIMIT 1");
$stmt->bind_param('i', $user['id_pengguna']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row || !password_verify($passLama, $row['password'])) {
    json_error(422, 'Validasi gagal', ['password_lama' => 'Password lama tidak sesuai']);
}

$newHash = password_hash($passBaru, PASSWORD_DEFAULT);
$upd = $db->prepare("UPDATE pengguna SET password = ? WHERE id_pengguna = ?");
$upd->bind_param('si', $newHash, $user['id_pengguna']);
$upd->execute();

write_audit((int)$user['id_pengguna'], 'UPDATE', 'auth/ganti_password', 'Pengguna mengganti password');
json_ok([], [], 200, 'Password berhasil diperbarui');