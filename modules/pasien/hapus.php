<?php
/**
 * modules/pasien/hapus.php — Hapus pasien via POST + CSRF (Khusus Admin).
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
if (!can('pasien', 'delete')) {
    akses_ditolak();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/pasien/index.php');
}
csrf_verify();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    flash('ID pasien tidak valid.', 'error');
    redirect('modules/pasien/index.php');
}

$resp = api_delete("pasien.php?id={$id}");

if ($resp['ok']) {
    flash('Data pasien berhasil dihapus.', 'success');
} elseif ($resp['http'] === 409) {
    flash('Pasien masih memiliki kunjungan dan tidak dapat dihapus.', 'error');
} else {
    flash($resp['message'] ?: 'Gagal menghapus data pasien.', 'error');
}

redirect('modules/pasien/index.php');
