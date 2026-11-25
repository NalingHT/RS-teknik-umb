<?php
/**
 * modules/pengguna/simpan.php — Proses simpan/ubah pengguna ke Server B (Khusus Admin).
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/pengguna/index.php');
}
csrf_verify();

$method = strtoupper(bersih($_POST['_method'] ?? 'POST'));
$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$edit   = ($method === 'PUT' && $id > 0);

$username     = bersih($_POST['username']     ?? '');
$password     = $_POST['password']            ?? '';
$nama_lengkap = bersih($_POST['nama_lengkap'] ?? '');
$email        = bersih($_POST['email']        ?? '');
$role         = bersih($_POST['role']         ?? '');
$status       = bersih($_POST['status']       ?? 'aktif');

$errors = [];
if (!$edit && $username === '') $errors[] = 'Username wajib diisi.';
if (!$edit && $password === '') {
    $errors[] = 'Password wajib diisi.';
} elseif ($password !== '' && mb_strlen($password) < 8) {
    $errors[] = 'Password minimal 8 karakter.';
}
if ($nama_lengkap === '') $errors[] = 'Nama lengkap wajib diisi.';
if (!in_array($role, ['admin', 'petugas', 'dokter', 'pasien'], true)) {
    $errors[] = 'Role tidak valid.';
}
if (!in_array($status, ['aktif', 'nonaktif'], true)) $status = 'aktif';

if (!empty($errors)) {
    flash(implode(' ', $errors), 'error');
    redirect($edit ? "modules/pengguna/form.php?id={$id}" : 'modules/pengguna/form.php');
}

$payload = [
    'nama_lengkap' => $nama_lengkap,
    'email'        => $email,
    'role'         => $role,
    'status'       => $status,
];
if (!$edit) $payload['username'] = $username;
if ($password !== '') $payload['password'] = $password;

$resp = $edit ? api_put("pengguna.php?id={$id}", $payload) : api_post('pengguna.php', $payload);

if ($resp['ok']) {
    flash($edit ? 'Data pengguna berhasil diperbarui.' : 'Pengguna baru berhasil ditambahkan.', 'success');
    redirect('modules/pengguna/index.php');
} else {
    $msg = $resp['message'] ?: ($edit ? 'Gagal memperbarui data pengguna.' : 'Gagal menambahkan pengguna.');
    flash($msg, 'error');
    redirect($edit ? "modules/pengguna/form.php?id={$id}" : 'modules/pengguna/form.php');
}
