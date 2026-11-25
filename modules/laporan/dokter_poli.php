<?php
/**
 * modules/laporan/dokter_poli.php — Laporan data dokter per poliklinik (Khusus Admin).
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin']);

$page_title = 'Laporan Dokter per Poliklinik';

$id_poli = bersih($_GET['id_poli'] ?? '');

$params = [
    'id_poli' => $id_poli,
    'limit'   => 1000,
];

$res_lap    = api_get('laporan/dokter_poli.php', $params);
$data_lap   = $res_lap['data'] ?? [];

$list_poli = (api_get('poliklinik.php', ['limit' => 100]))['data'] ?? [];

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
            <span class="fw-bold"><i class="bi bi-funnel me-1"></i>Filter Dokter per Poliklinik</span>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-printer me-1"></i>Cetak Laporan
            </button>
          </div>
          <div class="card-body">
            <form method="get" class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-semibold">Poliklinik</label>
                <select name="id_poli" class="form-select form-select-sm">
                  <option value="">-- Semua Poliklinik --</option>
                  <?php foreach ($list_poli as $p): ?>
                    <option value="<?= e($p['id_poli']) ?>" <?= $id_poli == $p['id_poli'] ? 'selected' : '' ?>>
                      <?= e($p['nama_poli']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Tampilkan</button>
                <a href="dokter_poli.php" class="btn btn-outline-secondary btn-sm">Reset</a>
              </div>
            </form>
          </div>
        </div>

        <!-- Tabel Laporan -->
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="text-center mb-4">
              <h4 class="fw-bold mb-1">LAPORAN DATA DOKTER & POLIKLINIK</h4>
              <p class="text-muted small mb-0">Total Data: <?= count($data_lap) ?> Dokter</p>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-striped table-sm">
                <thead class="table-light text-center">
                  <tr>
                    <th style="width:40px">No</th>
                    <th>NIP</th>
                    <th>Nama Dokter</th>
                    <th>Spesialis</th>
                    <th>Poliklinik</th>
                    <th>No. STR</th>
                    <th>No. HP</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($data_lap)): ?>
                    <tr><td colspan="8" class="text-center py-3 text-muted">Tidak ada data dokter pada poliklinik tersebut.</td></tr>
                  <?php else: ?>
                    <?php $no = 1; foreach ($data_lap as $row): ?>
                      <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= e($row['nip'] ?? '-') ?></td>
                        <td class="fw-semibold"><?= e($row['nama_dokter'] ?? '-') ?></td>
                        <td><?= e($row['spesialis'] ?? '-') ?></td>
                        <td><?= e($row['nama_poli'] ?? '-') ?></td>
                        <td><?= e($row['no_str'] ?? '-') ?></td>
                        <td><?= e($row['no_hp'] ?? '-') ?></td>
                        <td class="text-center">
                          <span class="badge bg-<?= ($row['status'] ?? '') === 'aktif' ? 'success' : 'secondary' ?>">
                            <?= e($row['status'] ?? '-') ?>
                          </span>
                        </td>
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
