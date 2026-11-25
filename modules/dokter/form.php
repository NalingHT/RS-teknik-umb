<?php
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin', 'dokter']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit  = $id > 0;
$is_admin = can('dokter', 'delete'); // admin bisa hapus
$page_title = $is_edit ? 'Edit Dokter' : 'Tambah Dokter';

$id_dokter_session = $_SESSION['id_dokter'] ?? null;

// Jika role dokter, hanya boleh edit diri sendiri
if (!$is_admin && $is_edit && $id_dokter_session != $id) {
    akses_ditolak();
}
if (!$is_admin && !$is_edit) {
    // Dokter tidak boleh tambah dokter baru
    akses_ditolak();
}

$dokter = [];
if ($is_edit) {
    $resp = api_get('dokter.php', ['id' => $id]);
    $dokter = $resp['data'] ?? $resp ?? [];
}

// Hanya admin yang ambil list pengguna & poliklinik bebas
$pengguna_list = [];
if ($is_admin && !$is_edit) {
    $resp_p = api_get('pengguna.php', ['role' => 'dokter']);
    $pengguna_list = $resp_p['data'] ?? $resp_p ?? [];
}

$poli_list = [];
$resp_poli = api_get('poliklinik.php', []);
$poli_list = $resp_poli['data'] ?? $resp_poli ?? [];

$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

// Hanya dokter sendiri (bukan admin) → field terbatas
$self_edit = !$is_admin && $is_edit && ($id_dokter_session == $id);

$judul = $is_edit ? 'Edit Dokter' : 'Tambah Dokter';

require __DIR__ . '/../../partials/header.php';
?>
<div class="app-layout">
  <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
  <div class="app-content">
    <?php require __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="app-main">
      <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="mb-0"><i class="bi bi-person-badge me-2"></i><?= e($judul) ?></h4>
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

              <?php if (!$is_edit && $is_admin): ?>
                <!-- Pilih Pengguna (hanya saat tambah, hanya admin) -->
                <div class="mb-3">
                  <label class="form-label">Akun Pengguna (role: dokter)</label>
                  <select name="id_pengguna" class="form-select">
                    <option value="">-- Tanpa akun (opsional) --</option>
                    <?php foreach ($pengguna_list as $u): ?>
                      <option value="<?= (int)($u['id_pengguna'] ?? 0) ?>"
                        <?= ($old['id_pengguna'] ?? '') == ($u['id_pengguna'] ?? '') ? 'selected' : '' ?>>
                        <?= e($u['username'] ?? '') ?> (<?= e($u['nama_lengkap'] ?? '') ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              <?php endif; ?>

              <!-- Poliklinik -->
              <div class="mb-3">
                <label class="form-label">Poliklinik <span class="text-danger">*</span></label>
                <?php if ($self_edit): ?>
                  <!-- Dokter sendiri: readonly tampilkan nama poli -->
                  <input type="text" class="form-control" readonly
                         value="<?= e($dokter['nama_poli'] ?? '') ?>">
                  <input type="hidden" name="id_poli" value="<?= (int)($dokter['id_poli'] ?? 0) ?>">
                <?php else: ?>
                  <select name="id_poli" class="form-select" required>
                    <option value="">-- Pilih Poliklinik --</option>
                    <?php
                      $sel_poli = $old['id_poli'] ?? ($dokter['id_poli'] ?? '');
                    ?>
                    <?php foreach ($poli_list as $p): ?>
                      <option value="<?= (int)($p['id_poli'] ?? 0) ?>"
                        <?= $sel_poli == ($p['id_poli'] ?? '') ? 'selected' : '' ?>>
                        <?= e($p['nama_poli'] ?? '') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                <?php endif; ?>
              </div>

              <!-- NIP -->
              <div class="mb-3">
                <label class="form-label">NIP</label>
                <?php if ($self_edit): ?>
                  <input type="text" class="form-control" readonly
                         value="<?= e($dokter['nip'] ?? '') ?>">
                  <input type="hidden" name="nip" value="<?= e($dokter['nip'] ?? '') ?>">
                <?php else: ?>
                  <input type="text" name="nip" class="form-control" maxlength="20"
                         value="<?= e($old['nip'] ?? $dokter['nip'] ?? '') ?>">
                <?php endif; ?>
              </div>

              <!-- Nama Dokter -->
              <div class="mb-3">
                <label class="form-label">Nama Dokter <span class="text-danger">*</span></label>
                <?php if ($self_edit): ?>
                  <input type="text" class="form-control" readonly
                         value="<?= e($dokter['nama_dokter'] ?? '') ?>">
                  <input type="hidden" name="nama_dokter" value="<?= e($dokter['nama_dokter'] ?? '') ?>">
                <?php else: ?>
                  <input type="text" name="nama_dokter" class="form-control" required maxlength="150"
                         value="<?= e($old['nama_dokter'] ?? $dokter['nama_dokter'] ?? '') ?>">
                <?php endif; ?>
              </div>

              <!-- Spesialis -->
              <div class="mb-3">
                <label class="form-label">Spesialis</label>
                <input type="text" name="spesialis" class="form-control" maxlength="100"
                       value="<?= e($old['spesialis'] ?? $dokter['spesialis'] ?? '') ?>">
              </div>

              <!-- No. STR -->
              <div class="mb-3">
                <label class="form-label">No. STR</label>
                <input type="text" name="no_str" class="form-control" maxlength="50"
                       value="<?= e($old['no_str'] ?? $dokter['no_str'] ?? '') ?>">
              </div>

              <!-- No. HP -->
              <div class="mb-3">
                <label class="form-label">No. HP</label>
                <input type="text" name="no_hp" class="form-control" maxlength="20"
                       value="<?= e($old['no_hp'] ?? $dokter['no_hp'] ?? '') ?>">
              </div>

              <!-- Status (hanya admin) -->
              <?php if ($is_admin): ?>
                <div class="mb-3">
                  <label class="form-label">Status</label>
                  <?php $sel_status = $old['status'] ?? ($dokter['status'] ?? 'aktif'); ?>
                  <select name="status" class="form-select">
                    <option value="aktif"    <?= $sel_status === 'aktif'    ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $sel_status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                  </select>
                </div>
              <?php endif; ?>

              <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                  <i class="bi bi-save me-1"></i><?= $is_edit ? 'Simpan Perubahan' : 'Tambah Dokter' ?>
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
<?php require __DIR__ . '/../../partials/footer.php'; ?>
