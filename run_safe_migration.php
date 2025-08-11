<?php
/**
 * Safe migration to add midterm_exam and final_exam task types
 * This approach modifies the enum column directly without dropping the table
 */

// Database connection
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== RUNNING SAFE MIGRATION ===\n";
    echo "Database: $database\n\n";
    
    // Check current enum values
    echo "=== CURRENT STATE ===\n";
    $stmt = $pdo->query("SELECT COLUMN_TYPE 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = DATABASE() 
                         AND TABLE_NAME = 'class_tasks' 
                         AND COLUMN_NAME = 'type'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "Current Column Type: {$row['COLUMN_TYPE']}\n\n";
    }
    
    // Method 1: Try to modify the enum directly (MySQL 8.0+)
    echo "=== ATTEMPTING DIRECT ENUM MODIFICATION ===\n";
    try {
        $pdo->exec("ALTER TABLE `class_tasks` 
                    MODIFY COLUMN `type` enum('assignment','quiz','activity','project','exam','midterm_exam','final_exam') NOT NULL DEFAULT 'assignment'");
        echo "✓ Direct modification successful!\n";
    } catch (Exception $e) {
        echo "✗ Direct modification failed: " . $e->getMessage() . "\n";
        echo "Trying alternative approach...\n\n";
        
        // Method 2: Use ALTER TABLE with ADD/DROP for older MySQL versions
        echo "=== ATTEMPTING ALTERNATIVE APPROACH ===\n";
        try {
            // First, add the new enum values one by one
            $pdo->exec("ALTER TABLE `class_tasks` MODIFY COLUMN `type` enum('assignment','quiz','activity','project','exam','midterm_exam') NOT NULL DEFAULT 'assignment'");
            echo "✓ Added midterm_exam successfully\n";
            
            $pdo->exec("ALTER TABLE `class_tasks` MODIFY COLUMN `type` enum('assignment','quiz','activity','project','exam','midterm_exam','final_exam') NOT NULL DEFAULT 'assignment'");
            echo "✓ Added final_exam successfully\n";
            
        } catch (Exception $e2) {
            echo "✗ Alternative approach failed: " . $e2->getMessage() . "\n";
            echo "Trying final approach...\n\n";
            
            // Method 3: Use a different approach for very old MySQL versions
            echo "=== ATTEMPTING FINAL APPROACH ===\n";
            try {
                // Create a new column with the desired enum
                $pdo->exec("ALTER TABLE `class_tasks` ADD COLUMN `type_new` enum('assignment','quiz','activity','project','exam','midterm_exam','final_exam') NOT NULL DEFAULT 'assignment' AFTER `type`");
                echo "✓ New column created\n";
                
                // Copy data from old column to new column
                $pdo->exec("UPDATE `class_tasks` SET `type_new` = `type` WHERE `type` IN ('assignment','quiz','activity','project','exam')");
                echo "✓ Data copied to new column\n";
                
                // Drop old column and rename new column
                $pdo->exec("ALTER TABLE `class_tasks` DROP COLUMN `type`");
                echo "✓ Old column dropped\n";
                
                $pdo->exec("ALTER TABLE `class_tasks` CHANGE `type_new` `type` enum('assignment','quiz','activity','project','exam','midterm_exam','final_exam') NOT NULL DEFAULT 'assignment'");
                echo "✓ New column renamed\n";
                
            } catch (Exception $e3) {
                echo "✗ All approaches failed. Manual intervention required.\n";
                echo "Error: " . $e3->getMessage() . "\n";
                exit(1);
            }
        }
    }
    
    // Verify the change
    echo "\n=== VERIFICATION ===\n";
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
    
    // Show the new enum values
    $stmt = $pdo->query("SELECT COLUMN_TYPE 
                         FROM INFORMATION_SCHEMA.COLUMNS 
                         WHERE TABLE_SCHEMA = DATABASE() 
                         AND TABLE_NAME = 'class_tasks' 
                         AND COLUMN_NAME = 'type'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "Column Type: {$row['COLUMN_TYPE']}\n\n";
    }
    
    // Test inserting a task with the new type
    echo "=== TESTING NEW TASK TYPE ===\n";
    try {
        $test_data = [
            'title' => 'Test Midterm Exam After Migration',
            'type' => 'midterm_exam',
            'points' => 100,
            'instructions' => 'Test instructions after migration',
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
    
    echo "\n=== MIGRATION COMPLETED SUCCESSFULLY ===\n";
    
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
