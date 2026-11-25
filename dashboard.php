<?php
/**
 * dashboard.php — Dashboard utama pasca login, berbeda tampilan per role.
 * Statistik diambil dari laporan/ringkasan.php Server B.
 */
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/api_client.php';
require_once __DIR__ . '/core/rbac.php';

require_login();

$page_title = 'Dashboard';
$cfg        = require __DIR__ . '/config/app.php';
$role       = $_SESSION['role'] ?? '';

$res    = api_get('laporan/ringkasan.php');
$stat   = $res['ok'] ? ($res['data'] ?? []) : [];
$api_ok = $res['ok'];

if (in_array($role, ['admin', 'petugas'])) {
    // Sinkronkan jumlah master data dengan endpoint masing-masing modul
    $r_poli = api_get('poliklinik.php', ['limit' => 100]);
    if ($r_poli['ok']) {
        $api_ok = true;
        $stat['total_poli'] = $r_poli['meta']['total'] ?? count($r_poli['data'] ?? []);
    } elseif (!isset($stat['total_poli']) && isset($stat['total_poliklinik'])) {
        $stat['total_poli'] = $stat['total_poliklinik'];
    }

    $r_dokter = api_get('dokter.php', ['limit' => 100]);
    if ($r_dokter['ok']) {
        $api_ok = true;
        $stat['total_dokter'] = $r_dokter['meta']['total'] ?? count($r_dokter['data'] ?? []);
    }

    $r_pasien = api_get('pasien.php', ['limit' => 100]);
    if ($r_pasien['ok']) {
        $api_ok = true;
        $stat['total_pasien'] = $r_pasien['meta']['total'] ?? count($r_pasien['data'] ?? []);
    }

    if (!isset($stat['kunjungan_hari_ini']) || !isset($stat['kunjungan_bulan_ini']) || !isset($stat['kunjungan_umum']) || !isset($stat['kunjungan_asuransi'])) {
        $r_kunj = api_get('kunjungan.php', ['limit' => 200]);
        if ($r_kunj['ok']) {
            $api_ok = true;
            $list_kunj = $r_kunj['data'] ?? [];
            $hari_ini  = date('Y-m-d');
            $bulan_ini = date('Y-m');
            $c_hari = 0; $c_bulan = 0; $c_umum = 0; $c_asuransi = 0;
            foreach ($list_kunj as $kj) {
                $tgl = substr((string)($kj['tanggal_kunjungan'] ?? ''), 0, 10);
                if ($tgl === $hari_ini) $c_hari++;
                if (str_starts_with($tgl, $bulan_ini)) $c_bulan++;
                if (($kj['cara_bayar'] ?? '') === 'asuransi') $c_asuransi++; else $c_umum++;
            }
            $stat['kunjungan_hari_ini']  = $stat['kunjungan_hari_ini']  ?? $c_hari;
            $stat['kunjungan_bulan_ini'] = $stat['kunjungan_bulan_ini'] ?? $c_bulan;
            $stat['kunjungan_umum']      = $stat['kunjungan_umum']      ?? $c_umum;
            $stat['kunjungan_asuransi']  = $stat['kunjungan_asuransi']  ?? $c_asuransi;
        }
    }
}

require __DIR__ . '/partials/header.php';
?>
<div class="app-layout">
  <?php require __DIR__ . '/partials/sidebar.php'; ?>
  <div class="app-content">
    <?php require __DIR__ . '/partials/topbar.php'; ?>
    <main class="app-main">
      <?php render_flash(); ?>

      <!-- Banner Navigasi ke Halaman Menu Utama -->
      <div class="card border-0 shadow-sm mb-4 text-white" style="background: linear-gradient(120deg, #0d6efd, #084298); border-radius: 0.75rem;">
        <div class="card-body p-3 p-md-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
          <div>
            <span class="badge bg-white text-primary fw-bold px-2 py-1 mb-1 rounded-pill">NAVIGASI TERPISAH</span>
            <h5 class="fw-bold mb-1"><i class="bi bi-grid-fill me-2"></i>Pusat Katalog Menu & Layanan</h5>
            <p class="mb-0 text-white-50 small">Semua fitur, modul, dan alur pelayanan kini memiliki halaman menu tersendiri yang lengkap dan tertata.</p>
          </div>
          <a href="/rs_app/menu.php" class="btn btn-light text-primary fw-semibold px-4 py-2 shadow-sm">
            <i class="bi bi-grid me-1"></i> Buka Menu Utama &rarr;
          </a>
        </div>
      </div>

      <?php if (!$api_ok): ?>
      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Layanan data sedang tidak tersedia. Statistik tidak dapat ditampilkan saat ini.
      </div>
      <?php endif; ?>

      <!-- ADMIN & PETUGAS: Statistik umum -->
      <?php if (in_array($role, ['admin', 'petugas'])): ?>
      <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
          <div class="card stat-card bg-primary text-white shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 p-3">
              <i class="bi bi-people-fill fs-1 opacity-75"></i>
              <div>
                <div class="fs-3 fw-bold"><?= e($stat['total_pasien'] ?? '—') ?></div>
                <div class="small opacity-75">Total Pasien</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card stat-card bg-success text-white shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 p-3">
              <i class="bi bi-person-badge-fill fs-1 opacity-75"></i>
              <div>
                <div class="fs-3 fw-bold"><?= e($stat['total_dokter'] ?? '—') ?></div>
                <div class="small opacity-75">Total Dokter</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card stat-card bg-info text-white shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 p-3">
              <i class="bi bi-clipboard2-pulse-fill fs-1 opacity-75"></i>
              <div>
                <div class="fs-3 fw-bold"><?= e($stat['total_poli'] ?? '—') ?></div>
                <div class="small opacity-75">Total Poliklinik</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-3 col-sm-6">
          <div class="card stat-card bg-warning text-dark shadow-sm">
            <div class="card-body d-flex align-items-center gap-3 p-3">
              <i class="bi bi-calendar-check-fill fs-1 opacity-50"></i>
              <div>
                <div class="fs-3 fw-bold"><?= e($stat['kunjungan_hari_ini'] ?? '—') ?></div>
                <div class="small opacity-75">Kunjungan Hari Ini</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Baris kedua statistik -->
      <div class="row g-3 mb-4">
        <div class="col-md-4 col-sm-6">
          <div class="card shadow-sm h-100">
            <div class="card-body text-center py-4">
              <i class="bi bi-calendar2-week text-primary fs-1 mb-2"></i>
              <div class="fs-4 fw-bold text-primary"><?= e($stat['kunjungan_bulan_ini'] ?? '—') ?></div>
              <div class="text-muted small">Kunjungan Bulan Ini</div>
            </div>
          </div>
        </div>
        <div class="col-md-4 col-sm-6">
          <div class="card shadow-sm h-100">
            <div class="card-body text-center py-4">
              <i class="bi bi-person-check text-success fs-1 mb-2"></i>
              <div class="fs-4 fw-bold text-success"><?= e($stat['kunjungan_umum'] ?? '—') ?></div>
              <div class="text-muted small">Kunjungan Umum</div>
            </div>
          </div>
        </div>
        <div class="col-md-4 col-sm-6">
          <div class="card shadow-sm h-100">
            <div class="card-body text-center py-4">
              <i class="bi bi-shield-check text-info fs-1 mb-2"></i>
              <div class="fs-4 fw-bold text-info"><?= e($stat['kunjungan_asuransi'] ?? '—') ?></div>
              <div class="text-muted small">Kunjungan Asuransi</div>
            </div>
          </div>
        </div>
      </div>

      <?php if ($role === 'admin'): ?>
      <!-- Shortcut admin -->
      <h6 class="text-muted text-uppercase small fw-bold mb-3">Aksi Cepat</h6>
      <div class="row g-3">
        <?php
        $shortcuts = [
          ['url'=>'/rs_app/modules/pengguna/form.php','icon'=>'bi-person-gear','label'=>'Tambah Pengguna','color'=>'primary'],
          ['url'=>'/rs_app/modules/poliklinik/form.php','icon'=>'bi-hospital','label'=>'Tambah Poliklinik','color'=>'success'],
          ['url'=>'/rs_app/modules/dokter/form.php','icon'=>'bi-person-badge','label'=>'Tambah Dokter','color'=>'info'],
          ['url'=>'/rs_app/modules/laporan/kunjungan.php','icon'=>'bi-bar-chart','label'=>'Laporan Kunjungan','color'=>'warning'],
        ];
        foreach ($shortcuts as $s): ?>
        <div class="col-md-3 col-sm-6">
          <a href="<?= e($s['url']) ?>" class="card text-decoration-none text-dark shadow-sm h-100 layanan-card">
            <div class="card-body text-center py-3">
              <i class="bi <?= e($s['icon']) ?> text-<?= e($s['color']) ?> fs-2 mb-2"></i>
              <div class="small fw-semibold"><?= e($s['label']) ?></div>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php endif; // end admin/petugas ?>

      <!-- DOKTER: Antrean hari ini -->
      <?php if ($role === 'dokter'): ?>
      <h5 class="fw-bold mb-3"><i class="bi bi-calendar-day me-2 text-primary"></i>Antrean Hari Ini</h5>
      <?php
        $id_dokter = $_SESSION['id_dokter'] ?? '';
        $antrean   = [];
        if ($id_dokter) {
            $r = api_get('kunjungan.php', [
                'id_dokter'          => $id_dokter,
                'tanggal_kunjungan'  => date('Y-m-d'),
                'limit'              => 50,
            ]);
            $antrean = $r['ok'] ? ($r['data'] ?? []) : [];
        }
      ?>
      <?php if (empty($antrean)): ?>
      <div class="alert alert-info"><i class="bi bi-info-circle me-2"></i>Belum ada antrean untuk hari ini.</div>
      <?php else: ?>
      <div class="card shadow-sm">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light"><tr>
                <th>#</th><th>No. Kunjungan</th><th>Pasien</th><th>Keluhan</th><th>Status</th><th>Aksi</th>
              </tr></thead>
              <tbody>
              <?php foreach ($antrean as $i => $k): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><span class="badge bg-secondary"><?= e($k['no_kunjungan'] ?? '') ?></span></td>
                <td><?= e($k['nama_pasien'] ?? '') ?></td>
                <td><?= e(mb_strimwidth($k['keluhan'] ?? '', 0, 50, '…')) ?></td>
                <td>
                  <?php $st = $k['status'] ?? ''; ?>
                  <span class="badge bg-<?= $st==='selesai'?'success':($st==='diperiksa'?'warning text-dark':'secondary') ?>">
                    <?= e($st) ?>
                  </span>
                </td>
                <td>
                  <a href="/rs_app/modules/kunjungan/periksa.php?id=<?= e($k['id_kunjungan'] ?? '') ?>"
                     class="btn btn-sm btn-primary">
                    <i class="bi bi-clipboard-pulse"></i> Periksa
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endif; ?>
      <?php endif; // end dokter ?>

      <!-- PASIEN: Info kunjungan terakhir + tombol daftar -->
      <?php if ($role === 'pasien'): ?>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body text-center py-4">
              <i class="bi bi-clipboard-plus text-primary fs-1 mb-3"></i>
              <h5 class="fw-bold">Daftar Berobat</h5>
              <p class="text-muted small">Daftarkan diri Anda untuk kunjungan ke poliklinik</p>
              <a href="/rs_app/modules/kunjungan/daftar.php" class="btn btn-primary px-4">
                <i class="bi bi-plus-circle me-1"></i>Daftar Sekarang
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h6 class="fw-bold text-primary mb-3"><i class="bi bi-clock-history me-1"></i>Kunjungan Terakhir</h6>
              <?php
                $id_pasien = $_SESSION['id_pasien'] ?? '';
                $riwayat   = [];
                if ($id_pasien) {
                    $rw = api_get('kunjungan.php', ['id_pasien' => $id_pasien, 'limit' => 5]);
                    $riwayat = $rw['ok'] ? ($rw['data'] ?? []) : [];
                }
              ?>
              <?php if (empty($riwayat)): ?>
              <p class="text-muted text-center py-3">Belum ada riwayat kunjungan.</p>
              <?php else: ?>
              <ul class="list-group list-group-flush">
                <?php foreach ($riwayat as $k): ?>
                <li class="list-group-item px-0">
                  <div class="d-flex justify-content-between">
                    <span class="fw-semibold small"><?= e($k['nama_poli'] ?? '') ?></span>
                    <span class="badge bg-<?= ($k['status']??'')==='selesai'?'success':'warning text-dark' ?>"><?= e($k['status'] ?? '') ?></span>
                  </div>
                  <small class="text-muted"><?= format_tanggal($k['tanggal_kunjungan'] ?? '') ?> — <?= e($k['nama_dokter'] ?? '') ?></small>
                </li>
                <?php endforeach; ?>
              </ul>
              <div class="mt-2 text-end">
                <a href="/rs_app/modules/kunjungan/index.php" class="small">Lihat semua &rarr;</a>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endif; // end pasien ?>

    </main>
  </div>
</div>
<?php require __DIR__ . '/partials/footer.php'; ?>
