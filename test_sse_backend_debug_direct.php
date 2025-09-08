<?php
/**
 * Direct SSE Backend Debug - Test the exact query being used
 */

// Test parameters - CHANGE THESE TO MATCH YOUR SETUP
$testUserId = 'TEA68B79B40CDC7B244'; // Your actual user ID
$testRole = 'teacher';

echo "<h1>🔍 Direct SSE Backend Debug</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .log{background:#f0f0f0;padding:10px;margin:5px 0;border-radius:3px;font-family:monospace;font-size:12px;} .success{background:#d4edda;color:#155724;} .error{background:#f8d7da;color:#721c24;} .info{background:#d1ecf1;color:#0c5460;}</style>\n";

// Include CodeIgniter
require_once('index.php');

// Get the CodeIgniter instance
$CI =& get_instance();

// Load required models
$CI->load->model('Notification_model');
$CI->load->database();

echo "<div class='log info'>🔍 Testing SSE backend for user: {$testUserId}</div>\n";

// Test 1: Check if notifications exist for this user
echo "<h2>Test 1: Check Notifications for User</h2>\n";

try {
    $CI->db->select('*');
    $CI->db->from('notifications');
    $CI->db->where('user_id', $testUserId);
    $CI->db->where('is_read', 0);
    $CI->db->order_by('created_at', 'DESC');
    $CI->db->limit(10);
    
    $query = $CI->db->get();
    $rows = $query->result_array();
    
    echo "<div class='log info'>📊 Found " . count($rows) . " unread notifications for user {$testUserId}</div>\n";
    
    if (count($rows) > 0) {
        echo "<div class='log success'>✅ Unread notifications found:</div>\n";
        foreach ($rows as $index => $row) {
            echo "<div class='log info'>  " . ($index + 1) . ". ID: {$row['id']}, Title: {$row['title']}, Created: {$row['created_at']}, Read: {$row['is_read']}</div>\n";
        }
    } else {
        echo "<div class='log error'>❌ No unread notifications found for user {$testUserId}</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div class='log error'>❌ Database error: " . $e->getMessage() . "</div>\n";
}

// Test 2: Simulate the exact SSE query logic
echo "<h2>Test 2: Simulate SSE Query Logic</h2>\n";

try {
    // Simulate the new notification ID tracking logic
    $sentNotifications = []; // This would be $this->lastSentAtByUser[$userId] in the actual code
    
    $CI->db->select('*');
    $CI->db->from('notifications');
    $CI->db->where('user_id', $testUserId);
    $CI->db->where('is_read', 0);
    $CI->db->order_by('created_at', 'DESC');
    $CI->db->limit(20);
    
    $query = $CI->db->get();
    $rows = $query->result_array();
    
    echo "<div class='log info'>📊 SSE Query returned " . count($rows) . " notifications</div>\n";
    
    $newNotifications = [];
    
    foreach ($rows as $row) {
        $notificationId = $row['id'];
        
        // Check if we've already sent this notification (simulating the new logic)
        if (!in_array($notificationId, $sentNotifications)) {
            echo "<div class='log success'>✅ New notification found - ID: {$notificationId}, Title: {$row['title']}</div>\n";
            
            $newNotifications[] = [
                'id' => $row['id'],
                'type' => $row['type'] ?? 'info',
                'title' => $row['title'] ?? 'Notification',
                'message' => $row['message'] ?? '',
                'timestamp' => $row['created_at'] ? date('c', strtotime($row['created_at'])) : date('c'),
                'is_urgent' => (bool)($row['is_urgent'] ?? false)
            ];
            
            // Mark as sent (simulating the new logic)
            $sentNotifications[] = $notificationId;
        } else {
            echo "<div class='log info'>📋 Notification ID {$notificationId} already sent</div>\n";
        }
    }
    
    echo "<div class='log info'>📊 Total new notifications to send: " . count($newNotifications) . "</div>\n";
    
    if (count($newNotifications) > 0) {
        echo "<div class='log success'>✅ Notifications that should be sent via SSE:</div>\n";
        foreach ($newNotifications as $index => $notification) {
            echo "<div class='log success'>  " . ($index + 1) . ". ID: {$notification['id']}, Title: {$notification['title']}</div>\n";
        }
    } else {
        echo "<div class='log error'>❌ No new notifications to send - this explains why SSE isn't working!</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div class='log error'>❌ SSE simulation error: " . $e->getMessage() . "</div>\n";
}

// Test 3: Check the most recent notifications
echo "<h2>Test 3: Most Recent Notifications</h2>\n";

try {
    $CI->db->select('*');
    $CI->db->from('notifications');
    $CI->db->where('user_id', $testUserId);
    $CI->db->order_by('created_at', 'DESC');
    $CI->db->limit(5);
    
    $query = $CI->db->get();
    $rows = $query->result_array();
    
    echo "<div class='log info'>📊 Most recent 5 notifications for user {$testUserId}:</div>\n";
    
    foreach ($rows as $index => $row) {
        $readStatus = $row['is_read'] ? 'READ' : 'UNREAD';
        $statusClass = $row['is_read'] ? 'info' : 'success';
        echo "<div class='log {$statusClass}'>  " . ($index + 1) . ". ID: {$row['id']}, Title: {$row['title']}, Created: {$row['created_at']}, Status: {$readStatus}</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div class='log error'>❌ Recent notifications error: " . $e->getMessage() . "</div>\n";
}

// Test 4: Create a test notification and immediately check
echo "<h2>Test 4: Create and Check Test Notification</h2>\n";

try {
    $notificationData = [
        'user_id' => $testUserId,
        'type' => 'test',
        'title' => 'SSE Backend Debug Test',
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

echo "<div class='log info'>🔍 Backend debug completed at " . date('Y-m-d H:i:s') . "</div>\n";
?>
