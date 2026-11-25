<?php
/**
 * core/api_client.php
 * HTTP client tunggal untuk memanggil Server B via cURL.
 * Fungsi: api_get, api_post, api_put, api_delete
 * Semua mengembalikan: ['ok'=>bool,'http'=>int,'data'=>mixed,'meta'=>array,'message'=>string,'errors'=>array]
 */

$_cfg_api = require __DIR__ . '/../config/app.php';

/**
 * Eksekusi cURL ke Server B.
 *
 * @param string $method  GET|POST|PUT|DELETE
 * @param string $path    Endpoint relatif dari api_base_url, mis. 'pasien.php?id=1'
 * @param array  $data    Body untuk POST/PUT (akan di-JSON-encode)
 * @return array Struktur seragam respons
 */
function _api_call(string $method, string $path, array $data = []): array {
    global $_cfg_api;

    $url = rtrim($_cfg_api['api_base_url'], '/') . '/' . ltrim($path, '/');

    $ch = curl_init();

    $headers = [
        'X-API-KEY: ' . ($_cfg_api['api_key'] ?? ''),
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    // Tambahkan token Bearer jika sudah login
    if (!empty($_SESSION['token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
    }

    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 2,                                // Cepat deteksi jika Server B offline
        CURLOPT_TIMEOUT        => (int)($_cfg_api['api_timeout'] ?? 5),
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => true,                             // aktif di produksi HTTPS
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    switch (strtoupper($method)) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
            break;
        case 'GET':
        default:
            // data GET sudah masuk ke $path sebagai query string
            break;
    }

    $raw      = curl_exec($ch);
    $http     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = curl_error($ch);
    curl_close($ch);

    // cURL gagal atau timeout
    if ($raw === false || $curl_err) {
        error_log("[API_CLIENT] cURL error ({$method} {$url}): {$curl_err}");
        return _api_gagal('Layanan data sedang tidak tersedia. Silakan coba beberapa saat lagi.');
    }

    // Respons bukan JSON
    $decoded = json_decode($raw, true);
    if ($decoded === null) {
        error_log("[API_CLIENT] Non-JSON response ({$method} {$url}): " . substr($raw, 0, 300));
        return _api_gagal('Layanan data sedang tidak tersedia. Silakan coba beberapa saat lagi.');
    }

    // HTTP 401 → session expired, hapus dan redirect login
    if ($http === 401) {
        $pesan = $decoded['message'] ?? 'Sesi berakhir, silakan login kembali.';
        session_hancurkan();
        // Simpan flash ke cookie sementara karena session sudah hancur
        redirect('login.php', $pesan, 'warning');
    }

    $ok = ($decoded['status'] ?? '') === 'success';

    return [
        'ok'      => $ok,
        'http'    => $http,
        'data'    => $decoded['data']    ?? null,
        'meta'    => $decoded['meta']    ?? [],
        'message' => $decoded['message'] ?? '',
        'errors'  => $decoded['errors']  ?? [],
    ];
}

/** Struktur gagal seragam (tanpa detail teknis). */
function _api_gagal(string $pesan): array {
    return [
        'ok'      => false,
        'http'    => 0,
        'data'    => null,
        'meta'    => [],
        'message' => $pesan,
        'errors'  => [],
    ];
}

/** GET request ke Server B. */
function api_get(string $path, array $params = []): array {
    if ($params) {
        $path .= (str_contains($path, '?') ? '&' : '?') . http_build_query($params);
    }
    return _api_call('GET', $path);
}

/** POST request ke Server B. */
function api_post(string $path, array $data = []): array {
    return _api_call('POST', $path, $data);
}

/** PUT request ke Server B. */
function api_put(string $path, array $data = []): array {
    return _api_call('PUT', $path, $data);
}

/** DELETE request ke Server B. */
function api_delete(string $path, array $data = []): array {
    return _api_call('DELETE', $path, $data);
}
