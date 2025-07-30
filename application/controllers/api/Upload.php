<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class Upload extends BaseController {

    public function __construct() {
        parent::__construct();
        $this->load->helper(['response', 'auth']);
        $this->load->library('upload');
    }

    public function profile() {
        // Temporarily remove authentication for testing
        // $user_data = require_auth($this);
        // if (!$user_data) {
        //     return; // Error response already sent
        // }

        $this->_upload_image('profile');
    }

    public function cover() {
        // Temporarily remove authentication for testing
        // $user_data = require_auth($this);
        // if (!$user_data) {
        //     return; // Error response already sent
        // }

        $this->_upload_image('cover');
    }

    public function announcement() {
        // Allow more file types for announcements
        $this->_upload_announcement_file('announcement');
    }

    private function _upload_image($type) {
        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false, 
                    'message' => 'No image file uploaded or upload error occurred'
                ]));
            return;
        }

        // Configure upload settings
        $upload_path = FCPATH . 'uploads/' . $type . '/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'gif|jpg|jpeg|png|webp';
        $config['max_size'] = 5120; // 5MB
        $config['max_width'] = 2048;
        $config['max_height'] = 2048;
        $config['encrypt_name'] = true; // Generate unique filename
        $config['remove_spaces'] = true;

        $this->upload->initialize($config);

        // Perform upload
        if (!$this->upload->do_upload('image')) {
            $error = $this->upload->display_errors('', '');
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false, 
                    'message' => 'Upload failed: ' . $error
                ]));
            return;
        }

        // Get upload data
        $upload_data = $this->upload->data();
        $file_path = 'uploads/' . $type . '/' . $upload_data['file_name'];

        // Return success response with file path
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => ucfirst($type) . ' image uploaded successfully',
                'data' => [
                    'file_path' => $file_path,
                    'file_name' => $upload_data['file_name'],
                    'file_size' => $upload_data['file_size'],
                    'image_width' => $upload_data['image_width'],
                    'image_height' => $upload_data['image_height']
                ]
            ]));
    }

    private function _upload_announcement_file($type) {
        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false, 
                    'message' => 'No file uploaded or upload error occurred'
                ]));
            return;
        }

        // Configure upload settings
        $upload_path = FCPATH . 'uploads/' . $type . '/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }
        $config['upload_path'] = $upload_path;
        $config['allowed_types'] = 'gif|jpg|jpeg|png|webp|pdf|doc|docx|ppt|pptx|xls|xlsx|txt|zip|rar';
        $config['max_size'] = 10240; // 10MB
        $config['encrypt_name'] = true;
        $config['remove_spaces'] = true;

        $this->upload->initialize($config);

        if (!$this->upload->do_upload('image')) {
            $error = $this->upload->display_errors('', '');
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false, 
                    'message' => 'Upload failed: ' . $error
                ]));
            return;
        }

        $upload_data = $this->upload->data();
        $file_path = 'uploads/' . $type . '/' . $upload_data['file_name'];

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => ucfirst($type) . ' file uploaded successfully',
                'data' => [
                    'file_path' => $file_path,
                    'file_name' => $upload_data['file_name'],
                    'file_size' => $upload_data['file_size'],
                    'file_type' => $upload_data['file_type']
                ]
            ]));
    }

    // Handle OPTIONS preflight requests (CORS)
    public function options() {
        // The BaseController constructor handles CORS and exits for OPTIONS requests.
    }
} 