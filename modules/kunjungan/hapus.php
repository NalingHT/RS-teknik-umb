<?php
/**
 * modules/kunjungan/hapus.php — Hapus kunjungan (Khusus Admin).
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin']);

if (!can('kunjungan', 'delete')) {
    akses_ditolak();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/kunjungan/index.php');
}

csrf_verify();

$id = bersih($_POST['id'] ?? '');
if (!$id) {
    flash('ID kunjungan tidak valid.', 'error');
    redirect('modules/kunjungan/index.php');
}

$resp = api_delete("kunjungan.php?id=" . urlencode($id));

if ($resp['ok']) {
    flash('Data kunjungan berhasil dihapus.', 'success');
} else {
    flash($resp['message'] ?: 'Gagal menghapus data kunjungan.', 'error');
}

redirect('modules/kunjungan/index.php');
