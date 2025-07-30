<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Image extends CI_Controller {
    
    public function serve($type, $filename) {
        $file_path = FCPATH . 'uploads/' . $type . '/' . $filename;

        if (file_exists($file_path)) {
            // Set CORS headers
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
            
            // Determine content type based on file extension
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    header('Content-Type: image/jpeg');
                    break;
                case 'png':
                    header('Content-Type: image/png');
                    break;
                case 'gif':
                    header('Content-Type: image/gif');
                    break;
                case 'webp':
                    header('Content-Type: image/webp');
                    break;
                default:
                    header('Content-Type: application/octet-stream');
                    break;
            }
            
            header('Content-Length: ' . filesize($file_path));
            header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
            readfile($file_path);
            exit;
        } else {
            show_404();
        }
    }
} 