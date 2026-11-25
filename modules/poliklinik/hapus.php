<?php
/**
 * modules/poliklinik/hapus.php — Hapus poliklinik via DELETE ke Server B.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
if (!can('poliklinik','delete')) akses_ditolak();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('modules/poliklinik/index.php');
csrf_verify();

$id = bersih($_POST['id'] ?? '');
if (!$id) { flash('ID tidak valid.', 'error'); redirect('modules/poliklinik/index.php'); }

$res = api_delete('poliklinik.php?id=' . urlencode($id));

if ($res['ok']) {
    flash('Poliklinik berhasil dihapus.', 'success');
} else {
    $msg = $res['message'] ?: 'Gagal menghapus.';
    if ($res['http'] === 409) $msg = 'Poliklinik masih digunakan oleh dokter dan tidak dapat dihapus.';
    flash($msg, 'error');
}

redirect('modules/poliklinik/index.php');
