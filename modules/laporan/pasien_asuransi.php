<?php
/**
 * modules/laporan/pasien_asuransi.php — Laporan pasien per asuransi.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin', 'petugas']);

$page_title = 'Laporan Pasien Asuransi';

$id_asuransi = bersih($_GET['id_asuransi'] ?? '');

$params = [
    'id_asuransi' => $id_asuransi,
    'limit'       => 1000,
];

$res_lap    = api_get('laporan/pasien_asuransi.php', $params);
$data_lap   = $res_lap['data'] ?? [];

$list_asuransi = (api_get('asuransi.php', ['limit' => 100]))['data'] ?? [];

require __DIR__ . '/../../partials/header.php';
?>
<div class="app-layout">
  <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
  <div class="app-content">
    <?php require __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="app-main">
      <div class="container-fluid">
        <?php render_flash(); ?>

        <!-- Filter Form -->
        <div class="card shadow-sm mb-4 print-hidden">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="bi bi-funnel me-1"></i>Filter Laporan Pasien Asuransi</span>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-printer me-1"></i>Cetak Laporan
            </button>
          </div>
          <div class="card-body">
            <form method="get" class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Asuransi</label>
                <select name="id_asuransi" class="form-select form-select-sm">
                  <option value="">-- Semua Asuransi --</option>
                  <?php foreach ($list_asuransi as $a): ?>
                    <option value="<?= e($a['id_asuransi']) ?>" <?= $id_asuransi == $a['id_asuransi'] ? 'selected' : '' ?>>
                      <?= e($a['nama_asuransi']) ?> (<?= e($a['jenis'] ?? '-') ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Tampilkan</button>
                <a href="pasien_asuransi.php" class="btn btn-outline-secondary btn-sm">Reset</a>
              </div>
            </form>
          </div>
        </div>

        <!-- Tabel Laporan -->
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="text-center mb-4">
              <h4 class="fw-bold mb-1">LAPORAN DATA PASIEN & ASURANSI</h4>
              <p class="text-muted small mb-0">Total Data: <?= count($data_lap) ?> Pasien</p>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-striped table-sm">
                <thead class="table-light text-center">
                  <tr>
                    <th style="width:40px">No</th>
                    <th>No. Rekam Medis</th>
                    <th>Nama Pasien</th>
                    <th>NIK</th>
                    <th>Nama Asuransi</th>
                    <th>Jenis Asuransi</th>
                    <th>No. Kartu Asuransi</th>
                    <th>No. HP</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($data_lap)): ?>
                    <tr><td colspan="8" class="text-center py-3 text-muted">Tidak ada data pasien dengan asuransi tersebut.</td></tr>
                  <?php else: ?>
                    <?php $no = 1; foreach ($data_lap as $row): ?>
                      <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= e($row['no_rm'] ?? '-') ?></td>
                        <td><?= e($row['nama_pasien'] ?? '-') ?></td>
                        <td><?= e($row['nik'] ?? '-') ?></td>
                        <td><?= e($row['nama_asuransi'] ?? '-') ?></td>
                        <td class="text-center"><?= e($row['jenis'] ?? '-') ?></td>
                        <td><?= e($row['no_kartu_asuransi'] ?? '-') ?></td>
                        <td><?= e($row['no_hp'] ?? '-') ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

          </div>
        </div>

      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
