<?php
// Endpoint POST auth/login.php — Autentikasi pengguna dan penerbitan Bearer token
require_once __DIR__ . '/../../../core/init.php';

require_api_key();
require_methods(['POST']);

$body = get_json_body();
$username = trim((string)($body['username'] ?? ''));
$password = (string)($body['password'] ?? '');

if ($username === '' || $password === '') {
    json_error(401, 'Username atau password salah');
}

$db = get_db();
$cfg = get_config();

$sql = "SELECT u.id_pengguna, u.username, u.password, u.nama_lengkap, u.role, u.status,
               u.gagal_login, u.blokir_sampai,
               d.id_dokter, p.id_pasien
        FROM pengguna u
        LEFT JOIN dokter d ON d.id_pengguna = u.id_pengguna
        LEFT JOIN pasien p ON p.id_pengguna = u.id_pengguna
        WHERE u.username = ? LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    write_audit(null, 'LOGIN_GAGAL', 'auth', "Username tidak ditemukan: {$username}");
    json_error(401, 'Username atau password salah');
}

// Cek apakah sedang diblokir sementara
if (!empty($user['blokir_sampai']) && strtotime($user['blokir_sampai']) > time()) {
    write_audit((int)$user['id_pengguna'], 'LOGIN_GAGAL', 'auth', 'Percobaan login saat akun diblokir sementara');
    json_error(401, 'Username atau password salah');
}

// Cek status aktif & verifikasi password
if ($user['status'] !== 'aktif' || !password_verify($password, $user['password'])) {
    $gagal = (int)$user['gagal_login'] + 1;
    $maxGagal = (int)($cfg['login_max_gagal'] ?? 5);
    $blokirMenit = (int)($cfg['login_blokir_menit'] ?? 10);
    $blokirSampai = null;

    if ($gagal >= $maxGagal) {
        $blokirSampai = date('Y-m-d H:i:s', time() + ($blokirMenit * 60));
    }

    $upd = $db->prepare("UPDATE pengguna SET gagal_login = ?, blokir_sampai = ? WHERE id_pengguna = ?");
    $upd->bind_param('isi', $gagal, $blokirSampai, $user['id_pengguna']);
    $upd->execute();

    write_audit((int)$user['id_pengguna'], 'LOGIN_GAGAL', 'auth', "Password salah (percobaan ke-{$gagal})");
    json_error(401, 'Username atau password salah');
}

// Login berhasil: reset gagal_login, update last_login, buat token
$rawToken  = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $rawToken);
$ttlMenit  = (int)($cfg['token_ttl_minutes'] ?? 120);
$expiresAt = date('Y-m-d H:i:s', time() + ($ttlMenit * 60));
$ip        = get_client_ip();

$db->begin_transaction();
try {
    $updOk = $db->prepare("UPDATE pengguna SET gagal_login = 0, blokir_sampai = NULL, last_login = NOW() WHERE id_pengguna = ?");
    $updOk->bind_param('i', $user['id_pengguna']);
    $updOk->execute();

    $insTok = $db->prepare("INSERT INTO api_token (id_pengguna, token_hash, ip_address, expires_at) VALUES (?, ?, ?, ?)");
    $insTok->bind_param('isss', $user['id_pengguna'], $tokenHash, $ip, $expiresAt);
    $insTok->execute();

    $db->commit();
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}

write_audit((int)$user['id_pengguna'], 'LOGIN', 'auth', 'Login berhasil');

$userData = [
    'id_pengguna'  => (int)$user['id_pengguna'],
    'username'     => $user['username'],
    'nama_lengkap' => $user['nama_lengkap'],
    'role'         => $user['role'],
];
if (!empty($user['id_dokter'])) {
    $userData['id_dokter'] = (int)$user['id_dokter'];
}
if (!empty($user['id_pasien'])) {
    $userData['id_pasien'] = (int)$user['id_pasien'];
}

json_ok([
    'token'      => $rawToken,
    'expires_at' => $expiresAt,
    'user'       => $userData,
], [], 200, 'Login berhasil');