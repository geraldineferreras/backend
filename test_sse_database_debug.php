<?php
/**
 * SSE Database Debug Test
 * This script tests the exact database queries used by the SSE system
 */

// Test parameters
$testUserId = 'TEA68B79B40CDC7B244';

echo "<h1>🔍 SSE Database Debug Test</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .log{background:#f0f0f0;padding:10px;margin:5px 0;border-radius:3px;font-family:monospace;font-size:12px;} .success{background:#d4edda;color:#155724;} .error{background:#f8d7da;color:#721c24;} .info{background:#d1ecf1;color:#0c5460;}</style>\n";

// Include CodeIgniter
require_once('index.php');

// Get the CodeIgniter instance
$CI =& get_instance();

// Load required models
$CI->load->model('Notification_model');
$CI->load->database();

echo "<div class='log info'>🔍 Testing SSE database queries for user: {$testUserId}</div>\n";

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

// Test 2: Check notifications table structure
echo "<h2>Test 2: Notifications Table Structure</h2>\n";
try {
    $query = $CI->db->query("DESCRIBE notifications");
    echo "<div class='log info'>📋 Table structure:</div>\n";
    foreach ($query->result() as $row) {
        echo "<div class='log info'>  - {$row->Field}: {$row->Type}</div>\n";
    }
} catch (Exception $e) {
    echo "<div class='log error'>❌ Table structure error: " . $e->getMessage() . "</div>\n";
}

// Test 3: Check total notifications
echo "<h2>Test 3: Total Notifications</h2>\n";
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
    echo "<div class='log error'>❌ Count error: " . $e->getMessage() . "</div>\n";
}

// Test 4: Test the exact SSE query logic
echo "<h2>Test 4: SSE Query Logic</h2>\n";
try {
    // Simulate the SSE query with timestamp logic
    $since = time() - 60; // 1 minute ago
    
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

// Test 5: Create a test notification and immediately check
echo "<h2>Test 5: Create and Check Test Notification</h2>\n";
try {
    $notificationData = [
        'user_id' => $testUserId,
        'type' => 'test',
        'title' => 'SSE Database Debug Test',
        'message' => 'This is a test notification created at ' . date('Y-m-d H:i:s'),
        'related_id' => null,
        'related_type' => 'test',
        'class_code' => null,
        'is_urgent' => 0
    ];
    
    $notificationId = $CI->Notification_model->create_notification($notificationData);
    
    if ($notificationId) {
        echo "<div class='log success'>✅ Test notification created! ID: {$notificationId}</div>\n";
        
        // Wait a moment for the database to be updated
        sleep(1);
        
        // Now test if it appears in the SSE query
        $since = time() - 60; // 1 minute ago
        
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

echo "<div class='log info'>🔍 Database debug test completed at " . date('Y-m-d H:i:s') . "</div>\n";
?>
