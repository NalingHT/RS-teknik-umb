<?php
/**
 * modules/asuransi/hapus.php — Hapus asuransi via POST + CSRF (Khusus Admin).
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
if (!can('asuransi', 'delete')) akses_ditolak();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('modules/asuransi/index.php');
}
csrf_verify();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    flash('ID asuransi tidak valid.', 'error');
    redirect('modules/asuransi/index.php');
}

$resp = api_delete("asuransi.php?id={$id}");

if ($resp['ok']) {
    flash('Data asuransi berhasil dihapus.', 'success');
} elseif ($resp['http'] === 409) {
    flash('Asuransi tidak dapat dihapus karena masih digunakan oleh pasien.', 'error');
} else {
    flash($resp['message'] ?: 'Gagal menghapus data asuransi.', 'error');
}

redirect('modules/asuransi/index.php');
