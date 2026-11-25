<?php
/**
 * partials/topbar.php
 * Bar atas untuk halaman terautentikasi.
 * Variabel: $page_title (string)
 */
?>
<div class="topbar d-flex align-items-center px-4 py-2 bg-white border-bottom shadow-sm">
  <button class="btn btn-sm btn-outline-secondary me-3 d-lg-none" id="toggleSidebar">
    <i class="bi bi-list"></i>
  </button>
  <h5 class="mb-0 fw-semibold text-primary"><?= e($page_title ?? 'Dashboard') ?></h5>
  <div class="ms-auto d-flex align-items-center gap-2">
    <span class="text-muted small d-none d-md-inline">
      <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i') ?>
    </span>
    <a href="/rs_app/modules/profil/index.php" class="btn btn-sm btn-outline-primary">
      <i class="bi bi-person-circle me-1"></i><?= e($_SESSION['nama_lengkap'] ?? 'Profil') ?>
    </a>
  </div>
</div>
