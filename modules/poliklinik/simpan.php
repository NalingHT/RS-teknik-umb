<?php
/**
 * modules/poliklinik/simpan.php — Proses POST/PUT poliklinik ke Server B.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('modules/poliklinik/index.php');
csrf_verify();

$id      = bersih($_POST['id'] ?? '');
$method  = bersih($_POST['_method'] ?? 'POST');
$is_edit = $method === 'PUT';

$payload = [
    'kode_poli' => bersih($_POST['kode_poli'] ?? ''),
    'nama_poli' => bersih($_POST['nama_poli'] ?? ''),
    'lokasi'    => bersih($_POST['lokasi']    ?? ''),
    'status'    => bersih($_POST['status']    ?? 'aktif'),
];

// Validasi sisi A
$errors = [];
if (!$payload['kode_poli']) $errors[] = 'Kode poliklinik wajib diisi.';
if (strlen($payload['kode_poli']) > 10) $errors[] = 'Kode poliklinik maksimal 10 karakter.';
if (!$payload['nama_poli']) $errors[] = 'Nama poliklinik wajib diisi.';

if ($errors) {
    flash(implode(' ', $errors), 'error');
    $url = $is_edit ? "modules/poliklinik/form.php?id={$id}" : 'modules/poliklinik/form.php';
    redirect($url);
}

if ($is_edit) {
    if (!can('poliklinik','update')) akses_ditolak();
    $res = api_put('poliklinik.php?id=' . urlencode($id), $payload);
} else {
    if (!can('poliklinik','create')) akses_ditolak();
    $res = api_post('poliklinik.php', $payload);
}

if ($res['ok']) {
    flash('Poliklinik berhasil ' . ($is_edit ? 'diperbarui' : 'ditambahkan') . '.', 'success');
} else {
    // Tampilkan error dari Server B (mis. kode duplikat)
    $msg = $res['message'] ?: 'Gagal menyimpan data.';
    if ($res['http'] === 409) $msg = 'Kode poliklinik sudah digunakan atau masih dipakai entitas lain.';
    flash($msg, 'error');
}

redirect('modules/poliklinik/index.php');
