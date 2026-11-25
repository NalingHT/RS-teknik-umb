<?php
/**
 * menu.php — Halaman Menu Utama & Katalog Layanan Aplikasi.
 * Berdiri sendiri, terpisah dari dashboard analitik, dengan tampilan UI yang ramai, interaktif, namun tetap tertata rapi.
 */
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/api_client.php';
require_once __DIR__ . '/core/rbac.php';

require_login();

$page_title = 'Menu Utama Layanan';
$cfg        = require __DIR__ . '/config/app.php';
$role       = $_SESSION['role'] ?? 'pasien';
$nama_user  = $_SESSION['nama_lengkap'] ?? $_SESSION['username'] ?? 'Pengguna';

// Ambil info ringkas jika API terhubung
$res_ringkasan = api_get('laporan/ringkasan.php');
$stat          = $res_ringkasan['ok'] ? ($res_ringkasan['data'] ?? []) : [];

require __DIR__ . '/partials/header.php';
?>
<div class="app-layout">
  <?php require __DIR__ . '/partials/sidebar.php'; ?>
  <div class="app-content">
    <?php require __DIR__ . '/partials/topbar.php'; ?>
    <main class="app-main">
      <div class="container-fluid">
        <?php render_flash(); ?>

        <!-- Banner Selamat Datang & Pencarian Cepat -->
        <div class="card border-0 shadow-sm mb-4 text-white" style="background: linear-gradient(120deg, #0d6efd, #0b5ed7, #0a58ca); border-radius: 1rem;">
          <div class="card-body p-4">
            <div class="row align-items-center g-3">
              <div class="col-lg-8">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <span class="badge bg-white text-primary px-3 py-1 rounded-pill fw-bold text-uppercase">
                    <i class="bi bi-shield-check me-1"></i> Role: <?= e($role) ?>
                  </span>
                  <span class="badge bg-white bg-opacity-25 text-white px-3 py-1 rounded-pill">
                    <i class="bi bi-building me-1"></i> <?= e($cfg['app_name']) ?>
                  </span>
                </div>
                <h3 class="fw-bold mb-1">Pusat Menu & Layanan Aplikasi</h3>
                <p class="mb-0 text-white-50">Selamat datang, <strong><?= e($nama_user) ?></strong>! Pilih menu atau modul di bawah ini untuk mengakses seluruh fitur sistem rumah sakit.</p>
              </div>
              <div class="col-lg-4 text-lg-end">
                <div class="p-3 bg-white bg-opacity-10 rounded-3 d-inline-block text-start w-100" style="max-width: 300px;">
                  <small class="text-white-50 d-block"><i class="bi bi-clock me-1"></i> Waktu Server Saat Ini</small>
                  <div class="fs-5 fw-bold" id="liveClock"><?= date('H:i:s') ?> WIB</div>
                  <small class="text-white-50"><?= date('l, d F Y') ?></small>
                </div>
              </div>
            </div>

            <!-- Input Filter Pencarian Menu Interaktif -->
            <div class="mt-3 pt-3 border-top border-white border-opacity-25">
              <div class="input-group">
                <span class="input-group-text bg-white border-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="cariMenu" class="form-control border-0 py-2" placeholder="Ketik nama menu, modul, atau layanan untuk memfilter secara instan (contoh: Kunjungan, Dokter, Laporan)...">
                <button class="btn btn-light text-primary fw-semibold px-3" type="button" onclick="document.getElementById('cariMenu').value=''; filterMenu();">Reset</button>
              </div>
            </div>
          </div>
        </div>

        <div class="row g-4">
          <!-- KOLOM KIRI: GRID MENU MODUL TERTATA -->
          <div class="col-xl-8 col-lg-7">

            <!-- KELOMPOK 1: PELAYANAN PASIEN & MEDIS -->
            <div class="kategori-menu mb-4">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold text-primary mb-0"><i class="bi bi-heart-pulse-fill me-2"></i>Pelayanan Pasien & Kunjungan</h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Kategori Utama</span>
              </div>
              <div class="row g-3">
                <!-- Card Kunjungan -->
                <div class="col-md-6 item-menu" data-nama="kunjungan antrean berobat periksa pasien">
                  <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body p-3">
                      <div class="d-flex align-items-start gap-3">
                        <div class="bg-primary text-white rounded-3 p-3 text-center" style="width: 55px; height: 55px;">
                          <i class="bi bi-calendar2-check fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                          <h6 class="fw-bold mb-1">Data Kunjungan</h6>
                          <p class="text-muted small mb-2">Daftar riwayat, antrean pemeriksaan dokter, serta status pelayanan.</p>
                          <div class="d-flex gap-2 flex-wrap">
                            <a href="/rs_app/modules/kunjungan/index.php" class="btn btn-sm btn-outline-primary">Buka Kunjungan &rarr;</a>
                            <?php if (can('kunjungan', 'create') && $role !== 'pasien'): ?>
                              <a href="/rs_app/modules/kunjungan/form.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Tambah</a>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Card Pendaftaran Mandiri Pasien -->
                <?php if ($role === 'pasien'): ?>
                <div class="col-md-6 item-menu" data-nama="daftar berobat poliklinik periksa mandiri">
                  <div class="card h-100 border-0 shadow-sm stat-card border-start border-success border-4">
                    <div class="card-body p-3">
                      <div class="d-flex align-items-start gap-3">
                        <div class="bg-success text-white rounded-3 p-3 text-center" style="width: 55px; height: 55px;">
                          <i class="bi bi-clipboard2-plus fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                          <h6 class="fw-bold mb-1 text-success">Daftar Berobat Mandiri</h6>
                          <p class="text-muted small mb-2">Formulir pendaftaran poliklinik secara online tanpa antre lama di loket.</p>
                          <a href="/rs_app/modules/kunjungan/daftar.php" class="btn btn-sm btn-success px-3">
                            <i class="bi bi-send me-1"></i> Daftar Sekarang
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

                <!-- Card Pasien -->
                <div class="col-md-6 item-menu" data-nama="pasien rekam medis identitas rm">
                  <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body p-3">
                      <div class="d-flex align-items-start gap-3">
                        <div class="bg-info text-white rounded-3 p-3 text-center" style="width: 55px; height: 55px;">
                          <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                          <h6 class="fw-bold mb-1">Master Data Pasien</h6>
                          <p class="text-muted small mb-2">Basis data rekam medis, NIK 16 digit, alamat, dan kepemilikan asuransi.</p>
                          <div class="d-flex gap-2">
                            <a href="/rs_app/modules/pasien/index.php" class="btn btn-sm btn-outline-info">Data Pasien &rarr;</a>
                            <?php if (can('pasien', 'create')): ?>
                              <a href="/rs_app/modules/pasien/form.php" class="btn btn-sm btn-info text-white"><i class="bi bi-plus-lg"></i> Baru</a>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Card Poliklinik -->
                <div class="col-md-6 item-menu" data-nama="poliklinik poli spesialis lokasi klinik">
                  <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body p-3">
                      <div class="d-flex align-items-start gap-3">
                        <div class="bg-primary bg-opacity-75 text-white rounded-3 p-3 text-center" style="width: 55px; height: 55px;">
                          <i class="bi bi-hospital fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                          <h6 class="fw-bold mb-1">Daftar Poliklinik</h6>
                          <p class="text-muted small mb-2">Master layanan spesialisasi klinik, kode poli, lokasi ruangan, dan status aktif.</p>
                          <div class="d-flex gap-2">
                            <a href="/rs_app/modules/poliklinik/index.php" class="btn btn-sm btn-outline-primary">Lihat Poli &rarr;</a>
                            <?php if (can('poliklinik', 'create')): ?>
                              <a href="/rs_app/modules/poliklinik/form.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Tambah</a>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

              </div>
            </div>

            <!-- KELOMPOK 2: DOKTER & MITRA ASURANSI -->
            <div class="kategori-menu mb-4">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold text-success mb-0"><i class="bi bi-diagram-3-fill me-2"></i>Tenaga Medis & Jaminan Kesehatan</h5>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Fasilitas & Relasi</span>
              </div>
              <div class="row g-3">
                <!-- Card Dokter -->
                <div class="col-md-6 item-menu" data-nama="dokter spesialis nip str jadwal tenaga medis">
                  <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body p-3">
                      <div class="d-flex align-items-start gap-3">
                        <div class="bg-success text-white rounded-3 p-3 text-center" style="width: 55px; height: 55px;">
                          <i class="bi bi-person-badge fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                          <h6 class="fw-bold mb-1">Direktori Dokter</h6>
                          <p class="text-muted small mb-2">Informasi dokter spesialis, NIP, STR, poli penugasan, serta kontak.</p>
                          <div class="d-flex gap-2">
                            <a href="/rs_app/modules/dokter/index.php" class="btn btn-sm btn-outline-success">Buka Dokter &rarr;</a>
                            <?php if (can('dokter', 'create')): ?>
                              <a href="/rs_app/modules/dokter/form.php" class="btn btn-sm btn-success"><i class="bi bi-plus-lg"></i> Baru</a>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Card Asuransi -->
                <div class="col-md-6 item-menu" data-nama="asuransi bpjs swasta perusahaan klaim penjamin">
                  <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body p-3">
                      <div class="d-flex align-items-start gap-3">
                        <div class="bg-warning text-dark rounded-3 p-3 text-center" style="width: 55px; height: 55px;">
                          <i class="bi bi-shield-check fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                          <h6 class="fw-bold mb-1">Mitra Asuransi & BPJS</h6>
                          <p class="text-muted small mb-2">Daftar rekanan penjamin biaya pengobatan, BPJS, asuransi swasta/perusahaan.</p>
                          <div class="d-flex gap-2">
                            <a href="/rs_app/modules/asuransi/index.php" class="btn btn-sm btn-outline-warning text-dark">Buka Asuransi &rarr;</a>
                            <?php if (can('asuransi', 'create')): ?>
                              <a href="/rs_app/modules/asuransi/form.php" class="btn btn-sm btn-warning text-dark"><i class="bi bi-plus-lg"></i> Tambah</a>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- KELOMPOK 3: LAPORAN & REKAPITULASI -->
            <?php if (in_array($role, ['admin', 'petugas', 'dokter'])): ?>
            <div class="kategori-menu mb-4">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-bar-graph-fill me-2 text-danger"></i>Pusat Laporan & Rekapitulasi</h5>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Cetak & Analisis</span>
              </div>
              <div class="row g-3">
                <div class="col-md-4 item-menu" data-nama="laporan kunjungan periode tanggal cetak pdf">
                  <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body p-3 text-center">
                      <div class="text-danger mb-2"><i class="bi bi-calendar-range fs-1"></i></div>
                      <h6 class="fw-bold mb-1">Laporan Kunjungan</h6>
                      <p class="text-muted small mb-3">Filter periode, poli, dokter, dan cetak rekap kunjungan.</p>
                      <a href="/rs_app/modules/laporan/kunjungan.php" class="btn btn-sm btn-outline-danger w-100">Buka Laporan</a>
                    </div>
                  </div>
                </div>

                <?php if (in_array($role, ['admin', 'petugas'])): ?>
                <div class="col-md-4 item-menu" data-nama="laporan pasien asuransi bpjs kepesertaan">
                  <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body p-3 text-center">
                      <div class="text-primary mb-2"><i class="bi bi-person-vcard fs-1"></i></div>
                      <h6 class="fw-bold mb-1">Pasien Asuransi</h6>
                      <p class="text-muted small mb-3">Rekapitulasi pasien berdasarkan mitra jaminan asuransi.</p>
                      <a href="/rs_app/modules/laporan/pasien_asuransi.php" class="btn btn-sm btn-outline-primary w-100">Buka Laporan</a>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

                <?php if ($role === 'admin'): ?>
                <div class="col-md-4 item-menu" data-nama="laporan dokter poli spesialis penempatan">
                  <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body p-3 text-center">
                      <div class="text-success mb-2"><i class="bi bi-diagram-3 fs-1"></i></div>
                      <h6 class="fw-bold mb-1">Dokter per Poli</h6>
                      <p class="text-muted small mb-3">Distribusi dokter aktif pada setiap poliklinik rumah sakit.</p>
                      <a href="/rs_app/modules/laporan/dokter_poli.php" class="btn btn-sm btn-outline-success w-100">Buka Laporan</a>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
            <?php endif; ?>

            <!-- KELOMPOK 4: PENGATURAN & AKUN SISTEM -->
            <div class="kategori-menu mb-4">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="fw-bold text-secondary mb-0"><i class="bi bi-gear-wide-connected me-2"></i>Pengaturan Akun & Keamanan</h5>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Keamanan</span>
              </div>
              <div class="row g-3">
                <?php if ($role === 'admin'): ?>
                <div class="col-md-6 item-menu" data-nama="pengguna user akun role admin petugas dokter">
                  <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body p-3">
                      <div class="d-flex align-items-start gap-3">
                        <div class="bg-dark text-white rounded-3 p-3 text-center" style="width: 55px; height: 55px;">
                          <i class="bi bi-person-gear fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                          <h6 class="fw-bold mb-1">Manajemen Pengguna</h6>
                          <p class="text-muted small mb-2">Kelola akun, hak akses role (admin, petugas, dokter, pasien), dan status.</p>
                          <a href="/rs_app/modules/pengguna/index.php" class="btn btn-sm btn-outline-dark">Kelola Akun &rarr;</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

                <div class="col-md-6 item-menu" data-nama="profil ganti password kata sandi akun saya">
                  <div class="card h-100 border-0 shadow-sm stat-card">
                    <div class="card-body p-3">
                      <div class="d-flex align-items-start gap-3">
                        <div class="bg-secondary text-white rounded-3 p-3 text-center" style="width: 55px; height: 55px;">
                          <i class="bi bi-person-circle fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                          <h6 class="fw-bold mb-1">Profil & Keamanan Akun</h6>
                          <p class="text-muted small mb-2">Lihat informasi akun Anda atau perbarui kata sandi secara berkala.</p>
                          <div class="d-flex gap-2">
                            <a href="/rs_app/modules/profil/index.php" class="btn btn-sm btn-outline-secondary">Profil Saya</a>
                            <a href="/rs_app/modules/profil/ganti_password.php" class="btn btn-sm btn-secondary">Ganti Password</a>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          </div>

          <!-- KOLOM KANAN: WIDGET INFORMASI, PENGUMUMAN & JADWAL LAYANAN -->
          <div class="col-xl-4 col-lg-5">

            <!-- Widget Tombol Cepat (Quick Actions) -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-lightning-charge-fill text-warning me-2"></i>Aksi Cepat
              </div>
              <div class="card-body p-3">
                <div class="d-grid gap-2">
                  <a href="/rs_app/dashboard.php" class="btn btn-outline-primary text-start d-flex align-items-center justify-content-between p-2">
                    <span><i class="bi bi-speedometer2 me-2"></i> Buka Dashboard Analitik</span>
                    <i class="bi bi-chevron-right text-muted small"></i>
                  </a>
                  <?php if ($role === 'pasien'): ?>
                    <a href="/rs_app/modules/kunjungan/daftar.php" class="btn btn-success text-start d-flex align-items-center justify-content-between p-2">
                      <span><i class="bi bi-calendar2-plus me-2"></i> Daftar Kunjungan Pasien</span>
                      <i class="bi bi-arrow-right"></i>
                    </a>
                  <?php endif; ?>
                  <?php if (can('pasien', 'create')): ?>
                    <a href="/rs_app/modules/pasien/form.php" class="btn btn-outline-secondary text-start d-flex align-items-center justify-content-between p-2">
                      <span><i class="bi bi-person-plus me-2"></i> Daftarkan Pasien Baru</span>
                      <i class="bi bi-chevron-right text-muted small"></i>
                    </a>
                  <?php endif; ?>
                  <?php if (in_array($role, ['admin', 'petugas', 'dokter'])): ?>
                    <a href="/rs_app/modules/laporan/kunjungan.php" class="btn btn-outline-danger text-start d-flex align-items-center justify-content-between p-2">
                      <span><i class="bi bi-printer me-2"></i> Cetak Laporan Kunjungan</span>
                      <i class="bi bi-chevron-right text-muted small"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Widget Alur Pelayanan Pasien (SOP Rumah Sakit) -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-bezier2 text-primary me-2"></i>Alur Pelayanan Pasien (SOP)
              </div>
              <div class="card-body p-3">
                <div class="d-flex align-items-start gap-3 mb-3">
                  <span class="badge rounded-circle bg-primary p-2" style="width:28px;height:28px;line-height:12px">1</span>
                  <div>
                    <h6 class="mb-0 fw-bold small">Pendaftaran & Rekam Medis</h6>
                    <small class="text-muted">Pasien mengambil nomor antrean / registrasi kunjungan di poliklinik tujuan.</small>
                  </div>
                </div>
                <div class="d-flex align-items-start gap-3 mb-3">
                  <span class="badge rounded-circle bg-warning text-dark p-2" style="width:28px;height:28px;line-height:12px">2</span>
                  <div>
                    <h6 class="mb-0 fw-bold small">Pemeriksaan Dokter</h6>
                    <small class="text-muted">Dokter memanggil pasien, melakukan anamnesa, pemeriksaan fisik, & diagnosa.</small>
                  </div>
                </div>
                <div class="d-flex align-items-start gap-3 mb-3">
                  <span class="badge rounded-circle bg-success p-2" style="width:28px;height:28px;line-height:12px">3</span>
                  <div>
                    <h6 class="mb-0 fw-bold small">Farmasi & Kasir</h6>
                    <small class="text-muted">Penyerahan resep obat, pembayaran umum, atau verifikasi klaim asuransi / BPJS.</small>
                  </div>
                </div>
                <div class="d-flex align-items-start gap-3">
                  <span class="badge rounded-circle bg-info text-white p-2" style="width:28px;height:28px;line-height:12px">4</span>
                  <div>
                    <h6 class="mb-0 fw-bold small">Selesai & Riwayat Tersimpan</h6>
                    <small class="text-muted">Data kunjungan otomatis tercatat dalam rekam medis terpadu SOA.</small>
                  </div>
                </div>
              </div>
            </div>

            <!-- Widget Informasi Rumah Sakit & Kontak Darurat -->
            <div class="card border-0 shadow-sm mb-4 bg-light">
              <div class="card-body p-3">
                <h6 class="fw-bold mb-2 text-primary"><i class="bi bi-info-circle-fill me-1"></i> Layanan Gawat Darurat (IGD)</h6>
                <p class="small text-muted mb-2">Instalasi Gawat Darurat melayani 24 jam non-stop dengan dokter jaga dan ambulans siaga.</p>
                <div class="p-2 bg-white rounded border d-flex align-items-center justify-content-between mb-2">
                  <span class="small fw-semibold"><i class="bi bi-telephone-fill text-danger me-1"></i> Hotline IGD:</span>
                  <span class="badge bg-danger fs-6">(0736) 123-456</span>
                </div>
                <div class="p-2 bg-white rounded border d-flex align-items-center justify-content-between">
                  <span class="small fw-semibold"><i class="bi bi-whatsapp text-success me-1"></i> WhatsApp Humas:</span>
                  <span class="badge bg-success fs-6">0812-7890-1234</span>
                </div>
              </div>
            </div>

            <!-- Widget Status Integrasi SOA -->
            <div class="card border-0 shadow-sm">
              <div class="card-header bg-white py-3 fw-bold">
                <i class="bi bi-hdd-network text-info me-2"></i>Status Integrasi SOA
              </div>
              <div class="card-body p-3 small">
                <div class="d-flex justify-content-between mb-2">
                  <span>Server A (UI Consumer):</span>
                  <span class="badge bg-success">Online (Active)</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span>Server B (Web Service):</span>
                  <span class="badge bg-<?= !empty($stat) ? 'success' : 'secondary' ?>">
                    <?= !empty($stat) ? 'Connected' : 'Standby / Check' ?>
                  </span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span>Protokol Komunikasi:</span>
                  <span class="fw-semibold">REST API / JSON via cURL</span>
                </div>
                <div class="d-flex justify-content-between">
                  <span>Session Idle Limit:</span>
                  <span class="fw-semibold"><?= e($cfg['session_idle_minutes'] ?? 30) ?> Menit</span>
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<script>
// Pencarian & Filter menu instan di sisi klien
function filterMenu() {
  const keyword = document.getElementById('cariMenu').value.toLowerCase().trim();
  const items   = document.querySelectorAll('.item-menu');
  
  items.forEach(item => {
    const dataNama = item.getAttribute('data-nama') || '';
    const text     = item.innerText.toLowerCase();
    if (keyword === '' || dataNama.includes(keyword) || text.includes(keyword)) {
      item.style.display = '';
    } else {
      item.style.display = 'none';
    }
  });

  // Cek apakah ada kategori yang seluruh isinya tersembunyi
  document.querySelectorAll('.kategori-menu').forEach(kat => {
    const visibleItems = kat.querySelectorAll('.item-menu:not([style*="display: none"])');
    kat.style.display = (visibleItems.length === 0 && keyword !== '') ? 'none' : '';
  });
}

document.getElementById('cariMenu').addEventListener('input', filterMenu);

// Jam realtime
function updateLiveClock() {
  const now = new Date();
  const h = String(now.getHours()).padStart(2, '0');
  const m = String(now.getMinutes()).padStart(2, '0');
  const s = String(now.getSeconds()).padStart(2, '0');
  const el = document.getElementById('liveClock');
  if (el) el.textContent = `${h}:${m}:${s} WIB`;
}
setInterval(updateLiveClock, 1000);
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
