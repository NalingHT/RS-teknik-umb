<?php
/**
 * core/csrf.php
 * Generasi dan verifikasi CSRF token untuk semua form POST.
 */

/**
 * Ambil atau buat CSRF token untuk session saat ini.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render hidden input CSRF untuk form HTML.
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verifikasi CSRF token dari POST. Jika gagal, tampilkan 403 dan hentikan eksekusi.
 */
function csrf_verify(): void {
    $token_kirim  = $_POST['csrf_token'] ?? '';
    $token_session = $_SESSION['csrf_token'] ?? '';

    if (!$token_kirim || !$token_session || !hash_equals($token_session, $token_kirim)) {
        http_response_code(403);
        // Regenerasi token agar tidak bisa dipakai lagi
        unset($_SESSION['csrf_token']);
        // Tampilkan halaman error sederhana
        echo '<!DOCTYPE html><html><head><title>Ditolak</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
<div class="card shadow p-4 text-center" style="max-width:400px">
<h4 class="text-danger"><i class="bi bi-shield-exclamation"></i> Permintaan Ditolak</h4>
<p class="text-muted">Token keamanan tidak valid atau sudah kedaluwarsa. Silakan kembali dan coba lagi.</p>
<a href="javascript:history.back()" class="btn btn-primary">Kembali</a>
</div></body></html>';
        exit;
    }
}
