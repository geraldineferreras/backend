<?php
/**
 * Test Comments System
 * This script tests if the classroom_stream_comments table exists and can be used
 */

// Database configuration
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

echo "🔍 Testing Comments System...\n\n";

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database successfully!\n\n";

    // Check if classroom_stream_comments table exists
    echo "📋 Checking classroom_stream_comments table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'classroom_stream_comments'");
    if ($stmt->rowCount() > 0) {
        echo "✅ classroom_stream_comments table exists\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE classroom_stream_comments");
        echo "📋 Table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
        }
        
        // Check if there are any existing comments
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM classroom_stream_comments");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "\n📊 Total comments: $count\n";
        
        if ($count > 0) {
            // Show recent comments
            $stmt = $pdo->query("SELECT id, stream_id, user_id, comment, created_at FROM classroom_stream_comments ORDER BY created_at DESC LIMIT 5");
            echo "\n📝 Recent comments:\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "   ID: {$row['id']}, Stream: {$row['stream_id']}, User: {$row['user_id']}, Comment: " . substr($row['comment'], 0, 50) . "...\n";
            }
        }
        
    } else {
        echo "❌ classroom_stream_comments table does not exist!\n";
        echo "Creating classroom_stream_comments table...\n";
        
        // Create the comments table
        $create_table_sql = "
        CREATE TABLE `classroom_stream_comments` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `stream_id` int(11) NOT NULL,
          `user_id` varchar(50) NOT NULL,
          `comment` text NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_stream_id` (`stream_id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_created_at` (`created_at`),
          CONSTRAINT `fk_stream_comments_stream` FOREIGN KEY (`stream_id`) REFERENCES `classroom_stream` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_stream_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ";
        
        $pdo->exec($create_table_sql);
        echo "✅ classroom_stream_comments table created successfully!\n";
    }
    
    // Check if there are any stream posts to test with
    echo "\n📋 Checking for stream posts...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM classroom_stream");
    $stream_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📊 Total stream posts: $stream_count\n";
    
    if ($stream_count > 0) {
        // Show sample stream posts
        $stmt = $pdo->query("SELECT id, class_code, title, content, created_at FROM classroom_stream ORDER BY created_at DESC LIMIT 3");
        echo "\n📝 Sample stream posts:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   ID: {$row['id']}, Class: {$row['class_code']}, Title: {$row['title']}\n";
        }
        
        // Test inserting a sample comment
        echo "\n🧪 Testing comment insertion...\n";
        try {
            // Get first stream post ID
            $stmt = $pdo->query("SELECT id FROM classroom_stream LIMIT 1");
            $stream_post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($stream_post) {
                $test_comment = [
                    'stream_id' => $stream_post['id'],
                    'user_id' => 'test_user_123',
                    'comment' => 'This is a test comment to verify the system is working.',
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $columns = implode(', ', array_keys($test_comment));
                $placeholders = ':' . implode(', :', array_keys($test_comment));
                
                $sql = "INSERT INTO classroom_stream_comments ($columns) VALUES ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($test_comment);
                
                $insert_id = $pdo->lastInsertId();
                echo "✅ Test comment inserted successfully! ID: $insert_id\n";
                
                // Clean up test data
                $pdo->exec("DELETE FROM classroom_stream_comments WHERE id = $insert_id");
                echo "✅ Test data cleaned up\n";
            }
            
        } catch (Exception $e) {
            echo "❌ Test comment insertion failed: " . $e->getMessage() . "\n";
        }
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
    
    echo "\n🎉 Comments system test completed!\n";
    echo "✅ The system should now be able to handle comments properly.\n";
    
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Make sure XAMPP is running\n";
    echo "2. Check if MySQL service is started\n";
    echo "3. Verify database name '{$database}' exists\n";
    echo "4. Check username/password in this script\n";
}
?>
