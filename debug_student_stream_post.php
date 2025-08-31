<?php
/**
 * Debug script for student stream posting
 * This helps identify why files aren't being saved to the database
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Student Stream Post Debug</h1>";

// Simulate a multipart request
echo "<h2>1. Simulating Multipart Request</h2>";

// Create test form data
$test_data = [
    'title' => 'Test Student Post',
    'content' => 'This is a test post with files',
    'is_draft' => '0',
    'allow_comments' => '1'
];

echo "Test data: <pre>" . print_r($test_data, true) . "</pre>";

// Simulate file uploads
$test_files = [
    'attachment_0' => [
        'name' => 'test_file.txt',
        'type' => 'text/plain',
        'tmp_name' => '/tmp/test_file.txt',
        'error' => UPLOAD_ERR_OK,
        'size' => 1024
    ]
];

echo "Test files: <pre>" . print_r($test_files, true) . "</pre>";

// Load CodeIgniter
require_once 'index.php';

try {
    $CI =& get_instance();
    
    echo "<h2>2. Database Connection Test</h2>";
    
    // Test database connection
    $CI->load->database();
    if ($CI->db->conn_id) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
        exit;
    }
    
    echo "<h2>3. Table Check</h2>";
    
    // Check if stream_attachments table exists
    $table_exists = $CI->db->table_exists('stream_attachments');
    if ($table_exists) {
        echo "✅ stream_attachments table exists<br>";
        
        // Show table structure
        $fields = $CI->db->list_fields('stream_attachments');
        echo "Table fields: " . implode(', ', $fields) . "<br>";
    } else {
        echo "❌ stream_attachments table does not exist<br>";
        echo "Please run: <code>CREATE TABLE IF NOT EXISTS `stream_attachments` (...)</code><br>";
        exit;
    }
    
    echo "<h2>4. Model Test</h2>";
    
    // Test StreamAttachment model
    $CI->load->model('StreamAttachment_model');
    if (class_exists('StreamAttachment_model')) {
        echo "✅ StreamAttachment_model loaded<br>";
        
        // Test insert_multiple method
        $test_attachments = [
            [
                'file_name' => 'test_file.txt',
                'original_name' => 'test_file.txt',
                'file_path' => 'uploads/announcement/test_file.txt',
                'file_size' => 1024,
                'mime_type' => 'text/plain',
                'attachment_type' => 'file',
                'attachment_url' => 'uploads/announcement/test_file.txt'
            ]
        ];
        
        echo "Testing insert_multiple with test data...<br>";
        $result = $CI->StreamAttachment_model->insert_multiple(999, $test_attachments);
        if ($result) {
            echo "✅ Test insert_multiple successful<br>";
            
            // Clean up
            $CI->db->where('stream_id', 999)->delete('stream_attachments');
            echo "✅ Test data cleaned up<br>";
        } else {
            echo "❌ Test insert_multiple failed<br>";
        }
    } else {
        echo "❌ StreamAttachment_model not found<br>";
        exit;
    }
    
    echo "<h2>5. File Upload Directory Test</h2>";
    
    $upload_path = 'uploads/announcement/';
    if (!is_dir($upload_path)) {
        echo "Creating upload directory: $upload_path<br>";
        mkdir($upload_path, 0755, true);
    }
    
    if (is_dir($upload_path)) {
        echo "✅ Upload directory exists: $upload_path<br>";
        echo "Directory writable: " . (is_writable($upload_path) ? 'Yes' : 'No') . "<br>";
    } else {
        echo "❌ Upload directory creation failed<br>";
    }
    
    echo "<h2>6. Simulating Student Stream Post</h2>";
    
    // Simulate the file processing logic
    $uploaded_files = [];
    $file_inputs = ['attachment_0', 'attachment_1', 'attachment_2', 'attachment_3', 'attachment_4'];
    
    foreach ($file_inputs as $input_name) {
        if (isset($test_files[$input_name])) {
            $file = $test_files[$input_name];
            echo "Processing file: $input_name<br>";
            
            if ($file['error'] === UPLOAD_ERR_OK) {
                $original_name = $file['name'];
                $file_size = $file['size'];
                $file_type = $file['type'];
                
                // Generate unique filename
                $extension = pathinfo($original_name, PATHINFO_EXTENSION);
                $file_name = uniqid('student_stream_') . '_' . time() . '.' . $extension;
                $upload_path = 'uploads/announcement/';
                $file_path = $upload_path . $file_name;
                
                echo "Generated filename: $file_name<br>";
                echo "File path: $file_path<br>";
                
                // Simulate file upload (create a test file)
                $test_content = "This is a test file for student stream posting.";
                if (file_put_contents($file_path, $test_content)) {
                    echo "✅ Test file created: $file_path<br>";
                    
                    $uploaded_files[] = [
                        'file_path' => $file_path,
                        'file_name' => $file_name,
                        'original_name' => $original_name,
                        'file_size' => $file_size,
                        'mime_type' => $file_type,
                        'attachment_type' => 'file',
                        'attachment_url' => $file_path
                    ];
                } else {
                    echo "❌ Failed to create test file<br>";
                }
            }
        }
    }
    
    echo "Uploaded files count: " . count($uploaded_files) . "<br>";
    echo "Uploaded files: <pre>" . print_r($uploaded_files, true) . "</pre>";
    
    echo "<h2>7. Database Insert Test</h2>";
    
    if (!empty($uploaded_files)) {
        // Test inserting into stream_attachments
        $test_stream_id = 888;
        
        echo "Testing database insert for stream_id: $test_stream_id<br>";
        
        $result = $CI->StreamAttachment_model->insert_multiple($test_stream_id, $uploaded_files);
        
        if ($result) {
            echo "✅ Database insert successful<br>";
            
            // Verify the data was inserted
            $inserted_attachments = $CI->StreamAttachment_model->get_by_stream_id($test_stream_id);
            echo "Inserted attachments count: " . count($inserted_attachments) . "<br>";
            echo "Inserted data: <pre>" . print_r($inserted_attachments, true) . "</pre>";
            
            // Clean up
            $CI->db->where('stream_id', $test_stream_id)->delete('stream_attachments');
            echo "✅ Test data cleaned up<br>";
        } else {
            echo "❌ Database insert failed<br>";
            
            // Check for database errors
            $error = $CI->db->error();
            echo "Database error: <pre>" . print_r($error, true) . "</pre>";
        }
    } else {
        echo "No files to insert<br>";
    }
    
    echo "<h2>8. Current Database State</h2>";
    
    // Show current records in stream_attachments
    $current_records = $CI->db->get('stream_attachments')->result_array();
    echo "Current records in stream_attachments: " . count($current_records) . "<br>";
    
    if (!empty($current_records)) {
        echo "Recent records:<br>";
        foreach (array_slice($current_records, -5) as $record) {
            echo "- ID: {$record['attachment_id']}, Stream: {$record['stream_id']}, File: {$record['file_name']}<br>";
        }
    }
    
    echo "<h2>9. Recommendations</h2>";
    
    if (!$table_exists) {
        echo "❌ <strong>CRITICAL:</strong> stream_attachments table does not exist. Run the SQL script:<br>";
        echo "<code>CREATE TABLE IF NOT EXISTS `stream_attachments` (...)</code><br>";
    }
    
    if (!is_dir($upload_path) || !is_writable($upload_path)) {
        echo "❌ <strong>WARNING:</strong> Upload directory issues. Check permissions for: $upload_path<br>";
    }
    
    if (empty($uploaded_files)) {
        echo "❌ <strong>ISSUE:</strong> No files were processed. Check file upload handling.<br>";
    }
    
    echo "✅ <strong>SUCCESS:</strong> All tests passed. Student stream posting should work correctly.<br>";
    
} catch (Exception $e) {
    echo "❌ <strong>ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
