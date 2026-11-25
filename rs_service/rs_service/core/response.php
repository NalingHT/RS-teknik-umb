<?php
// Helper standar respons JSON (sukses & gagal)

function json_ok($data = [], array $meta = [], int $code = 200, string $message = 'OK'): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    $res = [
        'status'  => 'success',
        'message' => $message,
        'data'    => $data,
    ];
    if (!empty($meta)) {
        $res['meta'] = $meta;
    }
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(int $code, string $message, array $errors = []): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    $res = [
        'status'  => 'error',
        'message' => $message,
    ];
    if (!empty($errors)) {
        $res['errors'] = $errors;
    }
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
    exit;
}