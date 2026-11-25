<?php
/**
 * modules/dokter/index.php — Daftar dokter dengan pencarian & paginasi.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin', 'petugas', 'dokter', 'pasien']);

$page_title = 'Dokter';
$q          = bersih($_GET['q'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 10;

$resp    = api_get('dokter.php', ['q' => $q, 'page' => $page, 'limit' => $limit]);
$dokters = $resp['ok'] ? ($resp['data'] ?? []) : [];
$total   = (int)($resp['meta']['total'] ?? $resp['total'] ?? count($dokters));

$is_admin          = can('dokter', 'delete');
$id_dokter_session = $_SESSION['id_dokter'] ?? null;

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
              <i class="bi bi-person-badge fs-5"></i>
            </span>
            <div>
              <h6 class="mb-0 fw-bold">Daftar Dokter</h6>
              <small class="text-muted">Total <?= $total ?> data</small>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="get" class="d-flex gap-2">
              <div class="input-group input-group-sm" style="width:230px">
                <input type="search" name="q" class="form-control" placeholder="Cari nama / spesialis…" value="<?= e($q) ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
              </div>
              <?php if ($q !== ''): ?>
                <a href="index.php" class="btn btn-sm btn-outline-danger">Reset</a>
              <?php endif; ?>
            </form>
            <?php if (can('dokter', 'create')): ?>
              <a href="form.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Tambah Dokter
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
                  <th>Nama Dokter</th>
                  <th>Spesialis</th>
                  <th>Poliklinik</th>
                  <th>NIP</th>
                  <th>No. HP</th>
                  <th>Status</th>
                  <th class="text-center" style="width:120px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($dokters)): ?>
                  <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data dokter.</td></tr>
                <?php else: ?>
                  <?php $no = ($page - 1) * $limit + 1; foreach ($dokters as $d): ?>
                    <?php
                      $id_d       = $d['id_dokter'] ?? $d['id'] ?? '';
                      $boleh_edit = $is_admin || ($id_dokter_session && $id_dokter_session == $id_d);
                      $status     = $d['status'] ?? 'aktif';
                    ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td class="fw-semibold"><?= e($d['nama_dokter'] ?? '-') ?></td>
                      <td><?= e($d['spesialis']   ?? '-') ?></td>
                      <td><span class="badge bg-primary-subtle text-primary"><?= e($d['nama_poli'] ?? '-') ?></span></td>
                      <td><?= e($d['nip']         ?? '-') ?></td>
                      <td><?= e($d['no_hp']       ?? '-') ?></td>
                      <td>
                        <span class="badge bg-<?= $status === 'aktif' ? 'success' : 'secondary' ?>">
                          <?= e(ucfirst($status)) ?>
                        </span>
                      </td>
                      <td>
                        <div class="d-flex justify-content-center gap-1">
                          <?php if ($boleh_edit): ?>
                            <a href="form.php?id=<?= e($id_d) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                              <i class="bi bi-pencil"></i>
                            </a>
                          <?php endif; ?>
                          <?php if ($is_admin): ?>
                            <form method="post" action="hapus.php" class="d-inline">
                              <?= csrf_field() ?>
                              <input type="hidden" name="id" value="<?= e($id_d) ?>">
                              <button type="submit" class="btn btn-sm btn-outline-danger btn-hapus"
                                      data-nama="<?= e($d['nama_dokter'] ?? 'dokter ini') ?>" title="Hapus">
                                <i class="bi bi-trash"></i>
                              </button>
                            </form>
                          <?php endif; ?>
                          <?php if (!$boleh_edit && !$is_admin): ?>
                            <span class="text-muted small">-</span>
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
              $url_pg = '/rs_app/modules/dokter/index.php' . ($q !== '' ? '?q=' . urlencode($q) : '');
              render_paginasi($page, (int)ceil($total / $limit), $url_pg);
            ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
