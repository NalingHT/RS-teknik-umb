<?php
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit = $id > 0;
$page_title = $edit ? 'Edit Asuransi' : 'Tambah Asuransi';

if ($edit) {
    // Edit: semua role yang bisa update
    if (!can('asuransi', 'update')) akses_ditolak();
    $resp = api_get('asuransi.php', ['id' => $id]);
    $row  = $resp['ok'] ? ($resp['data'] ?? []) : [];
    if (empty($row)) {
        flash('Data asuransi tidak ditemukan.', 'error');
        redirect('modules/asuransi/index.php');
    }
} else {
    // Tambah: hanya admin + hak create
    require_role(['admin']);
    if (!can('asuransi', 'create')) akses_ditolak();
    $row = [];
}

$jenis_opts  = ['BPJS', 'Swasta', 'Perusahaan'];
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
          <i class="bi bi-shield-check me-2"></i>
          <?= $edit ? 'Edit Asuransi' : 'Tambah Asuransi' ?>
        </div>
        <div class="card-body">
          <form method="post" action="simpan.php" novalidate>
            <?= csrf_field() ?>
            <?php if ($edit): ?>
              <input type="hidden" name="_method" value="PUT">
              <input type="hidden" name="id" value="<?= $id ?>">
            <?php endif; ?>

            <!-- Kode Asuransi -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Kode Asuransi <span class="text-danger">*</span>
              </label>
              <input type="text" name="kode_asuransi" maxlength="20"
                     class="form-control"
                     value="<?= e($row['kode_asuransi'] ?? '') ?>"
                     placeholder="Maks 20 karakter" required>
            </div>

            <!-- Nama Asuransi -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Nama Asuransi <span class="text-danger">*</span>
              </label>
              <input type="text" name="nama_asuransi"
                     class="form-control"
                     value="<?= e($row['nama_asuransi'] ?? '') ?>"
                     placeholder="Nama lengkap asuransi" required>
            </div>

            <!-- Jenis -->
            <div class="mb-3">
              <label class="form-label fw-semibold">
                Jenis <span class="text-danger">*</span>
              </label>
              <select name="jenis" class="form-select" required>
                <option value="">-- Pilih Jenis --</option>
                <?php foreach ($jenis_opts as $j): ?>
                  <option value="<?= e($j) ?>"
                    <?= (($row['jenis'] ?? '') === $j) ? 'selected' : '' ?>>
                    <?= e($j) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Kontak -->
            <div class="mb-3">
              <label class="form-label fw-semibold">Kontak</label>
              <input type="text" name="kontak"
                     class="form-control"
                     value="<?= e($row['kontak'] ?? '') ?>"
                     placeholder="No. telepon / email kontak">
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
