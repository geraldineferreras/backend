<?php
// Simple Railway database test
header('Content-Type: text/plain');

echo "🔍 Railway Database Test\n";
echo "======================\n";

// Test user ID
$userId = 'TEA68B79B40CDC7B244';

try {
    // Load CodeIgniter
    define('BASEPATH', '');
    define('APPPATH', __DIR__ . '/application/');
    define('ENVIRONMENT', 'production');
    
    // Load database config
    require_once APPPATH . 'config/database.php';
    
    // Get database connection
    $host = $envHost ? $envHost : '127.0.0.1';
    $username = $envUser ? $envUser : 'root';
    $password = $envPass ? $envPass : '';
    $database = $envName ? $envName : 'scms_db';
    
    echo "🔗 Database: $database\n";
    echo "🏠 Host: $host\n";
    echo "👤 User: $username\n";
    
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful\n";
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userCount = $stmt->fetchColumn();
    echo "👤 User exists: " . ($userCount > 0 ? "YES" : "NO") . "\n";
    
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
    
    // Get recent unread notifications
    $stmt = $pdo->prepare("
        SELECT id, title, message, type, created_at, is_read, user_id 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Recent unread notifications:\n";
    foreach ($notifications as $notification) {
        echo "  ID: {$notification['id']}, Title: {$notification['title']}, Created: {$notification['created_at']}\n";
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
