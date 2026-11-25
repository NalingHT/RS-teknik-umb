<?php
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();

$page_title = 'Ganti Password';
$errors     = [];
$success    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF
    csrf_verify();

    $password_lama           = $_POST['password_lama']           ?? '';
    $password_baru           = $_POST['password_baru']           ?? '';
    $konfirmasi_password_baru = $_POST['konfirmasi_password_baru'] ?? '';

    // --- Validasi sisi A ---
    if (empty($password_lama)) {
        $errors[] = 'Password lama wajib diisi.';
    }
    if (empty($password_baru)) {
        $errors[] = 'Password baru wajib diisi.';
    } elseif (strlen($password_baru) < 8) {
        $errors[] = 'Password baru minimal 8 karakter.';
    }
    if ($password_baru !== $konfirmasi_password_baru) {
        $errors[] = 'Konfirmasi password baru tidak cocok.';
    }

    if (empty($errors)) {
        $payload = [
            'password_lama' => $password_lama,
            'password_baru' => $password_baru,
        ];

        $response = api_post('auth/ganti_password.php', $payload);

        if ($response['ok']) {
            flash('Password berhasil diubah. Silakan login ulang jika diperlukan.', 'success');
            redirect('modules/profil/index.php');
        } else {
            $errors[] = $response['message'] ?? 'Gagal mengubah password. Periksa kembali password lama Anda.';
        }
    }
}

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
                        <h4 class="mb-1"><i class="bi bi-key me-2 text-primary"></i>Ganti Password</h4>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0">
                                <li class="breadcrumb-item"><a href="<?= base_url('modules/dashboard/index.php') ?>">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="<?= base_url('modules/profil/index.php') ?>">Profil</a></li>
                                <li class="breadcrumb-item active">Ganti Password</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Terdapat kesalahan:</strong>
                        <ul class="mb-0 mt-1">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row justify-content-center">
                    <div class="col-12 col-md-8 col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-primary text-white">
                                <i class="bi bi-shield-lock me-2"></i>Formulir Ganti Password
                            </div>
                            <div class="card-body">
                                <form method="POST" action="<?= base_url('modules/profil/ganti_password.php') ?>" novalidate>
                                    <?= csrf_field() ?>

                                    <!-- Password Lama -->
                                    <div class="mb-3">
                                        <label for="password_lama" class="form-label fw-semibold">
                                            Password Lama <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input
                                                type="password"
                                                class="form-control"
                                                id="password_lama"
                                                name="password_lama"
                                                placeholder="Masukkan password lama"
                                                required
                                                autocomplete="current-password"
                                            >
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_lama', this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Password Baru -->
                                    <div class="mb-3">
                                        <label for="password_baru" class="form-label fw-semibold">
                                            Password Baru <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input
                                                type="password"
                                                class="form-control"
                                                id="password_baru"
                                                name="password_baru"
                                                placeholder="Minimal 8 karakter"
                                                minlength="8"
                                                required
                                                autocomplete="new-password"
                                            >
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_baru', this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">Password minimal 8 karakter.</div>
                                    </div>

                                    <!-- Konfirmasi Password Baru -->
                                    <div class="mb-4">
                                        <label for="konfirmasi_password_baru" class="form-label fw-semibold">
                                            Konfirmasi Password Baru <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <input
                                                type="password"
                                                class="form-control"
                                                id="konfirmasi_password_baru"
                                                name="konfirmasi_password_baru"
                                                placeholder="Ulangi password baru"
                                                required
                                                autocomplete="new-password"
                                            >
                                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('konfirmasi_password_baru', this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div id="match-feedback" class="form-text"></div>
                                    </div>

                                    <!-- Tombol -->
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-1"></i> Simpan Password
                                        </button>
                                        <a href="<?= base_url('modules/profil/index.php') ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-arrow-left me-1"></i> Kembali
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>

<script>
// Toggle visibilitas password
function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
}

// Cek kecocokan konfirmasi password secara real-time
document.getElementById('konfirmasi_password_baru').addEventListener('input', function () {
    const baru    = document.getElementById('password_baru').value;
    const konfirm = this.value;
    const fb      = document.getElementById('match-feedback');
    if (konfirm === '') {
        fb.textContent = '';
        fb.className   = 'form-text';
    } else if (baru === konfirm) {
        fb.textContent = '✓ Password cocok';
        fb.className   = 'form-text text-success';
    } else {
        fb.textContent = '✗ Password tidak cocok';
        fb.className   = 'form-text text-danger';
    }
});
</script>
