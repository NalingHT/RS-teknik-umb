<?php
/**
 * partials/sidebar.php
 * Sidebar navigasi — ditampilkan hanya setelah login.
 * Membutuhkan: core/rbac.php sudah di-load.
 */
$menu_items = menu_untuk_role();
$role       = $_SESSION['role'] ?? '';
$nama       = e($_SESSION['nama_lengkap'] ?? 'Pengguna');
$cfg        = $cfg ?? require __DIR__ . '/../config/app.php';
$app_name   = e($cfg['app_name'] ?? 'RS App');

$role_badge = [
    'admin'   => 'danger',
    'petugas' => 'primary',
    'dokter'  => 'success',
    'pasien'  => 'info',
];
$badge = $role_badge[$role] ?? 'secondary';
?>
<!-- Sidebar -->
<nav id="sidebar" class="sidebar d-flex flex-column flex-shrink-0 p-3 text-white bg-primary" style="width:260px;min-height:100vh">
  <a href="/rs_app/dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
    <i class="bi bi-hospital-fill fs-4 me-2"></i>
    <span class="fs-6 fw-bold"><?= $app_name ?></span>
  </a>
  <div class="mb-3 mt-2 px-1">
    <small class="text-white-50">Halo,</small><br>
    <span class="fw-semibold"><?= $nama ?></span>
    <span class="badge bg-<?= $badge ?> ms-1"><?= e($role) ?></span>
  </div>
  <hr class="text-white-50">
  <ul class="nav nav-pills flex-column mb-auto">
    <?php foreach ($menu_items as $item): ?>
    <li class="nav-item">
      <a href="<?= e($item['url']) ?>" class="nav-link text-white <?= $item['aktif'] ? 'active bg-white bg-opacity-25' : '' ?>">
        <i class="bi <?= e($item['icon']) ?> me-2"></i><?= e($item['label']) ?>
      </a>
    </li>
    <?php endforeach; ?>
  </ul>
  <hr class="text-white-50">
  <form method="post" action="/rs_app/logout.php" class="d-grid">
    <?= csrf_field() ?>
    <button type="submit" class="btn btn-outline-light btn-sm">
      <i class="bi bi-box-arrow-right me-1"></i>Logout
    </button>
  </form>
</nav>
