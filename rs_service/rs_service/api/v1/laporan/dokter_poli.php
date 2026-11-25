<?php
// Endpoint GET laporan/dokter_poli.php — Rekap dokter per poliklinik & jumlah kunjungan per dokter
require_once __DIR__ . '/../../../core/init.php';

require_api_key();
require_methods(['GET']);
require_role(['admin'], 'laporan/dokter_poli');
$db = get_db();

$idPoli = get_query('id_poli', '');

$wherePoli = '';
$types     = '';
$vals      = [];
if ($idPoli !== '') {
    $wherePoli = "WHERE po.id_poli = ?";
    $types     = 'i';
    $vals      = [(int)$idPoli];
}

// 1. Jumlah dokter per poliklinik
$sqlPoli = "SELECT po.id_poli, po.kode_poli, po.nama_poli, po.lokasi, COUNT(d.id_dokter) AS jumlah_dokter
            FROM poliklinik po
            LEFT JOIN dokter d ON d.id_poli = po.id_poli
            {$wherePoli}
            GROUP BY po.id_poli, po.kode_poli, po.nama_poli, po.lokasi
            ORDER BY po.id_poli ASC";
$stmtPoli = $db->prepare($sqlPoli);
if ($types !== '') $stmtPoli->bind_param($types, ...$vals);
$stmtPoli->execute();
$rekapPoli = $stmtPoli->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($rekapPoli as &$rp) {
    $rp['id_poli']       = (int)$rp['id_poli'];
    $rp['jumlah_dokter'] = (int)$rp['jumlah_dokter'];
}

// 2. Jumlah kunjungan per dokter
$whereDok = ($idPoli !== '') ? "WHERE d.id_poli = ?" : "";
$sqlDok = "SELECT d.id_dokter, d.nip, d.nama_dokter, d.spesialis, d.status,
                  po.id_poli, po.nama_poli,
                  COUNT(k.id_kunjungan) AS jumlah_kunjungan
           FROM dokter d
           JOIN poliklinik po ON po.id_poli = d.id_poli
           LEFT JOIN kunjungan k ON k.id_dokter = d.id_dokter
           {$whereDok}
           GROUP BY d.id_dokter, d.nip, d.nama_dokter, d.spesialis, d.status, po.id_poli, po.nama_poli
           ORDER BY jumlah_kunjungan DESC, d.nama_dokter ASC";
$stmtDok = $db->prepare($sqlDok);
if ($types !== '') $stmtDok->bind_param($types, ...$vals);
$stmtDok->execute();
$rekapDokter = $stmtDok->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($rekapDokter as &$rd) {
    $rd['id_dokter']        = (int)$rd['id_dokter'];
    $rd['id_poli']          = (int)$rd['id_poli'];
    $rd['jumlah_kunjungan'] = (int)$rd['jumlah_kunjungan'];
}

json_ok([
    'jumlah_dokter_per_poli'      => $rekapPoli,
    'jumlah_kunjungan_per_dokter' => $rekapDokter,
]);