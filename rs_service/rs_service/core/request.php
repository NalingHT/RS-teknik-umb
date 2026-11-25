<?php
// Helper pembacaan HTTP method, JSON body, query string, dan pagination

function get_method(): string {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method === 'POST' && !empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
        $override = strtoupper(trim($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']));
        if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
            return $override;
        }
    }
    return $method;
}

function require_methods(array $allowed): string {
    $method = get_method();
    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        json_error(405, 'Metode HTTP tidak didukung');
    }
    return $method;
}

function get_json_body(): array {
    static $parsed = null;
    if ($parsed !== null) {
        return $parsed;
    }
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        $parsed = [];
        return $parsed;
    }
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        json_error(400, 'Format body JSON tidak valid');
    }
    $parsed = $data;
    return $parsed;
}

function get_query(string $key, $default = null) {
    return isset($_GET[$key]) ? trim((string)$_GET[$key]) : $default;
}

function get_pagination(): array {
    $page  = max(1, (int)get_query('page', 1));
    $limit = (int)get_query('limit', 10);
    if ($limit < 1) $limit = 10;
    if ($limit > 100) $limit = 100;
    $offset = ($page - 1) * $limit;
    return [$page, $limit, $offset];
}

function get_client_ip(): string {
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function filter_whitelist(array $input, array $allowedFields): array {
    $clean = [];
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $input)) {
            $val = $input[$field];
            $clean[$field] = is_string($val) ? trim($val) : $val;
        }
    }
    return $clean;
}