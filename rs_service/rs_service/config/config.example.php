<?php
// Contoh konfigurasi Server B (rs_service)
return [
  'db' => [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'name' => 'db_rumah_sakit'
  ],
  'api_keys' => [
    'rs_app' => 'GANTI_DENGAN_API_KEY_RAHASIA'
  ],
  'token_ttl_minutes' => 120,
  'login_max_gagal' => 5,
  'login_blokir_menit' => 10,
  'debug' => false,
];