<?php
// Endpoint GET laporan/kunjungan.php — Laporan kunjungan per periode & subtotal per poliklinik
require_once __DIR__ . '/../../../core/init.php';

require_api_key();
require_methods(['GET']);
$user = require_role(['admin', 'petugas', 'dokter'], 'laporan/kunjungan');
$db   = get_db();

$dari     = get_query('dari', date('Y-m-01'));
$sampai   = get_query('sampai', date('Y-m-t'));
$idPoli   = get_query('id_poli', '');
$idDokter = get_query('id_dokter', '');
$status   = get_query('status', '');

$v = new Validator();
$v->date(['dari' => $dari], 'dari')
  ->date(['sampai' => $sampai], 'sampai');
if ($dari > $sampai) {
    $v->addError('dari', 'Tanggal awal (dari) tidak boleh lebih besar dari tanggal akhir (sampai)');
}
$v->validate();

$where = ["k.tanggal_kunjungan BETWEEN ? AND ?"];
$types = 'ss';
$vals  = [$dari, $sampai];

if ($user['role'] === 'dokter') {
    $where[] = "k.id_dokter = ?";
    $types .= 'i';
    $vals[] = (int)($user['id_dokter'] ?? 0);
} elseif ($idDokter !== '') {
    $where[] = "k.id_dokter = ?";
    $types .= 'i';
    $vals[] = (int)$idDokter;
}

if ($idPoli !== '') {
    $where[] = "k.id_poli = ?";
    $types .= 'i';
    $vals[] = (int)$idPoli;
}
if ($status !== '') {
    $where[] = "k.status = ?";
    $types .= 's';
    $vals[] = $status;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// 1. Daftar kunjungan
$sql = "SELECT k.id_kunjungan, k.no_kunjungan, k.tanggal_kunjungan, k.keluhan, k.diagnosa, k.cara_bayar, k.status,
               p.no_rm, p.nama_pasien, d.nama_dokter, po.id_poli, po.kode_poli, po.nama_poli
        FROM kunjungan k
        JOIN pasien p ON p.id_pasien = k.id_pasien
        JOIN dokter d ON d.id_dokter = k.id_dokter
        JOIN poliklinik po ON po.id_poli = k.id_poli
        {$whereSql}
        ORDER BY k.tanggal_kunjungan ASC, k.id_kunjungan ASC";
$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$vals);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($items as &$it) {
    $it['id_kunjungan'] = (int)$it['id_kunjungan'];
    $it['id_poli']      = (int)$it['id_poli'];
}

// 2. Subtotal per poliklinik
$sqlSub = "SELECT po.id_poli, po.kode_poli, po.nama_poli, COUNT(k.id_kunjungan) AS jumlah_kunjungan
           FROM kunjungan k
           JOIN poliklinik po ON po.id_poli = k.id_poli
           {$whereSql}
           GROUP BY po.id_poli, po.kode_poli, po.nama_poli
           ORDER BY jumlah_kunjungan DESC";
$stmtSub = $db->prepare($sqlSub);
$stmtSub->bind_param($types, ...$vals);
$stmtSub->execute();
$subtotalPoli = $stmtSub->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($subtotalPoli as &$sp) {
    $sp['id_poli']          = (int)$sp['id_poli'];
    $sp['jumlah_kunjungan'] = (int)$sp['jumlah_kunjungan'];
}

json_ok([
    'periode'           => ['dari' => $dari, 'sampai' => $sampai],
    'total_kunjungan'   => count($items),
    'subtotal_per_poli' => $subtotalPoli,
    'daftar_kunjungan'  => $items,
]);