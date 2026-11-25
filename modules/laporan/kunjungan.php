<?php
/**
 * modules/laporan/kunjungan.php — Laporan data kunjungan pasien dengan filter & fitur cetak.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin', 'petugas', 'dokter']);

$role       = $_SESSION['role'] ?? '';
$page_title = 'Laporan Kunjungan';

// Filter
$dari      = bersih($_GET['dari']      ?? date('Y-m-01'));
$sampai    = bersih($_GET['sampai']    ?? date('Y-m-d'));
$id_poli   = bersih($_GET['id_poli']   ?? '');
$id_dokter = bersih($_GET['id_dokter'] ?? '');
$status    = bersih($_GET['status']    ?? '');

// Jika role dokter, kunci id_dokter ke miliknya
if ($role === 'dokter') {
    $id_dokter = (string)($_SESSION['id_dokter'] ?? '');
}

$params = [
    'dari'      => $dari,
    'sampai'    => $sampai,
    'id_poli'   => $id_poli,
    'id_dokter' => $id_dokter,
    'status'    => $status,
    'limit'     => 1000,
];

$res_lap    = api_get('laporan/kunjungan.php', $params);
$data_lap   = $res_lap['data'] ?? [];

// Ambil data dropdown
$list_poli   = (api_get('poliklinik.php', ['limit' => 100]))['data'] ?? [];
$list_dokter = (api_get('dokter.php',     ['limit' => 100]))['data'] ?? [];

// Hitung subtotal
$total_umum     = 0;
$total_asuransi = 0;
foreach ($data_lap as $d) {
    if (($d['cara_bayar'] ?? '') === 'asuransi') {
        $total_asuransi++;
    } else {
        $total_umum++;
    }
}
$grand_total = count($data_lap);

require __DIR__ . '/../../partials/header.php';
?>
<div class="app-layout">
  <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
  <div class="app-content">
    <?php require __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="app-main">
      <div class="container-fluid">
        <?php render_flash(); ?>

        <!-- Filter Form (Sembunyi saat cetak) -->
        <div class="card shadow-sm mb-4 print-hidden">
          <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold"><i class="bi bi-funnel me-1"></i>Filter Laporan Kunjungan</span>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-printer me-1"></i>Cetak Laporan
            </button>
          </div>
          <div class="card-body">
            <form method="get" class="row g-3">
              <div class="col-md-2">
                <label class="form-label small fw-semibold">Dari Tanggal</label>
                <input type="date" name="dari" class="form-control form-control-sm" value="<?= e($dari) ?>">
              </div>
              <div class="col-md-2">
                <label class="form-label small fw-semibold">Sampai Tanggal</label>
                <input type="date" name="sampai" class="form-control form-control-sm" value="<?= e($sampai) ?>">
              </div>
              <div class="col-md-3">
                <label class="form-label small fw-semibold">Poliklinik</label>
                <select name="id_poli" class="form-select form-select-sm">
                  <option value="">-- Semua Poli --</option>
                  <?php foreach ($list_poli as $p): ?>
                    <option value="<?= e($p['id_poli']) ?>" <?= $id_poli == $p['id_poli'] ? 'selected' : '' ?>><?= e($p['nama_poli']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php if ($role !== 'dokter'): ?>
              <div class="col-md-3">
                <label class="form-label small fw-semibold">Dokter</label>
                <select name="id_dokter" class="form-select form-select-sm">
                  <option value="">-- Semua Dokter --</option>
                  <?php foreach ($list_dokter as $dk): ?>
                    <option value="<?= e($dk['id_dokter']) ?>" <?= $id_dokter == $dk['id_dokter'] ? 'selected' : '' ?>><?= e($dk['nama_dokter']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="col-md-2">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                  <option value="">-- Semua --</option>
                  <option value="menunggu" <?= $status === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                  <option value="diperiksa" <?= $status === 'diperiksa' ? 'selected' : '' ?>>Diperiksa</option>
                  <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                </select>
              </div>
              <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Tampilkan</button>
                <a href="kunjungan.php" class="btn btn-outline-secondary btn-sm">Reset</a>
              </div>
            </form>
          </div>
        </div>

        <!-- Tabel Laporan -->
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="text-center mb-4">
              <h4 class="fw-bold mb-1">LAPORAN KUNJUNGAN PASIEN</h4>
              <p class="text-muted small mb-0">Periode: <?= format_tanggal($dari) ?> s.d. <?= format_tanggal($sampai) ?></p>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered table-striped table-sm">
                <thead class="table-light text-center">
                  <tr>
                    <th style="width:40px">No</th>
                    <th>No. Kunjungan</th>
                    <th>Tanggal</th>
                    <th>Nama Pasien</th>
                    <th>Poliklinik</th>
                    <th>Dokter</th>
                    <th>Cara Bayar</th>
                    <th>Diagnosa</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($data_lap)): ?>
                    <tr><td colspan="9" class="text-center py-3 text-muted">Tidak ada data kunjungan pada periode ini.</td></tr>
                  <?php else: ?>
                    <?php $no = 1; foreach ($data_lap as $row): ?>
                      <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= e($row['no_kunjungan'] ?? '-') ?></td>
                        <td class="text-nowrap"><?= format_tanggal($row['tanggal_kunjungan'] ?? '') ?></td>
                        <td><?= e($row['nama_pasien'] ?? '-') ?></td>
                        <td><?= e($row['nama_poli'] ?? '-') ?></td>
                        <td><?= e($row['nama_dokter'] ?? '-') ?></td>
                        <td class="text-center">
                          <span class="badge bg-<?= ($row['cara_bayar'] ?? '') === 'asuransi' ? 'info text-dark' : 'secondary' ?>">
                            <?= strtoupper(e($row['cara_bayar'] ?? 'umum')) ?>
                          </span>
                        </td>
                        <td><?= e($row['diagnosa'] ?? '-') ?></td>
                        <td class="text-center">
                          <span class="badge bg-<?= ($row['status'] ?? '') === 'selesai' ? 'success' : 'warning text-dark' ?>">
                            <?= e($row['status'] ?? '-') ?>
                          </span>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                  <tr>
                    <td colspan="6" class="text-end">Subtotal Umum:</td>
                    <td colspan="3"><?= $total_umum ?> kunjungan</td>
                  </tr>
                  <tr>
                    <td colspan="6" class="text-end">Subtotal Asuransi:</td>
                    <td colspan="3"><?= $total_asuransi ?> kunjungan</td>
                  </tr>
                  <tr>
                    <td colspan="6" class="text-end text-primary">Total Keseluruhan:</td>
                    <td colspan="3" class="text-primary"><?= $grand_total ?> kunjungan</td>
                  </tr>
                </tfoot>
              </table>
            </div>

          </div>
        </div>

      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
