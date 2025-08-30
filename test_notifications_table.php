<?php
/**
 * Test Notifications Table and System
 * This script tests if the notifications table exists and can be used
 */

// Database configuration
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

echo "🔍 Testing Notifications System...\n\n";

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database successfully!\n\n";

    // Check if notifications table exists
    echo "📋 Checking notifications table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ notifications table exists\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE notifications");
        echo "📋 Table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
        }
        
        // Check if there are any existing notifications
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "\n📊 Total notifications: $count\n";
        
        if ($count > 0) {
            // Show recent notifications
            $stmt = $pdo->query("SELECT id, user_id, type, title, message, created_at FROM notifications ORDER BY created_at DESC LIMIT 5");
            echo "\n📝 Recent notifications:\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "   ID: {$row['id']}, User: {$row['user_id']}, Type: {$row['type']}, Title: {$row['title']}\n";
            }
        }
        
    } else {
        echo "❌ notifications table does not exist!\n";
        echo "Creating notifications table...\n";
        
        // Create the notifications table
        $create_table_sql = "
        CREATE TABLE `notifications` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` varchar(50) NOT NULL,
          `type` enum('announcement','task','submission','excuse_letter','grade','enrollment','system') NOT NULL,
          `title` varchar(255) NOT NULL,
          `message` text NOT NULL,
          `related_id` int(11) DEFAULT NULL,
          `related_type` varchar(50) DEFAULT NULL,
          `class_code` varchar(20) DEFAULT NULL,
          `is_read` tinyint(1) NOT NULL DEFAULT 0,
          `is_urgent` tinyint(1) NOT NULL DEFAULT 0,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_type` (`type`),
          KEY `idx_is_read` (`is_read`),
          KEY `idx_created_at` (`created_at`),
          KEY `idx_class_code` (`class_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        
        $pdo->exec($create_table_sql);
        echo "✅ notifications table created successfully!\n";
    }
    
    // Check if notification_settings table exists
    echo "\n📋 Checking notification_settings table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'notification_settings'");
    if ($stmt->rowCount() > 0) {
        echo "✅ notification_settings table exists\n";
    } else {
        echo "❌ notification_settings table does not exist!\n";
        echo "Creating notification_settings table...\n";
        
        // Create the notification_settings table
        $create_settings_sql = "
        CREATE TABLE `notification_settings` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` varchar(50) NOT NULL,
          `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
          `push_notifications` tinyint(1) NOT NULL DEFAULT 1,
          `announcement_notifications` tinyint(1) NOT NULL DEFAULT 1,
          `task_notifications` tinyint(1) NOT NULL DEFAULT 1,
          `submission_notifications` tinyint(1) NOT NULL DEFAULT 1,
          `grade_notifications` tinyint(1) NOT NULL DEFAULT 1,
          `enrollment_notifications` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_user_settings` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        
        $pdo->exec($create_settings_sql);
        echo "✅ notification_settings table created successfully!\n";
    }
    
    // Test inserting a sample notification
    echo "\n🧪 Testing notification insertion...\n";
    try {
        $test_notification = [
            'user_id' => 'test_user_123',
            'type' => 'announcement',
            'title' => 'Test Student Post Notification',
            'message' => 'This is a test notification to verify the system is working.',
            'related_id' => 999,
            'related_type' => 'announcement',
            'class_code' => 'TEST123',
            'is_read' => 0,
            'is_urgent' => 0
        ];
        
        $columns = implode(', ', array_keys($test_notification));
        $placeholders = ':' . implode(', :', array_keys($test_notification));
        
        $sql = "INSERT INTO notifications ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($test_notification);
        
        $insert_id = $pdo->lastInsertId();
        echo "✅ Test notification inserted successfully! ID: $insert_id\n";
        
        // Clean up test data
        $pdo->exec("DELETE FROM notifications WHERE id = $insert_id");
        echo "✅ Test data cleaned up\n";
        
    } catch (Exception $e) {
        echo "❌ Test notification insertion failed: " . $e->getMessage() . "\n";
    }
    
    // Check if there are any users to test with
    echo "\n👥 Checking for test users...\n";
    $stmt = $pdo->query("SELECT user_id, full_name, role FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($users)) {
        echo "✅ Found users for testing:\n";
        foreach ($users as $user) {
            echo "   - {$user['user_id']}: {$user['full_name']} ({$user['role']})\n";
        }
    } else {
        echo "⚠️  No users found in database\n";
    }
    
    echo "\n🎉 Notifications system test completed!\n";
    echo "✅ The system should now be able to send notifications when students post to streams.\n";
    
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Make sure XAMPP is running\n";
    echo "2. Check if MySQL service is started\n";
    echo "3. Verify database name '{$database}' exists\n";
    echo "4. Check username/password in this script\n";
}
?>
