<?php
// Endpoint CRUD asuransi.php
require_once __DIR__ . '/../../core/init.php';

require_api_key();
$method = require_methods(['GET', 'POST', 'PUT', 'DELETE']);
$db     = get_db();
$id     = get_query('id') !== null ? (int)get_query('id') : null;

if ($method === 'GET') {
    require_role(['admin', 'petugas', 'dokter', 'pasien'], 'asuransi');

    if ($id !== null) {
        $stmt = $db->prepare("SELECT * FROM asuransi WHERE id_asuransi = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) json_error(404, 'Data asuransi tidak ditemukan');
        $row['id_asuransi'] = (int)$row['id_asuransi'];
        json_ok($row);
    }

    [$page, $limit, $offset] = get_pagination();
    $q = get_query('q', '');

    if ($q !== '') {
        $like = "%{$q}%";
        $stmtCnt = $db->prepare("SELECT COUNT(*) AS total FROM asuransi WHERE kode_asuransi LIKE ? OR nama_asuransi LIKE ?");
        $stmtCnt->bind_param('ss', $like, $like);
        $stmtCnt->execute();
        $total = (int)$stmtCnt->get_result()->fetch_assoc()['total'];

        $stmt = $db->prepare("SELECT * FROM asuransi WHERE kode_asuransi LIKE ? OR nama_asuransi LIKE ? ORDER BY id_asuransi DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('ssii', $like, $like, $limit, $offset);
    } else {
        $total = (int)$db->query("SELECT COUNT(*) AS total FROM asuransi")->fetch_assoc()['total'];
        $stmt = $db->prepare("SELECT * FROM asuransi ORDER BY id_asuransi DESC LIMIT ? OFFSET ?");
        $stmt->bind_param('ii', $limit, $offset);
    }

    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$r) {
        $r['id_asuransi'] = (int)$r['id_asuransi'];
    }
    json_ok($rows, ['page' => $page, 'limit' => $limit, 'total' => $total]);
}

$user = require_role(['admin'], 'asuransi');

if ($method === 'POST') {
    $data = filter_whitelist(get_json_body(), ['kode_asuransi', 'nama_asuransi', 'jenis', 'kontak', 'status']);
    $data['kode_asuransi'] = isset($data['kode_asuransi']) ? strtoupper($data['kode_asuransi']) : '';
    $data['jenis']         = $data['jenis'] ?? 'swasta';
    $data['status']        = $data['status'] ?? 'aktif';

    $v = new Validator();
    $v->required($data, ['kode_asuransi', 'nama_asuransi'])
      ->max($data, 'kode_asuransi', 20)
      ->max($data, 'nama_asuransi', 100)
      ->enum($data, 'jenis', ['pemerintah', 'swasta'])
      ->max($data, 'kontak', 50)
      ->enum($data, 'status', ['aktif', 'nonaktif'])
      ->unique('asuransi', 'kode_asuransi', $data['kode_asuransi'])
      ->validate();

    $kontak = ($data['kontak'] ?? '') !== '' ? $data['kontak'] : null;
    $stmt = $db->prepare("INSERT INTO asuransi (kode_asuransi, nama_asuransi, jenis, kontak, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $data['kode_asuransi'], $data['nama_asuransi'], $data['jenis'], $kontak, $data['status']);
    $stmt->execute();
    $newId = (int)$db->insert_id;

    write_audit((int)$user['id_pengguna'], 'CREATE', 'asuransi', "Tambah asuransi ID {$newId} ({$data['kode_asuransi']})");
    json_ok(['id_asuransi' => $newId], [], 201, 'Asuransi berhasil ditambahkan');
}

if ($method === 'PUT') {
    if (!$id) json_error(400, 'Parameter id wajib disertakan');

    $cek = $db->prepare("SELECT id_asuransi FROM asuransi WHERE id_asuransi = ? LIMIT 1");
    $cek->bind_param('i', $id);
    $cek->execute();
    if (!$cek->get_result()->fetch_assoc()) json_error(404, 'Data asuransi tidak ditemukan');

    $data = filter_whitelist(get_json_body(), ['kode_asuransi', 'nama_asuransi', 'jenis', 'kontak', 'status']);
    if (empty($data)) json_error(400, 'Tidak ada data yang diubah');
    if (isset($data['kode_asuransi'])) $data['kode_asuransi'] = strtoupper($data['kode_asuransi']);

    $v = new Validator();
    if (isset($data['kode_asuransi'])) {
        $v->required($data, ['kode_asuransi'])->max($data, 'kode_asuransi', 20)->unique('asuransi', 'kode_asuransi', $data['kode_asuransi'], 'id_asuransi', $id);
    }
    if (isset($data['nama_asuransi'])) {
        $v->required($data, ['nama_asuransi'])->max($data, 'nama_asuransi', 100);
    }
    if (isset($data['jenis'])) {
        $v->enum($data, 'jenis', ['pemerintah', 'swasta']);
    }
    if (isset($data['kontak'])) {
        $v->max($data, 'kontak', 50);
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

    $stmt = $db->prepare("UPDATE asuransi SET " . implode(', ', $sets) . " WHERE id_asuransi = ?");
    $stmt->bind_param($types, ...$vals);
    $stmt->execute();

    write_audit((int)$user['id_pengguna'], 'UPDATE', 'asuransi', "Ubah asuransi ID {$id}");
    json_ok(['id_asuransi' => $id], [], 200, 'Asuransi berhasil diperbarui');
}

if ($method === 'DELETE') {
    if (!$id) json_error(400, 'Parameter id wajib disertakan');

    $cek = $db->prepare("SELECT id_asuransi FROM asuransi WHERE id_asuransi = ? LIMIT 1");
    $cek->bind_param('i', $id);
    $cek->execute();
    if (!$cek->get_result()->fetch_assoc()) json_error(404, 'Data asuransi tidak ditemukan');

    $c1 = $db->prepare("SELECT COUNT(*) AS cnt FROM pasien WHERE id_asuransi = ?");
    $c1->bind_param('i', $id);
    $c1->execute();
    if ((int)$c1->get_result()->fetch_assoc()['cnt'] > 0) {
        json_error(409, 'Asuransi tidak dapat dihapus karena masih digunakan oleh data pasien');
    }

    $del = $db->prepare("DELETE FROM asuransi WHERE id_asuransi = ?");
    $del->bind_param('i', $id);
    $del->execute();

    write_audit((int)$user['id_pengguna'], 'DELETE', 'asuransi', "Hapus asuransi ID {$id}");
    json_ok([], [], 200, 'Asuransi berhasil dihapus');
}