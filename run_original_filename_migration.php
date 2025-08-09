<?php
// Database migration script to add original_filename field to class_tasks table
// This script should be run directly in the browser

// Load CodeIgniter database configuration
require_once 'application/config/database.php';

try {
    // Connect to database
    $hostname = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'scms_new';
    
    $pdo = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Database Migration: Add original_filename to class_tasks</h2>";
    echo "✓ Database connection successful!<br><br>";
    
    // Check if original_filename column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM class_tasks LIKE 'original_filename'");
    if ($stmt->rowCount() > 0) {
        echo "✓ original_filename column already exists<br>";
        
        // Show current table structure
        $stmt = $pdo->query("DESCRIBE class_tasks");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Current Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
    } else {
        echo "✗ original_filename column does not exist<br>";
        echo "Adding original_filename column...<br>";
        
        // Add the original_filename column
        $sql = "ALTER TABLE `class_tasks` ADD COLUMN `original_filename` VARCHAR(255) NULL AFTER `attachment_url`";
        $pdo->exec($sql);
        echo "✓ original_filename column added successfully!<br>";
        
        // Add index for better performance
        $sql = "CREATE INDEX `idx_original_filename` ON `class_tasks` (`original_filename`)";
        $pdo->exec($sql);
        echo "✓ Index created successfully!<br>";
        
        // Show updated table structure
        $stmt = $pdo->query("DESCRIBE class_tasks");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Updated Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>{$column['Field']}</td>";
            echo "<td>{$column['Type']}</td>";
            echo "<td>{$column['Null']}</td>";
            echo "<td>{$column['Key']}</td>";
            echo "<td>{$column['Default']}</td>";
            echo "<td>{$column['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
    }
    
    // Check for existing tasks with attachments
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM class_tasks WHERE attachment_url IS NOT NULL AND attachment_type = 'file'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Found {$result['count']} tasks with file attachments<br>";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT task_id, title, attachment_url, original_filename FROM class_tasks WHERE attachment_url IS NOT NULL AND attachment_type = 'file' LIMIT 5");
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Sample Tasks with Attachments:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Task ID</th><th>Title</th><th>Attachment URL</th><th>Original Filename</th></tr>";
        foreach ($tasks as $task) {
            echo "<tr>";
            echo "<td>{$task['task_id']}</td>";
            echo "<td>{$task['title']}</td>";
            echo "<td>{$task['attachment_url']}</td>";
            echo "<td>" . ($task['original_filename'] ?: 'NULL (will use task title)') . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        echo "<p><strong>Note:</strong> Existing tasks will have NULL for original_filename. ";
        echo "The system will fallback to using the task title + extension for these files. ";
        echo "New tasks created after this migration will store the actual original filename.</p>";
    }
    
    echo "<h2>Migration Complete!</h2>";
    echo "<p>The system is now ready to store and retrieve actual original filenames for task attachments.</p>";
    echo "<p>You can test the new functionality using:</p>";
    echo "<ul>";
    echo "<li><strong>Test File:</strong> <a href='test_task_file_info.html'>test_task_file_info.html</a></li>";
    echo "<li><strong>API Endpoint:</strong> <code>GET /api/tasks/files/info/{filename}</code></li>";
    echo "<li><strong>List Endpoint:</strong> <code>GET /api/tasks/files/list</code></li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<h2>Database Error</h2>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<p>Please make sure:</p>";
    echo "<ul>";
    echo "<li>XAMPP is running</li>";
    echo "<li>MySQL service is started</li>";
    echo "<li>Database 'scms_new' exists</li>";
    echo "<li>Username and password are correct</li>";
    echo "</ul>";
}
?>
