<?php
/**
 * modules/pasien/index.php — Daftar pasien dengan pencarian & paginasi.
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin', 'petugas', 'dokter', 'pasien']);

$page_title = 'Pasien';
$q          = bersih($_GET['q'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 10;

$resp    = api_get('pasien.php', ['q' => $q, 'page' => $page, 'limit' => $limit]);
$pasiens = $resp['ok'] ? ($resp['data'] ?? []) : [];
$total   = (int)($resp['meta']['total'] ?? $resp['total'] ?? count($pasiens));

$is_admin          = can('pasien', 'delete');
$is_petugas        = can('pasien', 'create');
$id_pasien_session = $_SESSION['id_pasien'] ?? null;

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
              <i class="bi bi-people fs-5"></i>
            </span>
            <div>
              <h6 class="mb-0 fw-bold">Daftar Pasien</h6>
              <small class="text-muted">Total <?= $total ?> data</small>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <form method="get" class="d-flex gap-2">
              <div class="input-group input-group-sm" style="width:230px">
                <input type="search" name="q" class="form-control" placeholder="Cari nama / NIK / No. RM…" value="<?= e($q) ?>">
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
              </div>
              <?php if ($q !== ''): ?>
                <a href="index.php" class="btn btn-sm btn-outline-danger">Reset</a>
              <?php endif; ?>
            </form>
            <?php if ($is_petugas): ?>
              <a href="form.php" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Tambah Pasien
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
                  <th>No. RM</th>
                  <th>Nama Pasien</th>
                  <th>NIK</th>
                  <th>JK</th>
                  <th>Tgl. Lahir</th>
                  <th>Asuransi</th>
                  <th>No. HP</th>
                  <th class="text-center" style="width:120px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($pasiens)): ?>
                  <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data pasien.</td></tr>
                <?php else: ?>
                  <?php $no = ($page - 1) * $limit + 1; foreach ($pasiens as $p): ?>
                    <?php
                      $id_p       = $p['id_pasien'] ?? $p['id'] ?? '';
                      $boleh_edit = $is_petugas || ($id_pasien_session && $id_pasien_session == $id_p);
                    ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td><span class="badge bg-secondary"><?= e($p['no_rm'] ?? '-') ?></span></td>
                      <td class="fw-semibold"><?= e($p['nama_pasien'] ?? '-') ?></td>
                      <td><?= e($p['nik'] ?? '-') ?></td>
                      <td><?= e($p['jenis_kelamin'] ?? '-') ?></td>
                      <td class="text-nowrap"><?= format_tanggal($p['tanggal_lahir'] ?? '') ?></td>
                      <td><?= e($p['nama_asuransi'] ?? 'Umum') ?></td>
                      <td><?= e($p['no_hp'] ?? '-') ?></td>
                      <td>
                        <div class="d-flex justify-content-center gap-1">
                          <?php if ($boleh_edit): ?>
                            <a href="form.php?id=<?= e($id_p) ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                              <i class="bi bi-pencil"></i>
                            </a>
                          <?php endif; ?>
                          <?php if ($is_admin): ?>
                            <form method="post" action="hapus.php" class="d-inline">
                              <?= csrf_field() ?>
                              <input type="hidden" name="id" value="<?= e($id_p) ?>">
                              <button type="submit" class="btn btn-sm btn-outline-danger btn-hapus"
                                      data-nama="<?= e($p['nama_pasien'] ?? 'pasien ini') ?>" title="Hapus">
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
              $url_pg = '/rs_app/modules/pasien/index.php' . ($q !== '' ? '?q=' . urlencode($q) : '');
              render_paginasi($page, (int)ceil($total / $limit), $url_pg);
            ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>
<?php require __DIR__ . '/../../partials/footer.php'; ?>
