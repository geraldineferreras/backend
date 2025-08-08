<?php
// Setup script for task_submission_attachments table
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
    
    echo "<h2>Database Connection Test</h2>";
    echo "✓ Database connection successful!<br><br>";
    
    // Check if task_submission_attachments table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'task_submission_attachments'");
    if ($stmt->rowCount() > 0) {
        echo "✓ task_submission_attachments table already exists<br>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE task_submission_attachments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Table Structure:</h3>";
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
        echo "✗ task_submission_attachments table does not exist<br>";
        echo "Creating table...<br>";
        
        // Create the table
        $sql = "
        CREATE TABLE IF NOT EXISTS `task_submission_attachments` (
          `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
          `submission_id` int(11) NOT NULL,
          `file_name` varchar(255) NOT NULL,
          `original_name` varchar(255) NOT NULL,
          `file_path` text NOT NULL,
          `file_size` int(11) DEFAULT NULL,
          `mime_type` varchar(100) DEFAULT NULL,
          `attachment_type` enum('file','link','youtube','google_drive') DEFAULT 'file',
          `attachment_url` text DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`attachment_id`),
          KEY `idx_submission_id` (`submission_id`),
          KEY `idx_attachment_type` (`attachment_type`),
          CONSTRAINT `fk_task_submission_attachments_submission` FOREIGN KEY (`submission_id`) REFERENCES `task_submissions` (`submission_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        CREATE INDEX `idx_submission_attachments_composite` ON `task_submission_attachments` (`submission_id`, `attachment_type`);
        ";
        
        $pdo->exec($sql);
        echo "✓ task_submission_attachments table created successfully!<br><br>";
    }
    
    // Check if task_submissions table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'task_submissions'");
    if ($stmt->rowCount() > 0) {
        echo "✓ task_submissions table exists<br>";
        
        // Check for some sample data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM task_submissions");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Found {$result['count']} submissions in task_submissions table<br>";
        
        if ($result['count'] > 0) {
            $stmt = $pdo->query("SELECT * FROM task_submissions LIMIT 3");
            $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Sample Submissions:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Submission ID</th><th>Task ID</th><th>Student ID</th><th>Class Code</th><th>Status</th></tr>";
            foreach ($submissions as $submission) {
                echo "<tr>";
                echo "<td>{$submission['submission_id']}</td>";
                echo "<td>{$submission['task_id']}</td>";
                echo "<td>{$submission['student_id']}</td>";
                echo "<td>{$submission['class_code']}</td>";
                echo "<td>{$submission['status']}</td>";
                echo "</tr>";
            }
            echo "</table><br>";
        }
    } else {
        echo "✗ task_submissions table does not exist<br>";
    }
    
    // Check if class_tasks table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'class_tasks'");
    if ($stmt->rowCount() > 0) {
        echo "✓ class_tasks table exists<br>";
        
        // Check for some sample data
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM class_tasks");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Found {$result['count']} tasks in class_tasks table<br>";
        
        if ($result['count'] > 0) {
            $stmt = $pdo->query("SELECT task_id, title, type FROM class_tasks LIMIT 5");
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Sample Tasks:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Task ID</th><th>Title</th><th>Type</th></tr>";
            foreach ($tasks as $task) {
                echo "<tr>";
                echo "<td>{$task['task_id']}</td>";
                echo "<td>{$task['title']}</td>";
                echo "<td>{$task['type']}</td>";
                echo "</tr>";
            }
            echo "</table><br>";
        }
    } else {
        echo "✗ class_tasks table does not exist<br>";
    }
    
    echo "<h2>API Endpoint Test</h2>";
    echo "<p>You can now test the API endpoint using the test file: <a href='test_api_endpoint.html'>test_api_endpoint.html</a></p>";
    echo "<p>Or test directly with: <code>GET http://localhost/scms_new_backup/api/tasks/58/submission?class_code=J56NHD</code></p>";
    
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
