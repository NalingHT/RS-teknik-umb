<?php
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();

$page_title = 'Profil Saya';

// Ambil data profil dari Server B
$response = api_get('auth/me.php');
$profil   = $response['data'] ?? [];
$error    = null;

if (empty($profil)) {
    $error = 'Gagal memuat data profil. Silakan coba lagi.';
}

$role = $_SESSION['role'] ?? '';

require __DIR__ . '/../../partials/header.php';
?>
<div class="app-layout">
    <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
    <div class="app-content">
        <?php require __DIR__ . '/../../partials/topbar.php'; ?>
        <main class="app-main">
            <div class="container-fluid py-4">

                <!-- Page heading -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h4 class="mb-1"><i class="bi bi-person-circle me-2 text-primary"></i>Profil Saya</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?= base_url('dashboard.php') ?>">Dashboard</a></li>
                                <li class="breadcrumb-item active">Profil</li>
                            </ol>
                        </nav>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= base_url('modules/profil/form.php') ?>" class="btn btn-primary">
                            <i class="bi bi-pencil-square me-1"></i> Ubah Profil
                        </a>
                        <a href="<?= base_url('modules/profil/ganti_password.php') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-key me-1"></i> Ganti Password
                        </a>
                    </div>
                </div>

                <?php if ($flash = get_flash()): ?>
                    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                        <?= e($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php else: ?>

                <div class="row g-4">
                    <!-- Kartu utama akun -->
                    <div class="col-12 col-lg-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-body text-center py-5">
                                <div class="mb-3">
                                    <span class="display-1 text-secondary">
                                        <i class="bi bi-person-circle"></i>
                                    </span>
                                </div>
                                <h5 class="fw-bold mb-1"><?= e($profil['nama_lengkap'] ?? '-') ?></h5>
                                <p class="text-muted mb-2">@<?= e($profil['username'] ?? '-') ?></p>
                                <?php
                                $roleBadge = [
                                    'admin'   => 'danger',
                                    'petugas' => 'warning',
                                    'dokter'  => 'primary',
                                    'pasien'  => 'success',
                                ];
                                $badgeColor = $roleBadge[$role] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $badgeColor ?> fs-6 px-3 py-2 text-capitalize">
                                    <?= e($role) ?>
                                </span>
                                <hr>
                                <div class="text-start px-2">
                                    <p class="mb-2">
                                        <i class="bi bi-envelope me-2 text-primary"></i>
                                        <?= e($profil['email'] ?? '-') ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Kartu detail per role -->
                    <div class="col-12 col-lg-8">
                        <?php if ($role === 'dokter'): ?>
                        <!-- Detail Dokter -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-clipboard2-pulse me-2"></i>Informasi Dokter
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">Nama Dokter</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['nama_dokter'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">Spesialis</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['spesialis'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">No. STR</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['no_str'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">No. HP</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['no_hp'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">Poliklinik</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['nama_poli'] ?? '-') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php elseif ($role === 'pasien'): ?>
                        <!-- Detail Pasien -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-success text-white">
                                <i class="bi bi-person-vcard me-2"></i>Informasi Pasien
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">Nama Pasien</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['nama_pasien'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">No. Rekam Medis</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['no_rm'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">NIK</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['nik'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">Jenis Kelamin</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['jenis_kelamin'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">Tanggal Lahir</label>
                                        <p class="fw-semibold mb-0">
                                            <?= e(format_tanggal($profil['tanggal_lahir'] ?? '')) ?>
                                        </p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">No. HP</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['no_hp'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-12">
                                        <label class="form-label text-muted small">Alamat</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['alamat'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">Asuransi</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['nama_asuransi'] ?? 'Umum / Tidak Terdaftar') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php else: ?>
                        <!-- Admin / Petugas: info akun saja -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-secondary text-white">
                                <i class="bi bi-shield-check me-2"></i>Informasi Akun
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">Nama Lengkap</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['nama_lengkap'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">Username</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['username'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">Email</label>
                                        <p class="fw-semibold mb-0"><?= e($profil['email'] ?? '-') ?></p>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label text-muted small">Role</label>
                                        <p class="fw-semibold mb-0 text-capitalize"><?= e($role) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Tombol aksi -->
                        <div class="d-flex gap-2">
                            <a href="<?= base_url('modules/profil/form.php') ?>" class="btn btn-primary">
                                <i class="bi bi-pencil-square me-1"></i> Ubah Profil
                            </a>
                            <a href="<?= base_url('modules/profil/ganti_password.php') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-key me-1"></i> Ganti Password
                            </a>
                        </div>
                    </div>
                </div>

                <?php endif; ?>
            </div>
        </main>
    </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
