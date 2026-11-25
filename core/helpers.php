<?php
/**
 * core/helpers.php
 * Fungsi pembantu global: escape, redirect, flash message, paginasi, format tanggal.
 */

/**
 * Escape output HTML untuk mencegah XSS.
 */
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

/**
 * Buat URL absolut relatif dari base rs_app.
 */
function base_url(string $path = ''): string {
    return '/rs_app/' . ltrim($path, '/');
}

/**
 * Redirect ke URL relatif dari webroot rs_app, dengan opsional flash message.
 */
function redirect(string $path, string $pesan = '', string $tipe = 'success'): never {
    if ($pesan !== '') {
        $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
    }
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    // Hitung kedalaman relatif agar redirect selalu dari root
    header('Location: /rs_app/' . ltrim($path, '/'));
    exit;
}

/**
 * Simpan flash message ke session (alias untuk dipanggil terpisah dari redirect).
 */
function flash(string $pesan, string $tipe = 'success'): void {
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

/**
 * Ambil dan hapus flash message dari session. Return array atau null.
 */
function get_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        // Dukung kedua konvensi: tipe/type, pesan/message
        $tipe  = $f['tipe'] ?? $f['type'] ?? 'info';
        $pesan = $f['pesan'] ?? $f['message'] ?? '';
        return [
            'tipe'    => $tipe,
            'type'    => $tipe,
            'pesan'   => $pesan,
            'message' => $pesan,
        ];
    }
    return null;
}

/**
 * Render HTML flash message Bootstrap.
 */
function render_flash(): void {
    $f = get_flash();
    if (!$f) return;
    $tipe_map = [
        'success' => 'success',
        'error'   => 'danger',
        'warning' => 'warning',
        'info'    => 'info',
    ];
    $bs = $tipe_map[$f['tipe']] ?? 'info';
    echo '<div class="alert alert-' . $bs . ' alert-dismissible fade show" role="alert">'
        . e($f['pesan'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'
        . '</div>';
}

/**
 * Hitung data paginasi.
 * @return array ['page','limit','offset','total_halaman','dari','sampai']
 */
function paginasi(int $total, int $page = 1, int $limit = 10): array {
    $page  = max(1, $page);
    $limit = max(1, $limit);
    $total_halaman = (int)ceil($total / $limit);
    $offset = ($page - 1) * $limit;
    return [
        'page'          => $page,
        'limit'         => $limit,
        'offset'        => $offset,
        'total_halaman' => $total_halaman,
        'dari'          => $total > 0 ? $offset + 1 : 0,
        'sampai'        => min($offset + $limit, $total),
    ];
}

/**
 * Render link paginasi Bootstrap.
 */
function render_paginasi(int $page, int $total_halaman, string $url_base): void {
    if ($total_halaman <= 1) return;
    echo '<nav><ul class="pagination pagination-sm justify-content-center mb-0">';
    for ($i = 1; $i <= $total_halaman; $i++) {
        $aktif = ($i === $page) ? ' active' : '';
        $url   = $url_base . (str_contains($url_base, '?') ? '&' : '?') . 'page=' . $i;
        echo "<li class=\"page-item{$aktif}\"><a class=\"page-link\" href=\"" . e($url) . "\">{$i}</a></li>";
    }
    echo '</ul></nav>';
}

/**
 * Format tanggal dari format Y-m-d ke d/m/Y (Indonesia).
 */
function format_tanggal(string $tgl): string {
    if (!$tgl) return '-';
    try {
        return (new DateTime($tgl))->format('d/m/Y');
    } catch (Exception) {
        return e($tgl);
    }
}

/**
 * Format datetime ke d/m/Y H:i.
 */
function format_datetime(string $dt): string {
    if (!$dt) return '-';
    try {
        return (new DateTime($dt))->format('d/m/Y H:i');
    } catch (Exception) {
        return e($dt);
    }
}

/**
 * Bersihkan string input (trim, strip null bytes).
 */
function bersih(string $val): string {
    return trim(str_replace("\0", '', $val));
}
