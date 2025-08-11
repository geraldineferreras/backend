<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class TestController extends BaseController {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Test endpoint to check if API is working
     * GET /api/test
     */
    public function test() {
        $response = [
            'success' => true,
            'message' => 'Test endpoint is working',
            'timestamp' => date('Y-m-d H:i:s'),
            'headers' => $this->get_headers_info()
        ];
        
        $this->send_success($response);
    }
    
    /**
     * Test headers endpoint
     * GET /api/test/headers
     */
    public function headers() {
        $headers_info = $this->get_headers_info();
        
        $response = [
            'success' => true,
            'message' => 'Headers test',
            'headers' => $headers_info
        ];
        
        $this->send_success($response);
    }
    
    /**
     * Get headers information
     */
    private function get_headers_info() {
        $headers_info = [];
        
        // Check getallheaders function
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $headers_info['getallheaders'] = $headers;
        } else {
            $headers_info['getallheaders'] = 'Function not available';
        }
        
        // Check $_SERVER variables
        $headers_info['server_vars'] = [];
        $auth_vars = [
            'HTTP_AUTHORIZATION',
            'REDIRECT_HTTP_AUTHORIZATION',
            'REQUEST_METHOD',
            'HTTP_ACCEPT',
            'HTTP_CONTENT_TYPE'
        ];
        
        foreach ($auth_vars as $var) {
            if (isset($_SERVER[$var])) {
                $headers_info['server_vars'][$var] = $_SERVER[$var];
            }
        }
        
        return $headers_info;
    }
}
