<?php
/**
 * partials/header.php
 * Header HTML umum + Bootstrap CDN. Disertakan di semua halaman.
 * Variabel opsional: $page_title (string), $is_public (bool)
 */
$cfg      = $cfg ?? require __DIR__ . '/../config/app.php';
$app_name = e($cfg['app_name'] ?? 'RS App');
$title    = isset($page_title) ? e($page_title) . ' — ' . $app_name : $app_name;

// Header keamanan dasar (hanya kirim jika belum dikirim)
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $title ?></title>
  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="/rs_app/assets/css/app.css">
</head>
<body class="<?= isset($is_public) && $is_public ? 'bg-light' : 'bg-light' ?>">
