<?php
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin', 'petugas', 'pasien']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit  = $id > 0;
$is_admin_or_petugas = can('pasien', 'create'); // admin + petugas
$id_pasien_session   = $_SESSION['id_pasien'] ?? null;
$page_title = $is_edit ? 'Edit Data Pasien' : 'Tambah Pasien';

// Pasien hanya boleh edit diri sendiri
if (!$is_admin_or_petugas) {
    if (!$is_edit || !$id_pasien_session || $id_pasien_session != $id) {
        akses_ditolak();
    }
}

$pasien = [];
if ($is_edit) {
    $resp   = api_get('pasien.php', ['id' => $id]);
    $pasien = $resp['data'] ?? $resp ?? [];
}

// Dropdown asuransi
$asuransi_list = [];
$resp_a = api_get('asuransi.php', []);
$asuransi_list = $resp_a['data'] ?? $resp_a ?? [];

$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

$judul = $is_edit ? 'Edit Data Pasien' : 'Tambah Pasien';

require __DIR__ . '/../../partials/header.php';
?>
<div class="app-layout">
  <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
  <div class="app-content">
    <?php require __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="app-main">
      <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0"><i class="bi bi-people me-2"></i><?= e($judul) ?></h4>
          <a href="index.php" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
          </a>
        </div>

        <?php render_flash(); ?>

        <div class="card shadow-sm">
          <div class="card-body">
            <form method="post" action="simpan.php" novalidate>
              <?= csrf_field() ?>
              <?php if ($is_edit): ?>
                <input type="hidden" name="id" value="<?= (int)$id ?>">
              <?php endif; ?>

              <!-- No. RM (readonly, generate server) -->
              <?php if ($is_edit && !empty($pasien['no_rm'])): ?>
                <div class="mb-3">
                  <label class="form-label">No. Rekam Medis</label>
                  <input type="text" class="form-control" readonly
                         value="<?= e($pasien['no_rm'] ?? '') ?>">
                  <small class="text-muted">Nomor RM digenerate otomatis oleh sistem.</small>
                </div>
              <?php endif; ?>

              <!-- NIK -->
              <div class="mb-3">
                <label class="form-label">NIK <small class="text-muted">(16 digit, opsional)</small></label>
                <input type="text" name="nik" class="form-control" maxlength="16"
                       pattern="\d{16}"
                       placeholder="16 digit angka"
                       value="<?= e($old['nik'] ?? $pasien['nik'] ?? '') ?>">
              </div>

              <!-- Nama Pasien -->
              <div class="mb-3">
                <label class="form-label">Nama Pasien <span class="text-danger">*</span></label>
                <input type="text" name="nama_pasien" class="form-control" required maxlength="150"
                       value="<?= e($old['nama_pasien'] ?? $pasien['nama_pasien'] ?? '') ?>">
              </div>

              <!-- Jenis Kelamin -->
              <div class="mb-3">
                <label class="form-label">Jenis Kelamin <span class="text-danger">*</span></label>
                <?php $sel_jk = $old['jenis_kelamin'] ?? ($pasien['jenis_kelamin'] ?? ''); ?>
                <select name="jenis_kelamin" class="form-select" required>
                  <option value="">-- Pilih --</option>
                  <option value="L" <?= $sel_jk === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                  <option value="P" <?= $sel_jk === 'P' ? 'selected' : '' ?>>Perempuan</option>
                </select>
              </div>

              <!-- Tempat Lahir -->
              <div class="mb-3">
                <label class="form-label">Tempat Lahir</label>
                <input type="text" name="tempat_lahir" class="form-control" maxlength="100"
                       value="<?= e($old['tempat_lahir'] ?? $pasien['tempat_lahir'] ?? '') ?>">
              </div>

              <!-- Tanggal Lahir -->
              <div class="mb-3">
                <label class="form-label">Tanggal Lahir <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_lahir" class="form-control" required
                       value="<?= e($old['tanggal_lahir'] ?? $pasien['tanggal_lahir'] ?? '') ?>">
              </div>

              <!-- Alamat -->
              <div class="mb-3">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control" rows="3"><?= e($old['alamat'] ?? $pasien['alamat'] ?? '') ?></textarea>
              </div>

              <!-- No. HP -->
              <div class="mb-3">
                <label class="form-label">No. HP</label>
                <input type="text" name="no_hp" class="form-control" maxlength="20"
                       value="<?= e($old['no_hp'] ?? $pasien['no_hp'] ?? '') ?>">
              </div>

              <!-- Asuransi -->
              <div class="mb-3">
                <label class="form-label">Asuransi</label>
                <?php $sel_asuransi = $old['id_asuransi'] ?? ($pasien['id_asuransi'] ?? ''); ?>
                <select name="id_asuransi" class="form-select" id="selectAsuransi">
                  <option value="">-- Tanpa Asuransi --</option>
                  <?php foreach ($asuransi_list as $a): ?>
                    <option value="<?= (int)($a['id_asuransi'] ?? 0) ?>"
                      <?= $sel_asuransi == ($a['id_asuransi'] ?? '') ? 'selected' : '' ?>>
                      <?= e($a['nama_asuransi'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <!-- No. Kartu Asuransi -->
              <div class="mb-3" id="wrapNoKartu"
                   style="<?= !$sel_asuransi ? 'display:none' : '' ?>">
                <label class="form-label">No. Kartu Asuransi
                  <span class="text-danger" id="noKartuRequired">*</span>
                </label>
                <input type="text" name="no_kartu_asuransi" class="form-control" maxlength="50"
                       id="inputNoKartu"
                       value="<?= e($old['no_kartu_asuransi'] ?? $pasien['no_kartu_asuransi'] ?? '') ?>">
              </div>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-save me-1"></i><?= $is_edit ? 'Simpan Perubahan' : 'Tambah Pasien' ?>
                </button>
                <a href="index.php" class="btn btn-secondary">Batal</a>
              </div>
            </form>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<script>
(function () {
  const selAsuransi  = document.getElementById('selectAsuransi');
  const wrapNoKartu  = document.getElementById('wrapNoKartu');
  const inputNoKartu = document.getElementById('inputNoKartu');

  function toggleNoKartu() {
    if (selAsuransi.value) {
      wrapNoKartu.style.display = '';
      inputNoKartu.required = true;
    } else {
      wrapNoKartu.style.display = 'none';
      inputNoKartu.required = false;
    }
  }

  selAsuransi.addEventListener('change', toggleNoKartu);
  toggleNoKartu();
})();
</script>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
