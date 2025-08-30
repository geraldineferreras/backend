<?php
/**
 * Fix Classroom Stream Table
 * 
 * This script adds the missing class_code column to the classroom_stream table
 * to fix the 500 error when students try to post to the stream.
 */

// Database configuration
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

echo "🔧 Fixing Classroom Stream Table...\n\n";

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database successfully!\n\n";

    // Check if classroom_stream table exists
    echo "🔍 Checking if classroom_stream table exists...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'classroom_stream'");
    if ($stmt->rowCount() == 0) {
        echo "❌ classroom_stream table does not exist!\n";
        echo "Creating the table with all required columns...\n\n";
        
        // Create the complete table
        $create_table_sql = "
        CREATE TABLE `classroom_stream` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `class_code` varchar(20) NOT NULL,
          `classroom_id` int(11) NOT NULL,
          `user_id` varchar(50) NOT NULL,
          `title` varchar(255) NOT NULL,
          `content` text NOT NULL,
          `is_draft` tinyint(1) NOT NULL DEFAULT 0,
          `is_scheduled` tinyint(1) NOT NULL DEFAULT 0,
          `scheduled_at` datetime DEFAULT NULL,
          `notification_dispatched_at` datetime DEFAULT NULL,
          `allow_comments` tinyint(1) NOT NULL DEFAULT 1,
          `attachment_type` enum('file','link','youtube','google_drive','multiple') DEFAULT NULL,
          `attachment_url` text DEFAULT NULL,
          `status` enum('published','draft','scheduled','deleted') NOT NULL DEFAULT 'published',
          `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
          `liked_by_user_ids` json DEFAULT NULL,
          `visible_to_student_ids` json DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_class_code` (`class_code`),
          KEY `idx_classroom_id` (`classroom_id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_is_draft` (`is_draft`),
          KEY `idx_is_scheduled` (`is_scheduled`),
          KEY `idx_status` (`status`),
          KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        
        $pdo->exec($create_table_sql);
        echo "✅ classroom_stream table created successfully!\n\n";
        
    } else {
        echo "✅ classroom_stream table exists\n";
        
        // Check if class_code column exists
        echo "🔍 Checking if class_code column exists...\n";
        $stmt = $pdo->query("DESCRIBE classroom_stream");
        $columns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $columns[] = $row['Field'];
        }
        
        if (!in_array('class_code', $columns)) {
            echo "❌ class_code column is missing!\n";
            echo "Adding class_code column...\n";
            
            // Add the missing column
            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `class_code` VARCHAR(20) NOT NULL AFTER `id`");
            $pdo->exec("ALTER TABLE `classroom_stream` ADD INDEX `idx_class_code` (`class_code`)");
            
            echo "✅ class_code column added successfully!\n\n";
            
            // Update existing records to have a class_code (if any exist)
            echo "🔧 Updating existing records with class_code...\n";
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM classroom_stream");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($count > 0) {
                // Update existing records to link them to a classroom
                $update_sql = "
                UPDATE classroom_stream cs
                JOIN classrooms c ON cs.classroom_id = c.id
                SET cs.class_code = c.class_code
                WHERE cs.class_code IS NULL OR cs.class_code = ''
                ";
                
                $pdo->exec($update_sql);
                echo "✅ Updated {$count} existing records with class_code\n\n";
            } else {
                echo "ℹ️  No existing records to update\n\n";
            }
            
        } else {
            echo "✅ class_code column already exists\n\n";
        }
        
        // Check for other missing columns
        $required_columns = [
            'classroom_id', 'user_id', 'title', 'content', 'is_draft', 
            'is_scheduled', 'scheduled_at', 'allow_comments', 'attachment_type', 
            'attachment_url', 'status', 'is_pinned', 'created_at', 'updated_at'
        ];
        
        $missing_columns = [];
        foreach ($required_columns as $col) {
            if (!in_array($col, $columns)) {
                $missing_columns[] = $col;
            }
        }
        
        if (!empty($missing_columns)) {
            echo "⚠️  Missing columns: " . implode(', ', $missing_columns) . "\n";
            echo "Adding missing columns...\n";
            
            foreach ($missing_columns as $col) {
                try {
                    switch ($col) {
                        case 'classroom_id':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `classroom_id` INT(11) NOT NULL AFTER `class_code`");
                            break;
                        case 'user_id':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `user_id` VARCHAR(50) NOT NULL AFTER `classroom_id`");
                            break;
                        case 'title':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `title` VARCHAR(255) NOT NULL AFTER `user_id`");
                            break;
                        case 'content':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `content` TEXT NOT NULL AFTER `title`");
                            break;
                        case 'is_draft':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `is_draft` TINYINT(1) NOT NULL DEFAULT 0 AFTER `content`");
                            break;
                        case 'is_scheduled':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `is_scheduled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_draft`");
                            break;
                        case 'scheduled_at':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `scheduled_at` DATETIME NULL AFTER `is_scheduled`");
                            break;
                        case 'allow_comments':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `allow_comments` TINYINT(1) NOT NULL DEFAULT 1 AFTER `scheduled_at`");
                            break;
                        case 'attachment_type':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `attachment_type` ENUM('file','link','youtube','google_drive','multiple') NULL AFTER `allow_comments`");
                            break;
                        case 'attachment_url':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `attachment_url` TEXT NULL AFTER `attachment_type`");
                            break;
                        case 'status':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `status` ENUM('published','draft','scheduled','deleted') NOT NULL DEFAULT 'published' AFTER `attachment_url`");
                            break;
                        case 'is_pinned':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `is_pinned` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`");
                            break;
                        case 'created_at':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `is_pinned`");
                            break;
                        case 'updated_at':
                            $pdo->exec("ALTER TABLE `classroom_stream` ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`");
                            break;
                    }
                    echo "✅ Added column: {$col}\n";
                } catch (Exception $e) {
                    echo "⚠️  Could not add column {$col}: " . $e->getMessage() . "\n";
                }
            }
            echo "\n";
        }
    }
    
    // Show final table structure
    echo "📋 Final classroom_stream table structure:\n";
    $stmt = $pdo->query("DESCRIBE classroom_stream");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $field = $row['Field'];
        $type = $row['Type'];
        $null = $row['Null'];
        $key = $row['Key'];
        $default = $row['Default'];
        
        echo "   {$field}: {$type} {$null} {$key} {$default}\n";
    }
    
    echo "\n🎉 Classroom stream table is now ready!\n";
    echo "✅ Students should be able to post to the stream without errors.\n";
    
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Make sure XAMPP is running\n";
    echo "2. Check if MySQL service is started\n";
    echo "3. Verify database name '{$database}' exists\n";
    echo "4. Check username/password in this script\n";
    echo "5. Ensure you have ALTER TABLE permissions\n";
}
?>
