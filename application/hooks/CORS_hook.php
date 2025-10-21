<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class CORS_hook {
    
    public function handle_cors() {
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
            'https://scmssystem.vercel.app'
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
        
        // Allow specific headers
        header('Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Authorization, X-Requested-With, X-API-Key, Cache-Control, Pragma, Origin, Accept, X-CSRF-TOKEN, X-XSRF-TOKEN, X-HTTP-Method-Override');
        
        // Allow credentials
        header('Access-Control-Allow-Credentials: true');
        
        // Handle preflight requests
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Max-Age: 86400'); // 24 hours cache
            http_response_code(200);
            exit();
        }
    }
}