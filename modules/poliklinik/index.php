<?php
/**
 * modules/poliklinik/index.php — Daftar poliklinik dengan pencarian & paginasi.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin','petugas','dokter','pasien']);

$page_title = 'Poliklinik';
$cfg        = require __DIR__ . '/../../config/app.php';

$q     = bersih($_GET['q']     ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;

$res  = api_get('poliklinik.php', ['q' => $q, 'page' => $page, 'limit' => $limit]);
$data = $res['ok'] ? ($res['data'] ?? []) : [];
$meta = $res['meta'] ?? [];
$total = (int)($meta['total'] ?? count($data));

require __DIR__ . '/../../partials/header.php';
?>
<div class="app-layout">
  <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
  <div class="app-content">
    <?php require __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="app-main">
      <?php render_flash(); ?>

      <?php if (!$res['ok']): ?>
      <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i><?= e($res['message']) ?></div>
      <?php endif; ?>

      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <span class="bg-primary bg-opacity-10 text-primary rounded-2 p-2">
              <i class="bi bi-hospital fs-5"></i>
            </span>
            <div>
              <h6 class="mb-0 fw-bold">Daftar Poliklinik</h6>
              <small class="text-muted">Total <?= $total ?> data</small>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="get" class="d-flex gap-2">
              <div class="input-group input-group-sm" style="width:230px">
                <input type="search" name="q" class="form-control" placeholder="Cari poliklinik…" value="<?= e($q) ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
              </div>
              <?php if ($q !== ''): ?>
                <a href="index.php" class="btn btn-sm btn-outline-danger">Reset</a>
              <?php endif; ?>
            </form>
            <?php if (can('poliklinik','create')): ?>
            <a href="/rs_app/modules/poliklinik/form.php" class="btn btn-sm btn-primary">
              <i class="bi bi-plus-circle me-1"></i>Tambah Poliklinik
            </a>
            <?php endif; ?>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th>No</th><th>Kode</th><th>Nama Poliklinik</th><th>Lokasi</th><th>Status</th>
                  <?php if (can('poliklinik','update') || can('poliklinik','delete')): ?><th>Aksi</th><?php endif; ?>
                </tr>
              </thead>
              <tbody>
              <?php if (empty($data)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td></tr>
              <?php else: ?>
              <?php $no = ($page - 1) * $limit + 1; foreach ($data as $row): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><span class="badge bg-secondary"><?= e($row['kode_poli'] ?? '') ?></span></td>
                <td class="fw-semibold"><?= e($row['nama_poli'] ?? '') ?></td>
                <td><?= e($row['lokasi'] ?? '-') ?></td>
                <td>
                  <span class="badge bg-<?= ($row['status'] ?? '') === 'aktif' ? 'success' : 'secondary' ?>">
                    <?= e($row['status'] ?? '-') ?>
                  </span>
                </td>
                <?php if (can('poliklinik','update') || can('poliklinik','delete')): ?>
                <td class="d-flex gap-1">
                  <?php if (can('poliklinik','update')): ?>
                  <a href="/rs_app/modules/poliklinik/form.php?id=<?= e($row['id_poli'] ?? '') ?>"
                     class="btn btn-xs btn-outline-primary btn-sm">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <?php endif; ?>
                  <?php if (can('poliklinik','delete')): ?>
                  <form method="post" action="/rs_app/modules/poliklinik/hapus.php" class="d-inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= e($row['id_poli'] ?? '') ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger btn-hapus"
                            data-nama="<?= e($row['nama_poli'] ?? 'poliklinik ini') ?>">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                  <?php endif; ?>
                </td>
                <?php endif; ?>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php if ($total > $limit): ?>
        <div class="card-footer">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <small class="text-muted">Total: <?= $total ?> data</small>
            <?php
              $url_pg = '/rs_app/modules/poliklinik/index.php?' . ($q ? 'q=' . urlencode($q) . '&' : '');
              render_paginasi($page, (int)ceil($total / $limit), $url_pg);
            ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
