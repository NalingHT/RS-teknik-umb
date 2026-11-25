<?php
/**
 * core/session.php
 * Manajemen session aman: httponly, samesite, timeout idle, regenerate ID.
 */

$cfg = require __DIR__ . '/../config/app.php';

// Konfigurasi cookie session sebelum session_start
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);

ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Periksa timeout idle session.
 * Jika idle melebihi batas, hancurkan session dan redirect ke login.
 */
function session_cek_idle(): void {
    global $cfg;
    $idle_maks = ($cfg['session_idle_minutes'] ?? 30) * 60;

    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $idle_maks) {
        session_hancurkan();
        redirect('login.php', 'Sesi Anda telah berakhir karena tidak aktif. Silakan login kembali.', 'warning');
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Hancurkan session sepenuhnya (saat logout atau timeout).
 */
function session_hancurkan(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
