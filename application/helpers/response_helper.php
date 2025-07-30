<?php
defined('BASEPATH') OR exit('No direct script access allowed');

function response_success($message, $data = null) {
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function response_error($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'status' => 'error',
        'message' => $message
    ]);
    exit;
}

function json_response($status, $message, $data = null, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
