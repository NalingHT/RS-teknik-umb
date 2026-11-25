<?php
/**
 * modules/profil/form.php — Form ubah data profil sendiri.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();

$role       = $_SESSION['role'] ?? '';
$page_title = 'Ubah Profil';

// Ambil profil saat ini dari auth/me.php
$res    = api_get('auth/me.php');
$profil = $res['data'] ?? [];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $nama_lengkap = bersih($_POST['nama_lengkap'] ?? '');
    $email        = bersih($_POST['email'] ?? '');
    $no_hp        = bersih($_POST['no_hp'] ?? '');
    $alamat       = bersih($_POST['alamat'] ?? '');

    if (!$nama_lengkap) {
        $errors['nama_lengkap'] = 'Nama lengkap wajib diisi.';
    }

    if (empty($errors)) {
        $payload = [
            'nama_lengkap' => $nama_lengkap,
            'email'        => $email,
            'no_hp'        => $no_hp,
            'alamat'       => $alamat,
        ];

        // Jika dokter atau pasien, kirim juga update ke profil masing-masing
        if ($role === 'dokter' && !empty($_SESSION['id_dokter'])) {
            $payload['spesialis'] = bersih($_POST['spesialis'] ?? '');
            $payload['no_str']    = bersih($_POST['no_str'] ?? '');
        }

        $update_res = api_put('auth/me.php', $payload);

        if ($update_res['ok']) {
            $_SESSION['nama_lengkap'] = $nama_lengkap;
            flash('Profil berhasil diperbarui.', 'success');
            redirect('modules/profil/index.php');
        } else {
            $errors['umum'] = $update_res['message'] ?: 'Gagal memperbarui profil.';
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
      <div class="container-fluid" style="max-width:700px">
        <?php render_flash(); ?>

        <?php if (!empty($errors['umum'])): ?>
          <div class="alert alert-danger"><?= e($errors['umum']) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Ubah Profil</h5>
          </div>
          <div class="card-body">
            <form method="post" action="form.php" novalidate>
              <?= csrf_field() ?>

              <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <input type="text" class="form-control bg-light" value="<?= e($profil['username'] ?? $_SESSION['username'] ?? '-') ?>" readonly>
                <div class="form-text">Username tidak dapat diubah.</div>
              </div>

              <div class="mb-3">
                <label for="nama_lengkap" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama_lengkap" id="nama_lengkap"
                       class="form-control <?= !empty($errors['nama_lengkap']) ? 'is-invalid' : '' ?>"
                       value="<?= e($_POST['nama_lengkap'] ?? $profil['nama_lengkap'] ?? $_SESSION['nama_lengkap'] ?? '') ?>" required>
                <?php if (!empty($errors['nama_lengkap'])): ?>
                  <div class="invalid-feedback"><?= e($errors['nama_lengkap']) ?></div>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <label for="email" class="form-label fw-semibold">Email</label>
                <input type="email" name="email" id="email" class="form-control"
                       value="<?= e($_POST['email'] ?? $profil['email'] ?? '') ?>">
              </div>

              <div class="mb-3">
                <label for="no_hp" class="form-label fw-semibold">No. Handphone / WhatsApp</label>
                <input type="text" name="no_hp" id="no_hp" class="form-control"
                       value="<?= e($_POST['no_hp'] ?? $profil['no_hp'] ?? '') ?>">
              </div>

              <?php if ($role === 'dokter'): ?>
                <div class="mb-3">
                  <label for="spesialis" class="form-label fw-semibold">Spesialis</label>
                  <input type="text" name="spesialis" id="spesialis" class="form-control"
                         value="<?= e($_POST['spesialis'] ?? $profil['spesialis'] ?? '') ?>">
                </div>
                <div class="mb-3">
                  <label for="no_str" class="form-label fw-semibold">No. STR</label>
                  <input type="text" name="no_str" id="no_str" class="form-control"
                         value="<?= e($_POST['no_str'] ?? $profil['no_str'] ?? '') ?>">
                </div>
              <?php endif; ?>

              <?php if ($role === 'pasien'): ?>
                <div class="mb-3">
                  <label for="alamat" class="form-label fw-semibold">Alamat</label>
                  <textarea name="alamat" id="alamat" rows="3" class="form-control"><?= e($_POST['alamat'] ?? $profil['alamat'] ?? '') ?></textarea>
                </div>
              <?php endif; ?>

              <div class="d-flex justify-content-between mt-4">
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary px-4">
                  <i class="bi bi-save me-1"></i>Simpan Perubahan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
