<?php
// Test script to debug SSE database queries
require_once 'application/config/database.php';

// Database connection
$host = $db['default']['hostname'];
$username = $db['default']['username'];
$password = $db['default']['password'];
$database = $db['default']['database'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== SSE Database Debug Test ===\n";
    echo "Database: $database\n";
    echo "Host: $host\n\n";
    
    // Test user ID
    $testUserId = 'TEA68B79B40CDC7B244';
    
    // 1. Check if notifications table exists
    echo "1. Checking notifications table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Notifications table exists\n";
    } else {
        echo "❌ Notifications table does not exist\n";
        exit;
    }
    
    // 2. Check table structure
    echo "\n2. Checking table structure...\n";
    $stmt = $pdo->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $column) {
        echo "   - {$column['Field']}: {$column['Type']}\n";
    }
    
    // 3. Check all notifications for the test user
    echo "\n3. Checking all notifications for user: $testUserId\n";
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$testUserId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        echo "✅ Found " . count($notifications) . " notifications for user $testUserId:\n";
        foreach ($notifications as $notif) {
            echo "   - ID: {$notif['id']}, Title: {$notif['title']}, Read: {$notif['is_read']}, Created: {$notif['created_at']}\n";
        }
    } else {
        echo "❌ No notifications found for user $testUserId\n";
    }
    
    // 4. Check unread notifications specifically
    echo "\n4. Checking unread notifications for user: $testUserId\n";
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$testUserId]);
    $unreadNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($unreadNotifications) > 0) {
        echo "✅ Found " . count($unreadNotifications) . " unread notifications:\n";
        foreach ($unreadNotifications as $notif) {
            echo "   - ID: {$notif['id']}, Title: {$notif['title']}, Created: {$notif['created_at']}\n";
        }
    } else {
        echo "❌ No unread notifications found for user $testUserId\n";
    }
    
    // 5. Check recent notifications (last hour)
    echo "\n5. Checking notifications from last hour...\n";
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY created_at DESC");
    $stmt->execute([$testUserId]);
    $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($recentNotifications) > 0) {
        echo "✅ Found " . count($recentNotifications) . " notifications from last hour:\n";
        foreach ($recentNotifications as $notif) {
            echo "   - ID: {$notif['id']}, Title: {$notif['title']}, Created: {$notif['created_at']}\n";
        }
    } else {
        echo "❌ No notifications from last hour for user $testUserId\n";
    }
    
    // 6. Check all users in notifications table
    echo "\n6. Checking all users in notifications table...\n";
    $stmt = $pdo->query("SELECT DISTINCT user_id, COUNT(*) as count FROM notifications GROUP BY user_id ORDER BY count DESC LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "✅ Users with notifications:\n";
        foreach ($users as $user) {
            echo "   - {$user['user_id']}: {$user['count']} notifications\n";
        }
    } else {
        echo "❌ No users found in notifications table\n";
    }
    
    // 7. Test the exact query used by SSE
    echo "\n7. Testing SSE query (unread notifications)...\n";
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at ASC LIMIT 10");
    $stmt->execute([$testUserId]);
    $sseNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($sseNotifications) > 0) {
        echo "✅ SSE query found " . count($sseNotifications) . " notifications:\n";
        foreach ($sseNotifications as $notif) {
            echo "   - ID: {$notif['id']}, Title: {$notif['title']}, Created: {$notif['created_at']}\n";
        }
    } else {
        echo "❌ SSE query found no notifications for user $testUserId\n";
    }
    
    // 8. Create a test notification
    echo "\n8. Creating a test notification...\n";
    $testTitle = "Database Debug Test " . date('Y-m-d H:i:s');
    $testMessage = "This is a test notification created by the database debug script";
    
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, related_id, related_type, class_code, is_read, is_urgent, created_at) VALUES (?, 'test', ?, ?, NULL, 'test', NULL, 0, 0, NOW())");
    $result = $stmt->execute([$testUserId, $testTitle, $testMessage]);
    
    if ($result) {
        $notificationId = $pdo->lastInsertId();
        echo "✅ Test notification created with ID: $notificationId\n";
        echo "   Title: $testTitle\n";
        echo "   Message: $testMessage\n";
    } else {
        echo "❌ Failed to create test notification\n";
    }
    
    echo "\n=== Debug Complete ===\n";
    echo "If you see notifications above but SSE is not receiving them, the issue is in the SSE controller logic.\n";
    echo "If you see no notifications, the issue is in the notification creation process.\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>