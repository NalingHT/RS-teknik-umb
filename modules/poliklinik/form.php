<?php
/**
 * modules/poliklinik/form.php — Form tambah/ubah poliklinik.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();

$id      = bersih($_GET['id'] ?? '');
$is_edit = $id !== '';

if ($is_edit) {
    require_role(['admin']);
    if (!can('poliklinik','update')) akses_ditolak();
} else {
    require_role(['admin']);
    if (!can('poliklinik','create')) akses_ditolak();
}

$page_title = $is_edit ? 'Ubah Poliklinik' : 'Tambah Poliklinik';
$cfg        = require __DIR__ . '/../../config/app.php';
$data       = [];
$errors     = [];

// Ambil data lama untuk form edit
if ($is_edit) {
    $res = api_get('poliklinik.php', ['id' => $id]);
    if (!$res['ok']) {
        flash($res['message'] ?: 'Data tidak ditemukan.', 'error');
        redirect('modules/poliklinik/index.php');
    }
    $data = $res['data'] ?? [];
}

require __DIR__ . '/../../partials/header.php';
?>
<div class="app-layout">
  <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
  <div class="app-content">
    <?php require __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="app-main">
      <div class="card shadow-sm" style="max-width:580px">
        <div class="card-header fw-bold">
          <i class="bi bi-hospital me-2"></i><?= e($page_title) ?>
        </div>
        <div class="card-body">
          <?php render_flash(); ?>
          <?php if (!empty($errors['umum'])): ?>
          <div class="alert alert-danger"><?= e($errors['umum']) ?></div>
          <?php endif; ?>

          <form method="post" action="/rs_app/modules/poliklinik/simpan.php" novalidate>
            <?= csrf_field() ?>
            <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= e($id) ?>">
            <input type="hidden" name="_method" value="PUT">
            <?php endif; ?>

            <div class="mb-3">
              <label class="form-label fw-semibold">Kode Poliklinik <span class="text-danger">*</span></label>
              <input type="text" name="kode_poli" maxlength="10"
                     class="form-control <?= !empty($errors['kode_poli']) ? 'is-invalid' : '' ?>"
                     value="<?= e($_POST['kode_poli'] ?? $data['kode_poli'] ?? '') ?>"
                     placeholder="Maks. 10 karakter" required>
              <?php if (!empty($errors['kode_poli'])): ?>
              <div class="invalid-feedback"><?= e($errors['kode_poli']) ?></div>
              <?php endif; ?>
            </div>

            <div class="mb-3">
              <label class="form-label fw-semibold">Nama Poliklinik <span class="text-danger">*</span></label>
              <input type="text" name="nama_poli"
                     class="form-control <?= !empty($errors['nama_poli']) ? 'is-invalid' : '' ?>"
                     value="<?= e($_POST['nama_poli'] ?? $data['nama_poli'] ?? '') ?>" required>
              <?php if (!empty($errors['nama_poli'])): ?>
              <div class="invalid-feedback"><?= e($errors['nama_poli']) ?></div>
              <?php endif; ?>
            </div>

            <div class="mb-3">
              <label class="form-label">Lokasi</label>
              <input type="text" name="lokasi" class="form-control"
                     value="<?= e($_POST['lokasi'] ?? $data['lokasi'] ?? '') ?>">
            </div>

            <div class="mb-4">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" class="form-select">
                <?php foreach (['aktif','nonaktif'] as $s): ?>
                <option value="<?= $s ?>" <?= ($data['status'] ?? 'aktif') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i><?= $is_edit ? 'Simpan Perubahan' : 'Simpan' ?>
              </button>
              <a href="/rs_app/modules/poliklinik/index.php" class="btn btn-secondary">Batal</a>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
