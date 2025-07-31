<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!function_exists('get_file_url')) {
    /**
     * Convert a file path to a proper serving URL
     * 
     * @param string $file_path The file path (e.g., 'uploads/announcement/filename.png')
     * @return string The serving URL
     */
    function get_file_url($file_path) {
        if (empty($file_path)) {
            return null;
        }
        
        // Remove 'uploads/' prefix and split into type and filename
        $path_parts = explode('/', $file_path);
        if (count($path_parts) < 3 || $path_parts[0] !== 'uploads') {
            return null;
        }
        
        $type = $path_parts[1]; // e.g., 'announcement', 'profile', 'cover'
        $filename = implode('/', array_slice($path_parts, 2)); // Handle subdirectories
        
        // Use the new file serving route
        return base_url("file/{$type}/{$filename}");
    }
}

if (!function_exists('get_image_url')) {
    /**
     * Convert an image path to a proper serving URL (for backward compatibility)
     * 
     * @param string $file_path The file path (e.g., 'uploads/profile/filename.jpg')
     * @return string The serving URL
     */
    function get_image_url($file_path) {
        if (empty($file_path)) {
            return null;
        }
        
        // Remove 'uploads/' prefix and split into type and filename
        $path_parts = explode('/', $file_path);
        if (count($path_parts) < 3 || $path_parts[0] !== 'uploads') {
            return null;
        }
        
        $type = $path_parts[1]; // e.g., 'profile', 'cover'
        $filename = implode('/', array_slice($path_parts, 2)); // Handle subdirectories
        
        // Use the image serving route for images
        return base_url("image/{$type}/{$filename}");
    }
}

if (!function_exists('is_image_file')) {
    /**
     * Check if a file is an image based on its extension
     * 
     * @param string $filename The filename
     * @return bool True if it's an image file
     */
    function is_image_file($filename) {
        $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $image_extensions);
    }
}

if (!function_exists('get_file_type')) {
    /**
     * Get the file type based on extension
     * 
     * @param string $filename The filename
     * @return string The file type (image, document, archive, etc.)
     */
    function get_file_type($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $document_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'];
        $archive_extensions = ['zip', 'rar'];
        $video_extensions = ['mp4'];
        $audio_extensions = ['mp3'];
        
        if (in_array($extension, $image_extensions)) {
            return 'image';
        } elseif (in_array($extension, $document_extensions)) {
            return 'document';
        } elseif (in_array($extension, $archive_extensions)) {
            return 'archive';
        } elseif (in_array($extension, $video_extensions)) {
            return 'video';
        } elseif (in_array($extension, $audio_extensions)) {
            return 'audio';
        } else {
            return 'file';
        }
    }
}

if (!function_exists('process_attachments')) {
    /**
     * Process attachments for both single and multiple files
     * 
     * @param array $post The post data
     * @return array The processed post data
     */
    function process_attachments($post) {
        if (!empty($post['attachment_url'])) {
            if ($post['attachment_type'] === 'multiple') {
                // Handle multiple files
                $files = json_decode($post['attachment_url'], true);
                if (is_array($files)) {
                    $post['attachments'] = [];
                    foreach ($files as $file) {
                        $post['attachments'][] = [
                            'file_path' => $file['file_path'],
                            'file_name' => $file['file_name'],
                            'file_size' => $file['file_size'],
                            'file_type' => $file['file_type'],
                            'serving_url' => get_file_url($file['file_path']),
                            'file_type_category' => get_file_type($file['file_path'])
                        ];
                    }
                }
            } else {
                // Handle single file (backward compatibility)
                $post['attachment_serving_url'] = get_file_url($post['attachment_url']);
                $post['attachment_file_type'] = get_file_type($post['attachment_url']);
            }
        }
        return $post;
    }
} 