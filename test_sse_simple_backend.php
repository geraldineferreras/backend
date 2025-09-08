<?php
/**
 * Simple SSE Backend Test
 * This script directly tests the SSE backend logic
 */

// Test parameters
$testUserId = 'TEA68B79B40CDC7B244';
$testRole = 'teacher';

echo "<h1>🔍 Simple SSE Backend Test</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .log{background:#f0f0f0;padding:10px;margin:5px 0;border-radius:3px;font-family:monospace;font-size:12px;} .success{background:#d4edda;color:#155724;} .error{background:#f8d7da;color:#721c24;} .info{background:#d1ecf1;color:#0c5460;}</style>\n";

// Include CodeIgniter
require_once('index.php');

// Get the CodeIgniter instance
$CI =& get_instance();

// Load required models
$CI->load->model('Notification_model');
$CI->load->database();

echo "<div class='log info'>🔍 Testing SSE backend logic for user: {$testUserId}</div>\n";

// Test 1: Check database connection
echo "<h2>Test 1: Database Connection</h2>\n";
try {
    $query = $CI->db->query("SELECT 1 as test");
    if ($query->num_rows() > 0) {
        echo "<div class='log success'>✅ Database connection working</div>\n";
    } else {
        echo "<div class='log error'>❌ Database connection failed</div>\n";
    }
} catch (Exception $e) {
    echo "<div class='log error'>❌ Database error: " . $e->getMessage() . "</div>\n";
}

// Test 2: Check notifications table
echo "<h2>Test 2: Notifications Table</h2>\n";
try {
    $query = $CI->db->query("SELECT COUNT(*) as total FROM notifications");
    $total = $query->row()->total;
    echo "<div class='log info'>📊 Total notifications in database: {$total}</div>\n";
    
    $query = $CI->db->query("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?", [$testUserId]);
    $userTotal = $query->row()->total;
    echo "<div class='log info'>📊 Notifications for user {$testUserId}: {$userTotal}</div>\n";
    
    $query = $CI->db->query("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0", [$testUserId]);
    $unreadTotal = $query->row()->total;
    echo "<div class='log info'>📊 Unread notifications for user {$testUserId}: {$unreadTotal}</div>\n";
    
} catch (Exception $e) {
    echo "<div class='log error'>❌ Notifications table error: " . $e->getMessage() . "</div>\n";
}

// Test 3: Simulate the exact SSE query
echo "<h2>Test 3: Simulate SSE Query</h2>\n";
try {
    // This is the exact query from the SSE code
    $CI->db->select('*');
    $CI->db->from('notifications');
    $CI->db->where('user_id', $testUserId);
    $CI->db->where('is_read', 0);
    $CI->db->order_by('created_at', 'DESC');
    $CI->db->limit(20);
    
    $query = $CI->db->get();
    $rows = $query->result_array();
    
    echo "<div class='log info'>📊 SSE Query returned " . count($rows) . " notifications</div>\n";
    
    if (count($rows) > 0) {
        echo "<div class='log success'>✅ Found unread notifications:</div>\n";
        foreach ($rows as $index => $row) {
            echo "<div class='log info'>  " . ($index + 1) . ". ID: {$row['id']}, Title: {$row['title']}, Created: {$row['created_at']}</div>\n";
        }
        
        // Test the notification ID tracking logic
        echo "<h3>Test 3a: Notification ID Tracking</h3>\n";
        $sentNotifications = []; // Simulate $this->lastSentAtByUser[$userId]
        $newNotifications = [];
        
        foreach ($rows as $row) {
            $notificationId = $row['id'];
            
            if (!in_array($notificationId, $sentNotifications)) {
                echo "<div class='log success'>✅ New notification found - ID: {$notificationId}, Title: {$row['title']}</div>\n";
                $newNotifications[] = $row;
                $sentNotifications[] = $notificationId;
            } else {
                echo "<div class='log info'>📋 Notification ID {$notificationId} already sent</div>\n";
            }
        }
        
        echo "<div class='log success'>📊 Total new notifications to send: " . count($newNotifications) . "</div>\n";
        
    } else {
        echo "<div class='log error'>❌ No unread notifications found for user {$testUserId}</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div class='log error'>❌ SSE query simulation error: " . $e->getMessage() . "</div>\n";
}

// Test 4: Create a test notification
echo "<h2>Test 4: Create Test Notification</h2>\n";
try {
    $notificationData = [
        'user_id' => $testUserId,
        'type' => 'test',
        'title' => 'SSE Backend Test Notification',
        'message' => 'This is a test notification created at ' . date('Y-m-d H:i:s'),
        'related_id' => null,
        'related_type' => 'test',
        'class_code' => null,
        'is_urgent' => 0
    ];
    
    $notificationId = $CI->Notification_model->create_notification($notificationData);
    
    if ($notificationId) {
        echo "<div class='log success'>✅ Test notification created! ID: {$notificationId}</div>\n";
        
        // Immediately check if it appears in the SSE query
        $CI->db->select('*');
        $CI->db->from('notifications');
        $CI->db->where('user_id', $testUserId);
        $CI->db->where('is_read', 0);
        $CI->db->where('id', $notificationId);
        
        $query = $CI->db->get();
        $rows = $query->result_array();
        
        if (count($rows) > 0) {
            echo "<div class='log success'>✅ Test notification found in SSE query!</div>\n";
            $row = $rows[0];
            echo "<div class='log info'>📋 ID: {$row['id']}, Title: {$row['title']}, Created: {$row['created_at']}, Read: {$row['is_read']}</div>\n";
        } else {
            echo "<div class='log error'>❌ Test notification NOT found in SSE query!</div>\n";
        }
        
    } else {
        echo "<div class='log error'>❌ Failed to create test notification</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div class='log error'>❌ Test notification error: " . $e->getMessage() . "</div>\n";
}

echo "<div class='log info'>🔍 Backend test completed at " . date('Y-m-d H:i:s') . "</div>\n";
?>
