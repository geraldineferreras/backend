<?php
// Test file to verify student submission endpoint
require_once 'application/config/database.php';

// Test database connection
try {
    $hostname = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'scms_new';
    
    $pdo = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection successful!\n";
    
    // Check if task_submission_attachments table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'task_submission_attachments'");
    if ($stmt->rowCount() > 0) {
        echo "✓ task_submission_attachments table exists\n";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE task_submission_attachments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Table structure:\n";
        foreach ($columns as $column) {
            echo "- {$column['Field']}: {$column['Type']}\n";
        }
    } else {
        echo "✗ task_submission_attachments table does not exist\n";
        echo "Please run the SQL file: create_task_submission_attachments_table.sql\n";
    }
    
    // Check if task 58 exists
    $stmt = $pdo->prepare("SELECT * FROM class_tasks WHERE task_id = ?");
    $stmt->execute([58]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        echo "✓ Task 58 exists\n";
        echo "Task title: " . $task['title'] . "\n";
    } else {
        echo "✗ Task 58 does not exist\n";
    }
    
    // Check for submissions for task 58
    $stmt = $pdo->prepare("SELECT * FROM task_submissions WHERE task_id = ?");
    $stmt->execute([58]);
    $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($submissions) . " submissions for task 58\n";
    
    foreach ($submissions as $submission) {
        echo "- Submission ID: {$submission['submission_id']}, Student: {$submission['student_id']}, Class: {$submission['class_code']}\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "\n=== Testing API Endpoint ===\n";

// Test the API endpoint
$url = 'http://localhost/scms_new_backup/api/tasks/58/submission?class_code=J56NHD';
$headers = [
    'Authorization: Bearer test_token',
    'Content-Type: application/json'
];

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => implode("\r\n", $headers)
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "Failed to connect to API endpoint\n";
    echo "URL: $url\n";
    echo "Make sure XAMPP is running and the project is accessible\n";
} else {
    echo "API Response:\n";
    echo $response . "\n";
}
?>
