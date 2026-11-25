<?php
// Seeder data awal pengguna, dokter, pasien, dan kunjungan contoh
$config = require __DIR__ . '/../config/config.php';
$db = $config['db'];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
$conn->set_charset('utf8mb4');

$conn->begin_transaction();

try {
    // Cek apakah admin sudah ada agar tidak duplikat jika dijalankan 2x
    $cek = $conn->query("SELECT COUNT(*) AS total FROM pengguna WHERE username = 'admin'")->fetch_assoc();
    if ($cek['total'] > 0) {
        die("Data seed sudah pernah dimasukkan sebelumnya.\n");
    }

    $passDefault = password_hash('Password123!', PASSWORD_DEFAULT);

    // 1. Insert Pengguna
    $stmtUser = $conn->prepare("INSERT INTO pengguna (username, password, nama_lengkap, email, role) VALUES (?, ?, ?, ?, ?)");
    
    $users = [
        ['admin', $passDefault, 'Administrator RS', 'admin@rs.local', 'admin'],
        ['petugas1', $passDefault, 'Budi Santoso (Petugas)', 'petugas1@rs.local', 'petugas'],
        ['dr_andi', $passDefault, 'dr. Andi Pratama, Sp.PD', 'andi@rs.local', 'dokter'],
        ['dr_siti', $passDefault, 'drg. Siti Aminah', 'siti@rs.local', 'dokter'],
        ['pasien1', $passDefault, 'Ahmad Fauzi', 'ahmad@mail.com', 'pasien'],
        ['pasien2', $passDefault, 'Rina Marlina', 'rina@mail.com', 'pasien'],
    ];

    $userIds = [];
    foreach ($users as $u) {
        $stmtUser->bind_param('sssss', $u[0], $u[1], $u[2], $u[3], $u[4]);
        $stmtUser->execute();
        $userIds[$u[0]] = $conn->insert_id;
    }

    // 2. Insert Dokter (id_poli: 1=UMUM, 2=GIGI)
    $stmtDok = $conn->prepare("INSERT INTO dokter (id_pengguna, id_poli, nip, nama_dokter, spesialis, no_str, no_hp) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $dokters = [
        [$userIds['dr_andi'], 1, '198501012010011001', 'dr. Andi Pratama, Sp.PD', 'Penyakit Dalam / Umum', 'STR-11002233', '081234567801'],
        [$userIds['dr_siti'], 2, '198805122014022002', 'drg. Siti Aminah', 'Konservasi Gigi', 'STR-44005566', '081234567802'],
    ];

    $dokterIds = [];
    foreach ($dokters as $d) {
        $stmtDok->bind_param('iisssss', $d[0], $d[1], $d[2], $d[3], $d[4], $d[5], $d[6]);
        $stmtDok->execute();
        $dokterIds[] = $conn->insert_id;
    }

    // 3. Insert Pasien (id_asuransi: 1=BPJS, NULL=Umum)
    $stmtPas = $conn->prepare("INSERT INTO pasien (id_pengguna, id_asuransi, no_rm, nik, nama_pasien, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, no_hp, no_kartu_asuransi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Pasien 1: BPJS
    $p1_user  = $userIds['pasien1'];
    $p1_asur  = 1;
    $p1_rm    = 'RM-000001';
    $p1_nik   = '1771010101990001';
    $p1_nama  = 'Ahmad Fauzi';
    $p1_jk    = 'L';
    $p1_tl    = 'Bengkulu';
    $p1_tgl   = '1999-01-01';
    $p1_alm   = 'Jl. WR Supratman, Bengkulu';
    $p1_hp    = '085211112222';
    $p1_kartu = '0001234567890';
    $stmtPas->bind_param('iisssssssss', $p1_user, $p1_asur, $p1_rm, $p1_nik, $p1_nama, $p1_jk, $p1_tl, $p1_tgl, $p1_alm, $p1_hp, $p1_kartu);
    $stmtPas->execute();
    $pasienId1 = $conn->insert_id;

    // Pasien 2: Umum (tanpa asuransi)
    $p2_user  = $userIds['pasien2'];
    $p2_asur  = null;
    $p2_rm    = 'RM-000002';
    $p2_nik   = '1771025205000002';
    $p2_nama  = 'Rina Marlina';
    $p2_jk    = 'P';
    $p2_tl    = 'Curup';
    $p2_tgl   = '2000-05-12';
    $p2_alm   = 'Jl. Kalimantan, Bengkulu';
    $p2_hp    = '085233334444';
    $p2_kartu = null;
    $stmtPas->bind_param('iisssssssss', $p2_user, $p2_asur, $p2_rm, $p2_nik, $p2_nama, $p2_jk, $p2_tl, $p2_tgl, $p2_alm, $p2_hp, $p2_kartu);
    $stmtPas->execute();
    $pasienId2 = $conn->insert_id;

    // 4. Insert Kunjungan Contoh
    $stmtKj = $conn->prepare("INSERT INTO kunjungan (no_kunjungan, id_pasien, id_dokter, id_poli, tanggal_kunjungan, keluhan, diagnosa, cara_bayar, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $hariIni = date('Y-m-d');
    $kunjungans = [
        ['KJ-' . date('Ymd') . '-0001', $pasienId1, $dokterIds[0], 1, $hariIni, 'Demam dan pusing sejak 2 hari lalu', 'Febris / Infeksi virus ringan', 'asuransi', 'selesai'],
        ['KJ-' . date('Ymd') . '-0002', $pasienId2, $dokterIds[1], 2, $hariIni, 'Sakit gigi geraham kanan bawah', 'Pulpitis akut', 'umum', 'diperiksa'],
        ['KJ-' . date('Ymd') . '-0003', $pasienId1, $dokterIds[0], 1, $hariIni, 'Kontrol tekanan darah rutin', null, 'asuransi', 'menunggu'],
    ];

    foreach ($kunjungans as $k) {
        $stmtKj->bind_param('siiisssss', $k[0], $k[1], $k[2], $k[3], $k[4], $k[5], $k[6], $k[7], $k[8]);
        $stmtKj->execute();
    }

    $conn->commit();
    echo "BERHASIL: Data seed pengguna, dokter, pasien, dan kunjungan berhasil dibuat.\n";
    echo "Password default semua akun uji: Password123!\n";
} catch (Exception $e) {
    $conn->rollback();
    echo "GAGAL SEED: " . $e->getMessage() . "\n";
}