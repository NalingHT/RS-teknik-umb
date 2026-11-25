<?php
/**
 * modules/asuransi/simpan.php — Proses simpan/ubah asuransi ke Server B.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/asuransi/index.php');
}
csrf_verify();

$method = strtoupper(bersih($_POST['_method'] ?? 'POST'));
$id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$edit   = ($method === 'PUT' && $id > 0);

if ($edit) {
    if (!can('asuransi', 'update')) akses_ditolak();
} else {
    require_role(['admin']);
    if (!can('asuransi', 'create')) akses_ditolak();
}

$kode_asuransi = bersih($_POST['kode_asuransi'] ?? '');
$nama_asuransi = bersih($_POST['nama_asuransi'] ?? '');
$jenis         = bersih($_POST['jenis']         ?? '');
$kontak        = bersih($_POST['kontak']        ?? '');
$status        = bersih($_POST['status']        ?? 'aktif');

$errors = [];
if ($kode_asuransi === '') {
    $errors[] = 'Kode asuransi wajib diisi.';
} elseif (mb_strlen($kode_asuransi) > 20) {
    $errors[] = 'Kode asuransi maksimal 20 karakter.';
}
if ($nama_asuransi === '') $errors[] = 'Nama asuransi wajib diisi.';
if (!in_array($jenis, ['BPJS', 'Swasta', 'Perusahaan'], true)) $errors[] = 'Jenis asuransi tidak valid.';
if (!in_array($status, ['aktif', 'nonaktif'], true)) $status = 'aktif';

if (!empty($errors)) {
    flash(implode(' ', $errors), 'error');
    redirect($edit ? "modules/asuransi/form.php?id={$id}" : 'modules/asuransi/form.php');
}

$payload = [
    'kode_asuransi' => $kode_asuransi,
    'nama_asuransi' => $nama_asuransi,
    'jenis'         => $jenis,
    'kontak'        => $kontak,
    'status'        => $status,
];

$resp = $edit ? api_put("asuransi.php?id={$id}", $payload) : api_post('asuransi.php', $payload);

if ($resp['ok']) {
    flash($edit ? 'Data asuransi berhasil diperbarui.' : 'Data asuransi berhasil ditambahkan.', 'success');
    redirect('modules/asuransi/index.php');
} else {
    $msg = $resp['message'] ?: ($edit ? 'Gagal memperbarui data asuransi.' : 'Gagal menambahkan data asuransi.');
    flash($msg, 'error');
    redirect($edit ? "modules/asuransi/form.php?id={$id}" : 'modules/asuransi/form.php');
}
