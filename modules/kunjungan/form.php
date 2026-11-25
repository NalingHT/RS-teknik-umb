<?php
/**
 * modules/kunjungan/form.php — Form tambah/ubah kunjungan (Admin & Petugas).
 */
require_once __DIR__ . '/../../core/session.php';
require_once __DIR__ . '/../../core/helpers.php';
require_once __DIR__ . '/../../core/csrf.php';
require_once __DIR__ . '/../../core/api_client.php';
require_once __DIR__ . '/../../core/rbac.php';

require_login();
require_role(['admin', 'petugas']);

$id   = (int)($_GET['id'] ?? 0);
$edit = $id > 0;
$data = [];
$page_title = $edit ? 'Edit Kunjungan' : 'Tambah Kunjungan';

// Load dropdown data
$resp_pasien = api_get('pasien.php', ['limit' => 200]);
$list_pasien = $resp_pasien['ok'] ? ($resp_pasien['data'] ?? []) : [];

$resp_poli = api_get('poliklinik.php', ['limit' => 200]);
$list_poli = $resp_poli['ok'] ? ($resp_poli['data'] ?? []) : [];

if ($edit) {
    $resp = api_get('kunjungan.php', ['id' => $id]);
    $data = $resp['ok'] ? ($resp['data'] ?? []) : [];
    if (empty($data) || (empty($data['id_kunjungan']) && empty($data['id']))) {
        flash('Data kunjungan tidak ditemukan.', 'error');
        redirect('modules/kunjungan/index.php');
    }
    $resp_dokter = api_get('dokter.php', ['id_poli' => $data['id_poli'] ?? 0, 'status' => 'aktif', 'limit' => 200]);
    $list_dokter = $resp_dokter['ok'] ? ($resp_dokter['data'] ?? []) : [];
} else {
    $list_dokter = [];
}

$old = $_SESSION['old_input'] ?? [];
unset($_SESSION['old_input']);

require __DIR__ . '/../../partials/header.php';
?>
<div class="app-layout">
  <?php require __DIR__ . '/../../partials/sidebar.php'; ?>
  <div class="app-content">
    <?php require __DIR__ . '/../../partials/topbar.php'; ?>
    <main class="app-main">
      <?php render_flash(); ?>

      <div class="card border-0 shadow-sm" style="max-width:780px">
        <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
          <h6 class="mb-0 fw-bold"><i class="bi bi-calendar2-plus me-2 text-primary"></i><?= e($page_title) ?></h6>
          <a href="index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Kembali</a>
        </div>
        <div class="card-body">
          <form method="post" action="simpan.php" id="formKunjungan" novalidate>
            <?= csrf_field() ?>
            <?php if ($edit): ?>
              <input type="hidden" name="id" value="<?= e($data['id_kunjungan'] ?? $data['id'] ?? $id) ?>">
            <?php endif; ?>

            <?php if ($edit): ?>
              <div class="mb-3">
                <label class="form-label fw-semibold">No. Kunjungan</label>
                <input type="text" class="form-control bg-light" value="<?= e($data['no_kunjungan'] ?? '') ?>" readonly>
              </div>
            <?php endif; ?>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold">Pasien <span class="text-danger">*</span></label>
                <?php $sel_pasien = $old['id_pasien'] ?? ($data['id_pasien'] ?? ''); ?>
                <select name="id_pasien" id="id_pasien" class="form-select" required <?= $edit ? 'disabled' : '' ?>>
                  <option value="">-- Pilih Pasien --</option>
                  <?php foreach ($list_pasien as $p): ?>
                    <?php $pid = $p['id_pasien'] ?? $p['id'] ?? ''; ?>
                    <option value="<?= e($pid) ?>"
                      data-asuransi="<?= e($p['id_asuransi'] ?? '') ?>"
                      <?= $sel_pasien == $pid ? 'selected' : '' ?>>
                      <?= e($p['nama_pasien'] ?? $p['nama'] ?? '') ?> (<?= e($p['no_rm'] ?? '-') ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if ($edit): ?>
                  <input type="hidden" name="id_pasien" value="<?= e($sel_pasien) ?>">
                <?php endif; ?>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Poliklinik <span class="text-danger">*</span></label>
                <?php $sel_poli = $old['id_poli'] ?? ($data['id_poli'] ?? ''); ?>
                <select name="id_poli" id="id_poli" class="form-select" required>
                  <option value="">-- Pilih Poli --</option>
                  <?php foreach ($list_poli as $p): ?>
                    <?php $plid = $p['id_poli'] ?? $p['id'] ?? ''; ?>
                    <option value="<?= e($plid) ?>" <?= $sel_poli == $plid ? 'selected' : '' ?>>
                      <?= e($p['nama_poli'] ?? $p['nama'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Dokter <span class="text-danger">*</span></label>
                <?php $sel_dokter = $old['id_dokter'] ?? ($data['id_dokter'] ?? ''); ?>
                <select name="id_dokter" id="id_dokter" class="form-select" required>
                  <option value="">-- Pilih Poli dahulu --</option>
                  <?php if ($edit && !empty($list_dokter)): ?>
                    <?php foreach ($list_dokter as $d): ?>
                      <?php $did = $d['id_dokter'] ?? $d['id'] ?? ''; ?>
                      <option value="<?= e($did) ?>" <?= $sel_dokter == $did ? 'selected' : '' ?>>
                        <?= e($d['nama_dokter'] ?? $d['nama'] ?? '') ?>
                      </option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
                <div id="dokter-loading" class="form-text text-muted d-none">
                  <span class="spinner-border spinner-border-sm"></span> Memuat daftar dokter…
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Tanggal Kunjungan <span class="text-danger">*</span></label>
                <input type="date" name="tanggal_kunjungan" class="form-control" required
                       min="<?= $edit ? '' : date('Y-m-d') ?>"
                       value="<?= e($old['tanggal_kunjungan'] ?? $data['tanggal_kunjungan'] ?? date('Y-m-d')) ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label fw-semibold">Cara Bayar <span class="text-danger">*</span></label>
                <?php $sel_cb = $old['cara_bayar'] ?? ($data['cara_bayar'] ?? 'umum'); ?>
                <select name="cara_bayar" id="cara_bayar" class="form-select" required>
                  <option value="">-- Pilih --</option>
                  <option value="umum"     <?= $sel_cb === 'umum'     ? 'selected' : '' ?>>Umum</option>
                  <option value="asuransi" <?= $sel_cb === 'asuransi' ? 'selected' : '' ?>>Asuransi</option>
                </select>
                <div id="asuransi-warning" class="alert alert-warning mt-2 d-none py-2 small">
                  Pasien yang dipilih tidak memiliki data asuransi.
                </div>
              </div>

              <div class="col-12">
                <label class="form-label fw-semibold">Keluhan</label>
                <textarea name="keluhan" class="form-control" rows="3"
                          placeholder="Tuliskan keluhan pasien…"><?= e($old['keluhan'] ?? $data['keluhan'] ?? '') ?></textarea>
              </div>
            </div>

            <div class="d-flex gap-2 mt-4">
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle me-1"></i><?= $edit ? 'Simpan Perubahan' : 'Simpan' ?>
              </button>
              <a href="index.php" class="btn btn-secondary">Batal</a>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
(function () {
  const poliSel    = document.getElementById('id_poli');
  const dokterSel  = document.getElementById('id_dokter');
  const loading    = document.getElementById('dokter-loading');
  const pasienSel  = document.getElementById('id_pasien');
  const caraBayar  = document.getElementById('cara_bayar');
  const asnWarn    = document.getElementById('asuransi-warning');

  function loadDokter(idPoli, selectedId) {
    if (!idPoli) {
      dokterSel.innerHTML = '<option value="">-- Pilih Poli dahulu --</option>';
      return;
    }
    loading.classList.remove('d-none');
    dokterSel.disabled = true;
    fetch('ajax_dokter.php?id_poli=' + encodeURIComponent(idPoli))
      .then(r => r.json())
      .then(json => {
        dokterSel.innerHTML = '<option value="">-- Pilih Dokter --</option>';
        (json.data || []).forEach(function (d) {
          const opt = document.createElement('option');
          const did = d.id_dokter || d.id;
          opt.value = did;
          opt.textContent = (d.nama_dokter || d.nama) + (d.spesialis ? ' (' + d.spesialis + ')' : '');
          if (String(did) === String(selectedId)) opt.selected = true;
          dokterSel.appendChild(opt);
        });
      })
      .catch(() => {
        dokterSel.innerHTML = '<option value="">Gagal memuat dokter</option>';
      })
      .finally(() => {
        loading.classList.add('d-none');
        dokterSel.disabled = false;
      });
  }

  if (poliSel) {
    poliSel.addEventListener('change', function () {
      loadDokter(this.value, '');
    });
  }

  function checkAsuransi() {
    if (!pasienSel || !caraBayar) return;
    const opt = pasienSel.options[pasienSel.selectedIndex];
    const hasAsuransi = opt && opt.dataset.asuransi && opt.dataset.asuransi !== '';
    if (caraBayar.value === 'asuransi' && !hasAsuransi) {
      asnWarn.classList.remove('d-none');
    } else {
      asnWarn.classList.add('d-none');
    }
  }

  if (pasienSel) pasienSel.addEventListener('change', checkAsuransi);
  if (caraBayar) caraBayar.addEventListener('change', checkAsuransi);

  document.getElementById('formKunjungan').addEventListener('submit', function (e) {
    if (pasienSel && caraBayar && caraBayar.value === 'asuransi') {
      const opt = pasienSel.options[pasienSel.selectedIndex];
      if (!opt || !opt.dataset.asuransi || opt.dataset.asuransi === '') {
        e.preventDefault();
        asnWarn.classList.remove('d-none');
        caraBayar.focus();
      }
    }
  });
})();
</script>

<?php require __DIR__ . '/../../partials/footer.php'; ?>
