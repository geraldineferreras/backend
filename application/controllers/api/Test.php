<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class Test extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function index() {
        echo json_encode([
            'status' => true,
            'message' => 'Test controller is working!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function login() {
        echo json_encode([
            'status' => true,
            'message' => 'Test login endpoint is working!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public function upload_test() {
        // This is a test endpoint to verify upload functionality
        // It doesn't require authentication for testing purposes
        
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Upload test endpoint is working',
                'endpoints' => [
                    'profile_upload' => base_url('api/upload/profile'),
                    'cover_upload' => base_url('api/upload/cover'),
                    'image_serve' => base_url('image/{type}/{filename}'),
                    'registration' => base_url('api/auth/register')
                ],
                'instructions' => [
                    '1. Upload profile image to: POST /api/upload/profile',
                    '2. Upload cover image to: POST /api/upload/cover', 
                    '3. Use returned file_path in registration payload',
                    '4. Images can be accessed via: /image/{type}/{filename}'
                ]
            ]));
    }
} 