<?php
/**
 * dokter.php — Halaman Publik: Direktori Dokter Spesialis & Tenaga Medis.
 * Berdiri sendiri, dilengkapi pencarian spesialis dan kartu profil dokter.
 */
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/api_client.php';

$page_title = 'Direktori Dokter Spesialis';
$is_public  = true;
$cfg        = require __DIR__ . '/config/app.php';

$res_dok    = api_get('dokter.php', ['status' => 'aktif', 'limit' => 50]);
$daftar_dok = $res_dok['ok'] ? ($res_dok['data'] ?? []) : [];
$offline    = !$res_dok['ok'];

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
        <li class="nav-item"><a class="nav-link" href="/rs_app/layanan.php">Layanan & Poli</a></li>
        <li class="nav-item"><a class="nav-link active fw-bold" href="/rs_app/dokter.php">Dokter Kami</a></li>
        <li class="nav-item"><a class="nav-link" href="/rs_app/kontak.php">Kontak</a></li>
        <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-lg-2" href="/rs_app/login.php">Login</a></li>
        <li class="nav-item"><a class="btn btn-light btn-sm text-primary fw-semibold" href="/rs_app/register.php">Daftar Pasien</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Header Banner -->
<div class="py-5 bg-primary text-white text-center" style="background: linear-gradient(135deg, #0d6efd, #052c65);">
  <div class="container">
    <span class="badge bg-white text-primary px-3 py-1 rounded-pill fw-bold mb-2">TIM MEDIS PROFESIONAL</span>
    <h2 class="fw-bold mb-2">Dokter Spesialis & Subspesialis</h2>
    <p class="lead mb-0 text-white-50">Didukung oleh dokter-dokter berpengalaman yang berdedikasi memberikan pelayanan kesehatan terbaik.</p>
  </div>
</div>

<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <h4 class="fw-bold mb-0 text-primary"><i class="bi bi-people me-2"></i>Daftar Dokter Praktik</h4>
    <div class="text-muted small">Total: <?= count($daftar_dok) ?> Dokter Terdaftar</div>
  </div>

  <?php if ($offline): ?>
    <div class="alert alert-warning text-center p-4">
      <i class="bi bi-exclamation-triangle fs-2 d-block mb-2 text-warning"></i>
      <h5>Data Dokter Belum Dapat Dimuat</h5>
      <p class="text-muted mb-0">Layanan data dokter sedang tidak terhubung. Silakan hubungi bagian pendaftaran untuk jadwal dokter terbaru.</p>
    </div>
  <?php elseif (empty($daftar_dok)): ?>
    <div class="alert alert-info text-center">Belum ada data dokter yang tersedia.</div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($daftar_dok as $dok): ?>
        <div class="col-lg-4 col-md-6">
          <div class="card h-100 border-0 shadow-sm stat-card text-center p-4">
            <div class="mb-3">
              <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                <i class="bi bi-person-badge fs-1"></i>
              </div>
            </div>
            <h5 class="fw-bold mb-1"><?= e($dok['nama_dokter'] ?? '-') ?></h5>
            <div class="badge bg-primary-subtle text-primary mb-2 px-3 py-1"><?= e($dok['spesialis'] ?? 'Dokter Umum') ?></div>
            <p class="text-muted small mb-2"><i class="bi bi-hospital me-1"></i>Poliklinik: <strong><?= e($dok['nama_poli'] ?? '-') ?></strong></p>
            <?php if (!empty($dok['nip'])): ?>
              <small class="text-muted d-block mb-3">NIP: <?= e($dok['nip']) ?></small>
            <?php endif; ?>
            <div class="mt-auto pt-3 border-top">
              <a href="/rs_app/login.php" class="btn btn-sm btn-outline-primary w-100">
                <i class="bi bi-calendar-check me-1"></i> Buat Janji Temu
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
