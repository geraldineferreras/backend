<?php
/**
 * Test Student Comment System
 * This script tests the student comment functionality with notifications
 */

// Database configuration
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

echo "🧪 Testing Student Comment System...\n\n";

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database successfully!\n\n";

    // Check if there are any stream posts to test with
    echo "📋 Checking for stream posts...\n";
    $stmt = $pdo->query("SELECT id, class_code, title, content, created_at FROM classroom_stream ORDER BY created_at DESC LIMIT 3");
    $stream_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($stream_posts)) {
        echo "✅ Found stream posts:\n";
        foreach ($stream_posts as $post) {
            echo "   ID: {$post['id']}, Class: {$post['class_code']}, Title: {$post['title']}\n";
        }
        
        // Get first post for testing
        $test_post = $stream_posts[0];
        $stream_id = $test_post['id'];
        $class_code = $test_post['class_code'];
        
        echo "\n🎯 Testing with post ID: $stream_id, Class: $class_code\n";
        
        // Check current comment count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM classroom_stream_comments WHERE stream_id = ?");
        $stmt->execute([$stream_id]);
        $current_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "📊 Current comment count: $current_count\n";
        
        // Check if there are any students enrolled in this class
        echo "\n👥 Checking for enrolled students...\n";
        $stmt = $pdo->prepare("
            SELECT ce.student_id, u.full_name, u.role 
            FROM classroom_enrollments ce 
            JOIN users u ON ce.student_id = u.user_id COLLATE utf8mb4_unicode_ci
            WHERE ce.classroom_id = (SELECT id FROM classrooms WHERE class_code = ?) 
            AND ce.status = 'active' 
            AND u.role = 'student'
            LIMIT 3
        ");
        $stmt->execute([$class_code]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($students)) {
            echo "✅ Found enrolled students:\n";
            foreach ($students as $student) {
                echo "   - {$student['student_id']}: {$student['full_name']}\n";
            }
            
            // Test adding a comment
            echo "\n🧪 Testing comment addition...\n";
            $test_comment = [
                'stream_id' => $stream_id,
                'user_id' => $students[0]['student_id'],
                'comment' => 'This is a test comment to verify the system is working.',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $columns = implode(', ', array_keys($test_comment));
            $placeholders = ':' . implode(', :', array_keys($test_comment));
            
            $sql = "INSERT INTO classroom_stream_comments ($columns) VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($test_comment);
            
            $comment_id = $pdo->lastInsertId();
            echo "✅ Test comment added successfully! Comment ID: $comment_id\n";
            
            // Check updated comment count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM classroom_stream_comments WHERE stream_id = ?");
            $stmt->execute([$stream_id]);
            $new_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "📊 Updated comment count: $new_count\n";
            
            // Check if comment appears in the list
            $stmt = $pdo->prepare("
                SELECT c.id, c.comment, c.created_at, u.full_name as user_name 
                FROM classroom_stream_comments c 
                JOIN users u ON c.user_id = u.user_id 
                WHERE c.stream_id = ? 
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$stream_id]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "\n📝 Comments for this post:\n";
            foreach ($comments as $comment) {
                echo "   - {$comment['user_name']}: {$comment['comment']} ({$comment['created_at']})\n";
            }
            
            // Clean up test data
            $pdo->exec("DELETE FROM classroom_stream_comments WHERE id = $comment_id");
            echo "\n✅ Test comment cleaned up\n";
            
        } else {
            echo "⚠️  No enrolled students found for class $class_code\n";
        }
        
    } else {
        echo "⚠️  No stream posts found to test with\n";
    }
    
    // Test notification system
    echo "\n🔔 Testing notification system...\n";
    
    // Check if notifications table has recent entries
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $recent_notifications = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📊 Recent notifications (last hour): $recent_notifications\n";
    
    if ($recent_notifications > 0) {
        $stmt = $pdo->query("
            SELECT type, title, message, created_at 
            FROM notifications 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📝 Recent notifications:\n";
        foreach ($notifications as $notification) {
            echo "   - {$notification['type']}: {$notification['title']} ({$notification['created_at']})\n";
        }
    }
    
    echo "\n🎉 Student comment system test completed!\n";
    echo "✅ The system should now:\n";
    echo "   1. Show correct comment counts in stream posts\n";
    echo "   2. Send notifications when comments are added\n";
    echo "   3. Display comments properly\n";
    
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Make sure XAMPP is running\n";
    echo "2. Check if MySQL service is started\n";
    echo "3. Verify database name '{$database}' exists\n";
    echo "4. Check username/password in this script\n";
}
?>
