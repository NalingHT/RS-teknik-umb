/* assets/js/app.js — Interaktivitas ringan RS App */

document.addEventListener('DOMContentLoaded', function () {

  // --- Toggle sidebar mobile ---
  const toggleBtn = document.getElementById('toggleSidebar');
  const sidebar   = document.getElementById('sidebar');
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener('click', () => sidebar.classList.toggle('open'));
    document.addEventListener('click', (e) => {
      if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('open');
      }
    });
  }

  // --- Konfirmasi hapus ---
  document.querySelectorAll('.btn-hapus').forEach(btn => {
    btn.addEventListener('click', function (e) {
      const nama = this.dataset.nama || 'data ini';
      if (!confirm(`Yakin ingin menghapus ${nama}? Tindakan tidak dapat dibatalkan.`)) {
        e.preventDefault();
      }
    });
  });

  // --- Filter dokter berdasarkan poli (kunjungan form) ---
  const pilihanPoli   = document.getElementById('id_poli');
  const pilihanDokter = document.getElementById('id_dokter');
  if (pilihanPoli && pilihanDokter) {
    pilihanPoli.addEventListener('change', function () {
      const id_poli = this.value;
      if (!id_poli) {
        pilihanDokter.innerHTML = '<option value="">-- Pilih Poli dulu --</option>';
        return;
      }
      pilihanDokter.innerHTML = '<option value="">Memuat...</option>';
      fetch(`/rs_app/modules/kunjungan/ajax_dokter.php?id_poli=${encodeURIComponent(id_poli)}`)
        .then(r => r.json())
        .then(data => {
          pilihanDokter.innerHTML = '<option value="">-- Pilih Dokter --</option>';
          if (data.ok && Array.isArray(data.data)) {
            data.data.forEach(d => {
              const opt = document.createElement('option');
              opt.value = d.id_dokter;
              opt.textContent = d.nama_dokter + (d.spesialis ? ` (${d.spesialis})` : '');
              pilihanDokter.appendChild(opt);
            });
          } else {
            pilihanDokter.innerHTML = '<option value="">Tidak ada dokter</option>';
          }
        })
        .catch(() => {
          pilihanDokter.innerHTML = '<option value="">Gagal memuat</option>';
        });
    });
  }

  // --- Toggle cara bayar asuransi ---
  const caraBayar     = document.getElementById('cara_bayar');
  const noKartuWrap   = document.getElementById('wrap_no_kartu_asuransi');
  if (caraBayar && noKartuWrap) {
    function toggleAsuransi() {
      noKartuWrap.style.display = (caraBayar.value === 'asuransi') ? 'block' : 'none';
    }
    caraBayar.addEventListener('change', toggleAsuransi);
    toggleAsuransi();
  }

  // --- Auto-dismiss alert setelah 4 detik ---
  document.querySelectorAll('.alert.alert-success, .alert.alert-info').forEach(el => {
    setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 300);
    }, 4000);
  });

});
