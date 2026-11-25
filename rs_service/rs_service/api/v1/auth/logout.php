<?php
// Endpoint POST auth/logout.php — Mencabut token sesi pengguna
require_once __DIR__ . '/../../../core/init.php';

require_api_key();
require_methods(['POST']);
$user = require_token();

$db = get_db();
$stmt = $db->prepare("UPDATE api_token SET revoked = 1 WHERE token_hash = ?");
$stmt->bind_param('s', $user['raw_token_hash']);
$stmt->execute();

write_audit((int)$user['id_pengguna'], 'LOGOUT', 'auth', 'Logout berhasil');
json_ok([], [], 200, 'Logout berhasil');