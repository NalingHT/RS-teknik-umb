<?php
/**
 * core/rbac.php
 * Kontrol akses berbasis peran (RBAC) sisi Server A.
 * Periksa §5 PRD untuk matriks lengkap.
 */

/**
 * Matriks hak akses: [modul][aksi] => [role yang diizinkan]
 */
const RBAC_MATRIKS = [
    'poliklinik' => [
        'read'   => ['admin','petugas','dokter','pasien'],
        'create' => ['admin'],
        'update' => ['admin'],
        'delete' => ['admin'],
    ],
    'pengguna' => [
        'read'   => ['admin'],
        'create' => ['admin'],
        'update' => ['admin'],
        'delete' => ['admin'],
    ],
    'dokter' => [
        'read'   => ['admin','petugas','dokter','pasien'],
        'create' => ['admin'],
        'update' => ['admin','dokter'],
        'delete' => ['admin'],
    ],
    'pasien' => [
        'read'   => ['admin','petugas','dokter','pasien'],
        'create' => ['admin','petugas'],
        'update' => ['admin','petugas','pasien'],
        'delete' => ['admin'],
    ],
    'asuransi' => [
        'read'   => ['admin','petugas','dokter','pasien'],
        'create' => ['admin'],
        'update' => ['admin'],
        'delete' => ['admin'],
    ],
    'kunjungan' => [
        'read'   => ['admin','petugas','dokter','pasien'],
        'create' => ['admin','petugas','pasien'],
        'update' => ['admin','petugas','dokter'],
        'delete' => ['admin'],
    ],
    'laporan_kunjungan' => [
        'read'   => ['admin','petugas','dokter'],
    ],
    'laporan_pasien_asuransi' => [
        'read'   => ['admin','petugas'],
    ],
    'laporan_dokter_poli' => [
        'read'   => ['admin'],
    ],
    'profil' => [
        'read'   => ['admin','petugas','dokter','pasien'],
        'update' => ['admin','petugas','dokter','pasien'],
    ],
    'dashboard' => [
        'read'   => ['admin','petugas','dokter','pasien'],
    ],
];

/**
 * Periksa apakah role saat ini memiliki hak atas modul + aksi.
 */
function can(string $modul, string $aksi): bool {
    $role = $_SESSION['role'] ?? '';
    return in_array($role, RBAC_MATRIKS[$modul][$aksi] ?? [], true);
}

/**
 * Redirect ke login jika belum login (panggil di atas setiap halaman protected).
 */
function require_login(): void {
    if (empty($_SESSION['token'])) {
        redirect('login.php', 'Silakan login terlebih dahulu.', 'warning');
    }
    session_cek_idle();
}

/**
 * Blokir akses jika role tidak termasuk dalam daftar yang diizinkan.
 */
function require_role(array $roles): void {
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $roles, true)) {
        akses_ditolak();
    }
}

/**
 * Tampilkan halaman akses ditolak dan hentikan eksekusi.
 */
function akses_ditolak(): never {
    http_response_code(403);
    require __DIR__ . '/../partials/header.php';
    echo '<div class="container mt-5 text-center">
        <div class="card shadow-sm p-5">
        <h2 class="text-danger"><i class="bi bi-shield-lock"></i> Akses Ditolak</h2>
        <p class="text-muted mt-3">Anda tidak memiliki izin untuk mengakses halaman ini.</p>
        <a href="/rs_app/dashboard.php" class="btn btn-primary mt-2">Kembali ke Dashboard</a>
        </div></div>';
    require __DIR__ . '/../partials/footer.php';
    exit;
}

/**
 * Bangun menu sidebar sesuai role.
 * @return array [['label','url','icon','aktif'=>bool], ...]
 */
function menu_untuk_role(string $halaman_aktif = ''): array {
    $role = $_SESSION['role'] ?? '';
    $menu = [];

    // Dashboard & Menu Utama — semua role
    $menu[] = ['label'=>'Dashboard','url'=>'/rs_app/dashboard.php','icon'=>'bi-speedometer2'];
    $menu[] = ['label'=>'Menu Utama','url'=>'/rs_app/menu.php','icon'=>'bi-grid-fill'];

    // Poliklinik — semua role (CRUD hanya admin)
    $menu[] = ['label'=>'Poliklinik','url'=>'/rs_app/modules/poliklinik/index.php','icon'=>'bi-hospital'];

    // Dokter
    $menu[] = ['label'=>'Dokter','url'=>'/rs_app/modules/dokter/index.php','icon'=>'bi-person-badge'];

    // Pasien
    if (in_array($role, ['admin','petugas','dokter','pasien'])) {
        $menu[] = ['label'=>'Pasien','url'=>'/rs_app/modules/pasien/index.php','icon'=>'bi-people'];
    }

    // Asuransi
    if (in_array($role, ['admin','petugas','dokter','pasien'])) {
        $menu[] = ['label'=>'Asuransi','url'=>'/rs_app/modules/asuransi/index.php','icon'=>'bi-shield-check'];
    }

    // Kunjungan
    $menu[] = ['label'=>'Kunjungan','url'=>'/rs_app/modules/kunjungan/index.php','icon'=>'bi-calendar2-check'];

    // Daftar berobat khusus pasien
    if ($role === 'pasien') {
        $menu[] = ['label'=>'Daftar Berobat','url'=>'/rs_app/modules/kunjungan/daftar.php','icon'=>'bi-clipboard-plus'];
    }

    // Pengguna — hanya admin
    if ($role === 'admin') {
        $menu[] = ['label'=>'Pengguna','url'=>'/rs_app/modules/pengguna/index.php','icon'=>'bi-person-gear'];
    }

    // Laporan
    if (in_array($role, ['admin','petugas','dokter'])) {
        $menu[] = ['label'=>'Lap. Kunjungan','url'=>'/rs_app/modules/laporan/kunjungan.php','icon'=>'bi-bar-chart'];
    }
    if (in_array($role, ['admin','petugas'])) {
        $menu[] = ['label'=>'Lap. Pasien Asuransi','url'=>'/rs_app/modules/laporan/pasien_asuransi.php','icon'=>'bi-file-earmark-text'];
    }
    if ($role === 'admin') {
        $menu[] = ['label'=>'Lap. Dokter-Poli','url'=>'/rs_app/modules/laporan/dokter_poli.php','icon'=>'bi-diagram-3'];
    }

    // Profil — semua
    $menu[] = ['label'=>'Profil','url'=>'/rs_app/modules/profil/index.php','icon'=>'bi-person-circle'];

    // Tandai menu aktif berdasarkan URL saat ini
    $url_saat_ini = $_SERVER['REQUEST_URI'];
    foreach ($menu as &$m) {
        $m['aktif'] = str_starts_with($url_saat_ini, $m['url']);
    }

    return $menu;
}
