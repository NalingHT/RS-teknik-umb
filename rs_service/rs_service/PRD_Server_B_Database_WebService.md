# PRD Server B — Database & Web Service (Service Provider)
## Sistem Informasi Rumah Sakit Berbasis SOA

> File ini **berdiri sendiri** untuk dibaca AI di CLI. Kerjakan **hanya folder `rs_service/`**.
> Pasangan dokumen: `PRD_Server_A_Aplikasi_UI.md` (konsumen layanan). Kontrak API di §5 adalah sumber kebenaran; jika diubah, ubah juga di PRD Server A.
> Mata Kuliah Web Semantik — Teknik Informatika, Universitas Muhammadiyah Bengkulu. Cakupan soal: **Soal 1 (bagian Server B), Soal 2, Soal 3**.

---

## 1. Peran & Konteks

| | Server A (bukan bagian file ini) | **Server B (file ini)** |
|---|---|---|
| Peran | Service Consumer: UI, login/session, RBAC tampilan | **Service Provider: MySQL + REST API (JSON)** |
| Contoh IP | `192.168.7.100` | **`192.168.7.101`** |
| Folder XAMPP | `htdocs/rs_app` | **`htdocs/rs_service`** |

> ⚠️ Pada slide PDF dosen, penamaan A dan B terbalik (A = API+DB, B = aplikasi). **Soal praktikum ini: Server A = UI, Server B = basisdata + web service.** Ikuti soal.

**Aturan emas:** hanya Server B yang menyentuh database. Server A mengakses data **semata-mata lewat HTTP + JSON**. Server B juga **tidak membuat HTML/UI** apa pun; semua output adalah JSON.

**Mode deploy:** (1) dua mesin/VM dengan IP berbeda (ideal); (2) satu mesin XAMPP dengan dua folder terpisah (simulasi). Pada keduanya, Server A memanggil `http://<host>/rs_service/api/v1/...` lewat cURL.

---

## 2. Tujuan & Non-Tujuan

**Tujuan:** membuat database rumah sakit dan web service lengkap (login, CRUD semua tabel, laporan) yang aman, tervalidasi, terdokumentasi, dan dapat dipakai ulang oleh beberapa aplikasi (reuse) lewat kontrak yang jelas.

**Non-tujuan:** UI, RDF/ontologi/SPARQL, framework besar, integrasi BPJS/SATUSEHAT asli.

---

## 3. Tech Stack & Struktur Folder

- PHP 8.x native, **MySQLi prepared statements (wajib)**, Apache (XAMPP), MySQL/MariaDB `utf8mb4`.
- Penamaan `snake_case` Bahasa Indonesia. Setiap file PHP diberi komentar tujuan singkat.
- `config/config.php` tidak di-commit; sediakan `config.example.php`.

```
rs_service/
├── config/
│   ├── config.example.php
│   └── config.php                # tidak di-commit
├── core/
│   ├── db.php                    # koneksi mysqli sekali, set_charset utf8mb4
│   ├── response.php              # json_ok($data,$meta=[],$code=200), json_error($code,$msg,$errors=[])
│   ├── request.php               # body JSON, query, method override (X-HTTP-Method-Override)
│   ├── auth.php                  # require_api_key(), require_token(), require_role([...]), current_user()
│   ├── validator.php             # required, max, enum, date, digits, unique, exists
│   └── logger.php                # audit_log (DB) + logs/error.log
├── api/v1/
│   ├── auth/      login.php, logout.php, me.php, register.php, ganti_password.php
│   ├── poliklinik.php
│   ├── pengguna.php
│   ├── dokter.php
│   ├── pasien.php
│   ├── asuransi.php
│   ├── kunjungan.php
│   └── laporan/   ringkasan.php, kunjungan.php, pasien_asuransi.php, dokter_poli.php
├── sql/
│   ├── rs_database.sql
│   └── seed_users.php
├── logs/                         # berisi .htaccess: Require all denied
└── README.md                     # setup + contoh curl
```

### config.example.php (isi minimal)
```php
<?php
return [
  'db' => ['host'=>'localhost','user'=>'rs_service_user','pass'=>'GANTI_PASSWORD','name'=>'db_rumah_sakit'],
  // satu API key per aplikasi pemanggil (A = rs_app). Aplikasi lain (SIM Dokter, dll.) = key lain.
  'api_keys' => [ 'rs_app' => 'GANTI_DENGAN_STRING_ACAK_PANJANG' ],
  'token_ttl_minutes' => 120,
  'login_max_gagal' => 5,
  'login_blokir_menit' => 10,
  'debug' => false,
];
```

---

## 4. Desain Database (Soal 2)

Nama database: `db_rumah_sakit`. Simpan sebagai `sql/rs_database.sql` (jawaban Soal 2 = kode SQL ini + screenshot struktur tabel + ERD).

```sql
CREATE DATABASE IF NOT EXISTS db_rumah_sakit
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_rumah_sakit;

-- 1. Poliklinik
CREATE TABLE poliklinik (
  id_poli      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_poli    VARCHAR(10)  NOT NULL UNIQUE,
  nama_poli    VARCHAR(100) NOT NULL,
  lokasi       VARCHAR(100) NULL,
  status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Pengguna (akun login semua peran)
CREATE TABLE pengguna (
  id_pengguna  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(50)  NOT NULL UNIQUE,
  password     VARCHAR(255) NOT NULL,           -- hasil password_hash()
  nama_lengkap VARCHAR(100) NOT NULL,
  email        VARCHAR(100) NULL UNIQUE,
  role         ENUM('admin','petugas','dokter','pasien') NOT NULL,
  status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  gagal_login  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  blokir_sampai DATETIME NULL,
  last_login   DATETIME NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Dokter (bisa menjadi pengguna)
CREATE TABLE dokter (
  id_dokter    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pengguna  INT UNSIGNED NULL UNIQUE,
  id_poli      INT UNSIGNED NOT NULL,
  nip          VARCHAR(30)  NULL UNIQUE,
  nama_dokter  VARCHAR(100) NOT NULL,
  spesialis    VARCHAR(100) NULL,
  no_str       VARCHAR(50)  NULL,
  no_hp        VARCHAR(20)  NULL,
  status       ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_dokter_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_dokter_poli FOREIGN KEY (id_poli) REFERENCES poliklinik(id_poli)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 4. Asuransi
CREATE TABLE asuransi (
  id_asuransi    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kode_asuransi  VARCHAR(20)  NOT NULL UNIQUE,
  nama_asuransi  VARCHAR(100) NOT NULL,
  jenis          ENUM('pemerintah','swasta') NOT NULL DEFAULT 'swasta',
  kontak         VARCHAR(50)  NULL,
  status         ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 5. Pasien (bisa menjadi pengguna aplikasi; asuransi opsional)
CREATE TABLE pasien (
  id_pasien      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pengguna    INT UNSIGNED NULL UNIQUE,
  id_asuransi    INT UNSIGNED NULL,
  no_rm          VARCHAR(20)  NOT NULL UNIQUE,       -- RM-000001
  nik            CHAR(16)     NULL UNIQUE,
  nama_pasien    VARCHAR(100) NOT NULL,
  jenis_kelamin  ENUM('L','P') NOT NULL,
  tempat_lahir   VARCHAR(50)  NULL,
  tanggal_lahir  DATE         NOT NULL,
  alamat         TEXT         NULL,
  no_hp          VARCHAR(20)  NULL,
  no_kartu_asuransi VARCHAR(30) NULL,                -- wajib bila id_asuransi terisi
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pasien_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_pasien_asuransi FOREIGN KEY (id_asuransi) REFERENCES asuransi(id_asuransi)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 6. Kunjungan (pendaftaran berobat) — dasar laporan
CREATE TABLE kunjungan (
  id_kunjungan    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  no_kunjungan    VARCHAR(25)  NOT NULL UNIQUE,      -- KJ-20261124-0001
  id_pasien       INT UNSIGNED NOT NULL,
  id_dokter       INT UNSIGNED NOT NULL,
  id_poli         INT UNSIGNED NOT NULL,
  tanggal_kunjungan DATE       NOT NULL,
  keluhan         TEXT         NULL,
  diagnosa        TEXT         NULL,
  cara_bayar      ENUM('umum','asuransi') NOT NULL DEFAULT 'umum',
  status          ENUM('menunggu','diperiksa','selesai','batal') NOT NULL DEFAULT 'menunggu',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_kj_pasien FOREIGN KEY (id_pasien) REFERENCES pasien(id_pasien)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_kj_dokter FOREIGN KEY (id_dokter) REFERENCES dokter(id_dokter)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_kj_poli FOREIGN KEY (id_poli) REFERENCES poliklinik(id_poli)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 7. Token sesi API
CREATE TABLE api_token (
  id_token     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pengguna  INT UNSIGNED NOT NULL,
  token_hash   CHAR(64) NOT NULL UNIQUE,             -- SHA-256 dari token mentah
  ip_address   VARCHAR(45) NULL,
  expires_at   DATETIME NOT NULL,
  revoked      TINYINT(1) NOT NULL DEFAULT 0,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_token_pengguna FOREIGN KEY (id_pengguna) REFERENCES pengguna(id_pengguna)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- 8. Audit log
CREATE TABLE audit_log (
  id_log       BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_pengguna  INT UNSIGNED NULL,
  aksi         VARCHAR(50)  NOT NULL,   -- LOGIN, LOGIN_GAGAL, CREATE, UPDATE, DELETE, AKSES_DITOLAK
  modul        VARCHAR(50)  NULL,
  detail       TEXT NULL,
  ip_address   VARCHAR(45) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_log_pengguna (id_pengguna),
  INDEX idx_log_waktu (created_at)
) ENGINE=InnoDB;

CREATE INDEX idx_kj_tanggal ON kunjungan (tanggal_kunjungan);
CREATE INDEX idx_kj_poli    ON kunjungan (id_poli);
CREATE INDEX idx_kj_dokter  ON kunjungan (id_dokter);

-- Data awal non-user
INSERT INTO poliklinik (kode_poli, nama_poli, lokasi) VALUES
 ('UMUM','Poli Umum','Gedung A Lt.1'),
 ('GIGI','Poli Gigi','Gedung A Lt.1'),
 ('ANAK','Poli Anak','Gedung B Lt.1'),
 ('JANT','Poli Jantung','Gedung B Lt.2'),
 ('MATA','Poli Mata','Gedung C Lt.1');

INSERT INTO asuransi (kode_asuransi, nama_asuransi, jenis, kontak) VALUES
 ('BPJS','BPJS Kesehatan','pemerintah','165'),
 ('PRUD','Prudential','swasta','1500085'),
 ('ALLZ','Allianz','swasta','1500136');
```

**Relasi:** `pengguna 1—0..1 dokter`, `pengguna 1—0..1 pasien`, `poliklinik 1—N dokter`, `asuransi 1—N pasien`, `pasien/dokter/poliklinik 1—N kunjungan`.

**`sql/seed_users.php`** (jalan sekali, browser atau CLI): buat `admin`, `petugas1`, 2 akun `dokter` (+ baris `dokter`), 2 akun `pasien` (+ baris `pasien`, salah satunya ber-asuransi BPJS), plus beberapa `kunjungan` contoh agar laporan ada isinya. Password dibuat dengan `password_hash()` (jangan hard-code hash di SQL); password uji dicantumkan di README.

---

## 5. Kontrak Web Service (Soal 3)

### 5.1 Konvensi
- Base URL: `http://<IP-SERVER-B>/rs_service/api/v1/`
- Request: JSON (`Content-Type: application/json`); filter lewat query string. Respons selalu `application/json; charset=utf-8`.
- Metode: `GET` baca, `POST` buat, `PUT` ubah, `DELETE` hapus. Dukung `X-HTTP-Method-Override` bila PUT/DELETE bermasalah di XAMPP.
- ID resource lewat query: `pasien.php?id=12`.
- **Header wajib di semua endpoint:** `X-API-KEY: <kunci aplikasi pemanggil>` (identitas **pemanggil layanan**).
- **Header setelah login:** `Authorization: Bearer <token>` (identitas & role **pengguna**).
- Endpoint yang hanya butuh API key: `auth/login.php`, `auth/register.php`, serta `GET poliklinik.php` dan `GET dokter.php` (untuk landing page publik; keduanya hanya mengembalikan kolom non-sensitif bila tanpa token).

> API key/token = identitas pemanggil; RBAC = kewenangan pengguna. Keduanya wajib dan tidak sama.

### 5.2 Format respons
Sukses:
```json
{ "status": "success", "message": "OK", "data": {}, "meta": { "page": 1, "limit": 10, "total": 57 } }
```
Gagal:
```json
{ "status": "error", "message": "Validasi gagal", "errors": { "nik": "NIK harus 16 digit" } }
```
Kode HTTP: `200` OK · `201` dibuat · `400` request rusak · `401` API key/token salah/kedaluwarsa · `403` role tidak berhak / bukan pemilik data · `404` tidak ada · `405` metode tidak didukung · `409` konflik (duplikat / masih dipakai relasi) · `422` validasi gagal · `500` error server (pesan generik; detail ke log).

### 5.3 Endpoint

**Autentikasi**

| Metode | Endpoint | Fungsi | Akses |
|---|---|---|---|
| POST | `auth/login.php` | `{username,password}` → `{token, expires_at, user:{id_pengguna, username, nama_lengkap, role, id_dokter?, id_pasien?}}`. Update `last_login`, catat audit. Setelah N kali gagal berturut-turut → blokir sementara (kolom `gagal_login`, `blokir_sampai`). Pesan gagal selalu generik. | API key |
| POST | `auth/register.php` | Registrasi mandiri pasien: `pengguna(role=pasien)` + `pasien` dalam **satu transaksi** | API key |
| POST | `auth/logout.php` | Set `revoked=1` | Token |
| GET | `auth/me.php` | Data user dari token | Token |
| POST | `auth/ganti_password.php` | `{password_lama,password_baru}` (min. 8 karakter) | Token |

**CRUD** — semua mendukung `GET` list (`?q=&page=&limit=`, limit maks 100), `GET ?id=`, `POST`, `PUT ?id=`, `DELETE ?id=`.

| Endpoint | Field POST/PUT | Aturan khusus |
|---|---|---|
| `poliklinik.php` | kode_poli, nama_poli, lokasi, status | `kode_poli` unik; DELETE → 409 bila dipakai dokter/kunjungan |
| `pengguna.php` | username, password, nama_lengkap, email, role, status | Hanya admin; password selalu di-hash; **respons tidak pernah memuat password**; admin tak boleh menghapus/menonaktifkan akun sendiri |
| `dokter.php` | id_pengguna?, id_poli, nip, nama_dokter, spesialis, no_str, no_hp, status | `id_pengguna` harus akun role `dokter` dan belum terpakai |
| `pasien.php` | id_pengguna?, id_asuransi?, nik, nama_pasien, jenis_kelamin, tempat_lahir, tanggal_lahir, alamat, no_hp, no_kartu_asuransi | `no_rm` digenerate server; NIK 16 digit; `no_kartu_asuransi` wajib bila `id_asuransi` ada; pasien hanya akses datanya sendiri |
| `asuransi.php` | kode_asuransi, nama_asuransi, jenis, kontak, status | DELETE → 409 bila dipakai pasien |
| `kunjungan.php` | id_pasien, id_dokter, id_poli, tanggal_kunjungan, keluhan, diagnosa, cara_bayar, status | `no_kunjungan` digenerate server; dokter harus di poli yang dipilih; `cara_bayar='asuransi'` hanya bila pasien punya asuransi; tanggal tidak lampau saat daftar; transisi status `menunggu→diperiksa→selesai` (atau `batal`) |

Respons list `kunjungan`, `dokter`, `pasien` sebaiknya sudah **JOIN** (`nama_pasien`, `nama_dokter`, `nama_poli`, `nama_asuransi`) agar Server A tidak perlu banyak request.

**Laporan** (GET)

| Endpoint | Parameter | Isi |
|---|---|---|
| `laporan/ringkasan.php` | — | total pasien, dokter, poliklinik, kunjungan hari ini & bulan ini, pasien ber-asuransi vs umum (disesuaikan role: dokter hanya miliknya) |
| `laporan/kunjungan.php` | `dari`, `sampai`, `id_poli?`, `id_dokter?`, `status?` | daftar kunjungan periode + subtotal per poli |
| `laporan/pasien_asuransi.php` | `id_asuransi?` | jumlah & daftar pasien per asuransi (termasuk "tanpa asuransi") |
| `laporan/dokter_poli.php` | `id_poli?` | jumlah dokter per poli + jumlah kunjungan per dokter |

---

## 6. Penegakan RBAC di Server B

Role **diambil dari token di DB**, bukan dari body/query/header yang dikirim Server A. Setiap endpoint memanggil `require_role()` dan, bila perlu, cek kepemilikan data. Pelanggaran → `403` + catat `AKSES_DITOLAK` di `audit_log`.

| Endpoint | admin | petugas | dokter | pasien |
|---|---|---|---|---|
| poliklinik | CRUD | R | R | R |
| pengguna | CRUD | — | — | — |
| dokter | CRUD | R | R, U (profil sendiri) | R |
| pasien | CRUD | C, R, U | R (pasien yang pernah ditangani) | R, U (**hanya data sendiri**) |
| asuransi | CRUD | R | R | R |
| kunjungan | CRUD | C, R, U | R (miliknya), U (diagnosa/status miliknya) | C (daftar), R (**hanya miliknya**) |
| laporan/ringkasan | ✔ | ✔ | ✔ (miliknya) | ✔ (miliknya) |
| laporan/kunjungan | ✔ | ✔ | ✔ (miliknya) | — |
| laporan/pasien_asuransi | ✔ | ✔ | — | — |
| laporan/dokter_poli | ✔ | — | — | — |

---

## 7. Keamanan Server B (wajib)

- API key di `config.php`, dibandingkan dengan `hash_equals()`; tidak ada/salah → `401`. Satu key per aplikasi pemanggil.
- Token `bin2hex(random_bytes(32))`; **DB hanya menyimpan SHA-256-nya**; cek `revoked=0` dan `expires_at` belum lewat (TTL dari config).
- `password_hash(PASSWORD_DEFAULT)` / `password_verify()`; batas percobaan login & blokir sementara.
- Semua query **prepared statements**; validasi tipe/panjang/enum/tanggal di server; kolom yang boleh diubah di-whitelist (hindari mass-assignment, mis. `role`).
- Transaksi (`begin_transaction`) untuk operasi multi-tabel (register, buat akun+dokter/pasien).
- `display_errors` mati di respons; detail error hanya ke `logs/error.log`.
- `audit_log`: login sukses/gagal, create/update/delete, akses ditolak.
- Akun MySQL khusus `rs_service_user` (privilege `SELECT, INSERT, UPDATE, DELETE` pada `db_rumah_sakit.*`), bukan `root`. MySQL bind ke `localhost` / tidak dibuka ke jaringan.
- Firewall: port 80/443 hanya dari IP Server A; port 3306 tertutup.
- Folder `config/`, `core/`, `logs/`, `sql/` dilindungi `.htaccess` (`Require all denied`) agar tak bisa diakses via URL.
- Tidak perlu CORS (pemanggil adalah PHP server-side).

---

## 8. Rencana Pengerjaan (urutan untuk AI di CLI)

Uji satu layanan sebelum lanjut ke berikutnya; tampilkan perintah curl setelah tiap tahap.

1. Buat struktur folder, `config.example.php`, `.htaccess` pelindung.
2. `sql/rs_database.sql` → impor → `seed_users.php`; verifikasi relasi di phpMyAdmin.
3. `core/*` (db, response, request, validator, logger, auth).
4. `auth/*` (login → me → logout → register → ganti_password).
5. CRUD berurutan: `poliklinik` → `asuransi` → `pengguna` → `dokter` → `pasien` → `kunjungan` (GET & POST dulu, lalu PUT/DELETE).
6. `laporan/*`.
7. `README.md` (setup + contoh curl + akun uji) dan dokumentasi endpoint (tabel §5.3 sudah cukup).

---

## 9. Skenario Uji (curl/Postman)

Contoh:
```bash
# login
curl -s -X POST http://192.168.7.101/rs_service/api/v1/auth/login.php \
  -H "X-API-KEY: <KEY>" -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"<PASSWORD_UJI>"}'

# list pasien
curl -s "http://192.168.7.101/rs_service/api/v1/pasien.php?page=1&limit=10" \
  -H "X-API-KEY: <KEY>" -H "Authorization: Bearer <TOKEN>"
```

| # | Uji | Harapan |
|---|---|---|
| 1 | Tanpa `X-API-KEY` | 401 |
| 2 | Login benar / salah | token + user / 401 pesan generik |
| 3 | 5× login salah berturut-turut | akun diblokir sementara |
| 4 | Token pasien memanggil `pengguna.php` | 403 + tercatat di audit_log |
| 5 | Pasien A meminta `pasien.php?id=<pasien B>` | 403 |
| 6 | Petugas `DELETE pasien.php?id=1` | 403 |
| 7 | DELETE poliklinik yang dipakai dokter | 409 |
| 8 | POST pasien NIK 10 digit | 422 + error per field |
| 9 | POST kunjungan `cara_bayar=asuransi` untuk pasien tanpa asuransi | 422 |
| 10 | Token kedaluwarsa / sudah logout | 401 |
| 11 | `' OR '1'='1` pada login & parameter `q` | tidak ada efek |
| 12 | Akses `http://.../rs_service/config/config.php` | ditolak (403/404) |
| 13 | `laporan/kunjungan.php` periode seed | total sesuai data seed |

**Definition of Done:** semua uji lulus; semua tabel punya CRUD API sesuai matriks §6; 4 laporan berfungsi; README tersedia; tidak ada kredensial hard-code.

---

## 10. Pemetaan ke Laporan Praktikum

| Soal | Isi untuk Server B |
|---|---|
| **1** | IP/nama folder Server B + screenshot: `ipconfig`/`ip a`, struktur folder `rs_service`, phpMyAdmin, contoh respons JSON di browser/Postman |
| **2** | Kode SQL §4 + screenshot struktur tabel + ERD |
| **3** | Source code seluruh `rs_service/api/v1/**` + screenshot uji curl/Postman (request, response, status code) |

Checklist screenshot Server B: IP · folder · daftar tabel · struktur tiap tabel · ERD · login sukses/gagal · 401 · 403 · 409 · 422 · GET list tiap modul · POST/PUT/DELETE · tiga laporan.

---

## 11. Catatan untuk AI Pelaksana
- Kerjakan **hanya** `rs_service/`. Jangan membuat UI/HTML di sini.
- PHP Native + MySQLi saja; tanpa framework/dependensi tambahan.
- Jangan hard-code password/API key; baca dari config.
- Jika informasi kurang (IP final, kredensial MySQL, role tambahan), **tanyakan dulu** sebelum membuat kode.
- Jaga kontrak §5 tetap konsisten dengan PRD Server A.
