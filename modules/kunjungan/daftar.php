<?php
/**
 * modules/kunjungan/daftar.php — Form pendaftaran berobat khusus pasien.
 * Pasien tidak memilih id_pasien (otomatis diambil dari session).
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['pasien']);

$page_title = 'Daftar Berobat';
$id_pasien  = $_SESSION['id_pasien'] ?? '';

// Ambil data pasien untuk cek asuransi
$pasien_res = api_get('pasien.php', ['id' => $id_pasien]);
$pasien     = $pasien_res['data'] ?? [];
$punya_asuransi = !empty($pasien['id_asuransi']) || !empty($pasien['no_kartu_asuransi']);

// Ambil list poliklinik aktif
$poli_res   = api_get('poliklinik.php', ['status' => 'aktif', 'limit' => 100]);
$list_poli  = $poli_res['data'] ?? [];

$errors = [];

require __DIR__ . '/../../partials/header.php';
?>
<div class="app-layout">
  <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
  <div class="app-content">
    <?php require __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="app-main">
      <div class="container-fluid" style="max-width:700px">
        <?php render_flash(); ?>

        <div class="card shadow-sm">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-calendar2-plus me-2"></i>Pendaftaran Kunjungan Berobat</h5>
          </div>
          <div class="card-body">
            <p class="text-muted small mb-3">
              Silakan lengkapi formulir di bawah ini untuk mendaftar kunjungan ke poliklinik tujuan Anda.
            </p>

            <form method="post" action="simpan.php" novalidate>
              <?= csrf_field() ?>
              <!-- id_pasien otomatis dari session di sisi server simpan.php -->

              <div class="mb-3">
                <label class="form-label fw-semibold">Nama Pasien</label>
                <input type="text" class="form-control bg-light" value="<?= e($_SESSION['nama_lengkap'] ?? 'Pasien') ?>" readonly>
              </div>

              <div class="mb-3">
                <label for="id_poli" class="form-label fw-semibold">Pilih Poliklinik <span class="text-danger">*</span></label>
                <select name="id_poli" id="id_poli" class="form-select" required>
                  <option value="">-- Pilih Poliklinik --</option>
                  <?php foreach ($list_poli as $p): ?>
                    <option value="<?= e($p['id_poli']) ?>"><?= e($p['nama_poli']) ?> (<?= e($p['lokasi'] ?? '-') ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-3">
                <label for="id_dokter" class="form-label fw-semibold">Pilih Dokter <span class="text-danger">*</span></label>
                <select name="id_dokter" id="id_dokter" class="form-select" required>
                  <option value="">-- Pilih Poliklinik terlebih dahulu --</option>
                </select>
                <div class="form-text">Daftar dokter akan otomatis muncul setelah poliklinik dipilih.</div>
              </div>

              <div class="mb-3">
                <label for="tanggal_kunjungan" class="form-label fw-semibold">Tanggal Rencana Kunjungan <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_kunjungan" id="tanggal_kunjungan" class="form-control"
                       min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
              </div>

              <div class="mb-3">
                <label for="cara_bayar" class="form-label fw-semibold">Cara Pembayaran <span class="text-danger">*</span></label>
                <select name="cara_bayar" id="cara_bayar" class="form-select" required>
                  <option value="umum">Umum (Biaya Sendiri)</option>
                  <?php if ($punya_asuransi): ?>
                    <option value="asuransi">Asuransi / BPJS</option>
                  <?php else: ?>
                    <option value="asuransi" disabled>Asuransi (Data asuransi belum terdaftar di profil)</option>
                  <?php endif; ?>
                </select>
              </div>

              <div class="mb-3">
                <label for="keluhan" class="form-label fw-semibold">Keluhan Singkat / Gejala</label>
                <textarea name="keluhan" id="keluhan" rows="3" class="form-control" placeholder="Tuliskan keluhan atau gejala yang dirasakan..."></textarea>
              </div>

              <div class="d-flex justify-content-between mt-4">
                <a href="index.php" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn btn-primary px-4">
                  <i class="bi bi-send me-1"></i>Kirim Pendaftaran
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
