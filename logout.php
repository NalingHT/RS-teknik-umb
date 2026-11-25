<?php
/**
 * logout.php — Hancurkan session, panggil auth/logout.php Server B, redirect ke login.
 */
require_once __DIR__ . '/core/session.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/csrf.php';
require_once __DIR__ . '/core/api_client.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    // Beri tahu Server B agar invalidasi token
    if (!empty($_SESSION['token'])) {
        api_post('auth/logout.php', []);
    }
}

session_hancurkan();
redirect('login.php', 'Anda telah berhasil logout.', 'info');
