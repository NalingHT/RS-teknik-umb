<?php
// Helper autentikasi API Key, Bearer Token, dan otorisasi RBAC

$GLOBALS['CURRENT_USER'] = null;
$GLOBALS['API_CLIENT']   = null;

function get_header_value(string $name): ?string {
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    if (!empty($_SERVER[$key])) {
        return trim($_SERVER[$key]);
    }
    if (function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $k => $v) {
            if (strcasecmp($k, $name) === 0) {
                return trim($v);
            }
        }
    }
    return null;
}

function require_api_key(): string {
    $provided = get_header_value('X-API-KEY');
    if (!$provided) {
        json_error(401, 'Header X-API-KEY wajib disertakan');
    }

    $keys = get_config()['api_keys'] ?? [];
    foreach ($keys as $clientName => $validKey) {
        if (hash_equals($validKey, $provided)) {
            $GLOBALS['API_CLIENT'] = $clientName;
            return $clientName;
        }
    }

    json_error(401, 'API key tidak valid');
    return '';
}

function parse_bearer_token(): ?string {
    $authHeader = get_header_value('Authorization');
    if (!$authHeader) return null;
    if (preg_match('/^Bearer\s+([a-f0-9]{64})$/i', $authHeader, $matches)) {
        return $matches[1];
    }
    return null;
}

function load_user_from_token(?string $rawToken): ?array {
    if (!$rawToken) return null;

    $tokenHash = hash('sha256', $rawToken);
    $db = get_db();

    $sql = "SELECT t.id_token, t.expires_at, t.revoked,
                   u.id_pengguna, u.username, u.nama_lengkap, u.email, u.role, u.status,
                   d.id_dokter, p.id_pasien
            FROM api_token t
            JOIN pengguna u ON u.id_pengguna = t.id_pengguna
            LEFT JOIN dokter d ON d.id_pengguna = u.id_pengguna
            LEFT JOIN pasien p ON p.id_pengguna = u.id_pengguna
            WHERE t.token_hash = ? LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('s', $tokenHash);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) return null;
    if ((int)$row['revoked'] === 1) return null;
    if (strtotime($row['expires_at']) <= time()) return null;
    if ($row['status'] !== 'aktif') return null;

    $row['raw_token_hash'] = $tokenHash;
    $GLOBALS['CURRENT_USER'] = $row;
    return $row;
}

function optional_token(): ?array {
    $raw = parse_bearer_token();
    if (!$raw) return null;
    $user = load_user_from_token($raw);
    if (!$user) {
        json_error(401, 'Token tidak valid atau sudah kedaluwarsa');
    }
    return $user;
}

function require_token(): array {
    $raw = parse_bearer_token();
    if (!$raw) {
        json_error(401, 'Token autentikasi (Bearer) wajib disertakan');
    }
    $user = load_user_from_token($raw);
    if (!$user) {
        json_error(401, 'Token tidak valid atau sudah kedaluwarsa');
    }
    return $user;
}

function current_user(): ?array {
    return $GLOBALS['CURRENT_USER'];
}

function require_role(array $allowedRoles, string $modul = 'api'): array {
    $user = require_token();
    if (!in_array($user['role'], $allowedRoles, true)) {
        write_audit((int)$user['id_pengguna'], 'AKSES_DITOLAK', $modul, "Role {$user['role']} mencoba akses {$modul}");
        json_error(403, 'Anda tidak memiliki hak akses untuk fitur ini');
    }
    return $user;
}