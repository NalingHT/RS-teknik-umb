<?php
// Endpoint POST auth/register.php — Registrasi mandiri pasien (pengguna + pasien dalam 1 transaksi)
require_once __DIR__ . '/../../../core/init.php';

require_api_key();
require_methods(['POST']);

$body = get_json_body();
$allowed = [
    'username', 'password', 'email',
    'id_asuransi', 'nik', 'nama_pasien', 'jenis_kelamin',
    'tempat_lahir', 'tanggal_lahir', 'alamat', 'no_hp', 'no_kartu_asuransi'
];
$data = filter_whitelist($body, $allowed);

$v = new Validator();
$v->required($data, ['username', 'password', 'nama_pasien', 'jenis_kelamin', 'tanggal_lahir'])
  ->max($data, 'username', 50)
  ->min($data, 'password', 8)
  ->max($data, 'nama_pasien', 100)
  ->enum($data, 'jenis_kelamin', ['L', 'P'])
  ->date($data, 'tanggal_lahir')
  ->email($data, 'email')
  ->digits($data, 'nik', 16)
  ->max($data, 'tempat_lahir', 50)
  ->max($data, 'no_hp', 20)
  ->max($data, 'no_kartu_asuransi', 30)
  ->unique('pengguna', 'username', $data['username'] ?? null)
  ->unique('pengguna', 'email', $data['email'] ?? null)
  ->unique('pasien', 'nik', $data['nik'] ?? null);

$idAsuransi = !empty($data['id_asuransi']) ? (int)$data['id_asuransi'] : null;
if ($idAsuransi !== null) {
    $v->exists('asuransi', 'id_asuransi', $idAsuransi);
    if (empty($data['no_kartu_asuransi'])) {
        $v->addError('no_kartu_asuransi', 'Nomor kartu asuransi wajib diisi bila memilih asuransi');
    }
}
$v->validate();

$db = get_db();
$db->begin_transaction();

try {
    // 1. Buat akun pengguna
    $passHash = password_hash($data['password'], PASSWORD_DEFAULT);
    $role     = 'pasien';
    $email    = !empty($data['email']) ? $data['email'] : null;

    $stmtUser = $db->prepare("INSERT INTO pengguna (username, password, nama_lengkap, email, role) VALUES (?, ?, ?, ?, ?)");
    $stmtUser->bind_param('sssss', $data['username'], $passHash, $data['nama_pasien'], $email, $role);
    $stmtUser->execute();
    $idPengguna = (int)$db->insert_id;

    // 2. Generate nomor RM otomatis (RM-000001)
    $resMax = $db->query("SELECT COALESCE(MAX(id_pasien), 0) + 1 AS next_id FROM pasien FOR UPDATE")->fetch_assoc();
    $noRm = sprintf('RM-%06d', (int)$resMax['next_id']);

    // 3. Buat profil pasien
    $nik        = !empty($data['nik']) ? $data['nik'] : null;
    $tempatLhr  = !empty($data['tempat_lahir']) ? $data['tempat_lahir'] : null;
    $alamat     = !empty($data['alamat']) ? $data['alamat'] : null;
    $noHp       = !empty($data['no_hp']) ? $data['no_hp'] : null;
    $noKartu    = ($idAsuransi !== null && !empty($data['no_kartu_asuransi'])) ? $data['no_kartu_asuransi'] : null;

    $stmtPas = $db->prepare("INSERT INTO pasien (id_pengguna, id_asuransi, no_rm, nik, nama_pasien, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, no_hp, no_kartu_asuransi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtPas->bind_param('iisssssssss', $idPengguna, $idAsuransi, $noRm, $nik, $data['nama_pasien'], $data['jenis_kelamin'], $tempatLhr, $data['tanggal_lahir'], $alamat, $noHp, $noKartu);
    $stmtPas->execute();
    $idPasien = (int)$db->insert_id;

    $db->commit();
    write_audit($idPengguna, 'CREATE', 'auth/register', "Registrasi pasien baru {$noRm}");

    json_ok([
        'id_pengguna' => $idPengguna,
        'id_pasien'   => $idPasien,
        'no_rm'       => $noRm,
        'username'    => $data['username'],
        'nama_pasien' => $data['nama_pasien'],
    ], [], 201, 'Registrasi pasien berhasil');
} catch (Throwable $e) {
    $db->rollback();
    throw $e;
}