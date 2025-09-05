<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pdf extends CI_Controller {
    public function serve($filename)
    {
        $file_path = FCPATH . 'uploads/' . $filename;

        if (file_exists($file_path)) {
            // Set CORS headers
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
            
            // Allow iframe embedding for PDF preview
            header('X-Frame-Options: ALLOWALL');
            
            // Set content type for PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        } else {
            show_404();
        }
    }
} 