<?php
/**
 * register.php — Registrasi pasien baru.
 * POST ke auth/register.php Server B, lalu redirect ke login.
 */
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/api_client.php';

$page_title = 'Daftar Pasien Baru';
$is_public  = true;
$cfg        = require __DIR__ . '/config/app.php';
$errors     = [];
$old        = [];

// Sudah login → dashboard
if (!empty($_SESSION['token'])) redirect('dashboard.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $old = [
        'username'      => bersih($_POST['username']      ?? ''),
        'nama_lengkap'  => bersih($_POST['nama_lengkap']  ?? ''),
        'email'         => bersih($_POST['email']         ?? ''),
        'nama_pasien'   => bersih($_POST['nama_pasien']   ?? ''),
        'jenis_kelamin' => bersih($_POST['jenis_kelamin'] ?? ''),
        'tanggal_lahir' => bersih($_POST['tanggal_lahir'] ?? ''),
        'tempat_lahir'  => bersih($_POST['tempat_lahir']  ?? ''),
        'nik'           => bersih($_POST['nik']           ?? ''),
        'no_hp'         => bersih($_POST['no_hp']         ?? ''),
        'alamat'        => bersih($_POST['alamat']        ?? ''),
    ];
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Validasi sisi A
    if (!$old['username'])      $errors['username']      = 'Username wajib diisi.';
    if (!$old['nama_lengkap'])  $errors['nama_lengkap']  = 'Nama lengkap wajib diisi.';
    if (!$old['nama_pasien'])   $errors['nama_pasien']   = 'Nama pasien wajib diisi.';
    if (!$old['jenis_kelamin']) $errors['jenis_kelamin'] = 'Jenis kelamin wajib dipilih.';
    if (!$old['tanggal_lahir']) $errors['tanggal_lahir'] = 'Tanggal lahir wajib diisi.';
    if (!$password)             $errors['password']      = 'Password wajib diisi.';
    elseif (strlen($password) < 8) $errors['password']  = 'Password minimal 8 karakter.';
    if ($password !== $password2)  $errors['password2'] = 'Konfirmasi password tidak cocok.';
    if ($old['nik'] && strlen($old['nik']) !== 16) $errors['nik'] = 'NIK harus 16 digit.';
    if (!in_array($old['jenis_kelamin'], ['L','P'], true)) {
        $errors['jenis_kelamin'] = 'Pilih jenis kelamin L atau P.';
    }

    if (!$errors) {
        $payload = array_merge($old, ['password' => $password, 'role' => 'pasien']);
        $res = api_post('auth/register.php', $payload);

        if ($res['ok']) {
            redirect('login.php', 'Registrasi berhasil! Silakan login.', 'success');
        } else {
            // Tampilkan error dari Server B
            $errors = array_merge($errors, $res['errors'] ?? []);
            $errors['umum'] = $res['message'] ?: 'Registrasi gagal. Silakan coba lagi.';
        }
    }
}

require __DIR__ . '/partials/header.php';
?>
<div class="min-vh-100 d-flex align-items-center justify-content-center bg-light py-5">
  <div class="card shadow-sm w-100" style="max-width:580px">
    <div class="card-header bg-primary text-white text-center py-3">
      <i class="bi bi-person-plus-fill fs-3 d-block mb-1"></i>
      <div class="fw-bold fs-5">Daftar Pasien Baru</div>
      <small class="text-white-50"><?= e($cfg['app_name']) ?></small>
    </div>
    <div class="card-body p-4">
      <?php if (!empty($errors['umum'])): ?>
      <div class="alert alert-danger"><?= e($errors['umum']) ?></div>
      <?php endif; ?>

      <form method="post" action="/rs_app/register.php" novalidate>
        <?= csrf_field() ?>
        <h6 class="text-primary fw-bold mb-3"><i class="bi bi-person me-1"></i>Akun Login</h6>
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control <?= !empty($errors['username']) ? 'is-invalid' : '' ?>"
                   value="<?= e($old['username'] ?? '') ?>" placeholder="username_anda">
            <?php if (!empty($errors['username'])): ?><div class="invalid-feedback"><?= e($errors['username']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="nama_lengkap" class="form-control <?= !empty($errors['nama_lengkap']) ? 'is-invalid' : '' ?>"
                   value="<?= e($old['nama_lengkap'] ?? '') ?>">
            <?php if (!empty($errors['nama_lengkap'])): ?><div class="invalid-feedback"><?= e($errors['nama_lengkap']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                   placeholder="Min. 8 karakter">
            <?php if (!empty($errors['password'])): ?><div class="invalid-feedback"><?= e($errors['password']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Konfirmasi Password <span class="text-danger">*</span></label>
            <input type="password" name="password2" class="form-control <?= !empty($errors['password2']) ? 'is-invalid' : '' ?>"
                   placeholder="Ulangi password">
            <?php if (!empty($errors['password2'])): ?><div class="invalid-feedback"><?= e($errors['password2']) ?></div><?php endif; ?>
          </div>
          <div class="col-12">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= e($old['email'] ?? '') ?>">
          </div>
        </div>

        <h6 class="text-primary fw-bold mb-3"><i class="bi bi-person-vcard me-1"></i>Data Pasien</h6>
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Nama Pasien <span class="text-danger">*</span></label>
            <input type="text" name="nama_pasien" class="form-control <?= !empty($errors['nama_pasien']) ? 'is-invalid' : '' ?>"
                   value="<?= e($old['nama_pasien'] ?? '') ?>">
            <?php if (!empty($errors['nama_pasien'])): ?><div class="invalid-feedback"><?= e($errors['nama_pasien']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-4">
            <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
            <select name="jenis_kelamin" class="form-select <?= !empty($errors['jenis_kelamin']) ? 'is-invalid' : '' ?>">
              <option value="">--</option>
              <option value="L" <?= ($old['jenis_kelamin'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-laki</option>
              <option value="P" <?= ($old['jenis_kelamin'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
            </select>
            <?php if (!empty($errors['jenis_kelamin'])): ?><div class="invalid-feedback"><?= e($errors['jenis_kelamin']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
            <input type="date" name="tanggal_lahir" class="form-control <?= !empty($errors['tanggal_lahir']) ? 'is-invalid' : '' ?>"
                   value="<?= e($old['tanggal_lahir'] ?? '') ?>">
            <?php if (!empty($errors['tanggal_lahir'])): ?><div class="invalid-feedback"><?= e($errors['tanggal_lahir']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">Tempat Lahir</label>
            <input type="text" name="tempat_lahir" class="form-control" value="<?= e($old['tempat_lahir'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">NIK (16 digit)</label>
            <input type="text" name="nik" maxlength="16" class="form-control <?= !empty($errors['nik']) ? 'is-invalid' : '' ?>"
                   value="<?= e($old['nik'] ?? '') ?>" placeholder="16 digit">
            <?php if (!empty($errors['nik'])): ?><div class="invalid-feedback"><?= e($errors['nik']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label">No. HP</label>
            <input type="text" name="no_hp" class="form-control" value="<?= e($old['no_hp'] ?? '') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control" rows="2"><?= e($old['alamat'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="d-grid mt-4">
          <button type="submit" class="btn btn-primary btn-lg">
            <i class="bi bi-person-check me-2"></i>Daftar Sekarang
          </button>
        </div>
      </form>
      <div class="text-center mt-3">
        <small class="text-muted">Sudah punya akun? </small>
        <a href="/rs_app/login.php" class="small">Login di sini</a>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
