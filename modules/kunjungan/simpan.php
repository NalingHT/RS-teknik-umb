<?php
/**
 * modules/kunjungan/simpan.php — Proses simpan kunjungan (admin, petugas, dan pendaftaran mandiri pasien).
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin', 'petugas', 'pasien']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/kunjungan/index.php');
}
csrf_verify();

$role = $_SESSION['role'] ?? '';
$id   = (int)($_POST['id'] ?? 0);
$edit = ($id > 0 && $role !== 'pasien');

if ($edit && !can('kunjungan', 'update')) {
    akses_ditolak();
}
if (!$edit && !can('kunjungan', 'create')) {
    akses_ditolak();
}

// Pasien wajib memakai id_pasien dari session (anti-spoofing)
if ($role === 'pasien') {
    $id_pasien = (int)($_SESSION['id_pasien'] ?? 0);
} else {
    $id_pasien = (int)($_POST['id_pasien'] ?? 0);
}

$id_poli           = (int)($_POST['id_poli']           ?? 0);
$id_dokter         = (int)($_POST['id_dokter']         ?? 0);
$tanggal_kunjungan = bersih($_POST['tanggal_kunjungan'] ?? '');
$keluhan           = bersih($_POST['keluhan']           ?? '');
$cara_bayar        = bersih($_POST['cara_bayar']        ?? '');

$errors = [];
if ($id_pasien <= 0)         $errors[] = 'Pasien wajib dipilih.';
if ($id_poli   <= 0)         $errors[] = 'Poliklinik wajib dipilih.';
if ($id_dokter <= 0)         $errors[] = 'Dokter wajib dipilih.';
if ($tanggal_kunjungan === '') $errors[] = 'Tanggal kunjungan wajib diisi.';
if (!in_array($cara_bayar, ['umum', 'asuransi'], true)) $errors[] = 'Cara bayar tidak valid.';

if (!$edit && $tanggal_kunjungan !== '' && $tanggal_kunjungan < date('Y-m-d')) {
    $errors[] = 'Tanggal kunjungan tidak boleh sebelum hari ini.';
}

if ($cara_bayar === 'asuransi' && $id_pasien > 0) {
    $resp_pasien = api_get('pasien.php', ['id' => $id_pasien]);
    $data_pasien = $resp_pasien['ok'] ? ($resp_pasien['data'] ?? []) : [];
    if (empty($data_pasien['id_asuransi']) && empty($data_pasien['no_kartu_asuransi'])) {
        $errors[] = 'Pasien tidak memiliki data asuransi.';
    }
}

if (!empty($errors)) {
    $_SESSION['old_input'] = $_POST;
    flash(implode(' ', $errors), 'error');
    if ($role === 'pasien') {
        redirect('modules/kunjungan/daftar.php');
    }
    redirect($edit ? "modules/kunjungan/form.php?id={$id}" : 'modules/kunjungan/form.php');
}

$payload = [
    'id_pasien'         => $id_pasien,
    'id_poli'           => $id_poli,
    'id_dokter'         => $id_dokter,
    'tanggal_kunjungan' => $tanggal_kunjungan,
    'keluhan'           => $keluhan,
    'cara_bayar'        => $cara_bayar,
];

if ($edit) {
    $resp = api_put('kunjungan.php?id=' . $id, $payload);
} else {
    $resp = api_post('kunjungan.php', $payload);
}

if ($resp['ok']) {
    flash($edit ? 'Data kunjungan berhasil diperbarui.' : 'Kunjungan berhasil disimpan.', 'success');
    redirect('modules/kunjungan/index.php');
} else {
    $_SESSION['old_input'] = $_POST;
    $msg = $resp['message'] ?: 'Terjadi kesalahan saat menyimpan data kunjungan.';
    flash($msg, 'error');
    if ($role === 'pasien') {
        redirect('modules/kunjungan/daftar.php');
    }
    redirect($edit ? "modules/kunjungan/form.php?id={$id}" : 'modules/kunjungan/form.php');
}
