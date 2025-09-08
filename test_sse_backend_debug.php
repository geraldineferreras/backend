<?php
/**
 * SSE Backend Debug Script
 * This script helps debug the SSE notification system by testing the exact queries used
 */

// Include CodeIgniter
require_once('index.php');

// Get the CodeIgniter instance
$CI =& get_instance();

// Load required models
$CI->load->model('Notification_model');
$CI->load->database();

echo "<h1>🔍 SSE Backend Debug Test</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .log{background:#f0f0f0;padding:10px;margin:5px 0;border-radius:3px;font-family:monospace;font-size:12px;} .success{background:#d4edda;color:#155724;} .error{background:#f8d7da;color:#721c24;} .info{background:#d1ecf1;color:#0c5460;}</style>\n";

// Test parameters
$testUserId = 'STU001'; // Change this to your test user ID
$testRole = 'student';

echo "<div class='log info'>🔍 Testing SSE backend queries for user: {$testUserId}</div>\n";

// Test 1: Check if notifications table exists and has data
echo "<h2>Test 1: Database Table Check</h2>\n";

try {
    $query = $CI->db->query("SHOW TABLES LIKE 'notifications'");
    if ($query->num_rows() > 0) {
        echo "<div class='log success'>✅ Notifications table exists</div>\n";
        
        // Check table structure
        $query = $CI->db->query("DESCRIBE notifications");
        echo "<div class='log info'>📋 Table structure:</div>\n";
        foreach ($query->result() as $row) {
            echo "<div class='log info'>  - {$row->Field}: {$row->Type}</div>\n";
        }
        
        // Check total notifications
        $query = $CI->db->query("SELECT COUNT(*) as total FROM notifications");
        $total = $query->row()->total;
        echo "<div class='log info'>📊 Total notifications in database: {$total}</div>\n";
        
        // Check notifications for test user
        $query = $CI->db->query("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?", [$testUserId]);
        $userTotal = $query->row()->total;
        echo "<div class='log info'>📊 Notifications for user {$testUserId}: {$userTotal}</div>\n";
        
    } else {
        echo "<div class='log error'>❌ Notifications table does not exist</div>\n";
    }
} catch (Exception $e) {
    echo "<div class='log error'>❌ Database error: " . $e->getMessage() . "</div>\n";
}

// Test 2: Test the exact query used in getNewNotifications
echo "<h2>Test 2: SSE Query Simulation</h2>\n";

try {
    // Simulate the getNewNotifications query
    $since = time() - 300; // 5 minutes ago
    
    echo "<div class='log info'>🔍 Testing query: SELECT * FROM notifications WHERE user_id = '{$testUserId}' AND is_read = 0 AND created_at > '" . date('Y-m-d H:i:s', $since) . "' ORDER BY created_at ASC LIMIT 10</div>\n";
    
    $CI->db->select('*');
    $CI->db->from('notifications');
    $CI->db->where('user_id', $testUserId);
    $CI->db->where('is_read', 0);
    $CI->db->where('created_at >', date('Y-m-d H:i:s', $since));
    $CI->db->order_by('created_at', 'ASC');
    $CI->db->limit(10);
    
    $query = $CI->db->get();
    $rows = $query->result_array();
    
    echo "<div class='log info'>📊 Query returned " . count($rows) . " notifications</div>\n";
    
    if (count($rows) > 0) {
        echo "<div class='log success'>✅ Found notifications matching SSE criteria:</div>\n";
        foreach ($rows as $index => $row) {
            echo "<div class='log info'>  " . ($index + 1) . ". ID: {$row['id']}, Title: {$row['title']}, Created: {$row['created_at']}, Read: {$row['is_read']}</div>\n";
        }
    } else {
        echo "<div class='log error'>❌ No notifications found matching SSE criteria</div>\n";
        
        // Let's check what notifications exist for this user
        $CI->db->select('*');
        $CI->db->from('notifications');
        $CI->db->where('user_id', $testUserId);
        $CI->db->order_by('created_at', 'DESC');
        $CI->db->limit(5);
        
        $query = $CI->db->get();
        $allRows = $query->result_array();
        
        if (count($allRows) > 0) {
            echo "<div class='log info'>📋 Recent notifications for user (last 5):</div>\n";
            foreach ($allRows as $index => $row) {
                $createdTs = strtotime($row['created_at']);
                $timeDiff = time() - $createdTs;
                echo "<div class='log info'>  " . ($index + 1) . ". ID: {$row['id']}, Title: {$row['title']}, Created: {$row['created_at']} ({$timeDiff}s ago), Read: {$row['is_read']}</div>\n";
            }
        } else {
            echo "<div class='log error'>❌ No notifications found for user {$testUserId} at all</div>\n";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='log error'>❌ Query error: " . $e->getMessage() . "</div>\n";
}

// Test 3: Test Notification_model methods
echo "<h2>Test 3: Notification Model Methods</h2>\n";

try {
    // Test get_user_notifications
    $notifications = $CI->Notification_model->get_user_notifications($testUserId, 5, 0, true);
    echo "<div class='log info'>📊 get_user_notifications (unread only): " . count($notifications) . " notifications</div>\n";
    
    if (count($notifications) > 0) {
        foreach ($notifications as $index => $notification) {
            echo "<div class='log info'>  " . ($index + 1) . ". ID: {$notification['id']}, Title: {$notification['title']}, Created: {$notification['created_at']}</div>\n";
        }
    }
    
    // Test get_unread_count
    $unreadCount = $CI->Notification_model->get_unread_count($testUserId);
    echo "<div class='log info'>📊 get_unread_count: {$unreadCount} unread notifications</div>\n";
    
} catch (Exception $e) {
    echo "<div class='log error'>❌ Model error: " . $e->getMessage() . "</div>\n";
}

// Test 4: Create a test notification
echo "<h2>Test 4: Create Test Notification</h2>\n";

try {
    $notificationData = [
        'user_id' => $testUserId,
        'type' => 'test',
        'title' => 'SSE Debug Test Notification',
        'message' => 'This is a test notification created at ' . date('Y-m-d H:i:s'),
        'related_id' => null,
        'related_type' => 'test',
        'class_code' => null,
        'is_urgent' => 0
    ];
    
    $notificationId = $CI->Notification_model->create_notification($notificationData);
    
    if ($notificationId) {
        echo "<div class='log success'>✅ Test notification created successfully! ID: {$notificationId}</div>\n";
        
        // Now test if it appears in the SSE query
        $since = time() - 300; // 5 minutes ago
        
        $CI->db->select('*');
        $CI->db->from('notifications');
        $CI->db->where('user_id', $testUserId);
        $CI->db->where('is_read', 0);
        $CI->db->where('created_at >', date('Y-m-d H:i:s', $since));
        $CI->db->order_by('created_at', 'ASC');
        $CI->db->limit(10);
        
        $query = $CI->db->get();
        $rows = $query->result_array();
        
        echo "<div class='log info'>📊 After creating test notification, SSE query returned " . count($rows) . " notifications</div>\n";
        
        if (count($rows) > 0) {
            echo "<div class='log success'>✅ Test notification appears in SSE query results:</div>\n";
            foreach ($rows as $index => $row) {
                echo "<div class='log info'>  " . ($index + 1) . ". ID: {$row['id']}, Title: {$row['title']}, Created: {$row['created_at']}</div>\n";
            }
        } else {
            echo "<div class='log error'>❌ Test notification does not appear in SSE query results</div>\n";
        }
        
    } else {
        echo "<div class='log error'>❌ Failed to create test notification</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div class='log error'>❌ Error creating test notification: " . $e->getMessage() . "</div>\n";
}

// Test 5: Check database connection and configuration
echo "<h2>Test 5: Database Configuration</h2>\n";

try {
    $config = $CI->db->get_platform();
    echo "<div class='log info'>📊 Database platform: {$config}</div>\n";
    
    $query = $CI->db->query("SELECT VERSION() as version");
    $version = $query->row()->version;
    echo "<div class='log info'>📊 Database version: {$version}</div>\n";
    
    $query = $CI->db->query("SELECT NOW() as current_time");
    $currentTime = $query->row()->current_time;
    echo "<div class='log info'>📊 Database current time: {$currentTime}</div>\n";
    
    $query = $CI->db->query("SELECT UNIX_TIMESTAMP() as unix_time");
    $unixTime = $query->row()->unix_time;
    echo "<div class='log info'>📊 Database unix timestamp: {$unixTime}</div>\n";
    
} catch (Exception $e) {
    echo "<div class='log error'>❌ Database configuration error: " . $e->getMessage() . "</div>\n";
}

echo "<div class='log info'>🔍 Debug test completed at " . date('Y-m-d H:i:s') . "</div>\n";
?>
