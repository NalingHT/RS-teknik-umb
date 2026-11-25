<?php
/**
 * login.php — Halaman login pengguna.
 * POST ke auth/login.php Server B, simpan token & data ke session.
 */
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/api_client.php';

$page_title = 'Login';
$is_public  = true;
$cfg        = require __DIR__ . '/config/app.php';
$errors     = [];

// Sudah login → dashboard
if (!empty($_SESSION['token'])) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = bersih($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validasi sisi A
    if (!$username) $errors['username'] = 'Username wajib diisi.';
    if (!$password) $errors['password'] = 'Password wajib diisi.';

    if (!$errors) {
        $res = api_post('auth/login.php', [
            'username' => $username,
            'password' => $password,
        ]);

        if ($res['ok'] && !empty($res['data']['token'])) {
            $user = $res['data']['user'] ?? [];

            // Regenerasi session ID setelah login (mencegah session fixation)
            session_regenerate_id(true);

            $_SESSION['token']        = $res['data']['token'];
            $_SESSION['expires_at']   = $res['data']['expires_at'] ?? '';
            $_SESSION['id_pengguna']  = $user['id_pengguna'] ?? '';
            $_SESSION['username']     = $user['username']    ?? $username;
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'] ?? $username;
            $_SESSION['role']         = $user['role']         ?? 'pasien';
            $_SESSION['id_dokter']    = $user['id_dokter']    ?? null;
            $_SESSION['id_pasien']    = $user['id_pasien']    ?? null;
            $_SESSION['last_activity']= time();

            redirect('dashboard.php', 'Selamat datang, ' . $user['nama_lengkap'] . '!', 'success');
        } else {
            // Pesan generik — tidak bocorkan detail teknis
            $errors['umum'] = 'Username atau password salah. Silakan coba kembali.';
        }
    }
}

require __DIR__ . '/partials/header.php';
?>
<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light">
  <div class="card shadow-sm" style="width:100%;max-width:420px">
    <div class="card-header bg-primary text-white text-center py-3">
      <i class="bi bi-hospital-fill fs-3 d-block mb-1"></i>
      <div class="fw-bold fs-5"><?= e($cfg['app_name']) ?></div>
      <small class="text-white-50">Silakan login untuk melanjutkan</small>
    </div>
    <div class="card-body p-4">
      <?php render_flash(); ?>

      <?php if (!empty($errors['umum'])): ?>
      <div class="alert alert-danger"><?= e($errors['umum']) ?></div>
      <?php endif; ?>

      <form method="post" action="/rs_app/login.php" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label fw-semibold">Username</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="username" class="form-control <?= !empty($errors['username']) ? 'is-invalid' : '' ?>"
                   value="<?= e($_POST['username'] ?? '') ?>" placeholder="Username" required autofocus>
            <?php if (!empty($errors['username'])): ?>
            <div class="invalid-feedback"><?= e($errors['username']) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                   placeholder="Password" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePwd" title="Tampilkan password">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
            <?php if (!empty($errors['password'])): ?>
            <div class="invalid-feedback"><?= e($errors['password']) ?></div>
            <?php endif; ?>
          </div>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login
          </button>
        </div>
      </form>

      <hr>
      <div class="text-center">
        <small class="text-muted">Belum punya akun? </small>
        <a href="/rs_app/register.php" class="small">Daftar sebagai Pasien</a>
      </div>
    </div>
  </div>
</div>

<script>
// Toggle visibilitas password
document.getElementById('togglePwd').addEventListener('click', function() {
  const inp = this.previousElementSibling.previousElementSibling;
  const ico = document.getElementById('eyeIcon');
  if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bi bi-eye-slash'; }
  else { inp.type = 'password'; ico.className = 'bi bi-eye'; }
});
</script>
<?php require __DIR__ . '/partials/footer.php'; ?>
