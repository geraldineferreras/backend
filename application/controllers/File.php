<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class File extends CI_Controller {
    
    public function serve($type, $filename) {
        $file_path = FCPATH . 'uploads/' . $type . '/' . $filename;

        if (file_exists($file_path)) {
            // Set CORS headers
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
            
            // Allow iframe embedding for file preview
            header('X-Frame-Options: ALLOWALL');
            
            // Determine content type based on file extension
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $content_type = $this->get_content_type($extension);
            
            header('Content-Type: ' . $content_type);
            header('Content-Length: ' . filesize($file_path));
            header('Cache-Control: public, max-age=31536000'); // Cache for 1 year
            
            // For images, PDFs, videos, and audio, serve inline; for other files, offer download
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'mp4', 'mp3'])) {
                header('Content-Disposition: inline; filename="' . $filename . '"');
            } else {
                header('Content-Disposition: attachment; filename="' . $filename . '"');
            }
            
            readfile($file_path);
            exit;
        } else {
            // Return a 404 error with a proper JSON response
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'error' => 'File not found',
                    'message' => 'The requested file does not exist',
                    'path' => $file_path
                ]));
        }
    }
    
    private function get_content_type($extension) {
        $content_types = [
            // Images
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            
            // Documents
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            
            // Text files
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            
            // Archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            
            // Video
            'mp4' => 'video/mp4',
            
            // Audio
            'mp3' => 'audio/mpeg',
            
            // Default
            'default' => 'application/octet-stream'
        ];
        
        return isset($content_types[$extension]) ? $content_types[$extension] : $content_types['default'];
    }
} 