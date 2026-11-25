<?php
// Endpoint GET laporan/ringkasan.php — Statistik ringkasan disesuaikan dengan role pengguna
require_once __DIR__ . '/../../../core/init.php';

require_api_key();
require_methods(['GET']);
$user = require_role(['admin', 'petugas', 'dokter', 'pasien'], 'laporan/ringkasan');
$db   = get_db();

$today = date('Y-m-d');
$month = date('Y-m');

if ($user['role'] === 'admin' || $user['role'] === 'petugas') {
    $totPasien = (int)$db->query("SELECT COUNT(*) AS cnt FROM pasien")->fetch_assoc()['cnt'];
    $totDokter = (int)$db->query("SELECT COUNT(*) AS cnt FROM dokter WHERE status = 'aktif'")->fetch_assoc()['cnt'];
    $totPoli   = (int)$db->query("SELECT COUNT(*) AS cnt FROM poliklinik WHERE status = 'aktif'")->fetch_assoc()['cnt'];

    $stmtHari = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE tanggal_kunjungan = ?");
    $stmtHari->bind_param('s', $today);
    $stmtHari->execute();
    $kjHariIni = (int)$stmtHari->get_result()->fetch_assoc()['cnt'];

    $likeMonth = $month . '%';
    $stmtBln = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE tanggal_kunjungan LIKE ?");
    $stmtBln->bind_param('s', $likeMonth);
    $stmtBln->execute();
    $kjBulanIni = (int)$stmtBln->get_result()->fetch_assoc()['cnt'];

    $pasAsuransi = (int)$db->query("SELECT COUNT(*) AS cnt FROM pasien WHERE id_asuransi IS NOT NULL")->fetch_assoc()['cnt'];
    $pasUmum     = (int)$db->query("SELECT COUNT(*) AS cnt FROM pasien WHERE id_asuransi IS NULL")->fetch_assoc()['cnt'];

    json_ok([
        'total_pasien'          => $totPasien,
        'total_dokter_aktif'    => $totDokter,
        'total_poliklinik_aktif'=> $totPoli,
        'kunjungan_hari_ini'    => $kjHariIni,
        'kunjungan_bulan_ini'   => $kjBulanIni,
        'komposisi_pasien'      => [
            'asuransi' => $pasAsuransi,
            'umum'     => $pasUmum,
        ],
    ]);
}

if ($user['role'] === 'dokter') {
    $idDokter = (int)($user['id_dokter'] ?? 0);

    $stmtPas = $db->prepare("SELECT COUNT(DISTINCT id_pasien) AS cnt FROM kunjungan WHERE id_dokter = ?");
    $stmtPas->bind_param('i', $idDokter);
    $stmtPas->execute();
    $totPasien = (int)$stmtPas->get_result()->fetch_assoc()['cnt'];

    $stmtHari = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE id_dokter = ? AND tanggal_kunjungan = ?");
    $stmtHari->bind_param('is', $idDokter, $today);
    $stmtHari->execute();
    $kjHariIni = (int)$stmtHari->get_result()->fetch_assoc()['cnt'];

    $likeMonth = $month . '%';
    $stmtBln = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE id_dokter = ? AND tanggal_kunjungan LIKE ?");
    $stmtBln->bind_param('is', $idDokter, $likeMonth);
    $stmtBln->execute();
    $kjBulanIni = (int)$stmtBln->get_result()->fetch_assoc()['cnt'];

    $stmtAsr = $db->prepare("SELECT COUNT(DISTINCT k.id_pasien) AS cnt FROM kunjungan k JOIN pasien p ON p.id_pasien = k.id_pasien WHERE k.id_dokter = ? AND p.id_asuransi IS NOT NULL");
    $stmtAsr->bind_param('i', $idDokter);
    $stmtAsr->execute();
    $pasAsuransi = (int)$stmtAsr->get_result()->fetch_assoc()['cnt'];

    $stmtUmu = $db->prepare("SELECT COUNT(DISTINCT k.id_pasien) AS cnt FROM kunjungan k JOIN pasien p ON p.id_pasien = k.id_pasien WHERE k.id_dokter = ? AND p.id_asuransi IS NULL");
    $stmtUmu->bind_param('i', $idDokter);
    $stmtUmu->execute();
    $pasUmum = (int)$stmtUmu->get_result()->fetch_assoc()['cnt'];

    json_ok([
        'total_pasien_ditangani' => $totPasien,
        'kunjungan_hari_ini'     => $kjHariIni,
        'kunjungan_bulan_ini'    => $kjBulanIni,
        'komposisi_pasien'       => [
            'asuransi' => $pasAsuransi,
            'umum'     => $pasUmum,
        ],
    ]);
}

// Role pasien: ringkasan kunjungan miliknya
$idPasien = (int)($user['id_pasien'] ?? 0);

$stmtTot = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE id_pasien = ?");
$stmtTot->bind_param('i', $idPasien);
$stmtTot->execute();
$totKj = (int)$stmtTot->get_result()->fetch_assoc()['cnt'];

$stmtHari = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE id_pasien = ? AND tanggal_kunjungan = ?");
$stmtHari->bind_param('is', $idPasien, $today);
$stmtHari->execute();
$kjHariIni = (int)$stmtHari->get_result()->fetch_assoc()['cnt'];

$likeMonth = $month . '%';
$stmtBln = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE id_pasien = ? AND tanggal_kunjungan LIKE ?");
$stmtBln->bind_param('is', $idPasien, $likeMonth);
$stmtBln->execute();
$kjBulanIni = (int)$stmtBln->get_result()->fetch_assoc()['cnt'];

$stmtAsr = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE id_pasien = ? AND cara_bayar = 'asuransi'");
$stmtAsr->bind_param('i', $idPasien);
$stmtAsr->execute();
$kjAsuransi = (int)$stmtAsr->get_result()->fetch_assoc()['cnt'];

$stmtUmu = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE id_pasien = ? AND cara_bayar = 'umum'");
$stmtUmu->bind_param('i', $idPasien);
$stmtUmu->execute();
$kjUmum = (int)$stmtUmu->get_result()->fetch_assoc()['cnt'];

json_ok([
    'total_kunjungan'     => $totKj,
    'kunjungan_hari_ini'  => $kjHariIni,
    'kunjungan_bulan_ini' => $kjBulanIni,
    'cara_bayar'          => [
        'asuransi' => $kjAsuransi,
        'umum'     => $kjUmum,
    ],
]);