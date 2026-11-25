<?php
/**
 * layanan.php — Halaman Publik: Daftar Poliklinik & Fasilitas Pelayanan Rumah Sakit.
 * Berdiri sendiri, terpisah dari halaman utama, dengan desain interaktif dan informatif.
 */
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/api_client.php';

$page_title = 'Layanan Poliklinik';
$is_public  = true;
$cfg        = require __DIR__ . '/config/app.php';

$res_poli    = api_get('poliklinik.php', ['limit' => 50]);
$daftar_poli = $res_poli['ok'] ? ($res_poli['data'] ?? []) : [];
$offline     = !$res_poli['ok'];

require __DIR__ . '/partials/header.php';
?>
<!-- Navbar publik -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/rs_app/">
      <i class="bi bi-hospital-fill me-2"></i><?= e($cfg['app_name']) ?>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPublik">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navPublik">
      <ul class="navbar-nav ms-auto gap-2 align-items-center">
        <li class="nav-item"><a class="nav-link" href="/rs_app/">Beranda</a></li>
        <li class="nav-item"><a class="nav-link active fw-bold" href="/rs_app/layanan.php">Layanan & Poli</a></li>
        <li class="nav-item"><a class="nav-link" href="/rs_app/dokter.php">Dokter Kami</a></li>
        <li class="nav-item"><a class="nav-link" href="/rs_app/kontak.php">Kontak</a></li>
        <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-lg-2" href="/rs_app/login.php">Login</a></li>
        <li class="nav-item"><a class="btn btn-light btn-sm text-primary fw-semibold" href="/rs_app/register.php">Daftar Pasien</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Header Banner Halaman -->
<div class="py-5 bg-primary text-white text-center" style="background: linear-gradient(135deg, #0d6efd, #084298);">
  <div class="container">
    <span class="badge bg-white text-primary px-3 py-1 rounded-pill fw-bold mb-2">FASILITAS KESEHATAN</span>
    <h2 class="fw-bold mb-2">Layanan & Poliklinik Spesialis</h2>
    <p class="lead mb-0 text-white-50">Menyediakan berbagai poliklinik rawat jalan dengan peralatan medis modern dan tenaga ahli profesional.</p>
  </div>
</div>

<div class="container py-5">
  <!-- Fasilitas Utama -->
  <div class="row g-4 mb-5">
    <div class="col-md-3 col-sm-6">
      <div class="card h-100 border-0 shadow-sm text-center p-3">
        <div class="text-primary fs-1 mb-2"><i class="bi bi-lightning-charge"></i></div>
        <h6 class="fw-bold">IGD 24 Jam</h6>
        <p class="text-muted small mb-0">Siaga melayani kondisi kegawatdaruratan medis setiap saat.</p>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card h-100 border-0 shadow-sm text-center p-3">
        <div class="text-success fs-1 mb-2"><i class="bi bi-capsule"></i></div>
        <h6 class="fw-bold">Farmasi / Apotek</h6>
        <p class="text-muted small mb-0">Obat-obatan lengkap dan berkualitas dengan pelayanan resep cepat.</p>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card h-100 border-0 shadow-sm text-center p-3">
        <div class="text-info fs-1 mb-2"><i class="bi bi-droplet-half"></i></div>
        <h6 class="fw-bold">Laboratorium</h6>
        <p class="text-muted small mb-0">Pemeriksaan darah, urine, dan diagnostik penunjang akurat.</p>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card h-100 border-0 shadow-sm text-center p-3">
        <div class="text-warning fs-1 mb-2"><i class="bi bi-shield-plus"></i></div>
        <h6 class="fw-bold">BPJS & Asuransi</h6>
        <p class="text-muted small mb-0">Menerima pasien umum, peserta BPJS Kesehatan, dan asuransi swasta.</p>
      </div>
    </div>
  </div>

  <h4 class="fw-bold mb-3 text-primary"><i class="bi bi-grid me-2"></i>Daftar Poliklinik Tersedia</h4>

  <?php if ($offline): ?>
    <div class="alert alert-warning text-center p-4">
      <i class="bi bi-exclamation-triangle fs-2 d-block mb-2 text-warning"></i>
      <h5>Layanan Data Sedang Dalam Pemeliharaan</h5>
      <p class="text-muted mb-0">Daftar poliklinik belum dapat dimuat dari web service. Silakan hubungi bagian informasi di <strong>(0736) 123-456</strong>.</p>
    </div>
  <?php elseif (empty($daftar_poli)): ?>
    <div class="alert alert-info text-center">Belum ada poliklinik yang terdaftar.</div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($daftar_poli as $poli): ?>
        <?php if (($poli['status'] ?? 'aktif') !== 'aktif') continue; ?>
        <div class="col-lg-4 col-md-6">
          <div class="card h-100 border-0 shadow-sm stat-card">
            <div class="card-body p-4">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
                  <i class="bi bi-hospital fs-3"></i>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= e($poli['kode_poli'] ?? '-') ?></span>
              </div>
              <h5 class="fw-bold mb-1"><?= e($poli['nama_poli'] ?? '-') ?></h5>
              <p class="text-muted small mb-3">
                <i class="bi bi-geo-alt me-1 text-danger"></i>Lokasi: <?= e($poli['lokasi'] ?? 'Gedung Utama') ?>
              </p>
              <hr class="opacity-25">
              <div class="d-flex justify-content-between align-items-center">
                <span class="badge bg-success-subtle text-success">Buka Setiap Hari Kerja</span>
                <a href="/rs_app/register.php" class="btn btn-sm btn-outline-primary">Daftar Periksa &rarr;</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
