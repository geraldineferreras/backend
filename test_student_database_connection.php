<?php
/**
 * Test script to check database connectivity and table existence
 * for student stream posting functionality
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Student Stream Database Connection Test</h1>";

// Load CodeIgniter
require_once 'index.php';

try {
    // Get CI instance
    $CI =& get_instance();
    
    echo "<h2>1. Database Connection Test</h2>";
    
    // Test database connection
    $CI->load->database();
    if ($CI->db->conn_id) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
        exit;
    }
    
    echo "<h2>2. Table Existence Check</h2>";
    
    // Check if stream_attachments table exists
    $table_exists = $CI->db->table_exists('stream_attachments');
    if ($table_exists) {
        echo "✅ stream_attachments table exists<br>";
        
        // Show table structure
        $fields = $CI->db->list_fields('stream_attachments');
        echo "Table fields: " . implode(', ', $fields) . "<br>";
        
        // Count records
        $count = $CI->db->count_all('stream_attachments');
        echo "Current records: $count<br>";
    } else {
        echo "❌ stream_attachments table does not exist<br>";
        echo "Please run the create_stream_attachments_table.sql script<br>";
    }
    
    // Check if classroom_stream table exists
    $stream_table_exists = $CI->db->table_exists('classroom_stream');
    if ($stream_table_exists) {
        echo "✅ classroom_stream table exists<br>";
        
        // Count records
        $stream_count = $CI->db->count_all('classroom_stream');
        echo "Current stream records: $stream_count<br>";
    } else {
        echo "❌ classroom_stream table does not exist<br>";
    }
    
    echo "<h2>3. Model Loading Test</h2>";
    
    // Test StreamAttachment model
    $CI->load->model('StreamAttachment_model');
    if (class_exists('StreamAttachment_model')) {
        echo "✅ StreamAttachment_model loaded successfully<br>";
        
        // Test model methods
        $methods = get_class_methods('StreamAttachment_model');
        echo "Available methods: " . implode(', ', $methods) . "<br>";
    } else {
        echo "❌ StreamAttachment_model failed to load<br>";
    }
    
    echo "<h2>4. Sample Data Test</h2>";
    
    // Test inserting sample data
    if ($table_exists) {
        $sample_data = [
            'stream_id' => 999, // Test ID
            'file_name' => 'test_file.txt',
            'original_name' => 'test_file.txt',
            'file_path' => 'uploads/test/test_file.txt',
            'file_size' => 1024,
            'mime_type' => 'text/plain',
            'attachment_type' => 'file',
            'attachment_url' => 'uploads/test/test_file.txt'
        ];
        
        try {
            $result = $CI->StreamAttachment_model->insert($sample_data);
            if ($result) {
                echo "✅ Sample data inserted successfully (ID: $result)<br>";
                
                // Clean up test data
                $CI->db->where('stream_id', 999)->delete('stream_attachments');
                echo "✅ Test data cleaned up<br>";
            } else {
                echo "❌ Failed to insert sample data<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error inserting sample data: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>5. File Upload Directory Test</h2>";
    
    $upload_path = 'uploads/announcement/';
    if (is_dir($upload_path)) {
        echo "✅ Upload directory exists: $upload_path<br>";
        echo "Directory writable: " . (is_writable($upload_path) ? 'Yes' : 'No') . "<br>";
    } else {
        echo "❌ Upload directory does not exist: $upload_path<br>";
        echo "Creating directory...<br>";
        if (mkdir($upload_path, 0755, true)) {
            echo "✅ Upload directory created successfully<br>";
        } else {
            echo "❌ Failed to create upload directory<br>";
        }
    }
    
    echo "<h2>6. Recent Student Posts</h2>";
    
    // Show recent student posts
    $recent_posts = $CI->db->select('id, title, content, attachment_type, created_at')
                          ->from('classroom_stream')
                          ->where('user_id IN (SELECT user_id FROM users WHERE role = "student")')
                          ->order_by('created_at', 'DESC')
                          ->limit(5)
                          ->get()
                          ->result_array();
    
    if (!empty($recent_posts)) {
        echo "Recent student posts:<br>";
        foreach ($recent_posts as $post) {
            echo "- ID: {$post['id']}, Title: {$post['title']}, Attachments: {$post['attachment_type']}, Created: {$post['created_at']}<br>";
            
            // Check attachments for this post
            $attachments = $CI->StreamAttachment_model->get_by_stream_id($post['id']);
            if (!empty($attachments)) {
                echo "  Attachments: " . count($attachments) . " files<br>";
            }
        }
    } else {
        echo "No recent student posts found<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
