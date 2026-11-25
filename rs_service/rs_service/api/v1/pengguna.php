<?php
// Endpoint CRUD pengguna.php (khusus admin)
require_once __DIR__ . '/../../core/init.php';

require_api_key();
$method = require_methods(['GET', 'POST', 'PUT', 'DELETE']);
$user   = require_role(['admin'], 'pengguna');
$db     = get_db();
$id     = get_query('id') !== null ? (int)get_query('id') : null;

$safeCols = "id_pengguna, username, nama_lengkap, email, role, status, gagal_login, blokir_sampai, last_login, created_at, updated_at";

if ($method === 'GET') {
    if ($id !== null) {
        $stmt = $db->prepare("SELECT {$safeCols} FROM pengguna WHERE id_pengguna = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) json_error(404, 'Data pengguna tidak ditemukan');
        $row['id_pengguna'] = (int)$row['id_pengguna'];
        $row['gagal_login'] = (int)$row['gagal_login'];
        json_ok($row);
    }

    [$page, $limit, $offset] = get_pagination();
    $q    = get_query('q', '');
    $role = get_query('role', '');

    $where = [];
    $types = '';
    $vals  = [];

    if ($q !== '') {
        $where[] = "(username LIKE ? OR nama_lengkap LIKE ? OR email LIKE ?)";
        $like = "%{$q}%";
        $types .= 'sss';
        array_push($vals, $like, $like, $like);
    }
    if ($role !== '') {
        $where[] = "role = ?";
        $types .= 's';
        $vals[] = $role;
    }

    $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    $stmtCnt = $db->prepare("SELECT COUNT(*) AS total FROM pengguna {$whereSql}");
    if ($types !== '') $stmtCnt->bind_param($types, ...$vals);
    $stmtCnt->execute();
    $total = (int)$stmtCnt->get_result()->fetch_assoc()['total'];

    $typesList = $types . 'ii';
    $valsList  = array_merge($vals, [$limit, $offset]);
    $stmt = $db->prepare("SELECT {$safeCols} FROM pengguna {$whereSql} ORDER BY id_pengguna DESC LIMIT ? OFFSET ?");
    $stmt->bind_param($typesList, ...$valsList);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as &$r) {
        $r['id_pengguna'] = (int)$r['id_pengguna'];
        $r['gagal_login'] = (int)$r['gagal_login'];
    }
    json_ok($rows, ['page' => $page, 'limit' => $limit, 'total' => $total]);
}

if ($method === 'POST') {
    $data = filter_whitelist(get_json_body(), ['username', 'password', 'nama_lengkap', 'email', 'role', 'status']);
    $data['status'] = $data['status'] ?? 'aktif';

    $v = new Validator();
    $v->required($data, ['username', 'password', 'nama_lengkap', 'role'])
      ->max($data, 'username', 50)
      ->min($data, 'password', 8)
      ->max($data, 'nama_lengkap', 100)
      ->email($data, 'email')
      ->max($data, 'email', 100)
      ->enum($data, 'role', ['admin', 'petugas', 'dokter', 'pasien'])
      ->enum($data, 'status', ['aktif', 'nonaktif'])
      ->unique('pengguna', 'username', $data['username'] ?? null)
      ->unique('pengguna', 'email', $data['email'] ?? null)
      ->validate();

    $passHash = password_hash($data['password'], PASSWORD_DEFAULT);
    $email    = ($data['email'] ?? '') !== '' ? $data['email'] : null;

    $stmt = $db->prepare("INSERT INTO pengguna (username, password, nama_lengkap, email, role, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssss', $data['username'], $passHash, $data['nama_lengkap'], $email, $data['role'], $data['status']);
    $stmt->execute();
    $newId = (int)$db->insert_id;

    write_audit((int)$user['id_pengguna'], 'CREATE', 'pengguna', "Tambah pengguna ID {$newId} ({$data['username']})");
    json_ok(['id_pengguna' => $newId], [], 201, 'Pengguna berhasil ditambahkan');
}

if ($method === 'PUT') {
    if (!$id) json_error(400, 'Parameter id wajib disertakan');

    $cek = $db->prepare("SELECT id_pengguna FROM pengguna WHERE id_pengguna = ? LIMIT 1");
    $cek->bind_param('i', $id);
    $cek->execute();
    if (!$cek->get_result()->fetch_assoc()) json_error(404, 'Data pengguna tidak ditemukan');

    $data = filter_whitelist(get_json_body(), ['username', 'password', 'nama_lengkap', 'email', 'role', 'status']);
    if (empty($data)) json_error(400, 'Tidak ada data yang diubah');

    // Admin tidak boleh menonaktifkan atau mengubah role akun sendiri
    if ($id === (int)$user['id_pengguna']) {
        if (isset($data['status']) && $data['status'] === 'nonaktif') {
            json_error(422, 'Validasi gagal', ['status' => 'Admin tidak dapat menonaktifkan akun sendiri']);
        }
        if (isset($data['role']) && $data['role'] !== 'admin') {
            json_error(422, 'Validasi gagal', ['role' => 'Admin tidak dapat menurunkan role akun sendiri']);
        }
    }

    $v = new Validator();
    if (isset($data['username'])) {
        $v->required($data, ['username'])->max($data, 'username', 50)->unique('pengguna', 'username', $data['username'], 'id_pengguna', $id);
    }
    if (isset($data['password']) && $data['password'] !== '') {
        $v->min($data, 'password', 8);
    }
    if (isset($data['nama_lengkap'])) {
        $v->required($data, ['nama_lengkap'])->max($data, 'nama_lengkap', 100);
    }
    if (isset($data['email'])) {
        $v->email($data, 'email')->max($data, 'email', 100)->unique('pengguna', 'email', $data['email'], 'id_pengguna', $id);
    }
    if (isset($data['role'])) {
        $v->enum($data, 'role', ['admin', 'petugas', 'dokter', 'pasien']);
    }
    if (isset($data['status'])) {
        $v->enum($data, 'status', ['aktif', 'nonaktif']);
    }
    $v->validate();

    $sets  = [];
    $types = '';
    $vals  = [];
    foreach ($data as $k => $val) {
        if ($k === 'password') {
            if ($val === '') continue;
            $sets[]  = "`password` = ?";
            $types  .= 's';
            $vals[]  = password_hash($val, PASSWORD_DEFAULT);
        } else {
            $sets[]  = "`{$k}` = ?";
            $types  .= 's';
            $vals[]  = ($val === '') ? null : $val;
        }
    }

    if (empty($sets)) json_error(400, 'Tidak ada data valid yang diubah');

    $types .= 'i';
    $vals[] = $id;
    $stmt = $db->prepare("UPDATE pengguna SET " . implode(', ', $sets) . " WHERE id_pengguna = ?");
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();

    write_audit((int)$user['id_pengguna'], 'UPDATE', 'pengguna', "Ubah pengguna ID {$id}");
    json_ok(['id_pengguna' => $id], [], 200, 'Pengguna berhasil diperbarui');
}

if ($method === 'DELETE') {
    if (!$id) json_error(400, 'Parameter id wajib disertakan');

    if ($id === (int)$user['id_pengguna']) {
        json_error(422, 'Validasi gagal', ['id' => 'Admin tidak dapat menghapus akun sendiri']);
    }

    $cek = $db->prepare("SELECT id_pengguna FROM pengguna WHERE id_pengguna = ? LIMIT 1");
    $cek->bind_param('i', $id);
    $cek->execute();
    if (!$cek->get_result()->fetch_assoc()) json_error(404, 'Data pengguna tidak ditemukan');

    $del = $db->prepare("DELETE FROM pengguna WHERE id_pengguna = ?");
    $del->bind_param('i', $id);
    $del->execute();

    write_audit((int)$user['id_pengguna'], 'DELETE', 'pengguna', "Hapus pengguna ID {$id}");
    json_ok([], [], 200, 'Pengguna berhasil dihapus');
}