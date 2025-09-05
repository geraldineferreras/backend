<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class BaseController extends CI_Controller {
    public function __construct() {
        parent::__construct();
        
        // Handle CORS preflight requests immediately
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->handle_cors_preflight();
            exit();
        }
        
        // Set CORS headers for all API responses
        $this->set_cors_headers();
    }
    
    private function handle_cors_preflight() {
        // Set CORS headers for preflight request
        $this->set_cors_headers();
        
        // Set additional headers for preflight
        header('Access-Control-Max-Age: 86400'); // 24 hours cache
        
        // Return 200 OK for preflight
        http_response_code(200);
        exit();
    }
    
    private function set_cors_headers() {
        // Get the origin from the request
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        
        // Allow specific origins (add your frontend URLs here)
        $allowed_origins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:8080',
            'http://localhost:5173',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'http://127.0.0.1:8080',
            'http://127.0.0.1:5173',
            'http://localhost',
            'http://127.0.0.1',
            'https://scmsupdatedbackup.vercel.app',
        ];
        
        // Check if origin is allowed or use wildcard for development
        if (in_array($origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } else {
            // For development, allow all origins
            header('Access-Control-Allow-Origin: *');
        }
        
        // Allow specific methods
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        
        // Allow specific headers - comprehensive list for axios compatibility
        header('Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Authorization, X-Requested-With, X-API-Key, Cache-Control, Pragma, Origin, Accept, X-CSRF-TOKEN, X-XSRF-TOKEN, X-HTTP-Method-Override');
        
        // Allow credentials (cookies, authorization headers, etc.)
        header('Access-Control-Allow-Credentials: true');
        
        // Set content type for JSON responses
        header('Content-Type: application/json; charset=utf-8');
        
        // Additional headers for better axios compatibility
        header('X-Content-Type-Options: nosniff');
        
        // Set X-Frame-Options to allow embedding from allowed origins
        if (in_array($origin, $allowed_origins)) {
            header('X-Frame-Options: ALLOW-FROM ' . $origin);
        } else {
            // For development, allow all origins to embed
            header('X-Frame-Options: ALLOWALL');
        }
        
        header('X-XSS-Protection: 1; mode=block');
    }

    protected function send_response($data, $status_code = 200) {
        $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
    
    protected function send_success($data = null, $message = 'Success', $status_code = 200) {
        $response = [
            'status' => true,
            'message' => $message
        ];
        if ($data !== null) {
            $response['data'] = $data;
        }
        $this->send_response($response, $status_code);
    }
    
    protected function send_error($message = 'Error', $status_code = 400, $data = null) {
        $response = [
            'status' => false,
            'message' => $message
        ];
        if ($data !== null) {
            $response['data'] = $data;
        }
        $this->send_response($response, $status_code);
    }
    
    protected function get_json_input() {
        $input = file_get_contents('php://input');
        $data = json_decode($input);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->send_error('Invalid JSON format', 400);
            return null;
        }
        return $data;
    }
    
    protected function validate_required_fields($data, $required_fields) {
        $missing_fields = [];
        foreach ($required_fields as $field) {
            if (!isset($data->$field) || empty($data->$field)) {
                $missing_fields[] = $field;
            }
        }
        if (!empty($missing_fields)) {
            $this->send_error('Missing required fields: ' . implode(', ', $missing_fields), 400);
            return false;
        }
        return true;
    }
} 