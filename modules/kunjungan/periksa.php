<?php
/**
 * modules/kunjungan/periksa.php — Halaman pemeriksaan oleh dokter (isi diagnosa & ubah status).
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['dokter']);

$id = bersih($_GET['id'] ?? '');
if (!$id) {
    flash('ID kunjungan tidak valid.', 'error');
    redirect('modules/kunjungan/index.php');
}

$resp = api_get('kunjungan.php', ['id' => $id]);
$data = $resp['data'] ?? null;

if (!$data) {
    flash('Data kunjungan tidak ditemukan.', 'error');
    redirect('modules/kunjungan/index.php');
}

// Validasi kepemilikan kunjungan oleh dokter
$id_dokter_session = (string)($_SESSION['id_dokter'] ?? '');
$id_dokter_data    = (string)($data['id_dokter'] ?? '');
if ($id_dokter_session !== '' && $id_dokter_data !== '' && $id_dokter_session !== $id_dokter_data) {
    akses_ditolak();
}

$page_title = 'Pemeriksaan Pasien';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $diagnosa = bersih($_POST['diagnosa'] ?? '');
    $status   = bersih($_POST['status'] ?? 'menunggu');

    if (!in_array($status, ['menunggu', 'diperiksa', 'selesai'])) {
        $errors['status'] = 'Status tidak valid.';
    }

    if (empty($errors)) {
        $update_res = api_put("kunjungan.php?id=" . urlencode($id), [
            'diagnosa' => $diagnosa,
            'status'   => $status,
        ]);

        if ($update_res['ok']) {
            flash('Pemeriksaan berhasil disimpan.', 'success');
            redirect('modules/kunjungan/index.php');
        } else {
            $errors['umum'] = $update_res['message'] ?: 'Gagal menyimpan hasil pemeriksaan.';
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
      <div class="container-fluid" style="max-width:800px">
        <?php render_flash(); ?>

        <?php if (!empty($errors['umum'])): ?>
          <div class="alert alert-danger"><?= e($errors['umum']) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i>Data Pasien & Kunjungan</h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <small class="text-muted d-block">No. Kunjungan</small>
                <strong><?= e($data['no_kunjungan'] ?? '-') ?></strong>
              </div>
              <div class="col-md-6">
                <small class="text-muted d-block">Tanggal Kunjungan</small>
                <strong><?= format_tanggal($data['tanggal_kunjungan'] ?? '') ?></strong>
              </div>
              <div class="col-md-6">
                <small class="text-muted d-block">Nama Pasien</small>
                <strong><?= e($data['nama_pasien'] ?? '-') ?></strong> (RM: <?= e($data['no_rm'] ?? '-') ?>)
              </div>
              <div class="col-md-6">
                <small class="text-muted d-block">Cara Bayar</small>
                <span class="badge bg-<?= ($data['cara_bayar'] ?? '') === 'asuransi' ? 'info' : 'secondary' ?>">
                  <?= strtoupper(e($data['cara_bayar'] ?? 'umum')) ?>
                </span>
              </div>
              <div class="col-12">
                <small class="text-muted d-block">Keluhan Pasien</small>
                <div class="p-2 bg-light rounded border"><?= nl2br(e($data['keluhan'] ?? 'Tidak ada catatan keluhan.')) ?></div>
              </div>
            </div>
          </div>
        </div>

        <div class="card shadow-sm">
          <div class="card-header bg-white border-bottom">
            <h5 class="mb-0 text-primary"><i class="bi bi-clipboard2-pulse me-2"></i>Form Hasil Pemeriksaan & Diagnosa</h5>
          </div>
          <div class="card-body">
            <form method="post" action="periksa.php?id=<?= urlencode($id) ?>">
              <?= csrf_field() ?>

              <div class="mb-3">
                <label for="status" class="form-label fw-semibold">Status Kunjungan <span class="text-danger">*</span></label>
                <select name="status" id="status" class="form-select <?= !empty($errors['status']) ? 'is-invalid' : '' ?>" required>
                  <option value="menunggu" <?= ($data['status'] ?? '') === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                  <option value="diperiksa" <?= ($data['status'] ?? '') === 'diperiksa' ? 'selected' : '' ?>>Sedang Diperiksa</option>
                  <option value="selesai" <?= ($data['status'] ?? '') === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
                <?php if (!empty($errors['status'])): ?>
                  <div class="invalid-feedback"><?= e($errors['status']) ?></div>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <label for="diagnosa" class="form-label fw-semibold">Diagnosa & Resep / Tindakan Medis</label>
                <textarea name="diagnosa" id="diagnosa" rows="5" class="form-control"
                          placeholder="Tuliskan diagnosa dokter, tindakan, instruksi obat/resep..."><?= e($data['diagnosa'] ?? '') ?></textarea>
              </div>

              <div class="d-flex justify-content-between mt-4">
                <a href="index.php" class="btn btn-outline-secondary">Kembali</a>
                <button type="submit" class="btn btn-success px-4">
                  <i class="bi bi-check2-circle me-1"></i>Simpan Pemeriksaan
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
