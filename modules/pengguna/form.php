<?php
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin']);

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit = $id > 0;
$page_title = $edit ? 'Edit Pengguna' : 'Tambah Pengguna';

if ($edit) {
    $resp = api_get('pengguna.php', ['id' => $id]);
    $row  = $resp['ok'] ? ($resp['data'] ?? []) : [];
    if (empty($row)) {
        flash('Data pengguna tidak ditemukan.', 'error');
        redirect('modules/pengguna/index.php');
    }
} else {
    $row = [];
}

$role_opts   = ['admin', 'petugas', 'dokter', 'pasien'];
$status_opts = ['aktif', 'nonaktif'];

require __DIR__ . '/../../partials/header.php';
?>
<div class="app-layout">
  <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
  <div class="app-content">
    <?php require __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="app-main container-fluid py-4">

      <?php render_flash(); ?>

      <div class="card shadow-sm" style="max-width:640px;">
        <div class="card-header bg-primary text-white">
          <i class="bi bi-person-gear me-2"></i>
          <?= $edit ? 'Edit Pengguna' : 'Tambah Pengguna' ?>
        </div>
        <div class="card-body">
          <form method="post" action="simpan.php" novalidate>
            <?= csrf_field() ?>
            <?php if ($edit): ?>
              <input type="hidden" name="_method" value="PUT">
              <input type="hidden" name="id" value="<?= $id ?>">
            <?php endif; ?>

            <!-- Username -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Username <span class="text-danger">*</span>
              </label>
              <input type="text" name="username"
                     class="form-control"
                     value="<?= e($row['username'] ?? '') ?>"
                     <?= $edit ? 'readonly' : 'required' ?>
                     placeholder="Username unik">
              <?php if ($edit): ?>
                <div class="form-text text-muted">Username tidak dapat diubah.</div>
              <?php endif; ?>
            </div>

            <!-- Password -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Password <?= $edit ? '' : '<span class="text-danger">*</span>' ?>
              </label>
              <input type="password" name="password"
                     class="form-control"
                     <?= $edit ? '' : 'required' ?>
                     autocomplete="new-password"
                     placeholder="<?= $edit ? 'Kosongkan jika tidak ingin mengubah password' : 'Min. 8 karakter' ?>">
              <?php if ($edit): ?>
                <div class="form-text text-muted">Kosongkan jika tidak ingin mengubah password.</div>
              <?php endif; ?>
            </div>

            <!-- Nama Lengkap -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Nama Lengkap <span class="text-danger">*</span>
              </label>
              <input type="text" name="nama_lengkap"
                     class="form-control"
                     value="<?= e($row['nama_lengkap'] ?? '') ?>"
                     placeholder="Nama lengkap pengguna" required>
            </div>

            <!-- Email -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Email</label>
              <input type="email" name="email"
                     class="form-control"
                     value="<?= e($row['email'] ?? '') ?>"
                     placeholder="email@contoh.com">
            </div>

            <!-- Role -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Role <span class="text-danger">*</span>
              </label>
              <select name="role" class="form-select" required>
                <option value="">-- Pilih Role --</option>
                <?php foreach ($role_opts as $r): ?>
                  <option value="<?= e($r) ?>"
                    <?= (($row['role'] ?? '') === $r) ? 'selected' : '' ?>>
                    <?= ucfirst($r) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Status -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" class="form-select">
                <?php foreach ($status_opts as $s): ?>
                  <option value="<?= e($s) ?>"
                    <?= (($row['status'] ?? 'aktif') === $s) ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i><?= $edit ? 'Simpan Perubahan' : 'Simpan' ?>
              </button>
              <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
          </form>
        </div>
      </div>

    </main>
  </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
