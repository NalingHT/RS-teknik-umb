<?php
/**
 * tes_webservice.php
 * Alat uji web service (Server A -> Server B) lewat browser, tanpa CMD.
 * Cara pakai: taruh di rs_app/ (Server A), buka http://<IP-SERVER-A>/rs_app/tes_webservice.php
 * Request dikirim dari SERVER (cURL), jadi API key tidak pernah dikirim ke JavaScript browser.
 * Hanya bisa diakses dari jaringan lokal. HAPUS FILE INI setelah pengujian selesai.
 */
ini_set('session.cookie_httponly', '1');
ini_set('session.use_strict_mode', '1');
session_start();

/* ---------- Pembatasan akses: hanya jaringan lokal ---------- */
function ip_lokal($ip) {
    return $ip === '127.0.0.1' || $ip === '::1'
        || preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', $ip);
}
if (!ip_lokal($_SERVER['REMOTE_ADDR'] ?? '')) {
    http_response_code(403);
    exit('Akses ditolak: alat uji hanya untuk jaringan lokal.');
}

/* ---------- Helper ---------- */
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

function base_valid($u) {
    return (bool)preg_match(
        '~^https?://(localhost|127\.0\.0\.1|10\.\d+\.\d+\.\d+|192\.168\.\d+\.\d+|172\.(1[6-9]|2\d|3[01])\.\d+\.\d+)(:\d+)?(/[A-Za-z0-9_\-./]*)?$~',
        $u
    );
}

/** Sembunyikan nilai token/password pada tampilan (aman untuk screenshot laporan). */
function sembunyi($data) {
    if (!is_array($data)) return $data;
    foreach ($data as $k => $v) {
        if (is_array($v)) { $data[$k] = sembunyi($v); }
        elseif (in_array(strtolower((string)$k), ['token', 'password', 'password_baru', 'password_lama'], true)) {
            $data[$k] = is_string($v) && strlen($v) > 6 ? substr($v, 0, 6) . '…(disembunyikan)' : '***';
        }
    }
    return $data;
}

function cantik($resp) {
    if (is_array($resp['json'])) {
        return json_encode(sembunyi($resp['json']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    return $resp['raw'] === false || $resp['raw'] === '' ? '(respons kosong)' : $resp['raw'];
}

/**
 * Kirim request ke Server B.
 * $opt: key(bool) kirim X-API-KEY; token(bool) kirim Bearer; key_value / token_value menimpa nilai.
 */
function panggil($method, $path, $body = null, $opt = []) {
    $res = ['method' => $method, 'path' => $path, 'url' => '', 'http' => 0, 'ms' => 0,
            'raw' => '', 'json' => null, 'err' => '', 'headers' => []];
    if (empty($_SESSION['base'])) { $res['err'] = 'Koneksi belum diatur.'; return $res; }
    if (!preg_match('~^[A-Za-z0-9_\-./?=&%:+,]*$~', $path) || strpos($path, '..') !== false) {
        $res['err'] = 'Path tidak valid.'; return $res;
    }
    $url = rtrim($_SESSION['base'], '/') . '/' . ltrim($path, '/');
    $res['url'] = $url;

    $h = ['Accept: application/json'];
    if (($opt['key'] ?? true)) {
        $key = $opt['key_value'] ?? ($_SESSION['key'] ?? '');
        $h[] = 'X-API-KEY: ' . $key;
        $res['headers'][] = 'X-API-KEY: ********';
    }
    if (($opt['token'] ?? true)) {
        $tok = $opt['token_value'] ?? ($_SESSION['token'] ?? '');
        if ($tok !== '') { $h[] = 'Authorization: Bearer ' . $tok; $res['headers'][] = 'Authorization: Bearer ********'; }
    }
    $payload = null;
    if ($body !== null && $body !== '') {
        $payload = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
        $h[] = 'Content-Type: application/json';
        $res['headers'][] = 'Content-Type: application/json';
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => $h,
    ]);
    if ($payload !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $t = microtime(true);
    $raw = curl_exec($ch);
    $res['ms']   = (int)round((microtime(true) - $t) * 1000);
    $res['http'] = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $res['err']  = $raw === false ? curl_error($ch) : '';
    curl_close($ch);

    $res['raw']  = $raw === false ? '' : $raw;
    $j = json_decode($res['raw'], true);
    $res['json'] = is_array($j) ? $j : null;
    return $res;
}

function daftar($json) {
    $d = is_array($json) ? ($json['data'] ?? []) : [];
    if (!is_array($d)) return [];
    if (array_is_list($d)) return $d;
    foreach (['items', 'rows', 'data', 'list'] as $k) {
        if (isset($d[$k]) && is_array($d[$k])) return $d[$k];
    }
    return [];
}

function ambil_id($resp, $idField) {
    $j = $resp['json'] ?? null;
    $d = is_array($j) ? ($j['data'] ?? null) : null;
    foreach ([$idField, 'id', 'insert_id', 'id_baru'] as $k) {
        if (is_array($d) && isset($d[$k])) return (int)$d[$k];
        if (is_array($j) && isset($j[$k])) return (int)$j[$k];
    }
    return null;
}

function cari_id($file, $idField, $kolom, $nilai) {
    $r = panggil('GET', $file . '?limit=100&q=' . rawurlencode($nilai));
    foreach (daftar($r['json']) as $row) {
        if (is_array($row) && isset($row[$kolom], $row[$idField]) && (string)$row[$kolom] === (string)$nilai) {
            return (int)$row[$idField];
        }
    }
    return null;
}

/* ---------- Uji otomatis ---------- */
function jalankan_suite() {
    $hasil = [];
    $no = 0;
    $catat = function ($nama, $resp, array $harap, $ket = '') use (&$hasil, &$no) {
        $no++;
        $lulus = in_array($resp['http'], $harap, true);
        $hasil[] = ['no' => $no, 'nama' => $nama, 'req' => $resp['method'] . ' ' . $resp['path'],
                    'harap' => implode('/', $harap), 'http' => $resp['http'], 'lulus' => $lulus,
                    'ms' => $resp['ms'], 'pesan' => $resp['err'] ?: ($resp['json']['message'] ?? $ket),
                    'detail' => cantik($resp)];
    };
    $lewati = function ($nama, $alasan) use (&$hasil, &$no) {
        $no++;
        $hasil[] = ['no' => $no, 'nama' => $nama, 'req' => '-', 'harap' => '-', 'http' => '-', 'lulus' => null,
                    'ms' => 0, 'pesan' => $alasan, 'detail' => ''];
    };
    $ok = [200, 201];
    $uniq = substr((string)time(), -6);

    /* A. Keamanan dasar */
    $catat('Tanpa X-API-KEY ditolak', panggil('GET', 'poliklinik.php', null, ['key' => false, 'token' => false]), [401]);
    $catat('API key salah ditolak', panggil('GET', 'poliklinik.php', null, ['key_value' => 'kunci-salah', 'token' => false]), [401]);
    $catat('Tanpa token ditolak (pasien.php)', panggil('GET', 'pasien.php', null, ['token' => false]), [401]);
    $catat('Token palsu ditolak', panggil('GET', 'pasien.php', null, ['token_value' => 'token-palsu-123']), [401]);

    /* B. Autentikasi (butuh login) */
    $catat('auth/me.php', panggil('GET', 'auth/me.php'), [200]);

    /* C. GET semua tabel */
    $idPoliAda = $idDokterAda = null;
    foreach (['poliklinik' => 'id_poli', 'asuransi' => 'id_asuransi', 'pengguna' => 'id_pengguna',
              'dokter' => 'id_dokter', 'pasien' => 'id_pasien', 'kunjungan' => 'id_kunjungan'] as $f => $idf) {
        $r = panggil('GET', $f . '.php?page=1&limit=10');
        $catat("GET daftar $f", $r, [200]);
        if ($f === 'pengguna') {
            $no++;
            $bocor = stripos($r['raw'], '"password"') !== false;
            $hasil[] = ['no' => $no, 'nama' => 'Respons pengguna tidak memuat password', 'req' => 'GET pengguna.php',
                        'harap' => 'tanpa kolom password', 'http' => $r['http'], 'lulus' => !$bocor && $r['http'] === 200,
                        'ms' => 0, 'pesan' => $bocor ? 'Kolom password terlihat di respons!' : 'Aman', 'detail' => ''];
        }
        $rows = daftar($r['json']);
        if ($f === 'poliklinik' && $rows) $idPoliAda = (int)($rows[0]['id_poli'] ?? 0) ?: null;
        if ($f === 'dokter' && $rows) {
            $idDokterAda = (int)($rows[0]['id_dokter'] ?? 0) ?: null;
            $idPoliDokter = (int)($rows[0]['id_poli'] ?? 0) ?: null;
        }
    }

    /* D. Laporan */
    $tahun = date('Y');
    $catat('Laporan ringkasan', panggil('GET', 'laporan/ringkasan.php'), [200]);
    $catat('Laporan kunjungan (periode setahun)', panggil('GET', "laporan/kunjungan.php?dari=$tahun-01-01&sampai=$tahun-12-31"), [200]);
    $catat('Laporan pasien per asuransi', panggil('GET', 'laporan/pasien_asuransi.php'), [200]);
    $catat('Laporan dokter per poli', panggil('GET', 'laporan/dokter_poli.php'), [200]);

    /* E. Siklus CRUD (data uji dibuat lalu dihapus) */
    $siklus = function ($nama, $file, $idf, $kolomUnik, $nilaiUnik, array $buat, array $ubah) use ($catat, $ok) {
        $r = panggil('POST', $file . '.php', $buat);
        $catat("CRUD $nama: POST (tambah)", $r, $ok);
        $id = ambil_id($r, $idf) ?: cari_id($file . '.php', $idf, $kolomUnik, $nilaiUnik);
        if (!$id) return null;
        $catat("CRUD $nama: GET ?id=$id", panggil('GET', "$file.php?id=$id"), [200]);
        $catat("CRUD $nama: PUT ?id=$id (ubah)", panggil('PUT', "$file.php?id=$id", $ubah), [200]);
        return $id;
    };
    $hapus = function ($nama, $file, $id) use ($catat) {
        if ($id) $catat("CRUD $nama: DELETE ?id=$id", panggil('DELETE', "$file.php?id=$id"), [200, 204]);
    };

    $kodePoli = 'T' . $uniq;
    $idPoli = $siklus('poliklinik', 'poliklinik', 'id_poli', 'kode_poli', $kodePoli,
        ['kode_poli' => $kodePoli, 'nama_poli' => 'Poli Uji', 'lokasi' => 'Gedung Uji', 'status' => 'aktif'],
        ['kode_poli' => $kodePoli, 'nama_poli' => 'Poli Uji (diubah)', 'lokasi' => 'Gedung Uji 2', 'status' => 'aktif']);

    $kodeAsr = 'A' . $uniq;
    $idAsr = $siklus('asuransi', 'asuransi', 'id_asuransi', 'kode_asuransi', $kodeAsr,
        ['kode_asuransi' => $kodeAsr, 'nama_asuransi' => 'Asuransi Uji', 'jenis' => 'swasta', 'kontak' => '0800', 'status' => 'aktif'],
        ['kode_asuransi' => $kodeAsr, 'nama_asuransi' => 'Asuransi Uji (diubah)', 'jenis' => 'swasta', 'kontak' => '0801', 'status' => 'aktif']);
    $hapus('asuransi', 'asuransi', $idAsr);

    $userUji = 'uji' . $uniq;
    $idUser = $siklus('pengguna', 'pengguna', 'id_pengguna', 'username', $userUji,
        ['username' => $userUji, 'password' => 'Uji12345!', 'nama_lengkap' => 'Pengguna Uji', 'email' => $userUji . '@contoh.test', 'role' => 'petugas', 'status' => 'aktif'],
        ['username' => $userUji, 'nama_lengkap' => 'Pengguna Uji (diubah)', 'email' => $userUji . '@contoh.test', 'role' => 'petugas', 'status' => 'aktif']);
    $hapus('pengguna', 'pengguna', $idUser);

    $poliUntukDokter = $idPoli ?: $idPoliAda;
    if ($poliUntukDokter) {
        $nipUji = 'N' . $uniq;
        $idDok = $siklus('dokter', 'dokter', 'id_dokter', 'nip', $nipUji,
            ['id_poli' => $poliUntukDokter, 'nip' => $nipUji, 'nama_dokter' => 'dr. Uji', 'spesialis' => 'Umum', 'no_str' => 'STR-UJI', 'no_hp' => '08110000', 'status' => 'aktif'],
            ['id_poli' => $poliUntukDokter, 'nip' => $nipUji, 'nama_dokter' => 'dr. Uji (diubah)', 'spesialis' => 'Umum', 'no_str' => 'STR-UJI', 'no_hp' => '08110001', 'status' => 'aktif']);
        $hapus('dokter', 'dokter', $idDok);
    } else {
        $lewati('CRUD dokter', 'Tidak ada poliklinik untuk dipakai.');
    }

    /* Pasien + validasi + kunjungan */
    $nik = '17710' . str_pad((string)mt_rand(0, 99999999999), 11, '0', STR_PAD_LEFT);
    $dataPasien = ['nik' => $nik, 'nama_pasien' => 'Pasien Uji', 'jenis_kelamin' => 'L', 'tempat_lahir' => 'Bengkulu',
                   'tanggal_lahir' => '2000-01-15', 'alamat' => 'Jl. Uji No. 1', 'no_hp' => '08123456789'];
    $idPas = $siklus('pasien', 'pasien', 'id_pasien', 'nik', $nik, $dataPasien,
        array_merge($dataPasien, ['nama_pasien' => 'Pasien Uji (diubah)']));

    $catat('Validasi: NIK 10 digit ditolak', panggil('POST', 'pasien.php',
        array_merge($dataPasien, ['nik' => '1234567890'])), [400, 422]);

    if ($idPas && $idDokterAda && !empty($idPoliDokter)) {
        $besok = date('Y-m-d', strtotime('+1 day'));
        $kj = ['id_pasien' => $idPas, 'id_dokter' => $idDokterAda, 'id_poli' => $idPoliDokter,
               'tanggal_kunjungan' => $besok, 'keluhan' => 'Demam (uji)', 'cara_bayar' => 'umum'];
        $r = panggil('POST', 'kunjungan.php', $kj);
        $catat('CRUD kunjungan: POST (tambah)', $r, $ok);
        $idKj = ambil_id($r, 'id_kunjungan');
        if (!$idKj && isset($r['json']['data']['no_kunjungan'])) {
            $idKj = cari_id('kunjungan.php', 'id_kunjungan', 'no_kunjungan', $r['json']['data']['no_kunjungan']);
        }
        if ($idKj) {
            $catat("CRUD kunjungan: GET ?id=$idKj", panggil('GET', "kunjungan.php?id=$idKj"), [200]);
            $catat("CRUD kunjungan: PUT ?id=$idKj (ubah)", panggil('PUT', "kunjungan.php?id=$idKj",
                array_merge($kj, ['keluhan' => 'Demam tinggi (diubah)'])), [200]);
            $hapus('kunjungan', 'kunjungan', $idKj);
        }
    } else {
        $lewati('CRUD kunjungan', 'Butuh minimal 1 dokter di database dan pasien uji yang berhasil dibuat.');
    }
    $hapus('pasien', 'pasien', $idPas);
    $hapus('poliklinik', 'poliklinik', $idPoli);

    /* F. Validasi: pengguna tidak boleh dibuat oleh role non-admin tidak diuji di sini (butuh login role lain) */
    return $hasil;
}

/* ---------- Penanganan form ---------- */
$pesan = ''; $hasilKirim = null; $suite = null; $loginRes = null;
$in = ['method' => 'GET', 'path' => 'poliklinik.php', 'body' => '', 'pakai_key' => '1', 'pakai_token' => '1'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        http_response_code(400); exit('CSRF token tidak valid.');
    }
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'koneksi') {
        $base = trim($_POST['base'] ?? '');
        if (!base_valid($base)) {
            $pesan = 'Base URL tidak valid atau bukan alamat jaringan lokal. Contoh: http://192.168.1.103/rs_service/api/v1/';
        } else {
            $_SESSION['base'] = $base;
            if (($_POST['key'] ?? '') !== '') $_SESSION['key'] = $_POST['key'];
            unset($_SESSION['token'], $_SESSION['user']);
            $pesan = 'Koneksi disimpan di session. Silakan login.';
        }
    } elseif ($aksi === 'login') {
        $loginRes = panggil('POST', 'auth/login.php',
            ['username' => $_POST['username'] ?? '', 'password' => $_POST['password'] ?? ''], ['token' => false]);
        $tok = $loginRes['json']['data']['token'] ?? '';
        if ($loginRes['http'] === 200 && $tok) {
            $_SESSION['token'] = $tok;
            $_SESSION['user']  = $loginRes['json']['data']['user'] ?? [];
            $_SESSION['exp']   = $loginRes['json']['data']['expires_at'] ?? '';
            $pesan = 'Login berhasil.';
        } else {
            unset($_SESSION['token'], $_SESSION['user']);
            $pesan = 'Login gagal (HTTP ' . $loginRes['http'] . ').';
        }
    } elseif ($aksi === 'logout') {
        $loginRes = panggil('POST', 'auth/logout.php');
        unset($_SESSION['token'], $_SESSION['user'], $_SESSION['exp']);
        $pesan = 'Logout dikirim ke Server B (HTTP ' . $loginRes['http'] . ') dan token lokal dihapus.';
    } elseif ($aksi === 'kirim') {
        $in['method'] = in_array($_POST['method'] ?? '', ['GET', 'POST', 'PUT', 'DELETE'], true) ? $_POST['method'] : 'GET';
        $in['path']   = trim($_POST['path'] ?? '');
        $in['body']   = $_POST['body'] ?? '';
        $in['pakai_key']   = isset($_POST['pakai_key']) ? '1' : '';
        $in['pakai_token'] = isset($_POST['pakai_token']) ? '1' : '';
        if ($in['body'] !== '' && json_decode($in['body']) === null) {
            $pesan = 'Body bukan JSON yang valid.';
        } else {
            $hasilKirim = panggil($in['method'], $in['path'], $in['body'] === '' ? null : $in['body'],
                ['key' => (bool)$in['pakai_key'], 'token' => (bool)$in['pakai_token']]);
        }
    } elseif ($aksi === 'suite') {
        if (empty($_SESSION['token'])) { $pesan = 'Login dulu sebelum menjalankan uji otomatis.'; }
        else { $suite = jalankan_suite(); }
    } elseif ($aksi === 'reset') {
        $_SESSION = []; session_destroy(); header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?')); exit;
    }
}

$sudahKoneksi = !empty($_SESSION['base']);
$sudahLogin   = !empty($_SESSION['token']);
$endpoint = ['auth/me.php', 'poliklinik.php', 'asuransi.php', 'pengguna.php', 'dokter.php', 'pasien.php', 'kunjungan.php',
             'laporan/ringkasan.php', 'laporan/kunjungan.php?dari=2026-01-01&sampai=2026-12-31',
             'laporan/pasien_asuransi.php', 'laporan/dokter_poli.php'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Uji Web Service — Server A → Server B</title>
<style>
  :root { --bg:#f4f6f9; --card:#fff; --ink:#1b2a3a; --muted:#64748b; --line:#dbe2ea; --ok:#15803d; --bad:#b91c1c; --warn:#b45309; --pri:#1d4ed8; }
  * { box-sizing:border-box; }
  body { margin:0; font:14px/1.5 system-ui,Segoe UI,Arial,sans-serif; background:var(--bg); color:var(--ink); }
  header { background:#12263f; color:#fff; padding:16px 24px; }
  header h1 { margin:0; font-size:18px; } header p { margin:2px 0 0; color:#b8c7da; font-size:13px; }
  main { max-width:1100px; margin:20px auto; padding:0 16px; display:grid; gap:16px; }
  .card { background:var(--card); border:1px solid var(--line); border-radius:10px; padding:16px 18px; }
  .card h2 { margin:0 0 10px; font-size:15px; }
  label { display:block; font-size:12px; color:var(--muted); margin:8px 0 3px; }
  input[type=text],input[type=password],select,textarea { width:100%; padding:8px 10px; border:1px solid var(--line); border-radius:6px; font:inherit; }
  textarea { font-family:Consolas,monospace; min-height:110px; }
  .row { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:12px; }
  button { background:var(--pri); color:#fff; border:0; border-radius:6px; padding:8px 14px; font:inherit; cursor:pointer; margin-top:12px; }
  button.sec { background:#475569; } button.red { background:var(--bad); }
  .msg { padding:9px 12px; border-radius:6px; background:#e0ecff; border:1px solid #b6cdf7; }
  .badge { display:inline-block; padding:1px 8px; border-radius:99px; font-size:12px; font-weight:600; color:#fff; }
  .b-ok { background:var(--ok); } .b-bad { background:var(--bad); } .b-skip { background:var(--warn); }
  pre { background:#0f172a; color:#e2e8f0; padding:12px; border-radius:8px; overflow:auto; max-height:420px; font:12.5px/1.5 Consolas,monospace; margin:8px 0 0; }
  table { width:100%; border-collapse:collapse; font-size:13px; }
  th,td { border-bottom:1px solid var(--line); padding:6px 8px; text-align:left; vertical-align:top; }
  th { background:#f1f5f9; font-size:12px; }
  details summary { cursor:pointer; color:var(--pri); font-size:12px; }
  .meta { color:var(--muted); font-size:12px; } .inline { display:flex; gap:16px; flex-wrap:wrap; align-items:center; }
  .inline label { display:inline-flex; gap:6px; align-items:center; margin:8px 0 0; color:var(--ink); font-size:13px; }
  .warn { background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; padding:9px 12px; border-radius:6px; font-size:13px; }
</style>
</head>
<body>
<header>
  <h1>Uji Web Service — Server A → Server B</h1>
  <p>Request dikirim dari server (cURL). API key dan token tidak pernah dikirim ke JavaScript browser.</p>
</header>
<main>

<div class="warn">⚠️ Alat bantu pengujian sementara. Hapus file <code>tes_webservice.php</code> dari Server A setelah selesai dan jangan ikut diunggah/di-zip untuk laporan.</div>
<?php if ($pesan): ?><div class="msg"><?= h($pesan) ?></div><?php endif; ?>

<!-- 1. Koneksi -->
<section class="card">
  <h2>1. Koneksi ke Server B</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
    <input type="hidden" name="aksi" value="koneksi">
    <div class="row">
      <div><label>Base URL web service</label>
        <input type="text" name="base" value="<?= h($_SESSION['base'] ?? 'http://192.168.1.103/rs_service/api/v1/') ?>"></div>
      <div><label>API key (kosongkan jika tidak diganti)</label>
        <input type="password" name="key" autocomplete="off" placeholder="<?= !empty($_SESSION['key']) ? '•••••••• (tersimpan di session)' : 'tempel API key Server B' ?>"></div>
    </div>
    <button>Simpan koneksi</button>
  </form>
</section>

<!-- 2. Login -->
<section class="card">
  <h2>2. Login (auth/login.php)</h2>
  <?php if ($sudahLogin): ?>
    <p>Status: <span class="badge b-ok">login</span>
       <b><?= h($_SESSION['user']['username'] ?? '') ?></b> (role: <b><?= h($_SESSION['user']['role'] ?? '') ?></b>)
       <span class="meta">— token berlaku sampai <?= h($_SESSION['exp'] ?? '-') ?></span></p>
    <form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
      <input type="hidden" name="aksi" value="logout"><button class="sec">Logout (auth/logout.php)</button></form>
  <?php elseif ($sudahKoneksi): ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="aksi" value="login">
      <div class="row">
        <div><label>Username</label><input type="text" name="username" autocomplete="off"></div>
        <div><label>Password</label><input type="password" name="password" autocomplete="off"></div>
      </div>
      <button>Login</button>
    </form>
  <?php else: ?>
    <p class="meta">Simpan koneksi terlebih dahulu.</p>
  <?php endif; ?>
  <?php if ($loginRes): ?>
    <p class="meta">HTTP <?= (int)$loginRes['http'] ?> · <?= (int)$loginRes['ms'] ?> ms · <?= h($loginRes['method'] . ' ' . $loginRes['url']) ?></p>
    <pre><?= h(cantik($loginRes)) ?></pre>
  <?php endif; ?>
</section>

<?php if ($sudahKoneksi): ?>
<!-- 3. Request manual -->
<section class="card">
  <h2>3. Kirim request manual</h2>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="aksi" value="kirim">
    <div class="row">
      <div><label>Metode</label>
        <select name="method"><?php foreach (['GET','POST','PUT','DELETE'] as $m): ?>
          <option <?= $in['method'] === $m ? 'selected' : '' ?>><?= $m ?></option><?php endforeach; ?></select></div>
      <div style="grid-column:span 2"><label>Endpoint (relatif terhadap Base URL; tambahkan <code>?id=…</code> bila perlu)</label>
        <input type="text" name="path" list="ep" value="<?= h($in['path']) ?>">
        <datalist id="ep"><?php foreach ($endpoint as $e): ?><option value="<?= h($e) ?>"><?php endforeach; ?></datalist></div>
    </div>
    <label>Body JSON (untuk POST/PUT) <span class="meta">— contoh cepat:</span>
      <?php foreach (['poliklinik','asuransi','pasien','dokter','kunjungan','pengguna'] as $t): ?>
        <a href="#" onclick="isiContoh('<?= $t ?>');return false;"><?= $t ?></a>&nbsp;
      <?php endforeach; ?></label>
    <textarea name="body" id="body" placeholder='{"nama_poli":"..."}'><?= h($in['body']) ?></textarea>
    <div class="inline">
      <label><input type="checkbox" name="pakai_key" value="1" <?= $in['pakai_key'] ? 'checked' : '' ?>> Kirim X-API-KEY</label>
      <label><input type="checkbox" name="pakai_token" value="1" <?= $in['pakai_token'] ? 'checked' : '' ?>> Kirim token login</label>
      <span class="meta">(hapus centang untuk menguji respons 401)</span>
    </div>
    <button>Kirim</button>
  </form>
  <?php if ($hasilKirim): $kode = $hasilKirim['http']; ?>
    <p style="margin-top:14px"><span class="badge <?= ($kode >= 200 && $kode < 300) ? 'b-ok' : 'b-bad' ?>">HTTP <?= (int)$kode ?></span>
      <span class="meta"><?= (int)$hasilKirim['ms'] ?> ms · <?= h($hasilKirim['method'] . ' ' . $hasilKirim['url']) ?></span></p>
    <?php if ($hasilKirim['err']): ?><p class="meta">Error cURL: <?= h($hasilKirim['err']) ?></p><?php endif; ?>
    <p class="meta">Header terkirim: <?= h(implode(' | ', $hasilKirim['headers'])) ?></p>
    <pre><?= h(cantik($hasilKirim)) ?></pre>
  <?php endif; ?>
</section>

<!-- 4. Uji otomatis -->
<section class="card">
  <h2>4. Uji otomatis seluruh web service</h2>
  <p class="meta">Menjalankan: uji keamanan (401), GET semua tabel, 4 laporan, siklus CRUD (POST → GET → PUT → DELETE) untuk
    poliklinik, asuransi, pengguna, dokter, pasien, kunjungan, serta uji validasi. Data uji dibuat lalu dihapus kembali.
    Hasil tabel di bawah dapat di-screenshot sebagai bukti Soal 3.</p>
  <form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="aksi" value="suite">
    <button <?= $sudahLogin ? '' : 'disabled title="Login dulu"' ?>>Jalankan semua uji</button></form>

  <?php if ($suite !== null):
      $lulus = count(array_filter($suite, fn($x) => $x['lulus'] === true));
      $gagal = count(array_filter($suite, fn($x) => $x['lulus'] === false));
      $skip  = count($suite) - $lulus - $gagal; ?>
    <p style="margin-top:14px"><b>Ringkasan:</b> <span class="badge b-ok"><?= $lulus ?> lulus</span>
      <span class="badge b-bad"><?= $gagal ?> gagal</span> <span class="badge b-skip"><?= $skip ?> dilewati</span>
      <span class="meta">— <?= h(date('d-m-Y H:i:s')) ?></span></p>
    <div style="overflow:auto"><table>
      <tr><th>#</th><th>Pengujian</th><th>Request</th><th>Harapan</th><th>Hasil</th><th>Status</th><th>Pesan / detail</th></tr>
      <?php foreach ($suite as $s): ?>
      <tr>
        <td><?= (int)$s['no'] ?></td><td><?= h($s['nama']) ?></td><td><code><?= h($s['req']) ?></code></td>
        <td><?= h($s['harap']) ?></td><td><?= h($s['http']) ?></td>
        <td><?php if ($s['lulus'] === true): ?><span class="badge b-ok">LULUS</span>
            <?php elseif ($s['lulus'] === false): ?><span class="badge b-bad">GAGAL</span>
            <?php else: ?><span class="badge b-skip">LEWATI</span><?php endif; ?></td>
        <td><?= h($s['pesan']) ?>
          <?php if ($s['detail'] !== ''): ?><details><summary>respons</summary><pre><?= h($s['detail']) ?></pre></details><?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
    </table></div>
  <?php endif; ?>
</section>
<?php endif; ?>

<form method="post" style="text-align:right"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
  <input type="hidden" name="aksi" value="reset"><button class="red">Reset session alat uji</button></form>
</main>

<script>
const contoh = {
  poliklinik: {kode_poli:"TST", nama_poli:"Poli Uji", lokasi:"Gedung X", status:"aktif"},
  asuransi:   {kode_asuransi:"TST", nama_asuransi:"Asuransi Uji", jenis:"swasta", kontak:"0800", status:"aktif"},
  pasien:     {nik:"1771012345678901", nama_pasien:"Pasien Uji", jenis_kelamin:"L", tempat_lahir:"Bengkulu", tanggal_lahir:"2000-01-15", alamat:"Jl. Uji", no_hp:"08123456789"},
  dokter:     {id_poli:1, nip:"NIP-UJI", nama_dokter:"dr. Uji", spesialis:"Umum", no_str:"STR-UJI", no_hp:"08110000", status:"aktif"},
  kunjungan:  {id_pasien:1, id_dokter:1, id_poli:1, tanggal_kunjungan:"2026-12-01", keluhan:"Demam", cara_bayar:"umum"},
  pengguna:   {username:"uji01", password:"Uji12345!", nama_lengkap:"Pengguna Uji", email:"uji01@contoh.test", role:"petugas", status:"aktif"}
};
function isiContoh(k){ document.getElementById('body').value = JSON.stringify(contoh[k], null, 2); }
</script>
</body>
</html>
