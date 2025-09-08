<?php
// Direct database test for SSE notifications
require_once 'application/config/database.php';

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'scms_new';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful\n";
    
    // Test user ID
    $userId = 'TEA68B79B40CDC7B244';
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userCount = $stmt->fetchColumn();
    echo "👤 User exists: " . ($userCount > 0 ? "YES" : "NO") . "\n";
    
    // Check notifications table structure
    $stmt = $pdo->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "📋 Notifications table columns:\n";
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
    
    // Check total notifications for user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
    $stmt->execute([$userId]);
    $totalCount = $stmt->fetchColumn();
    echo "📊 Total notifications for user: $totalCount\n";
    
    // Check unread notifications for user
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadCount = $stmt->fetchColumn();
    echo "📬 Unread notifications for user: $unreadCount\n";
    
    // Get recent unread notifications (last 10)
    $stmt = $pdo->prepare("
        SELECT id, title, message, type, created_at, is_read, user_id 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Recent unread notifications:\n";
    foreach ($notifications as $notification) {
        echo "  ID: {$notification['id']}, Title: {$notification['title']}, Created: {$notification['created_at']}, User ID: {$notification['user_id']}\n";
    }
    
    // Test the exact SSE query
    $since = time() - 3600; // 1 hour ago
    $stmt = $pdo->prepare("
        SELECT * 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 AND created_at > ? 
        ORDER BY created_at ASC 
        LIMIT 10
    ");
    $stmt->execute([$userId, date('Y-m-d H:i:s', $since)]);
    $sseNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "🔍 SSE query results (last hour): " . count($sseNotifications) . " notifications\n";
    foreach ($sseNotifications as $notification) {
        echo "  ID: {$notification['id']}, Title: {$notification['title']}, Created: {$notification['created_at']}\n";
    }
    
    // Test without time filter (all unread)
    $stmt = $pdo->prepare("
        SELECT * 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 
        ORDER BY created_at ASC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $allUnread = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "🔍 All unread notifications: " . count($allUnread) . " notifications\n";
    foreach ($allUnread as $notification) {
        echo "  ID: {$notification['id']}, Title: {$notification['title']}, Created: {$notification['created_at']}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
