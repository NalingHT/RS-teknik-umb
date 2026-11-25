<?php
/**
 * modules/dokter/hapus.php — Hapus dokter via POST + CSRF (Khusus Admin).
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
if (!can('dokter', 'delete')) {
    akses_ditolak();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/dokter/index.php');
}
csrf_verify();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    flash('ID dokter tidak valid.', 'error');
    redirect('modules/dokter/index.php');
}

$resp = api_delete("dokter.php?id={$id}");

if ($resp['ok']) {
    flash('Dokter berhasil dihapus.', 'success');
} elseif ($resp['http'] === 409) {
    flash('Dokter masih memiliki kunjungan aktif dan tidak dapat dihapus.', 'error');
} else {
    flash($resp['message'] ?: 'Gagal menghapus data dokter.', 'error');
}

redirect('modules/dokter/index.php');
