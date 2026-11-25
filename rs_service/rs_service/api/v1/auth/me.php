<?php
// Endpoint GET auth/me.php — Informasi profil pengguna dari Bearer token aktif
require_once __DIR__ . '/../../../core/init.php';

require_api_key();
require_methods(['GET']);
$user = require_token();

$data = [
    'id_pengguna'  => (int)$user['id_pengguna'],
    'username'     => $user['username'],
    'nama_lengkap' => $user['nama_lengkap'],
    'email'        => $user['email'],
    'role'         => $user['role'],
];
if (!empty($user['id_dokter'])) {
    $data['id_dokter'] = (int)$user['id_dokter'];
}
if (!empty($user['id_pasien'])) {
    $data['id_pasien'] = (int)$user['id_pasien'];
}

json_ok($data);