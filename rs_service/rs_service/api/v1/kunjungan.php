<?php
// Endpoint CRUD kunjungan.php
require_once __DIR__ . '/../../core/init.php';

require_api_key();
$method = require_methods(['GET', 'POST', 'PUT', 'DELETE']);
$db     = get_db();
$id     = get_query('id') !== null ? (int)get_query('id') : null;

// --- READ (Semua role login dengan filter kepemilikan) ---
if ($method === 'GET') {
    $user = require_role(['admin', 'petugas', 'dokter', 'pasien'], 'kunjungan');

    if ($id !== null) {
        $sql = "SELECT k.*, p.no_rm, p.nama_pasien, d.nama_dokter, po.kode_poli, po.nama_poli
                FROM kunjungan k
                JOIN pasien p ON p.id_pasien = k.id_pasien
                JOIN dokter d ON d.id_dokter = k.id_dokter
                JOIN poliklinik po ON po.id_poli = k.id_poli
                WHERE k.id_kunjungan = ? LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) json_error(404, 'Data kunjungan tidak ditemukan');

        if ($user['role'] === 'pasien' && (int)($user['id_pasien'] ?? 0) !== (int)$row['id_pasien']) {
            write_audit((int)$user['id_pengguna'], 'AKSES_DITOLAK', 'kunjungan', "Pasien baca kunjungan ID {$id}");
            json_error(403, 'Anda hanya dapat melihat kunjungan milik Anda sendiri');
        }
        if ($user['role'] === 'dokter' && (int)($user['id_dokter'] ?? 0) !== (int)$row['id_dokter']) {
            write_audit((int)$user['id_pengguna'], 'AKSES_DITOLAK', 'kunjungan', "Dokter baca kunjungan ID {$id}");
            json_error(403, 'Anda hanya dapat melihat kunjungan pasien Anda');
        }

        $row['id_kunjungan'] = (int)$row['id_kunjungan'];
        $row['id_pasien']    = (int)$row['id_pasien'];
        $row['id_dokter']    = (int)$row['id_dokter'];
        $row['id_poli']      = (int)$row['id_poli'];
        json_ok($row);
    }

    [$page, $limit, $offset] = get_pagination();
    $q        = get_query('q', '');
    $status   = get_query('status', '');
    $idPoli   = get_query('id_poli', '');
    $idDokter = get_query('id_dokter', '');

    $where = [];
    $types = '';
    $vals  = [];

    if ($user['role'] === 'pasien') {
        $where[] = "k.id_pasien = ?";
        $types .= 'i';
        $vals[] = (int)($user['id_pasien'] ?? 0);
    } elseif ($user['role'] === 'dokter') {
        $where[] = "k.id_dokter = ?";
        $types .= 'i';
        $vals[] = (int)($user['id_dokter'] ?? 0);
    }

    if ($q !== '') {
        $where[] = "(k.no_kunjungan LIKE ? OR p.nama_pasien LIKE ? OR p.no_rm LIKE ? OR d.nama_dokter LIKE ?)";
        $like = "%{$q}%";
        $types .= 'ssss';
        array_push($vals, $like, $like, $like, $like);
    }
    if ($status !== '') {
        $where[] = "k.status = ?";
        $types .= 's';
        $vals[] = $status;
    }
    if ($idPoli !== '') {
        $where[] = "k.id_poli = ?";
        $types .= 'i';
        $vals[] = (int)$idPoli;
    }
    if ($idDokter !== '') {
        $where[] = "k.id_dokter = ?";
        $types .= 'i';
        $vals[] = (int)$idDokter;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    $joinSql  = "JOIN pasien p ON p.id_pasien = k.id_pasien
                 JOIN dokter d ON d.id_dokter = k.id_dokter
                 JOIN poliklinik po ON po.id_poli = k.id_poli";

    $stmtCnt = $db->prepare("SELECT COUNT(*) AS total FROM kunjungan k {$joinSql} {$whereSql}");
    if ($types !== '') $stmtCnt->bind_param($types, ...$vals);
    $stmtCnt->execute();
    $total = (int)$stmtCnt->get_result()->fetch_assoc()['total'];

    $typesList = $types . 'ii';
    $valsList  = array_merge($vals, [$limit, $offset]);
    $sql = "SELECT k.*, p.no_rm, p.nama_pasien, d.nama_dokter, po.kode_poli, po.nama_poli
            FROM kunjungan k {$joinSql} {$whereSql}
            ORDER BY k.id_kunjungan DESC LIMIT ? OFFSET ?";
    $stmt = $db->prepare($sql);
    $stmt->bind_param($typesList, ...$valsList);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as &$r) {
        $r['id_kunjungan'] = (int)$r['id_kunjungan'];
        $r['id_pasien']    = (int)$r['id_pasien'];
        $r['id_dokter']    = (int)$r['id_dokter'];
        $r['id_poli']      = (int)$r['id_poli'];
    }
    json_ok($rows, ['page' => $page, 'limit' => $limit, 'total' => $total]);
}

// --- CREATE (Admin, Petugas, atau Pasien mendaftar diri sendiri) ---
if ($method === 'POST') {
    $user = require_role(['admin', 'petugas', 'pasien'], 'kunjungan');
    $data = filter_whitelist(get_json_body(), [
        'id_pasien', 'id_dokter', 'id_poli', 'tanggal_kunjungan', 'keluhan', 'cara_bayar'
    ]);

    if ($user['role'] === 'pasien') {
        $data['id_pasien'] = (int)($user['id_pasien'] ?? 0);
    }
    $data['cara_bayar'] = $data['cara_bayar'] ?? 'umum';

    $v = new Validator();
    $v->required($data, ['id_pasien', 'id_dokter', 'id_poli', 'tanggal_kunjungan'])
      ->date($data, 'tanggal_kunjungan')
      ->enum($data, 'cara_bayar', ['umum', 'asuransi']);

    // Tanggal pendaftaran tidak boleh lampau
    if (!empty($data['tanggal_kunjungan']) && $data['tanggal_kunjungan'] < date('Y-m-d')) {
        $v->addError('tanggal_kunjungan', 'Tanggal kunjungan tidak boleh tanggal yang sudah lewat');
    }

    // Cek pasien & kepesertaan asuransi
    $idPasien = (int)($data['id_pasien'] ?? 0);
    $stmtP = $db->prepare("SELECT id_pasien, id_asuransi FROM pasien WHERE id_pasien = ? LIMIT 1");
    $stmtP->bind_param('i', $idPasien);
    $stmtP->execute();
    $pasRow = $stmtP->get_result()->fetch_assoc();
    if (!$pasRow) {
        $v->addError('id_pasien', 'Data pasien tidak ditemukan');
    } elseif ($data['cara_bayar'] === 'asuransi' && empty($pasRow['id_asuransi'])) {
        $v->addError('cara_bayar', 'Pasien tidak terdaftar pada asuransi manapun');
    }

    // Cek dokter & kesesuaian poli
    $idDokter = (int)($data['id_dokter'] ?? 0);
    $idPoli   = (int)($data['id_poli'] ?? 0);
    $stmtD = $db->prepare("SELECT id_dokter, id_poli, status FROM dokter WHERE id_dokter = ? LIMIT 1");
    $stmtD->bind_param('i', $idDokter);
    $stmtD->execute();
    $dokRow = $stmtD->get_result()->fetch_assoc();
    if (!$dokRow) {
        $v->addError('id_dokter', 'Data dokter tidak ditemukan');
    } elseif ($dokRow['status'] !== 'aktif') {
        $v->addError('id_dokter', 'Dokter sedang tidak aktif');
    } elseif ((int)$dokRow['id_poli'] !== $idPoli) {
        $v->addError('id_dokter', 'Dokter yang dipilih tidak bertugas pada poliklinik tersebut');
    }

    $v->exists('poliklinik', 'id_poli', $idPoli);
    $v->validate();

    $db->begin_transaction();
    try {
        $tglKode = date('Ymd', strtotime($data['tanggal_kunjungan']));
        $prefix  = "KJ-{$tglKode}-";
        $stmtSeq = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE no_kunjungan LIKE ? FOR UPDATE");
        $likePref = $prefix . '%';
        $stmtSeq->bind_param('s', $likePref);
        $stmtSeq->execute();
        $nextSeq = (int)$stmtSeq->get_result()->fetch_assoc()['cnt'] + 1;
        $noKunjungan = sprintf('%s%04d', $prefix, $nextSeq);

        $keluhan = ($data['keluhan'] ?? '') !== '' ? $data['keluhan'] : null;
        $statusAwal = 'menunggu';

        $stmt = $db->prepare("INSERT INTO kunjungan (no_kunjungan, id_pasien, id_dokter, id_poli, tanggal_kunjungan, keluhan, cara_bayar, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('siiissss', $noKunjungan, $idPasien, $idDokter, $idPoli, $data['tanggal_kunjungan'], $keluhan, $data['cara_bayar'], $statusAwal);
        $stmt->execute();
        $newId = (int)$db->insert_id;

        $db->commit();
        write_audit((int)$user['id_pengguna'], 'CREATE', 'kunjungan', "Daftar kunjungan {$noKunjungan} (ID {$newId})");
        json_ok(['id_kunjungan' => $newId, 'no_kunjungan' => $noKunjungan], [], 201, 'Pendaftaran kunjungan berhasil');
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }
}

// --- UPDATE (Admin, Petugas; Dokter hanya diagnosa & status kunjungan miliknya) ---
if ($method === 'PUT') {
    if (!$id) json_error(400, 'Parameter id wajib disertakan');
    $user = require_role(['admin', 'petugas', 'dokter'], 'kunjungan');

    $cek = $db->prepare("SELECT * FROM kunjungan WHERE id_kunjungan = ? LIMIT 1");
    $cek->bind_param('i', $id);
    $cek->execute();
    $existing = $cek->get_result()->fetch_assoc();
    if (!$existing) json_error(404, 'Data kunjungan tidak ditemukan');

    if ($user['role'] === 'dokter' && (int)($user['id_dokter'] ?? 0) !== (int)$existing['id_dokter']) {
        write_audit((int)$user['id_pengguna'], 'AKSES_DITOLAK', 'kunjungan', "Dokter ubah kunjungan ID {$id} bukan miliknya");
        json_error(403, 'Anda hanya dapat memperbarui kunjungan pasien Anda sendiri');
    }

    $allowed = ($user['role'] === 'dokter')
        ? ['diagnosa', 'status']
        : ['id_dokter', 'id_poli', 'tanggal_kunjungan', 'keluhan', 'diagnosa', 'cara_bayar', 'status'];

    $data = filter_whitelist(get_json_body(), $allowed);
    if (empty($data)) json_error(400, 'Tidak ada data yang diubah');

    $v = new Validator();
    if (isset($data['tanggal_kunjungan'])) $v->date($data, 'tanggal_kunjungan');
    if (isset($data['cara_bayar'])) {
        $v->enum($data, 'cara_bayar', ['umum', 'asuransi']);
        if ($data['cara_bayar'] === 'asuransi') {
            $stmtP = $db->prepare("SELECT id_asuransi FROM pasien WHERE id_pasien = ? LIMIT 1");
            $stmtP->bind_param('i', $existing['id_pasien']);
            $stmtP->execute();
            $pRow = $stmtP->get_result()->fetch_assoc();
            if (empty($pRow['id_asuransi'])) {
                $v->addError('cara_bayar', 'Pasien tidak terdaftar pada asuransi manapun');
            }
        }
    }

    // Cek validitas transisi status: menunggu -> diperiksa -> selesai (atau batal)
    if (isset($data['status'])) {
        $v->enum($data, 'status', ['menunggu', 'diperiksa', 'selesai', 'batal']);
        $curStatus = $existing['status'];
        $newStatus = $data['status'];
        $validTransitions = [
            'menunggu'  => ['menunggu', 'diperiksa', 'batal'],
            'diperiksa' => ['diperiksa', 'selesai', 'batal'],
            'selesai'   => ['selesai'],
            'batal'     => ['batal'],
        ];
        if (!in_array($newStatus, $validTransitions[$curStatus] ?? [], true)) {
            $v->addError('status', "Transisi status dari '{$curStatus}' ke '{$newStatus}' tidak diizinkan");
        }
    }

    // Cek dokter & poli jika diubah
    $finalDokter = isset($data['id_dokter']) ? (int)$data['id_dokter'] : (int)$existing['id_dokter'];
    $finalPoli   = isset($data['id_poli']) ? (int)$data['id_poli'] : (int)$existing['id_poli'];
    if (isset($data['id_dokter']) || isset($data['id_poli'])) {
        $stmtD = $db->prepare("SELECT id_poli FROM dokter WHERE id_dokter = ? LIMIT 1");
        $stmtD->bind_param('i', $finalDokter);
        $stmtD->execute();
        $dRow = $stmtD->get_result()->fetch_assoc();
        if (!$dRow) {
            $v->addError('id_dokter', 'Data dokter tidak ditemukan');
        } elseif ((int)$dRow['id_poli'] !== $finalPoli) {
            $v->addError('id_dokter', 'Dokter yang dipilih tidak bertugas pada poliklinik tersebut');
        }
    }
    $v->validate();

    $sets  = [];
    $types = '';
    $vals  = [];
    foreach ($data as $k => $val) {
        $sets[] = "`{$k}` = ?";
        if ($k === 'id_dokter' || $k === 'id_poli') {
            $types .= 'i';
            $vals[] = (int)$val;
        } else {
            $types .= 's';
            $vals[] = ($val === '') ? null : $val;
        }
    }
    $types .= 'i';
    $vals[] = $id;

    $stmt = $db->prepare("UPDATE kunjungan SET " . implode(', ', $sets) . " WHERE id_kunjungan = ?");
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();

    write_audit((int)$user['id_pengguna'], 'UPDATE', 'kunjungan', "Ubah kunjungan ID {$id}");
    json_ok(['id_kunjungan' => $id], [], 200, 'Data kunjungan berhasil diperbarui');
}

// --- DELETE (Hanya Admin) ---
if ($method === 'DELETE') {
    if (!$id) json_error(400, 'Parameter id wajib disertakan');
    $user = require_role(['admin'], 'kunjungan');

    $cek = $db->prepare("SELECT id_kunjungan FROM kunjungan WHERE id_kunjungan = ? LIMIT 1");
    $cek->bind_param('i', $id);
    $cek->execute();
    if (!$cek->get_result()->fetch_assoc()) json_error(404, 'Data kunjungan tidak ditemukan');

    $del = $db->prepare("DELETE FROM kunjungan WHERE id_kunjungan = ?");
    $del->bind_param('i', $id);
    $del->execute();

    write_audit((int)$user['id_pengguna'], 'DELETE', 'kunjungan', "Hapus kunjungan ID {$id}");
    json_ok([], [], 200, 'Data kunjungan berhasil dihapus');
}