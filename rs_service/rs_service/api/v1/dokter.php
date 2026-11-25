<?php
// Endpoint CRUD dokter.php
require_once __DIR__ . '/../../core/init.php';

require_api_key();
$method = require_methods(['GET', 'POST', 'PUT', 'DELETE']);
$db     = get_db();$id     = get_query('id') !== null ? (int)get_query('id') : null;

// --- READ (Publik dengan API Key, atau semua role login) ---
if ($method === 'GET') {$user = optional_token();

    if ($id !== null) {
        if ($user) {$sql = "SELECT d.*, p.kode_poli, p.nama_poli, u.username
                    FROM dokter d
                    JOIN poliklinik p ON p.id_poli = d.id_poli
                    LEFT JOIN pengguna u ON u.id_pengguna = d.id_pengguna
                    WHERE d.id_dokter = ? LIMIT 1";
        } else {
            $sql = "SELECT d.id_dokter, d.id_poli, d.nama_dokter, d.spesialis, d.status, p.kode_poli, p.nama_poli
                    FROM dokter d
                    JOIN poliklinik p ON p.id_poli = d.id_poli
                    WHERE d.id_dokter = ? LIMIT 1";
        }
        $stmt =$db->prepare($sql);$stmt->bind_param('i', $id);$stmt->execute();
        $row =$stmt->get_result()->fetch_assoc();
        if (!$row) json_error(404, 'Data dokter tidak ditemukan');

        $row['id_dokter'] = (int)$row['id_dokter'];
        $row['id_poli']   = (int)$row['id_poli'];
        if (isset($row['id_pengguna']) &&$row['id_pengguna'] !== null) {
            $row['id_pengguna'] = (int)$row['id_pengguna'];
        }
        json_ok($row);
    }

    [$page, $limit,$offset] = get_pagination();
    $q      = get_query('q', '');$idPoli = get_query('id_poli', '');

    $where = [];
    $types = '';$vals  = [];

    if ($q !== '') {$where[] = "(d.nama_dokter LIKE ? OR d.spesialis LIKE ? OR d.nip LIKE ?)";
        $like = "\%{$q}%";
        $types .= 'sss';
        array_push($vals,$like, $like,$like);
    }
    if ($idPoli !== '') {$where[] = "d.id_poli = ?";
        $types .= 'i';
        $vals[] = (int)$idPoli;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmtCnt =$db->prepare("SELECT COUNT(*) AS total FROM dokter d {$whereSql}");
    if ($types !== '') $stmtCnt->bind_param($types, ...$vals);$stmtCnt->execute();
    $total = (int)$stmtCnt->get_result()->fetch_assoc()['total'];

    if ($user) {$cols = "d.*, p.kode_poli, p.nama_poli, u.username";
        $join = "JOIN poliklinik p ON p.id_poli = d.id_poli LEFT JOIN pengguna u ON u.id_pengguna = d.id_pengguna";
    } else {
        $cols = "d.id_dokter, d.id_poli, d.nama_dokter, d.spesialis, d.status, p.kode_poli, p.nama_poli";
        $join = "JOIN poliklinik p ON p.id_poli = d.id_poli";
    }

    $typesList = $types . 'ii';$valsList  = array_merge($vals, [$limit, $offset]);$stmt = $db->prepare("SELECT {$cols} FROM dokter d {$join} {$whereSql} ORDER BY d.id_dokter DESC LIMIT ? OFFSET ?");
    $stmt->bind_param($typesList, ...$valsList);$stmt->execute();
    $rows =$stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as &$r) {
        $r['id_dokter'] = (int)$r['id_dokter'];
        $r['id_poli']   = (int)$r['id_poli'];
        if (isset($r['id_pengguna']) &&$r['id_pengguna'] !== null) {
            $r['id_pengguna'] = (int)$r['id_pengguna'];
        }
    }
    json_ok($rows, ['page' =>$page, 'limit' => $limit, 'total' =>$total]);
}

// --- CREATE (Hanya Admin) ---
if ($method === 'POST') {
    $user = require_role(['admin'], 'dokter');$data = filter_whitelist(get_json_body(), ['id_pengguna', 'id_poli', 'nip', 'nama_dokter', 'spesialis', 'no_str', 'no_hp', 'status']);
    $data['status'] =$data['status'] ?? 'aktif';

    $v = new Validator();
    $v->required($data, ['id_poli', 'nama_dokter'])
      ->max($data, 'nip', 30) ->max($data, 'nama_dokter', 100)
      ->max($data, 'spesialis', 100) ->max($data, 'no_str', 50)
      ->max($data, 'no_hp', 20) ->enum($data, 'status', ['aktif', 'nonaktif'])
      ->exists('poliklinik', 'id_poli', $data['id_poli'] ?? null)
      ->unique('dokter', 'nip', $data['nip'] ?? null);

    $idPengguna = (!empty($data['id_pengguna'])) ? (int)$data['id_pengguna'] : null;
    if ($idPengguna !== null) {
        $stmtU =$db->prepare("SELECT role FROM pengguna WHERE id_pengguna = ? LIMIT 1");
        $stmtU->bind_param('i', $idPengguna);$stmtU->execute();
        $uRow =$stmtU->get_result()->fetch_assoc();
        if (!$uRow) {$v->addError('id_pengguna', 'Akun pengguna tidak ditemukan');
        } elseif ($uRow['role'] !== 'dokter') {$v->addError('id_pengguna', 'Akun pengguna harus memiliki role dokter');
        } else {
            $v->unique('dokter', 'id_pengguna',$idPengguna);
        }
    }
    $v->validate();

    $idPoli    = (int)$data['id_poli'];$nip       = ($data['nip'] ?? '') !== '' ?$data['nip'] : null;
    $spesialis = ($data['spesialis'] ?? '') !== '' ?$data['spesialis'] : null;
    $noStr     = ($data['no_str'] ?? '') !== '' ?$data['no_str'] : null;
    $noHp      = ($data['no_hp'] ?? '') !== '' ?$data['no_hp'] : null;

    $stmt =$db->prepare("INSERT INTO dokter (id_pengguna, id_poli, nip, nama_dokter, spesialis, no_str, no_hp, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('iissssss',$idPengguna, $idPoli,$nip, $data['nama_dokter'],$spesialis, $noStr,$noHp, $data['status']);$stmt->execute();
    $newId = (int)$db->insert_id;

    write_audit((int)$user['id_pengguna'], 'CREATE', 'dokter', "Tambah dokter ID {$newId} ({$data['nama_dokter']})");
    json_ok(['id_dokter' => $newId], [], 201, 'Dokter berhasil ditambahkan');
}

// --- UPDATE (Admin semua dokter; Dokter hanya profil sendiri) ---
if ($method === 'PUT') {
    if (!$id) json_error(400, 'Parameter id wajib disertakan');$user = require_role(['admin', 'dokter'], 'dokter');

    if ($user['role'] === 'dokter' && (int)($user['id_dokter'] ?? 0) !==$id) {
        write_audit((int)$user['id_pengguna'], 'AKSES_DITOLAK', 'dokter', "Dokter mencoba ubah profil dokter ID {$id}");
        json_error(403, 'Anda hanya dapat mengubah profil dokter Anda sendiri');
    }

    $cek =$db->prepare("SELECT id_dokter FROM dokter WHERE id_dokter = ? LIMIT 1");
    $cek->bind_param('i', $id);$cek->execute();
    if (!$cek->get_result()->fetch_assoc()) json_error(404, 'Data dokter tidak ditemukan');

    $allowed = ($user['role'] === 'admin')
        ? ['id_pengguna', 'id_poli', 'nip', 'nama_dokter', 'spesialis', 'no_str', 'no_hp', 'status']
        : ['nama_dokter', 'spesialis', 'no_str', 'no_hp'];

    $data = filter_whitelist(get_json_body(),$allowed);
    if (empty($data)) json_error(400, 'Tidak ada data yang diubah');

    $v = new Validator();
    if (isset($data['id_poli'])) {
        $v->exists('poliklinik', 'id_poli',$data['id_poli']);
    }
    if (isset($data['nip'])) {
        $v->max($data, 'nip', 30)->unique('dokter', 'nip', $data['nip'], 'id_dokter',$id);
    }
    if (isset($data['nama_dokter'])) {$v->required($data, ['nama_dokter'])->max($data, 'nama_dokter', 100);
    }
    if (isset($data['spesialis'])) $v->max($data, 'spesialis', 100);
    if (isset($data['no_str']))    $v->max($data, 'no_str', 50);
    if (isset($data['no_hp']))     $v->max($data, 'no_hp', 20);
    if (isset($data['status']))    $v->enum($data, 'status', ['aktif', 'nonaktif']);

    if (array_key_exists('id_pengguna', $data) && $data['id_pengguna'] !== '' &&$data['id_pengguna'] !== null) {
        $idPengguna = (int)$data['id_pengguna'];
        $stmtU =$db->prepare("SELECT role FROM pengguna WHERE id_pengguna = ? LIMIT 1");
        $stmtU->bind_param('i', $idPengguna);$stmtU->execute();
        $uRow =$stmtU->get_result()->fetch_assoc();
        if (!$uRow) {$v->addError('id_pengguna', 'Akun pengguna tidak ditemukan');
        } elseif ($uRow['role'] !== 'dokter') {$v->addError('id_pengguna', 'Akun pengguna harus memiliki role dokter');
        } else {
            $v->unique('dokter', 'id_pengguna', $idPengguna, 'id_dokter',$id);
        }
    }
    $v->validate();

    $sets  = [];
    $types = '';$vals  = [];
    foreach ($data as$k => $val) {$sets[] = "`{$k}` = ?";
        if ($k === 'id_poli') {$types .= 'i';
            $vals[] = (int)$val;
        } elseif ($k === 'id_pengguna') {$types .= 'i';
            $vals[] = ($val === '' || $val === null) ? null : (int)$val;
        } else {
            $types .= 's';$vals[] = ($val === '') ? null : $val;
        }
    }
    $types .= 'i';
    $vals[] =$id;

    $stmt =$db->prepare("UPDATE dokter SET " . implode(', ', $sets) . " WHERE id_dokter = ?");
    $stmt->bind_param($types, ...$vals);$stmt->execute();

    write_audit((int)$user['id_pengguna'], 'UPDATE', 'dokter', "Ubah dokter ID {$id}");
    json_ok(['id_dokter' => $id], [], 200, 'Data dokter berhasil diperbarui');
}

// --- DELETE (Hanya Admin) ---
if ($method === 'DELETE') {
    if (!$id) json_error(400, 'Parameter id wajib disertakan');$user = require_role(['admin'], 'dokter');

    $cek =$db->prepare("SELECT id_dokter FROM dokter WHERE id_dokter = ? LIMIT 1");
    $cek->bind_param('i', $id);$cek->execute();
    if (!$cek->get_result()->fetch_assoc()) json_error(404, 'Data dokter tidak ditemukan');

    $c1 =$db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE id_dokter = ?");
    $c1->bind_param('i', $id);$c1->execute();
    if ((int)$c1->get_result()->fetch_assoc()['cnt'] > 0) {
        json_error(409, 'Dokter tidak dapat dihapus karena masih memiliki riwayat kunjungan');
    }

    $del =$db->prepare("DELETE FROM dokter WHERE id_dokter = ?");
    $del->bind_param('i', $id);$del->execute();

    write_audit((int)$user['id_pengguna'], 'DELETE', 'dokter', "Hapus dokter ID {$id}");
    json_ok([], [], 200, 'Data dokter berhasil dihapus');
}