<?php
/**
 * modules/kunjungan/index.php — Daftar kunjungan dengan pencarian & paginasi.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin', 'petugas', 'dokter', 'pasien']);

$page_title = 'Kunjungan';
$role       = $_SESSION['role'] ?? '';
$q          = bersih($_GET['q'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 10;

$params = ['q' => $q, 'page' => $page, 'limit' => $limit];
if ($role === 'dokter' && !empty($_SESSION['id_dokter'])) {
    $params['id_dokter'] = $_SESSION['id_dokter'];
} elseif ($role === 'pasien' && !empty($_SESSION['id_pasien'])) {
    $params['id_pasien'] = $_SESSION['id_pasien'];
}

$resp      = api_get('kunjungan.php', $params);
$kunjungan = $resp['ok'] ? ($resp['data'] ?? []) : [];
$total     = (int)($resp['meta']['total'] ?? $resp['total'] ?? count($kunjungan));

$status_badge = [
    'menunggu'  => 'secondary',
    'diperiksa' => 'warning text-dark',
    'selesai'   => 'success',
];

require __DIR__ . '/../../partials/header.php';
?>
<div class="app-layout">
  <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
  <div class="app-content">
    <?php require __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="app-main">
      <?php render_flash(); ?>

      <?php if (!$resp['ok']): ?>
        <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i><?= e($resp['message']) ?></div>
      <?php endif; ?>

      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <span class="bg-primary bg-opacity-10 text-primary rounded-2 p-2">
              <i class="bi bi-calendar2-check fs-5"></i>
            </span>
            <div>
              <h6 class="mb-0 fw-bold">Daftar Kunjungan</h6>
              <small class="text-muted">Total <?= $total ?> data</small>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="get" class="d-flex gap-2">
              <div class="input-group input-group-sm" style="width:230px">
                <input type="search" name="q" class="form-control" placeholder="Cari no. kunjungan / pasien…" value="<?= e($q) ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
              </div>
              <?php if ($q !== ''): ?>
                <a href="index.php" class="btn btn-sm btn-outline-danger">Reset</a>
              <?php endif; ?>
            </form>
            <?php if ($role === 'pasien'): ?>
              <a href="daftar.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Daftar Berobat
              </a>
            <?php elseif (can('kunjungan', 'create')): ?>
              <a href="form.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Tambah Kunjungan
              </a>
            <?php endif; ?>
          </div>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:50px">No</th>
                  <th>No. Kunjungan</th>
                  <th>Tanggal</th>
                  <th>Pasien</th>
                  <th>Poliklinik</th>
                  <th>Dokter</th>
                  <th>Cara Bayar</th>
                  <th>Status</th>
                  <th class="text-center" style="width:130px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($kunjungan)): ?>
                  <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data kunjungan.</td></tr>
                <?php else: ?>
                  <?php $no = ($page - 1) * $limit + 1; foreach ($kunjungan as $k): ?>
                    <?php $id_k = $k['id_kunjungan'] ?? $k['id'] ?? ''; ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td><span class="badge bg-secondary"><?= e($k['no_kunjungan'] ?? '-') ?></span></td>
                      <td class="text-nowrap"><?= e(format_tanggal($k['tanggal_kunjungan'] ?? '')) ?></td>
                      <td class="fw-semibold"><?= e($k['nama_pasien'] ?? '-') ?></td>
                      <td><?= e($k['nama_poli']   ?? '-') ?></td>
                      <td><?= e($k['nama_dokter'] ?? '-') ?></td>
                      <td>
                        <span class="badge bg-<?= ($k['cara_bayar'] ?? '') === 'asuransi' ? 'info text-dark' : 'light text-dark border' ?>">
                          <?= e(ucfirst($k['cara_bayar'] ?? '-')) ?>
                        </span>
                      </td>
                      <td>
                        <?php $st = $k['status'] ?? 'menunggu'; ?>
                        <span class="badge bg-<?= e($status_badge[$st] ?? 'secondary') ?>">
                          <?= e(ucfirst($st)) ?>
                        </span>
                      </td>
                      <td>
                        <div class="d-flex justify-content-center gap-1">
                          <?php if ($role !== 'dokter' && $role !== 'pasien' && can('kunjungan', 'update')): ?>
                            <a href="form.php?id=<?= e($id_k) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                              <i class="bi bi-pencil"></i>
                            </a>
                          <?php endif; ?>
                          <?php if ($role === 'dokter' && (($k['id_dokter'] ?? '') == ($_SESSION['id_dokter'] ?? '') || empty($_SESSION['id_dokter']))): ?>
                            <a href="periksa.php?id=<?= e($id_k) ?>" class="btn btn-sm btn-outline-success" title="Periksa">
                              <i class="bi bi-clipboard-pulse"></i>
                            </a>
                          <?php endif; ?>
                          <?php if (can('kunjungan', 'delete')): ?>
                            <form method="post" action="hapus.php" class="d-inline">
                              <?= csrf_field() ?>
                              <input type="hidden" name="id" value="<?= e($id_k) ?>">
                              <button type="submit" class="btn btn-sm btn-outline-danger btn-hapus"
                                      data-nama="<?= e($k['no_kunjungan'] ?? 'kunjungan ini') ?>" title="Hapus">
                                <i class="bi bi-trash"></i>
                              </button>
                            </form>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <?php if ($total > $limit): ?>
          <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">Menampilkan halaman <?= $page ?> dari <?= (int)ceil($total / $limit) ?></small>
            <?php
              $url_pg = '/rs_app/modules/kunjungan/index.php' . ($q !== '' ? '?q=' . urlencode($q) : '');
              render_paginasi($page, (int)ceil($total / $limit), $url_pg);
            ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
