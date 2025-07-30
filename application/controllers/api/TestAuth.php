<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class TestAuth extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    public function login() {
        echo json_encode([
            'status' => true,
            'message' => 'TestAuth login endpoint is working!',
            'timestamp' => date('Y-m-d H:i:s'),
            'request_method' => $_SERVER['REQUEST_METHOD']
        ]);
    }
} 