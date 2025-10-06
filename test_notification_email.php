<?php
/**
 * Test Notification Email System
 */

// Load CodeIgniter
require_once('index.php');

header('Content-Type: text/plain');
echo "=== NOTIFICATION EMAIL TEST ===\n\n";

$CI =& get_instance();
$CI->load->database();

// Test 1: Check if notification helper exists
echo "1. Loading notification helper...\n";
if (!function_exists('send_email_notification')) {
    require_once APPPATH . 'helpers/email_notification_helper.php';
}
echo "✅ Notification helper loaded\n\n";

// Test 2: Check if we have users in database
echo "2. Checking users in database...\n";
try {
    $CI->db->select('user_id, email, role');
    $CI->db->from('users');
    $CI->db->limit(5);
    $query = $CI->db->get();
    $users = $query->result_array();
    
    echo "Found " . count($users) . " users:\n";
    foreach ($users as $user) {
        echo "- {$user['user_id']} ({$user['role']}): {$user['email']}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Error getting users: " . $e->getMessage() . "\n\n";
}

// Test 3: Check recent notifications
echo "3. Checking recent notifications...\n";
try {
    $CI->db->select('id, user_id, type, title, created_at');
    $CI->db->from('notifications');
    $CI->db->order_by('created_at', 'DESC');
    $CI->db->limit(5);
    $query = $CI->db->get();
    $notifications = $query->result_array();
    
    echo "Found " . count($notifications) . " recent notifications:\n";
    foreach ($notifications as $notif) {
        echo "- ID {$notif['id']}: {$notif['type']} for {$notif['user_id']} - {$notif['title']}\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Error getting notifications: " . $e->getMessage() . "\n\n";
}

// Test 4: Try to send a test notification email
echo "4. Testing notification email sending...\n";
if (!empty($users)) {
    $test_user = $users[0];
    echo "Testing with user: {$test_user['user_id']} ({$test_user['email']})\n";
    
    try {
        $result = send_email_notification(
            $test_user['user_id'],
            'test',
            'Test Notification Email',
            'This is a test notification to check if email sending works.',
            null,
            'test',
            null
        );
        
        if ($result) {
            echo "✅ SUCCESS: Test notification email sent!\n";
        } else {
            echo "❌ FAILED: Test notification email not sent\n";
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ No users found to test with\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
