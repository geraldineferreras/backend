<?php
/**
 * Create stream_attachments table if it doesn't exist
 * This script ensures the required table is available for student stream posting
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Create Stream Attachments Table</h1>";

// Load CodeIgniter
require_once 'index.php';

try {
    $CI =& get_instance();
    
    echo "<h2>1. Database Connection</h2>";
    
    // Test database connection
    $CI->load->database();
    if ($CI->db->conn_id) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
        exit;
    }
    
    echo "<h2>2. Check Existing Table</h2>";
    
    // Check if table already exists
    $table_exists = $CI->db->table_exists('stream_attachments');
    if ($table_exists) {
        echo "✅ stream_attachments table already exists<br>";
        
        // Show table structure
        $fields = $CI->db->list_fields('stream_attachments');
        echo "Table fields: " . implode(', ', $fields) . "<br>";
        
        // Count records
        $count = $CI->db->count_all('stream_attachments');
        echo "Current records: $count<br>";
        
        echo "<h3>Table is ready! Student stream posting should work now.</h3>";
        exit;
    }
    
    echo "❌ stream_attachments table does not exist<br>";
    echo "Creating table...<br>";
    
    echo "<h2>3. Creating Table</h2>";
    
    // SQL to create the table
    $sql = "
    CREATE TABLE IF NOT EXISTS `stream_attachments` (
      `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
      `stream_id` int(11) NOT NULL,
      `file_name` varchar(255) NOT NULL,
      `original_name` varchar(255) NOT NULL,
      `file_path` text NOT NULL,
      `file_size` int(11) DEFAULT NULL,
      `mime_type` varchar(100) DEFAULT NULL,
      `attachment_type` enum('file','link','youtube','google_drive') DEFAULT 'file',
      `attachment_url` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`attachment_id`),
      KEY `idx_stream_id` (`stream_id`),
      KEY `idx_attachment_type` (`attachment_type`),
      CONSTRAINT `fk_stream_attachments_stream` FOREIGN KEY (`stream_id`) REFERENCES `classroom_stream` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    // Execute the SQL
    $result = $CI->db->query($sql);
    
    if ($result) {
        echo "✅ Table creation SQL executed successfully<br>";
        
        // Verify table was created
        $table_exists = $CI->db->table_exists('stream_attachments');
        if ($table_exists) {
            echo "✅ stream_attachments table created successfully!<br>";
            
            // Show table structure
            $fields = $CI->db->list_fields('stream_attachments');
            echo "Table fields: " . implode(', ', $fields) . "<br>";
            
            // Create index for better performance
            $index_sql = "CREATE INDEX `idx_stream_attachments_composite` ON `stream_attachments` (`stream_id`, `attachment_type`);";
            $index_result = $CI->db->query($index_sql);
            
            if ($index_result) {
                echo "✅ Performance index created successfully<br>";
            } else {
                echo "⚠️ Index creation failed (may already exist)<br>";
            }
            
            echo "<h3>✅ SUCCESS: stream_attachments table is ready!</h3>";
            echo "<p>Student stream posting with files should now work correctly.</p>";
            
        } else {
            echo "❌ Table creation failed - table does not exist after creation<br>";
        }
    } else {
        echo "❌ Table creation failed<br>";
        
        // Check for database errors
        $error = $CI->db->error();
        echo "Database error: <pre>" . print_r($error, true) . "</pre>";
    }
    
    echo "<h2>4. Test Model Loading</h2>";
    
    // Test if StreamAttachment model can be loaded
    $CI->load->model('StreamAttachment_model');
    if (class_exists('StreamAttachment_model')) {
        echo "✅ StreamAttachment_model loaded successfully<br>";
        
        // Test model methods
        $methods = get_class_methods('StreamAttachment_model');
        echo "Available methods: " . implode(', ', $methods) . "<br>";
    } else {
        echo "❌ StreamAttachment_model failed to load<br>";
    }
    
    echo "<h2>5. Next Steps</h2>";
    
    if ($table_exists) {
        echo "✅ <strong>Ready to test:</strong><br>";
        echo "1. Run <code>test_student_database_connection.php</code> to verify everything works<br>";
        echo "2. Test student stream posting with files using <code>test_student_stream_file_upload.html</code><br>";
        echo "3. Check that files are being saved to the database<br>";
    } else {
        echo "❌ <strong>Issue detected:</strong> Table creation failed. Please check database permissions and try again.<br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
