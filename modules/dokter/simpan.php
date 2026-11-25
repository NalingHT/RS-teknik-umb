<?php
/**
 * modules/dokter/simpan.php — Proses simpan/ubah dokter ke Server B.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin', 'dokter']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/dokter/index.php');
}
csrf_verify();

$is_admin          = can('dokter', 'delete');
$id_dokter_session = $_SESSION['id_dokter'] ?? null;

$id      = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$is_edit = $id > 0;

if (!$is_admin && $is_edit) {
    if (!$id_dokter_session || $id_dokter_session != $id) {
        flash('Anda tidak berhak mengubah data dokter lain.', 'error');
        redirect('modules/dokter/index.php');
    }
}
if (!$is_admin && !$is_edit) {
    akses_ditolak();
}

$nama_dokter = bersih($_POST['nama_dokter'] ?? '');
$id_poli     = (int)($_POST['id_poli']     ?? 0);
$nip         = bersih($_POST['nip']         ?? '');
$spesialis   = bersih($_POST['spesialis']   ?? '');
$no_str      = bersih($_POST['no_str']      ?? '');
$no_hp       = bersih($_POST['no_hp']       ?? '');
$status      = bersih($_POST['status']      ?? 'aktif');
$id_pengguna = (int)($_POST['id_pengguna'] ?? 0);

$errors = [];
if ($nama_dokter === '') $errors[] = 'Nama dokter wajib diisi.';
if ($id_poli <= 0)       $errors[] = 'Poliklinik wajib dipilih.';
if (!in_array($status, ['aktif', 'nonaktif'], true)) $status = 'aktif';

if (!empty($errors)) {
    $_SESSION['old_input'] = $_POST;
    flash(implode(' ', $errors), 'error');
    redirect($is_edit ? "modules/dokter/form.php?id={$id}" : 'modules/dokter/form.php');
}

$payload = [
    'nama_dokter' => $nama_dokter,
    'id_poli'     => $id_poli,
    'nip'         => $nip,
    'spesialis'   => $spesialis,
    'no_str'      => $no_str,
    'no_hp'       => $no_hp,
];

if ($is_admin) {
    $payload['status'] = $status;
    if (!$is_edit && $id_pengguna > 0) {
        $payload['id_pengguna'] = $id_pengguna;
    }
} else {
    $payload = [
        'no_hp'       => $no_hp,
        'spesialis'   => $spesialis,
        'no_str'      => $no_str,
        'nama_dokter' => $nama_dokter,
        'id_poli'     => $id_poli,
    ];
}

$resp = $is_edit ? api_put("dokter.php?id={$id}", $payload) : api_post('dokter.php', $payload);

if ($resp['ok']) {
    flash($is_edit ? 'Data dokter berhasil diperbarui.' : 'Dokter berhasil ditambahkan.', 'success');
    redirect('modules/dokter/index.php');
} else {
    $_SESSION['old_input'] = $_POST;
    $msg = $resp['message'] ?: 'Terjadi kesalahan saat menyimpan data dokter.';
    flash($msg, 'error');
    redirect($is_edit ? "modules/dokter/form.php?id={$id}" : 'modules/dokter/form.php');
}
