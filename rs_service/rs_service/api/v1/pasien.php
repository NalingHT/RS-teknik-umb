<?php
// Endpoint CRUD pasien.php
require_once __DIR__ . '/../../core/init.php';

require_api_key();
$method = require_methods(['GET', 'POST', 'PUT', 'DELETE']);
$db     = get_db();
$id     = get_query('id') !== null ? (int)get_query('id') : null;

// --- READ ---
if ($method === 'GET') {
    $user = require_role(['admin', 'petugas', 'dokter', 'pasien'], 'pasien');

    if ($id !== null) {
        // Cek kepemilikan data untuk role pasien & dokter
        if ($user['role'] === 'pasien' && (int)($user['id_pasien'] ?? 0) !== $id) {
            write_audit((int)$user['id_pengguna'], 'AKSES_DITOLAK', 'pasien', "Pasien mencoba baca pasien ID {$id}");
            json_error(403, 'Anda hanya dapat mengakses data pasien milik Anda sendiri');
        }
        if ($user['role'] === 'dokter') {
            $idDok = (int)($user['id_dokter'] ?? 0);
            $cekKj = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE id_pasien = ? AND id_dokter = ?");
            $cekKj->bind_param('ii', $id, $idDok);
            $cekKj->execute();
            if ((int)$cekKj->get_result()->fetch_assoc()['cnt'] === 0) {
                write_audit((int)$user['id_pengguna'], 'AKSES_DITOLAK', 'pasien', "Dokter baca pasien ID {$id} di luar tanggungan");
                json_error(403, 'Anda hanya dapat melihat pasien yang pernah Anda tangani');
            }
        }

        $sql = "SELECT p.*, a.kode_asuransi, a.nama_asuransi, u.username
                FROM pasien p
                LEFT JOIN asuransi a ON a.id_asuransi = p.id_asuransi
                LEFT JOIN pengguna u ON u.id_pengguna = p.id_pengguna
                WHERE p.id_pasien = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) json_error(404, 'Data pasien tidak ditemukan');

        $row['id_pasien']   = (int)$row['id_pasien'];
        $row['id_pengguna'] = $row['id_pengguna'] !== null ? (int)$row['id_pengguna'] : null;
        $row['id_asuransi'] = $row['id_asuransi'] !== null ? (int)$row['id_asuransi'] : null;
        json_ok($row);
    }

    [$page, $limit, $offset] = get_pagination();
    $q = get_query('q', '');

    $where = [];
    $types = '';
    $vals  = [];

    // Filter wajib berdasarkan role
    if ($user['role'] === 'pasien') {
        $where[] = "p.id_pasien = ?";
        $types .= 'i';
        $vals[] = (int)($user['id_pasien'] ?? 0);
    } elseif ($user['role'] === 'dokter') {
        $where[] = "p.id_pasien IN (SELECT DISTINCT id_pasien FROM kunjungan WHERE id_dokter = ?)";
        $types .= 'i';
        $vals[] = (int)($user['id_dokter'] ?? 0);
    }

    if ($q !== '') {
        $where[] = "(p.no_rm LIKE ? OR p.nik LIKE ? OR p.nama_pasien LIKE ? OR p.no_hp LIKE ?)";
        $like = "%{$q}%";
        $types .= 'ssss';
        array_push($vals, $like, $like, $like, $like);
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmtCnt = $db->prepare("SELECT COUNT(*) AS total FROM pasien p {$whereSql}");
    if ($types !== '') $stmtCnt->bind_param($types, ...$vals);
    $stmtCnt->execute();
    $total = (int)$stmtCnt->get_result()->fetch_assoc()['total'];

    $typesList = $types . 'ii';
    $valsList  = array_merge($vals, [$limit, $offset]);
    $sql = "SELECT p.*, a.kode_asuransi, a.nama_asuransi
            FROM pasien p
            LEFT JOIN asuransi a ON a.id_asuransi = p.id_asuransi
            {$whereSql}
            ORDER BY p.id_pasien DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($typesList, ...$valsList);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as &$r) {
        $r['id_pasien']   = (int)$r['id_pasien'];
        $r['id_pengguna'] = $r['id_pengguna'] !== null ? (int)$r['id_pengguna'] : null;
        $r['id_asuransi'] = $r['id_asuransi'] !== null ? (int)$r['id_asuransi'] : null;
    }
    json_ok($rows, ['page' => $page, 'limit' => $limit, 'total' => $total]);
}

// --- CREATE (Admin & Petugas) ---
if ($method === 'POST') {
    $user = require_role(['admin', 'petugas'], 'pasien');
    $data = filter_whitelist(get_json_body(), [
        'id_pengguna', 'id_asuransi', 'nik', 'nama_pasien',
        'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'no_hp', 'no_kartu_asuransi'
    ]);

    $v = new Validator();
    $v->required($data, ['nama_pasien', 'jenis_kelamin', 'tanggal_lahir'])
      ->max($data, 'nama_pasien', 100)
      ->enum($data, 'jenis_kelamin', ['L', 'P'])
      ->date($data, 'tanggal_lahir')
      ->digits($data, 'nik', 16)
      ->max($data, 'tempat_lahir', 50)
      ->max($data, 'no_hp', 20)
      ->max($data, 'no_kartu_asuransi', 30)
      ->unique('pasien', 'nik', $data['nik'] ?? null);

    $idPengguna = !empty($data['id_pengguna']) ? (int)$data['id_pengguna'] : null;
    if ($idPengguna !== null) {
        $stmtU = $db->prepare("SELECT role FROM pengguna WHERE id_pengguna = ? LIMIT 1");
        $stmtU->bind_param('i', $idPengguna);
        $stmtU->execute();
        $uRow = $stmtU->get_result()->fetch_assoc();
        if (!$uRow) {
            $v->addError('id_pengguna', 'Akun pengguna tidak ditemukan');
        } elseif ($uRow['role'] !== 'pasien') {
            $v->addError('id_pengguna', 'Akun pengguna harus memiliki role pasien');
        } else {
            $v->unique('pasien', 'id_pengguna', $idPengguna);
        }
    }

    $idAsuransi = !empty($data['id_asuransi']) ? (int)$data['id_asuransi'] : null;
    if ($idAsuransi !== null) {
        $v->exists('asuransi', 'id_asuransi', $idAsuransi);
        if (empty($data['no_kartu_asuransi'])) {
            $v->addError('no_kartu_asuransi', 'Nomor kartu asuransi wajib diisi bila id_asuransi terisi');
        }
    }
    $v->validate();

    $db->begin_transaction();
    try {
        $resMax = $db->query("SELECT COALESCE(MAX(id_pasien), 0) + 1 AS next_id FROM pasien FOR UPDATE")->fetch_assoc();
        $noRm   = sprintf('RM-%06d', (int)$resMax['next_id']);

        $nik       = ($data['nik'] ?? '') !== '' ? $data['nik'] : null;
        $tempatLhr = ($data['tempat_lahir'] ?? '') !== '' ? $data['tempat_lahir'] : null;
        $alamat    = ($data['alamat'] ?? '') !== '' ? $data['alamat'] : null;
        $noHp      = ($data['no_hp'] ?? '') !== '' ? $data['no_hp'] : null;
        $noKartu   = ($idAsuransi !== null && ($data['no_kartu_asuransi'] ?? '') !== '') ? $data['no_kartu_asuransi'] : null;

        $stmt = $db->prepare("INSERT INTO pasien (id_pengguna, id_asuransi, no_rm, nik, nama_pasien, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, no_hp, no_kartu_asuransi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iisssssssss', $idPengguna, $idAsuransi, $noRm, $nik, $data['nama_pasien'], $data['jenis_kelamin'], $tempatLhr, $data['tanggal_lahir'], $alamat, $noHp, $noKartu);
        $stmt->execute();
        $newId = (int)$db->insert_id;

        $db->commit();
        write_audit((int)$user['id_pengguna'], 'CREATE', 'pasien', "Tambah pasien {$noRm} (ID {$newId})");
        json_ok(['id_pasien' => $newId, 'no_rm' => $noRm], [], 201, 'Data pasien berhasil ditambahkan');
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

// --- UPDATE (Admin, Petugas; Pasien hanya data sendiri) ---
if ($method === 'PUT') {
    if (!$id) json_error(400, 'Parameter id wajib disertakan');
    $user = require_role(['admin', 'petugas', 'pasien'], 'pasien');

    if ($user['role'] === 'pasien' && (int)($user['id_pasien'] ?? 0) !== $id) {
        write_audit((int)$user['id_pengguna'], 'AKSES_DITOLAK', 'pasien', "Pasien mencoba ubah pasien ID {$id}");
        json_error(403, 'Anda hanya dapat mengubah data pasien milik Anda sendiri');
    }

    $cek = $db->prepare("SELECT * FROM pasien WHERE id_pasien = ? LIMIT 1");
    $cek->bind_param('i', $id);
    $cek->execute();
    $existing = $cek->get_result()->fetch_assoc();
    if (!$existing) json_error(404, 'Data pasien tidak ditemukan');

    $allowed = ($user['role'] === 'pasien')
        ? ['id_asuransi', 'nik', 'nama_pasien', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'no_hp', 'no_kartu_asuransi']
        : ['id_pengguna', 'id_asuransi', 'nik', 'nama_pasien', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'alamat', 'no_hp', 'no_kartu_asuransi'];

    $data = filter_whitelist(get_json_body(), $allowed);
    if (empty($data)) json_error(400, 'Tidak ada data yang diubah');

    $v = new Validator();
    if (isset($data['nama_pasien']))   $v->required($data, ['nama_pasien'])->max($data, 'nama_pasien', 100);
    if (isset($data['jenis_kelamin'])) $v->enum($data, 'jenis_kelamin', ['L', 'P']);
    if (isset($data['tanggal_lahir'])) $v->date($data, 'tanggal_lahir');
    if (isset($data['nik']))           $v->digits($data, 'nik', 16)->unique('pasien', 'nik', $data['nik'], 'id_pasien', $id);
    if (isset($data['tempat_lahir']))  $v->max($data, 'tempat_lahir', 50);
    if (isset($data['no_hp']))         $v->max($data, 'no_hp', 20);
    if (isset($data['no_kartu_asuransi'])) $v->max($data, 'no_kartu_asuransi', 30);

    $finalAsuransi = array_key_exists('id_asuransi', $data)
        ? (($data['id_asuransi'] === '' || $data['id_asuransi'] === null) ? null : (int)$data['id_asuransi'])
        : ($existing['id_asuransi'] !== null ? (int)$existing['id_asuransi'] : null);

    $finalKartu = array_key_exists('no_kartu_asuransi', $data)
        ? $data['no_kartu_asuransi']
        : $existing['no_kartu_asuransi'];

    if ($finalAsuransi !== null) {
        $v->exists('asuransi', 'id_asuransi', $finalAsuransi);
        if (empty($finalKartu)) {
            $v->addError('no_kartu_asuransi', 'Nomor kartu asuransi wajib diisi bila id_asuransi terisi');
        }
    }
    $v->validate();

    $sets  = [];
    $types = '';
    $vals  = [];
    foreach ($data as $k => $val) {
        $sets[] = "`{$k}` = ?";
        if ($k === 'id_pengguna' || $k === 'id_asuransi') {
            $types .= 'i';
            $vals[] = ($val === '' || $val === null) ? null : (int)$val;
        } else {
            $types .= 's';
            $vals[] = ($val === '') ? null : $val;
        }
    }
    if ($finalAsuransi === null && !array_key_exists('no_kartu_asuransi', $data)) {
        $sets[]  = "`no_kartu_asuransi` = ?";
        $types  .= 's';
        $vals[]  = null;
    }

    $types .= 'i';
    $vals[] = $id;
    $stmt = $db->prepare("UPDATE pasien SET " . implode(', ', $sets) . " WHERE id_pasien = ?");
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();

    write_audit((int)$user['id_pengguna'], 'UPDATE', 'pasien', "Ubah pasien ID {$id}");
    json_ok(['id_pasien' => $id], [], 200, 'Data pasien berhasil diperbarui');
}

// --- DELETE (Hanya Admin) ---
if ($method === 'DELETE') {
    if (!$id) json_error(400, 'Parameter id wajib disertakan');
    $user = require_role(['admin'], 'pasien');

    $cek = $db->prepare("SELECT id_pasien FROM pasien WHERE id_pasien = ? LIMIT 1");
    $cek->bind_param('i', $id);
    $cek->execute();
    if (!$cek->get_result()->fetch_assoc()) json_error(404, 'Data pasien tidak ditemukan');

    $c1 = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE id_pasien = ?");
    $c1->bind_param('i', $id);
    $c1->execute();
    if ((int)$c1->get_result()->fetch_assoc()['cnt'] > 0) {
        json_error(409, 'Pasien tidak dapat dihapus karena masih memiliki riwayat kunjungan');
    }

    $del = $db->prepare("DELETE FROM pasien WHERE id_pasien = ?");
    $del->bind_param('i', $id);
    $del->execute();

    write_audit((int)$user['id_pengguna'], 'DELETE', 'pasien', "Hapus pasien ID {$id}");
    json_ok([], [], 200, 'Data pasien berhasil dihapus');
}