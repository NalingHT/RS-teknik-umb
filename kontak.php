<?php
/**
 * kontak.php — Halaman Publik: Informasi Kontak, Lokasi, dan Layanan Darurat Rumah Sakit.
 */
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/helpers.php';

$page_title = 'Kontak & Lokasi';
$is_public  = true;
$cfg        = require __DIR__ . '/config/app.php';

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
        <li class="nav-item"><a class="nav-link" href="/rs_app/dokter.php">Dokter Kami</a></li>
        <li class="nav-item"><a class="nav-link active fw-bold" href="/rs_app/kontak.php">Kontak</a></li>
        <li class="nav-item"><a class="btn btn-outline-light btn-sm ms-lg-2" href="/rs_app/login.php">Login</a></li>
        <li class="nav-item"><a class="btn btn-light btn-sm text-primary fw-semibold" href="/rs_app/register.php">Daftar Pasien</a></li>
      </ul>
    </div>
  </div>
</nav>

<!-- Header Banner -->
<div class="py-5 bg-primary text-white text-center" style="background: linear-gradient(135deg, #0d6efd, #031633);">
  <div class="container">
    <span class="badge bg-white text-primary px-3 py-1 rounded-pill fw-bold mb-2">HUBUNGI KAMI</span>
    <h2 class="fw-bold mb-2">Kontak, Layanan Pengaduan & Lokasi</h2>
    <p class="lead mb-0 text-white-50">Kami siap membantu dan melayani konsultasi serta kebutuhan kesehatan Anda dan keluarga.</p>
  </div>
</div>

<div class="container py-5">
  <div class="row g-4">
    <!-- Info Kontak -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100 p-4">
        <h4 class="fw-bold text-primary mb-4"><i class="bi bi-geo-alt-fill me-2"></i>Informasi Alamat & Telepon</h4>
        
        <div class="d-flex align-items-start gap-3 mb-4">
          <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle">
            <i class="bi bi-geo-alt fs-4"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1">Alamat Rumah Sakit</h6>
            <p class="text-muted mb-0">Jl. Bali No. 1, Teluk Segara, Kota Bengkulu, Bengkulu 38119</p>
          </div>
        </div>

        <div class="d-flex align-items-start gap-3 mb-4">
          <div class="bg-danger bg-opacity-10 text-danger p-3 rounded-circle">
            <i class="bi bi-telephone-inbound fs-4"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1">Hotline IGD 24 Jam (Emergency)</h6>
            <p class="text-danger fw-bold fs-5 mb-0">(0736) 123-456</p>
          </div>
        </div>

        <div class="d-flex align-items-start gap-3 mb-4">
          <div class="bg-success bg-opacity-10 text-success p-3 rounded-circle">
            <i class="bi bi-whatsapp fs-4"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1">WhatsApp Customer Service / Informasi</h6>
            <p class="text-muted mb-0">0812-7890-1234 (Senin - Sabtu: 07.30 - 16.00 WIB)</p>
          </div>
        </div>

        <div class="d-flex align-items-start gap-3">
          <div class="bg-info bg-opacity-10 text-info p-3 rounded-circle">
            <i class="bi bi-envelope-at fs-4"></i>
          </div>
          <div>
            <h6 class="fw-bold mb-1">Surat Elektronik (Email)</h6>
            <p class="text-muted mb-0">humas@rsmuhbengkulu.ac.id / info@rsmuhbengkulu.ac.id</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Form Pertanyaan / Pengaduan -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100 p-4">
        <h4 class="fw-bold text-primary mb-3"><i class="bi bi-chat-left-dots-fill me-2"></i>Kirim Pertanyaan / Masukan</h4>
        <p class="text-muted small mb-4">Punya pertanyaan seputar jadwal poli, dokter, atau layanan BPJS? Tuliskan pesan Anda di bawah:</p>
        
        <form onsubmit="alert('Terima kasih! Pesan Anda telah kami terima dan akan segera ditindaklanjuti.'); return false;">
          <div class="mb-3">
            <label class="form-label fw-semibold">Nama Lengkap</label>
            <input type="text" class="form-control" placeholder="Nama Anda" required>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">No. HP / WhatsApp</label>
              <input type="tel" class="form-control" placeholder="08..." required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Email</label>
              <input type="email" class="form-control" placeholder="email@contoh.com">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Pesan / Keluhan / Masukan</label>
            <textarea class="form-control" rows="4" placeholder="Tuliskan pertanyaan atau masukan Anda secara jelas..." required></textarea>
          </div>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-send me-1"></i> Kirim Pesan
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
