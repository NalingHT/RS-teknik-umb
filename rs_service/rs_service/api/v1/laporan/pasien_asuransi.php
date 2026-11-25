<?php
// Endpoint GET laporan/pasien_asuransi.php — Rekap & daftar pasien per asuransi (termasuk tanpa asuransi)
require_once __DIR__ . '/../../../core/init.php';

require_api_key();
require_methods(['GET']);
require_role(['admin', 'petugas'], 'laporan/pasien_asuransi');
$db = get_db();

$idAsuransi = get_query('id_asuransi', '');

// Rekap jumlah per asuransi (termasuk tanpa asuransi)
$sqlRekap = "SELECT COALESCE(a.id_asuransi, 0) AS id_asuransi,
                    COALESCE(a.kode_asuransi, 'UMUM') AS kode_asuransi,
                    COALESCE(a.nama_asuransi, 'Tanpa Asuransi (Umum)') AS nama_asuransi,
                    COUNT(p.id_pasien) AS jumlah_pasien
             FROM pasien p
             LEFT JOIN asuransi a ON a.id_asuransi = p.id_asuransi
             GROUP BY COALESCE(a.id_asuransi, 0), COALESCE(a.kode_asuransi, 'UMUM'), COALESCE(a.nama_asuransi, 'Tanpa Asuransi (Umum)')
             ORDER BY jumlah_pasien DESC";
$rekap = $db->query($sqlRekap)->fetch_all(MYSQLI_ASSOC);
foreach ($rekap as &$rk) {
    $rk['id_asuransi']   = (int)$rk['id_asuransi'];
    $rk['jumlah_pasien'] = (int)$rk['jumlah_pasien'];
}

// Daftar pasien (bisa difilter ?id_asuransi=1 atau ?id_asuransi=0 untuk tanpa asuransi)
$where = '';
$types = '';
$vals  = [];
if ($idAsuransi !== '') {
    if ((int)$idAsuransi === 0) {
        $where = "WHERE p.id_asuransi IS NULL";
    } else {
        $where = "WHERE p.id_asuransi = ?";
        $types = 'i';
        $vals  = [(int)$idAsuransi];
    }
}

$sqlList = "SELECT p.id_pasien, p.no_rm, p.nik, p.nama_pasien, p.jenis_kelamin, p.no_hp, p.no_kartu_asuransi,
                   COALESCE(a.kode_asuransi, 'UMUM') AS kode_asuransi,
                   COALESCE(a.nama_asuransi, 'Tanpa Asuransi (Umum)') AS nama_asuransi
            FROM pasien p
            LEFT JOIN asuransi a ON a.id_asuransi = p.id_asuransi
            {$where}
            ORDER BY p.nama_pasien ASC";
$stmt = $db->prepare($sqlList);
if ($types !== '') $stmt->bind_param($types, ...$vals);
$stmt->execute();
$daftar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($daftar as &$df) {
    $df['id_pasien'] = (int)$df['id_pasien'];
}

json_ok([
    'rekap_asuransi' => $rekap,
    'total_pasien'   => count($daftar),
    'daftar_pasien'  => $daftar,
]);