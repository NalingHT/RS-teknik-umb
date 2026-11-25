<?php
// Konfigurasi aktif lokal XAMPP
return [
  'db' => [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'db_rumah_sakit'
  ],
  'api_keys' => [
    'rs_app' => 'RS_KEY_2026_UMB_WEBSEMANTIK_998877'
  ],
  'token_ttl_minutes' => 120,
  'login_max_gagal' => 5,
  'login_blokir_menit' => 10,
  'debug' => true,
];