<?php
/**
 * modules/pasien/simpan.php — Proses simpan/ubah pasien ke Server B.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin', 'petugas', 'pasien']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/pasien/index.php');
}
csrf_verify();

$is_admin_or_petugas = can('pasien', 'create');
$id_pasien_session   = $_SESSION['id_pasien'] ?? null;

$id      = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$is_edit = $id > 0;

if (!$is_admin_or_petugas) {
    if (!$id_pasien_session) {
        akses_ditolak();
    }
    $id      = (int)$id_pasien_session;
    $is_edit = true;
}

$nama_pasien       = bersih($_POST['nama_pasien']       ?? '');
$jenis_kelamin     = bersih($_POST['jenis_kelamin']     ?? '');
$tanggal_lahir     = bersih($_POST['tanggal_lahir']     ?? '');
$tempat_lahir      = bersih($_POST['tempat_lahir']      ?? '');
$alamat            = bersih($_POST['alamat']            ?? '');
$no_hp             = bersih($_POST['no_hp']             ?? '');
$nik               = bersih($_POST['nik']               ?? '');
$id_asuransi       = (int)($_POST['id_asuransi']        ?? 0);
$no_kartu_asuransi = bersih($_POST['no_kartu_asuransi'] ?? '');

$errors = [];
if ($nama_pasien === '') $errors[] = 'Nama pasien wajib diisi.';
if (!in_array($jenis_kelamin, ['L', 'P'], true)) $errors[] = 'Jenis kelamin wajib dipilih (L atau P).';
if ($tanggal_lahir === '') {
    $errors[] = 'Tanggal lahir wajib diisi.';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_lahir)) {
    $errors[] = 'Format tanggal lahir tidak valid.';
}
if ($nik !== '' && !preg_match('/^\d{16}$/', $nik)) {
    $errors[] = 'NIK harus 16 digit angka.';
}
if ($id_asuransi > 0 && $no_kartu_asuransi === '') {
    $errors[] = 'No. kartu asuransi wajib diisi jika asuransi dipilih.';
}

if (!empty($errors)) {
    $_SESSION['old_input'] = $_POST;
    flash(implode(' ', $errors), 'error');
    redirect($is_edit ? "modules/pasien/form.php?id={$id}" : 'modules/pasien/form.php');
}

$payload = [
    'nama_pasien'       => $nama_pasien,
    'jenis_kelamin'     => $jenis_kelamin,
    'tanggal_lahir'     => $tanggal_lahir,
    'tempat_lahir'      => $tempat_lahir,
    'alamat'            => $alamat,
    'no_hp'             => $no_hp,
    'nik'               => $nik,
    'id_asuransi'       => $id_asuransi > 0 ? $id_asuransi : null,
    'no_kartu_asuransi' => $id_asuransi > 0 ? $no_kartu_asuransi : '',
];

$resp = $is_edit ? api_put("pasien.php?id={$id}", $payload) : api_post('pasien.php', $payload);

if ($resp['ok']) {
    flash($is_edit ? 'Data pasien berhasil diperbarui.' : 'Pasien berhasil didaftarkan.', 'success');
    redirect('modules/pasien/index.php');
} else {
    $_SESSION['old_input'] = $_POST;
    $msg = $resp['message'] ?: 'Terjadi kesalahan saat menyimpan data pasien.';
    flash($msg, 'error');
    redirect($is_edit ? "modules/pasien/form.php?id={$id}" : 'modules/pasien/form.php');
}
