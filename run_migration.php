<?php
/**
 * Run migration to add midterm_exam and final_exam task types
 */

// Database connection
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== RUNNING MIGRATION ===\n";
    echo "Database: $database\n\n";
    
    // Step 1: Create a temporary table with the new structure
    echo "Step 1: Creating temporary table...\n";
    $pdo->exec("CREATE TABLE `class_tasks_temp` LIKE `class_tasks`");
    echo "✓ Temporary table created\n";
    
    // Step 2: Modify the type column in the temporary table
    echo "Step 2: Modifying type column...\n";
    $pdo->exec("ALTER TABLE `class_tasks_temp` 
                MODIFY COLUMN `type` enum('assignment','quiz','activity','project','exam','midterm_exam','final_exam') NOT NULL DEFAULT 'assignment'");
    echo "✓ Type column modified\n";
    
    // Step 3: Copy data from the original table to the temporary table
    echo "Step 3: Copying data...\n";
    $pdo->exec("INSERT INTO `class_tasks_temp` SELECT * FROM `class_tasks`");
    echo "✓ Data copied\n";
    
    // Step 4: Drop the original table
    echo "Step 4: Dropping original table...\n";
    $pdo->exec("DROP TABLE `class_tasks`");
    echo "✓ Original table dropped\n";
    
    // Step 5: Rename the temporary table to the original name
    echo "Step 5: Renaming temporary table...\n";
    $pdo->exec("RENAME TABLE `class_tasks_temp` TO `class_tasks`");
    echo "✓ Table renamed\n";
    
    // Step 6: Recreate the indexes
    echo "Step 6: Recreating indexes...\n";
    $pdo->exec("ALTER TABLE `class_tasks` 
                ADD PRIMARY KEY (`task_id`),
                ADD KEY `idx_teacher_id` (`teacher_id`),
                ADD KEY `idx_type` (`type`),
                ADD KEY `idx_status` (`status`),
                ADD KEY `idx_is_draft` (`is_draft`),
                ADD KEY `idx_is_scheduled` (`is_scheduled`),
                ADD KEY `idx_created_at` (`created_at`)");
    echo "✓ Indexes recreated\n";
    
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
