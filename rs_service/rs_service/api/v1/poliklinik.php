<?php
// Endpoint CRUD poliklinik.php
require_once __DIR__ . '/../../core/init.php';

require_api_key();
$method = require_methods(['GET', 'POST', 'PUT', 'DELETE']);
$db     = get_db();
$id     = get_query('id') !== null ? (int)get_query('id') : null;

// --- READ (Publik dengan API Key, atau semua role login) ---
if ($method === 'GET') {
    $user = optional_token();

    if ($id !== null) {
        $cols = $user ? '*' : 'id_poli, kode_poli, nama_poli, lokasi, status';
        $stmt = $db->prepare("SELECT {$cols} FROM poliklinik WHERE id_poli = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) {
            json_error(404, 'Data poliklinik tidak ditemukan');
        }
        $row['id_poli'] = (int)$row['id_poli'];
        json_ok($row);
    }

    [$page, $limit, $offset] = get_pagination();
    $q = get_query('q', '');
    $cols = $user ? '*' : 'id_poli, kode_poli, nama_poli, lokasi, status';

    if ($q !== '') {
        $like = "%{$q}%";
        $stmtCnt = $db->prepare("SELECT COUNT(*) AS total FROM poliklinik WHERE kode_poli LIKE ? OR nama_poli LIKE ? OR lokasi LIKE ?");
        $stmtCnt->bind_param('sss', $like, $like, $like);
        $stmtCnt->execute();
        $total = (int)$stmtCnt->get_result()->fetch_assoc()['total'];

        $stmt = $db->prepare("SELECT {$cols} FROM poliklinik WHERE kode_poli LIKE ? OR nama_poli LIKE ? OR lokasi LIKE ? ORDER BY id_poli DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('sssii', $like, $like, $like, $limit, $offset);
    } else {
        $total = (int)$db->query("SELECT COUNT(*) AS total FROM poliklinik")->fetch_assoc()['total'];
        $stmt = $db->prepare("SELECT {$cols} FROM poliklinik ORDER BY id_poli DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $limit, $offset);
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$r) {
        $r['id_poli'] = (int)$r['id_poli'];
    }
    json_ok($rows, ['page' => $page, 'limit' => $limit, 'total' => $total]);
}

// --- CREATE, UPDATE, DELETE (Hanya Admin) ---
$user = require_role(['admin'], 'poliklinik');

if ($method === 'POST') {
    $data = filter_whitelist(get_json_body(), ['kode_poli', 'nama_poli', 'lokasi', 'status']);
    $data['kode_poli'] = isset($data['kode_poli']) ? strtoupper($data['kode_poli']) : '';
    $data['status']    = $data['status'] ?? 'aktif';

    $v = new Validator();
    $v->required($data, ['kode_poli', 'nama_poli'])
      ->max($data, 'kode_poli', 10)
      ->max($data, 'nama_poli', 100)
      ->max($data, 'lokasi', 100)
      ->enum($data, 'status', ['aktif', 'nonaktif'])
      ->unique('poliklinik', 'kode_poli', $data['kode_poli'])
      ->validate();

    $lokasi = ($data['lokasi'] ?? '') !== '' ? $data['lokasi'] : null;
    $stmt = $db->prepare("INSERT INTO poliklinik (kode_poli, nama_poli, lokasi, status) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('ssss', $data['kode_poli'], $data['nama_poli'], $lokasi, $data['status']);
    $stmt->execute();
    $newId = (int)$db->insert_id;

    write_audit((int)$user['id_pengguna'], 'CREATE', 'poliklinik', "Tambah poliklinik ID {$newId} ({$data['kode_poli']})");
    json_ok(['id_poli' => $newId], [], 201, 'Poliklinik berhasil ditambahkan');
}

if ($method === 'PUT') {
    if (!$id) json_error(400, 'Parameter id wajib disertakan');

    $cek = $db->prepare("SELECT id_poli FROM poliklinik WHERE id_poli = ? LIMIT 1");
    $cek->bind_param('i', $id);
    $cek->execute();
    if (!$cek->get_result()->fetch_assoc()) {
        json_error(404, 'Data poliklinik tidak ditemukan');
    }

    $data = filter_whitelist(get_json_body(), ['kode_poli', 'nama_poli', 'lokasi', 'status']);
    if (empty($data)) json_error(400, 'Tidak ada data yang diubah');
    if (isset($data['kode_poli'])) $data['kode_poli'] = strtoupper($data['kode_poli']);

    $v = new Validator();
    if (isset($data['kode_poli'])) {
        $v->required($data, ['kode_poli'])->max($data, 'kode_poli', 10)->unique('poliklinik', 'kode_poli', $data['kode_poli'], 'id_poli', $id);
    }
    if (isset($data['nama_poli'])) {
        $v->required($data, ['nama_poli'])->max($data, 'nama_poli', 100);
    }
    if (isset($data['lokasi'])) {
        $v->max($data, 'lokasi', 100);
    }
    if (isset($data['status'])) {
        $v->enum($data, 'status', ['aktif', 'nonaktif']);
    }
    $v->validate();

    $sets = [];
    $types = '';
    $vals = [];
    foreach ($data as $k => $val) {
        $sets[] = "`{$k}` = ?";
        $types .= 's';
        $vals[] = ($val === '') ? null : $val;
    }
    $types .= 'i';
    $vals[] = $id;

    $stmt = $db->prepare("UPDATE poliklinik SET " . implode(', ', $sets) . " WHERE id_poli = ?");
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();

    write_audit((int)$user['id_pengguna'], 'UPDATE', 'poliklinik', "Ubah poliklinik ID {$id}");
    json_ok(['id_poli' => $id], [], 200, 'Poliklinik berhasil diperbarui');
}

if ($method === 'DELETE') {
    if (!$id) json_error(400, 'Parameter id wajib disertakan');

    $cek = $db->prepare("SELECT id_poli FROM poliklinik WHERE id_poli = ? LIMIT 1");
    $cek->bind_param('i', $id);
    $cek->execute();
    if (!$cek->get_result()->fetch_assoc()) {
        json_error(404, 'Data poliklinik tidak ditemukan');
    }

    // Cek relasi dokter & kunjungan -> 409
    $c1 = $db->prepare("SELECT COUNT(*) AS cnt FROM dokter WHERE id_poli = ?");
    $c1->bind_param('i', $id);
    $c1->execute();
    $cntDok = (int)$c1->get_result()->fetch_assoc()['cnt'];

    $c2 = $db->prepare("SELECT COUNT(*) AS cnt FROM kunjungan WHERE id_poli = ?");
    $c2->bind_param('i', $id);
    $c2->execute();
    $cntKj = (int)$c2->get_result()->fetch_assoc()['cnt'];

    if ($cntDok > 0 || $cntKj > 0) {
        json_error(409, 'Poliklinik tidak dapat dihapus karena masih digunakan oleh data dokter atau kunjungan');
    }

    $del = $db->prepare("DELETE FROM poliklinik WHERE id_poli = ?");
    $del->bind_param('i', $id);
    $del->execute();

    write_audit((int)$user['id_pengguna'], 'DELETE', 'poliklinik', "Hapus poliklinik ID {$id}");
    json_ok([], [], 200, 'Poliklinik berhasil dihapus');
}