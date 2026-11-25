# PRD Server A — Aplikasi & UI (Service Consumer)
## Sistem Informasi Rumah Sakit Berbasis SOA

> File ini **berdiri sendiri** untuk dibaca AI di CLI. Kerjakan **hanya folder `rs_app/`**.
> Pasangan dokumen: `PRD_Server_B_Database_WebService.md` (penyedia layanan). Kontrak API di §4 adalah salinan dari PRD Server B; jika Server B berubah, sesuaikan di sini.
> Mata Kuliah Web Semantik — Teknik Informatika, Universitas Muhammadiyah Bengkulu. Cakupan soal: **Soal 1 (bagian Server A) dan Soal 4**.

---

## 1. Peran & Konteks

| | **Server A (file ini)** | Server B (bukan bagian file ini) |
|---|---|---|
| Peran | **Service Consumer: semua UI untuk user, login/session, RBAC, CRUD antarmuka, laporan tampilan** | Service Provider: MySQL + REST API (JSON) |
| Contoh IP | **`192.168.7.100`** | `192.168.7.101` |
| Folder XAMPP | **`htdocs/rs_app`** | `htdocs/rs_service` |

> ⚠️ Pada slide PDF dosen, penamaan A dan B terbalik (A = API+DB, B = aplikasi). **Soal praktikum ini: Server A = UI, Server B = basisdata + web service.** Ikuti soal.

**Aturan emas:** Server A **tidak menyimpan kredensial database dan tidak menjalankan query SQL** (tidak boleh ada `koneksi.php` / `mysqli_connect` di `rs_app`). Semua data diambil dan diubah lewat HTTP + JSON ke Server B memakai cURL.

**Mode deploy:** (1) dua mesin/VM dengan IP berbeda (ideal); (2) satu mesin XAMPP dengan dua folder (simulasi) — tetap panggil API lewat URL `http://localhost/rs_service/api/v1/` dengan cURL, bukan `include` file.

---

## 2. Tujuan & Non-Tujuan

**Tujuan:** membuat UI lengkap — landing page → login → dashboard → modul-modul berbasis RBAC — yang seluruh datanya berasal dari web service Server B, dengan keamanan sisi aplikasi (session, CSRF, escape output) yang baik.

**Non-tujuan:** akses DB langsung, pembuatan API, RDF/ontologi/SPARQL, framework besar.

---

## 3. Tech Stack & Struktur Folder

- PHP 8.x native (boleh Python bila dosen mengizinkan; PRD ini menggunakan PHP), Apache/XAMPP.
- HTML + Bootstrap 5 (CDN boleh) + sedikit JS. cURL untuk memanggil API.
- Setiap file diberi komentar tujuan singkat. `config/app.php` tidak di-commit; sediakan `app.example.php`.

```
rs_app/
├── config/
│   ├── app.example.php         # API_BASE_URL, API_KEY, nama aplikasi
│   └── app.php                 # tidak di-commit
├── core/
│   ├── api_client.php          # api_get/api_post/api_put/api_delete($path,$data=[]) via cURL
│   ├── session.php             # session_start aman, timeout idle, regenerate id
│   ├── csrf.php                # csrf_token(), csrf_field(), csrf_verify()
│   ├── rbac.php                # can($modul,$aksi), require_login(), require_role([...]), menu_untuk_role()
│   └── helpers.php             # e(), redirect(), flash(), paginasi(), format_tanggal()
├── partials/                   # header.php, sidebar.php, topbar.php, footer.php
├── assets/                     # css/, js/, img/
├── index.php                   # LANDING PAGE (publik)
├── login.php   register.php   logout.php
├── dashboard.php
└── modules/
    ├── poliklinik/   index.php, form.php, simpan.php, hapus.php
    ├── dokter/       index.php, form.php, simpan.php, hapus.php
    ├── pasien/       index.php, form.php, simpan.php, hapus.php
    ├── asuransi/     index.php, form.php, simpan.php, hapus.php
    ├── kunjungan/    index.php, form.php, simpan.php, hapus.php, daftar.php (pendaftaran oleh pasien), periksa.php (isi diagnosa oleh dokter)
    ├── pengguna/     index.php, form.php, simpan.php, hapus.php (khusus admin)
    ├── laporan/      kunjungan.php, pasien_asuransi.php, dokter_poli.php
    └── profil/       index.php, ganti_password.php
```

### config/app.example.php
```php
<?php
return [
  'app_name'      => 'RS Sehat Sentosa',
  'api_base_url'  => 'http://192.168.7.101/rs_service/api/v1/',   // atau http://localhost/rs_service/api/v1/
  'api_key'       => 'GANTI_SAMA_DENGAN_KEY_rs_app_DI_SERVER_B',
  'api_timeout'   => 10,
  'session_idle_minutes' => 30,
];
```

---

## 4. Kontrak Web Service yang Dikonsumsi (dari Server B)

### 4.1 Konvensi
- Base URL dari `config/app.php`. Request/response JSON. Metode: `GET` baca, `POST` buat, `PUT` ubah, `DELETE` hapus. ID lewat query: `pasien.php?id=12`.
- **Setiap request** mengirim header `X-API-KEY` (identitas Server A sebagai pemanggil) dan, setelah login, `Authorization: Bearer <token>` (identitas pengguna).
- Respons sukses: `{"status":"success","message":"OK","data":…,"meta":{"page","limit","total"}}`.
- Respons gagal: `{"status":"error","message":"…","errors":{"field":"pesan"}}`.
- Kode HTTP: `200/201` sukses · `401` token/key salah atau kedaluwarsa · `403` tidak berhak · `404` tidak ada · `409` konflik/masih dipakai · `422` validasi gagal · `500` error server.

### 4.2 Daftar endpoint

| Endpoint | Metode | Dipakai oleh halaman |
|---|---|---|
| `auth/login.php` → `{token, expires_at, user:{id_pengguna, username, nama_lengkap, role, id_dokter?, id_pasien?}}` | POST | login.php |
| `auth/register.php` | POST | register.php |
| `auth/logout.php` | POST | logout.php |
| `auth/me.php` | GET | profil |
| `auth/ganti_password.php` `{password_lama,password_baru}` | POST | profil/ganti_password.php |
| `poliklinik.php` | GET/POST/PUT/DELETE | landing (GET publik), modul poliklinik, dropdown |
| `dokter.php` | GET/POST/PUT/DELETE | landing (GET publik), modul dokter, dropdown |
| `pasien.php` | GET/POST/PUT/DELETE | modul pasien, dropdown |
| `asuransi.php` | GET/POST/PUT/DELETE | modul asuransi, dropdown |
| `kunjungan.php` | GET/POST/PUT/DELETE | modul kunjungan, daftar, periksa |
| `pengguna.php` | GET/POST/PUT/DELETE | modul pengguna (admin) |
| `laporan/ringkasan.php` | GET | dashboard |
| `laporan/kunjungan.php?dari=&sampai=&id_poli=&id_dokter=&status=` | GET | laporan kunjungan |
| `laporan/pasien_asuransi.php?id_asuransi=` | GET | laporan pasien-asuransi |
| `laporan/dokter_poli.php?id_poli=` | GET | laporan dokter-poli |

Semua `GET` list menerima `?q=&page=&limit=`.

### 4.3 Field form per modul (untuk validasi sisi A sebelum kirim)

| Modul | Field | Catatan validasi |
|---|---|---|
| Poliklinik | kode_poli*, nama_poli*, lokasi, status | kode maks 10 karakter |
| Pengguna | username*, password* (saat tambah), nama_lengkap*, email, role*, status | role ∈ admin/petugas/dokter/pasien; password min. 8 |
| Dokter | id_pengguna (dropdown akun role dokter), id_poli*, nip, nama_dokter*, spesialis, no_str, no_hp, status | |
| Pasien | id_asuransi (dropdown), nik (16 digit), nama_pasien*, jenis_kelamin* (L/P), tempat_lahir, tanggal_lahir*, alamat, no_hp, no_kartu_asuransi | no_kartu wajib bila asuransi dipilih; `no_rm` digenerate server (tampilkan saja) |
| Asuransi | kode_asuransi*, nama_asuransi*, jenis*, kontak, status | |
| Kunjungan | id_pasien*, id_poli*, id_dokter* (difilter sesuai poli), tanggal_kunjungan*, keluhan, cara_bayar*, (dokter: diagnosa, status) | `asuransi` hanya bila pasien punya asuransi; tanggal tidak lampau; `no_kunjungan` digenerate server |

(*) wajib. Pesan error dari field `errors` milik Server B ditampilkan di bawah input terkait.

---

## 5. Pengguna & RBAC (Server A)

| Role | Deskripsi |
|---|---|
| `admin` | Kelola seluruh master data & pengguna, semua laporan |
| `petugas` | Pendaftaran: kelola pasien & kunjungan |
| `dokter` | Lihat antrean/kunjungan miliknya, isi diagnosa |
| `pasien` | Mendaftar sendiri, lihat/ubah profil sendiri, daftar berobat, lihat riwayat sendiri |

Dokter dan pasien dapat menjadi pengguna: akunnya ada di `pengguna`, dihubungkan lewat `id_pengguna` pada data dokter/pasien (data `id_dokter`/`id_pasien` dikembalikan saat login dan disimpan di session).

### Matriks menu & aksi (C=Create, R=Read, U=Update, D=Delete)

| Modul | admin | petugas | dokter | pasien |
|---|---|---|---|---|
| Dashboard | ✔ | ✔ | ✔ | ✔ |
| Poliklinik | CRUD | R | R | R |
| Pengguna | CRUD | — | — | — |
| Dokter | CRUD | R | R, U (profil sendiri) | R |
| Pasien | CRUD | C, R, U | R | R, U (hanya data sendiri) |
| Asuransi | CRUD | R | R | R |
| Kunjungan | CRUD | C, R, U | R (miliknya), U (diagnosa/status) | C (daftar), R (miliknya) |
| Laporan kunjungan | ✔ | ✔ | ✔ (miliknya) | — |
| Laporan pasien-asuransi | ✔ | ✔ | — | — |
| Laporan dokter-poli | ✔ | — | — | — |
| Profil & ganti password | ✔ | ✔ | ✔ | ✔ |

**Penegakan di Server A:** `rbac.php` membangun menu sidebar dari role; tombol Tambah/Ubah/Hapus hanya tampil bila `can($modul,$aksi)`; setiap halaman memanggil `require_login()` dan `require_role([...])` di baris awal (mengetik URL manual tetap diblokir). **Ini hanya lapisan pertama** — Server B tetap memeriksa ulang setiap request. Bila Server B membalas `403`, tampilkan halaman "Akses ditolak".

---

## 6. Kebutuhan Fungsional

### 6.1 Halaman publik
1. **Landing page** (`index.php`): hero rumah sakit, daftar poliklinik & dokter (dari `GET poliklinik.php` dan `GET dokter.php` dengan API key saja), jam layanan, kontak/lokasi, tombol **Login** dan **Daftar Pasien**. Bila Server B mati, bagian data diganti pesan ramah, halaman tetap tampil.
2. **Register** (`register.php`): form pasien baru → `POST auth/register.php` → redirect ke login.
3. **Login** (`login.php`): `POST auth/login.php` → simpan `token`, `role`, `nama_lengkap`, `id_pengguna`, `id_dokter`, `id_pasien`, waktu aktivitas ke `$_SESSION` → `session_regenerate_id(true)` → redirect dashboard. Gagal → tampilkan pesan generik.

### 6.2 Setelah login
4. **Dashboard** (`dashboard.php`): kartu statistik dari `laporan/ringkasan.php`. Admin/petugas: total pasien, dokter, poli, kunjungan hari ini/bulan ini, komposisi umum vs asuransi. Dokter: antrean hari ini. Pasien: kunjungan terakhir + tombol "Daftar Berobat".
5. **Modul CRUD** (poliklinik, dokter, pasien, asuransi, kunjungan, pengguna): halaman index = tabel + pencarian (`q`) + paginasi (`page`,`limit`); form tambah/ubah; simpan (POST ke Server B); hapus dengan konfirmasi + CSRF. Dropdown relasi (poli, dokter, asuransi, pasien) diisi dari endpoint masing-masing. Dropdown dokter pada form kunjungan difilter oleh poli yang dipilih.
6. **Kunjungan khusus:** pasien memakai `daftar.php` (id_pasien otomatis dari session); dokter memakai `periksa.php` untuk mengisi diagnosa dan mengubah status (`menunggu→diperiksa→selesai`).
7. **Laporan:** filter (periode/poli/dokter/asuransi) → tabel hasil + subtotal; tombol **Cetak** (CSS `@media print`); opsional ekspor CSV.
8. **Profil:** lihat/ubah data diri, ganti password.
9. **Logout:** `POST auth/logout.php`, hancurkan session & cookie, redirect ke login.

### 6.3 Perilaku `core/api_client.php`
- Fungsi `api_get/post/put/delete($path,$data=[])` membungkus satu fungsi cURL internal: set `X-API-KEY`, `Authorization: Bearer` (bila ada token di session), `Content-Type: application/json`, timeout dari config, `CURLOPT_RETURNTRANSFER`.
- Selalu mengembalikan struktur seragam: `['ok'=>bool,'http'=>int,'data'=>mixed,'meta'=>array,'message'=>string,'errors'=>array]` — tidak pernah melempar warning PHP ke layar.
- `http=401` pada halaman terautentikasi → hapus session, redirect login dengan flash "Sesi berakhir, silakan login kembali".
- cURL error / `http=0` / respons bukan JSON → `ok=false`, pesan "Layanan data sedang tidak tersedia", **tanpa** stack trace atau URL internal di layar.
- Jika HTTPS dipakai di produksi, verifikasi sertifikat tetap aktif (`CURLOPT_SSL_VERIFYPEER=true`).

---

## 7. Keamanan Server A (wajib)

- Tidak ada kredensial DB di `rs_app`; API key hanya di `config/app.php` (dilindungi `.htaccess`, tidak di-commit).
- Session: `httponly`, `samesite=Lax`, `use_strict_mode`, regenerate ID setelah login, timeout idle 30 menit, hancurkan penuh saat logout.
- CSRF token pada **semua** form POST (termasuk hapus & logout).
- Semua output di-escape `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`); jangan pernah echo data API mentah.
- Validasi input sisi A (tipe, panjang, enum) sebelum dikirim; jangan percaya hidden field untuk `role`/`id_pasien` — nilai kepemilikan diambil dari session.
- Token Bearer hanya di session server-side, tidak ke cookie/JS/URL.
- Folder `config/`, `core/`, `partials/` dilindungi `.htaccess` (`Require all denied`) atau diletakkan di luar webroot bila memungkinkan.
- Header keamanan dasar: `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`.
- Pesan error ke user generik; detail ke log server.

---

## 8. Persyaratan Non-Fungsional
- Berjalan di XAMPP (Windows) dan Apache/PHP Ubuntu tanpa perubahan selain file config.
- UI responsif (Bootstrap), konsisten antar modul, ada flash message sukses/gagal, konfirmasi sebelum hapus.
- Waktu muat halaman list wajar (paginasi; hindari banyak request berulang — manfaatkan data JOIN dari API).
- Kode terbaca: fungsi kecil, nama jelas, komentar tujuan file.

---

## 9. Rencana Pengerjaan (urutan untuk AI di CLI)

Server B idealnya sudah menyediakan endpoint sebelum modul terkait dibuat. Jika belum, kerjakan dulu bagian yang tidak bergantung pada API (core, partials, landing statis) lalu sambungkan.

1. Struktur folder, `app.example.php`, `.htaccess` pelindung.
2. `core/*` (session, csrf, rbac, helpers, **api_client**) — uji `api_client` dengan memanggil `auth/login.php` dan `laporan/ringkasan.php`.
3. `partials/*` + CSS dasar (layout sidebar/topbar).
4. `index.php` (landing), `register.php`, `login.php`, `logout.php`.
5. `dashboard.php` per role.
6. Modul urut: poliklinik → asuransi → pengguna → dokter → pasien → kunjungan (+ daftar & periksa).
7. Modul laporan (3 laporan + cetak) dan profil.
8. Uji RBAC & keamanan (§10), rapikan UI, ambil screenshot.
9. `README.md` (cara konfigurasi API URL/key, akun uji).

---

## 10. Skenario Uji & Kriteria Penerimaan

| # | Uji | Harapan |
|---|---|---|
| 1 | Buka `dashboard.php` tanpa login | redirect ke login |
| 2 | Login salah | pesan generik, tidak ada detail teknis |
| 3 | Login admin / petugas / dokter / pasien | dashboard & menu berbeda sesuai §5 |
| 4 | Pasien mengetik URL `modules/pengguna/index.php` | ditolak / halaman akses ditolak |
| 5 | Pasien mencoba membuka data pasien lain via `?id=` | ditolak (403 dari Server B ditampilkan ramah) |
| 6 | Petugas mencoba URL hapus pasien | ditolak |
| 7 | Submit form tanpa CSRF token / token salah | ditolak |
| 8 | Isi `<script>alert(1)</script>` pada nama pasien | tampil sebagai teks, tidak dieksekusi |
| 9 | Form pasien dengan NIK 10 digit | pesan error di bawah field NIK |
| 10 | Kunjungan `asuransi` untuk pasien tanpa asuransi | ditolak dengan pesan |
| 11 | Matikan Apache Server B lalu buka dashboard & landing | pesan "layanan data tidak tersedia", tanpa error PHP |
| 12 | Hapus poliklinik yang dipakai dokter | pesan "masih digunakan" (dari 409) |
| 13 | Idle > 30 menit lalu klik menu | diminta login ulang |
| 14 | Cari kata `mysqli`/`koneksi` dan kredensial DB di seluruh `rs_app` | tidak ditemukan |
| 15 | Laporan kunjungan, lalu Cetak | tabel rapi, tanpa sidebar saat dicetak |

**Definition of Done:** semua uji lulus; setiap modul memiliki index/form/simpan/hapus sesuai matriks RBAC; tiga laporan berfungsi; landing → login → dashboard → modul berjalan untuk keempat role; README tersedia.

---

## 11. Pemetaan ke Laporan Praktikum

| Soal | Isi untuk Server A |
|---|---|
| **1** | IP/nama folder Server A + screenshot: `ipconfig`/`ip a`, struktur folder `rs_app`, landing page di browser |
| **4** | Source code **setiap file** (core, partials, landing, login, dashboard, tiap modul, laporan, profil) + screenshot tiap halaman per role |

**Format laporan:** kop `LAPORAN PRAKTIKUM SOA` + `NPM`, `NAMA`, `KELAS`; jawaban per soal; diagram arsitektur; kode; tangkapan hasil uji; kesimpulan. Unggah ke classroom mata kuliah.

Checklist screenshot Server A: IP · folder · landing · register · login (gagal & sukses) · dashboard admin/petugas/dokter/pasien · tiap modul (index, form, hapus) · laporan + hasil cetak · akses ditolak · layanan tidak tersedia (Server B mati).

---

## 12. Bahan Jawaban Pertanyaan Analisis (dari slide)

> Pada soal ini **konsumen = Server A**, **penyedia layanan = Server B** (kebalikan slide). Sesuaikan istilah saat menulis laporan.

1. **Mengapa konsumen tidak boleh query langsung ke DB penyedia?** Menjaga pemisahan tanggung jawab, kredensial DB tidak tersebar, validasi/otorisasi terpusat di layanan, dan skema DB dapat berubah tanpa merusak aplikasi (loose coupling).
2. **Dampak jika server layanan mati?** Aplikasi tak dapat membaca/menulis data (single point of failure); UI menampilkan pesan ramah; mitigasi: monitoring, backup, replikasi.
3. **Beda autentikasi API dan RBAC?** API key/token membuktikan *siapa pemanggil/pengguna*; RBAC menentukan *apa yang boleh dilakukan* pengguna itu.
4. **Bagaimana satu API melayani tiga aplikasi?** Kontrak endpoint terdokumentasi + API key berbeda per aplikasi + otorisasi per key → reuse tanpa akses DB langsung.
5. **Risiko jika API tidak memvalidasi akses?** Siapa pun yang tahu URL dapat membaca/mengubah/menghapus data pasien (kebocoran data medis, manipulasi data, SQL injection, tanpa jejak audit).

---

## 13. Catatan untuk AI Pelaksana
- Kerjakan **hanya** `rs_app/`. Jangan membuat koneksi database atau query SQL.
- Ikuti penamaan Server A (UI) / Server B (DB + API) persis seperti soal.
- PHP Native + Bootstrap CDN saja; tanpa framework/dependensi tambahan.
- Jangan hard-code API key/URL di banyak file; baca dari `config/app.php`.
- Jika informasi kurang (IP final, API key, role tambahan, tema/warna rumah sakit), **tanyakan dulu** sebelum membuat kode.
- Setelah tiap tahap §9, jelaskan cara menguji agar hasilnya bisa di-screenshot.
