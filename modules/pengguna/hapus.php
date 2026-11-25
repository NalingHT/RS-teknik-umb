<?php
/**
 * modules/pengguna/hapus.php — Hapus pengguna via POST + CSRF (Khusus Admin).
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

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    flash('ID pengguna tidak valid.', 'error');
    redirect('modules/pengguna/index.php');
}

$resp = api_delete("pengguna.php?id={$id}");

if ($resp['ok']) {
    flash('Pengguna berhasil dihapus.', 'success');
} elseif ($resp['http'] === 409) {
    flash('Pengguna tidak dapat dihapus karena masih terkait data dokter/pasien.', 'error');
} else {
    flash($resp['message'] ?: 'Gagal menghapus data pengguna.', 'error');
}

redirect('modules/pengguna/index.php');
