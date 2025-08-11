<?php
/**
 * Debug script to identify task type field issue
 * This script will help diagnose why the type field is empty for new task types
 */

// Database connection (adjust these values according to your setup)
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== DATABASE CONNECTION SUCCESSFUL ===\n\n";
    
    // Check current table structure
    echo "=== CURRENT TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE class_tasks");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['Field'] === 'type') {
            echo "Field: {$row['Field']}\n";
            echo "Type: {$row['Type']}\n";
            echo "Null: {$row['Null']}\n";
            echo "Default: {$row['Default']}\n";
            echo "Extra: {$row['Extra']}\n\n";
        }
    }
    
    // Check current enum values
    echo "=== CURRENT ENUM VALUES ===\n";
    $stmt = $pdo->query("SELECT COLUMN_TYPE 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = DATABASE() 
                         AND TABLE_NAME = 'class_tasks' 
                         AND COLUMN_NAME = 'type'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "Column Type: {$row['COLUMN_TYPE']}\n\n";
    }
    
    // Check recent tasks to see what's in the type field
    echo "=== RECENT TASKS (LAST 10) ===\n";
    $stmt = $pdo->query("SELECT task_id, title, type, created_at 
                         FROM class_tasks 
                         ORDER BY created_at DESC 
                         LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['task_id']}, Title: {$row['title']}, Type: '{$row['type']}', Created: {$row['created_at']}\n";
    }
    echo "\n";
    
    // Check if there are any tasks with empty type
    echo "=== TASKS WITH EMPTY TYPE FIELD ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM class_tasks WHERE type = '' OR type IS NULL");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Tasks with empty type: {$row['count']}\n\n";
    
    // Check MySQL version and SQL mode
    echo "=== MYSQL VERSION AND SQL MODE ===\n";
    $stmt = $pdo->query("SELECT VERSION() as version, @@sql_mode as sql_mode");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL Version: {$row['version']}\n";
    echo "SQL Mode: {$row['sql_mode']}\n\n";
    
    // Test inserting a task with the new type
    echo "=== TESTING TASK INSERTION ===\n";
    try {
        $test_data = [
            'title' => 'Test Midterm Exam',
            'type' => 'midterm_exam',
            'points' => 100,
            'instructions' => 'Test instructions',
            'class_codes' => json_encode(['TEST']),
            'teacher_id' => 'TEST_TEACHER',
            'status' => 'active'
        ];
        
        $sql = "INSERT INTO class_tasks (title, type, points, instructions, class_codes, teacher_id, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $test_data['title'],
            $test_data['type'],
            $test_data['points'],
            $test_data['instructions'],
            $test_data['class_codes'],
            $test_data['teacher_id'],
            $test_data['status']
        ]);
        
        if ($result) {
            $test_id = $pdo->lastInsertId();
            echo "✓ Test task inserted successfully with ID: $test_id\n";
            
            // Check what was actually stored
            $stmt = $pdo->prepare("SELECT task_id, title, type FROM class_tasks WHERE task_id = ?");
            $stmt->execute([$test_id]);
            $stored = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "Stored data - ID: {$stored['task_id']}, Title: {$stored['title']}, Type: '{$stored['type']}'\n";
            
            // Clean up test data
            $pdo->exec("DELETE FROM class_tasks WHERE task_id = $test_id");
            echo "✓ Test data cleaned up\n";
        } else {
            echo "✗ Failed to insert test task\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Error during test insertion: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== DEBUGGING COMPLETE ===\n";
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>
