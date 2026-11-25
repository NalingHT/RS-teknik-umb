<?php
/**
 * index.php — Landing page publik RS Muhammadiyah Bengkulu Teknik.
 * Data poliklinik & dokter diambil dari Server B via cURL.
 * Jika Server B mati, tampilkan pesan ramah, halaman tetap tampil.
 */
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/api_client.php';

$page_title = 'Selamat Datang';
$is_public  = true;
$cfg        = require __DIR__ . '/config/app.php';

// Jika sudah login, langsung ke dashboard
if (!empty($_SESSION['token'])) {
    redirect('dashboard.php');
}

// Ambil data poliklinik & dokter dari Server B (GET publik dengan API key saja)
$res_poli   = api_get('poliklinik.php', ['limit' => 20]);
$res_dokter = api_get('dokter.php',     ['limit' => 12, 'status' => 'aktif']);

$daftar_poli   = $res_poli['ok']   ? ($res_poli['data']   ?? []) : [];
$daftar_dokter = $res_dokter['ok'] ? ($res_dokter['data'] ?? []) : [];
$server_b_mati = !$res_poli['ok'] && !$res_dokter['ok'];

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
        <li class="nav-item"><a class="nav-link active fw-bold" href="/rs_app/">Beranda</a></li>
        <li class="nav-item"><a class="nav-link" href="/rs_app/layanan.php">Layanan & Poli</a></li>
        <li class="nav-item"><a class="nav-link" href="/rs_app/dokter.php">Dokter Kami</a></li>
        <li class="nav-item"><a class="nav-link" href="/rs_app/kontak.php">Kontak</a></li>
        <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-lg-2" href="/rs_app/login.php">Login</a></li>
        <li class="nav-item"><a class="btn btn-light btn-sm text-primary fw-semibold" href="/rs_app/register.php">Daftar Pasien</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Hero -->
<section class="hero-section text-center">
  <div class="container">
    <div class="mb-3"><i class="bi bi-hospital-fill" style="font-size:3rem"></i></div>
    <h1><?= e($cfg['app_name']) ?></h1>
    <p class="lead mb-4">Melayani dengan sepenuh hati untuk kesehatan Anda dan keluarga</p>
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <a href="/rs_app/register.php" class="btn btn-light btn-lg px-4 fw-semibold">
        <i class="bi bi-person-plus me-2"></i>Daftar Sebagai Pasien
      </a>
      <a href="/rs_app/login.php" class="btn btn-outline-light btn-lg px-4">
        <i class="bi bi-box-arrow-in-right me-2"></i>Login
      </a>
    </div>
  </div>
</section>

<!-- Jam Layanan -->
<section class="py-4 bg-primary bg-opacity-10">
  <div class="container">
    <div class="row justify-content-center text-center g-3">
      <div class="col-md-3">
        <i class="bi bi-clock text-primary fs-3"></i>
        <div class="fw-semibold mt-1">Senin – Jumat</div>
        <div class="text-muted small">07.30 – 16.00 WIB</div>
      </div>
      <div class="col-md-3">
        <i class="bi bi-clock text-primary fs-3"></i>
        <div class="fw-semibold mt-1">Sabtu</div>
        <div class="text-muted small">07.30 – 12.00 WIB</div>
      </div>
      <div class="col-md-3">
        <i class="bi bi-telephone text-primary fs-3"></i>
        <div class="fw-semibold mt-1">Hotline</div>
        <div class="text-muted small">(0736) 123-456</div>
      </div>
      <div class="col-md-3">
        <i class="bi bi-geo-alt text-primary fs-3"></i>
        <div class="fw-semibold mt-1">Lokasi</div>
        <div class="text-muted small">Jl. Bali No.1, Bengkulu</div>
      </div>
    </div>
  </div>
</section>

<!-- Poliklinik -->
<section class="py-5" id="layanan">
  <div class="container">
    <h2 class="text-center fw-bold mb-1">Poliklinik Kami</h2>
    <p class="text-center text-muted mb-4">Berbagai layanan kesehatan spesialis tersedia untuk Anda</p>

    <?php if ($server_b_mati): ?>
    <div class="alert alert-warning text-center">
      <i class="bi bi-exclamation-triangle me-2"></i>
      Data layanan sedang tidak tersedia. Hubungi kami di <strong>(0736) 123-456</strong> untuk informasi lebih lanjut.
    </div>
    <?php elseif (empty($daftar_poli)): ?>
    <p class="text-center text-muted">Belum ada data poliklinik.</p>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($daftar_poli as $poli): ?>
      <?php if (($poli['status'] ?? 'aktif') !== 'aktif') continue; ?>
      <div class="col-md-4 col-sm-6">
        <div class="card layanan-card shadow-sm h-100 p-3 text-center">
          <div class="card-body">
            <div class="text-primary fs-1 mb-2"><i class="bi bi-clipboard2-pulse"></i></div>
            <h6 class="fw-bold"><?= e($poli['nama_poli'] ?? '') ?></h6>
            <?php if (!empty($poli['lokasi'])): ?>
            <p class="text-muted small"><i class="bi bi-geo-alt me-1"></i><?= e($poli['lokasi']) ?></p>
            <?php endif; ?>
            <span class="badge bg-success-subtle text-success"><?= e($poli['kode_poli'] ?? '') ?></span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Dokter -->
<section class="py-5 bg-light" id="dokter">
  <div class="container">
    <h2 class="text-center fw-bold mb-1">Dokter Kami</h2>
    <p class="text-center text-muted mb-4">Tim dokter berpengalaman siap melayani Anda</p>

    <?php if (!$server_b_mati && !empty($daftar_dokter)): ?>
    <div class="row g-3">
      <?php foreach ($daftar_dokter as $dok): ?>
      <div class="col-md-4 col-sm-6">
        <div class="card layanan-card shadow-sm h-100">
          <div class="card-body text-center p-4">
            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:70px;height:70px">
              <i class="bi bi-person-badge text-primary fs-2"></i>
            </div>
            <h6 class="fw-bold"><?= e($dok['nama_dokter'] ?? '') ?></h6>
            <?php if (!empty($dok['spesialis'])): ?>
            <p class="text-muted small mb-1"><?= e($dok['spesialis']) ?></p>
            <?php endif; ?>
            <?php if (!empty($dok['nama_poli'])): ?>
            <span class="badge bg-primary-subtle text-primary"><?= e($dok['nama_poli']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php elseif (!$server_b_mati): ?>
    <p class="text-center text-muted">Belum ada data dokter.</p>
    <?php else: ?>
    <div class="alert alert-warning text-center">
      <i class="bi bi-exclamation-triangle me-2"></i>
      Data dokter sedang tidak tersedia sementara.
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- Kontak / CTA -->
<section class="py-5 bg-primary text-white" id="kontak">
  <div class="container text-center">
    <h3 class="fw-bold mb-3">Hubungi Kami</h3>
    <div class="row justify-content-center g-4">
      <div class="col-md-3">
        <i class="bi bi-telephone-fill fs-3 mb-2"></i>
        <div>(0736) 123-456</div>
      </div>
      <div class="col-md-3">
        <i class="bi bi-envelope-fill fs-3 mb-2"></i>
        <div>info@rsmuhbengkulu.ac.id</div>
      </div>
      <div class="col-md-4">
        <i class="bi bi-geo-alt-fill fs-3 mb-2"></i>
        <div>Jl. Bali No.1, Kota Bengkulu, 38119</div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
