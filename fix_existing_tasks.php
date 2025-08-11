<?php
/**
 * Fix existing tasks that have empty type fields
 * Update them to use the appropriate task types
 */

// Database connection
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== FIXING EXISTING TASKS ===\n";
    echo "Database: $database\n\n";
    
    // Find tasks with empty type fields
    echo "=== FINDING TASKS WITH EMPTY TYPE FIELDS ===\n";
    $stmt = $pdo->query("SELECT task_id, title, type, created_at FROM class_tasks WHERE type = '' OR type IS NULL ORDER BY created_at DESC");
    $empty_type_tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($empty_type_tasks)) {
        echo "No tasks with empty type fields found.\n";
        exit;
    }
    
    echo "Found " . count($empty_type_tasks) . " tasks with empty type fields:\n";
    foreach ($empty_type_tasks as $task) {
        echo "ID: {$task['task_id']}, Title: {$task['title']}, Type: '{$task['type']}', Created: {$task['created_at']}\n";
    }
    echo "\n";
    
    // Update tasks based on their titles
    echo "=== UPDATING TASK TYPES ===\n";
    $updated_count = 0;
    
    foreach ($empty_type_tasks as $task) {
        $new_type = null;
        
        // Determine the appropriate type based on the title
        $title_lower = strtolower($task['title']);
        
        if (strpos($title_lower, 'midterm') !== false) {
            $new_type = 'midterm_exam';
        } elseif (strpos($title_lower, 'final') !== false) {
            $new_type = 'final_exam';
        } elseif (strpos($title_lower, 'quiz') !== false) {
            $new_type = 'quiz';
        } elseif (strpos($title_lower, 'activity') !== false) {
            $new_type = 'activity';
        } elseif (strpos($title_lower, 'project') !== false) {
            $new_type = 'project';
        } elseif (strpos($title_lower, 'exam') !== false) {
            $new_type = 'exam';
        } else {
            // Default to assignment if no specific type can be determined
            $new_type = 'assignment';
        }
        
        // Update the task
        $update_sql = "UPDATE class_tasks SET type = ?, updated_at = NOW() WHERE task_id = ?";
        $stmt = $pdo->prepare($update_sql);
        $result = $stmt->execute([$new_type, $task['task_id']]);
        
        if ($result) {
            echo "✓ Updated task ID {$task['task_id']} from '{$task['type']}' to '{$new_type}' (Title: {$task['title']})\n";
            $updated_count++;
        } else {
            echo "✗ Failed to update task ID {$task['task_id']}\n";
        }
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Successfully updated $updated_count out of " . count($empty_type_tasks) . " tasks.\n\n";
    
    // Verify the updates
    echo "=== VERIFICATION ===\n";
    $stmt = $pdo->query("SELECT task_id, title, type, created_at FROM class_tasks WHERE type = '' OR type IS NULL ORDER BY created_at DESC");
    $remaining_empty = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($remaining_empty)) {
        echo "✓ All tasks now have valid type fields.\n";
    } else {
        echo "⚠ " . count($remaining_empty) . " tasks still have empty type fields:\n";
        foreach ($remaining_empty as $task) {
            echo "ID: {$task['task_id']}, Title: {$task['title']}, Type: '{$task['type']}'\n";
        }
    }
    
    // Show recent tasks to confirm the fix
    echo "\n=== RECENT TASKS (LAST 10) ===\n";
    $stmt = $pdo->query("SELECT task_id, title, type, created_at FROM class_tasks ORDER BY created_at DESC LIMIT 10");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['task_id']}, Title: {$row['title']}, Type: '{$row['type']}', Created: {$row['created_at']}\n";
    }
    
    echo "\n=== FIX COMPLETED ===\n";
    
} catch (PDOException $e) {
    echo "Script failed: " . $e->getMessage() . "\n";
}
?>
