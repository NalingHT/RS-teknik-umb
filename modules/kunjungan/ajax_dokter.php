<?php
/**
 * modules/kunjungan/ajax_dokter.php — Endpoint JSON daftar dokter aktif berdasarkan poliklinik.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/api_client.php';

header('Content-Type: application/json');

if (empty($_SESSION['token'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'Unauthorized']);
    exit;
}

$id_poli = (int)($_GET['id_poli'] ?? 0);
if ($id_poli <= 0) {
    echo json_encode(['ok' => false, 'data' => []]);
    exit;
}

$resp = api_get('dokter.php', ['id_poli' => $id_poli, 'status' => 'aktif', 'limit' => 100]);

echo json_encode([
    'ok'   => $resp['ok'],
    'data' => $resp['data'] ?? [],
]);
exit;
