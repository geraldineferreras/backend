<?php
// Simple notification test
header('Content-Type: text/plain');

echo "=== Simple Notification Test ===\n\n";

// Load CodeIgniter
require_once('index.php');

$CI =& get_instance();
$CI->load->model('Notification_model');
$CI->load->model('User_model');
$CI->load->helper('notification');

echo "✅ CodeIgniter loaded\n";
echo "✅ Models and helpers loaded\n\n";

// Test 1: Check if notifications table exists
echo "1. Checking notifications table...\n";
$table_exists = $CI->db->table_exists('notifications');
if ($table_exists) {
    echo "✅ Notifications table exists\n";
    
    // Count existing notifications
    $count = $CI->db->count_all('notifications');
    echo "   Current notifications count: {$count}\n";
} else {
    echo "❌ Notifications table does not exist\n";
    echo "Creating notifications table...\n";
    
    // Create the table
    $create_sql = "
    CREATE TABLE `notifications` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` varchar(50) NOT NULL,
      `type` enum('announcement','task','submission','excuse_letter','grade','enrollment','system') NOT NULL,
      `title` varchar(255) NOT NULL,
      `message` text NOT NULL,
      `related_id` int(11) DEFAULT NULL,
      `related_type` varchar(50) DEFAULT NULL,
      `class_code` varchar(20) DEFAULT NULL,
      `is_read` tinyint(1) NOT NULL DEFAULT 0,
      `is_urgent` tinyint(1) NOT NULL DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_user_id` (`user_id`),
      KEY `idx_type` (`type`),
      KEY `idx_is_read` (`is_read`),
      KEY `idx_created_at` (`created_at`),
      KEY `idx_class_code` (`class_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ";
    
    $CI->db->query($create_sql);
    echo "✅ Notifications table created\n";
}

// Test 2: Get a test user
echo "\n2. Getting test user...\n";
$test_user = $CI->db->get('users', 1)->row_array();
if ($test_user) {
    echo "✅ Test user found: {$test_user['full_name']} ({$test_user['user_id']})\n";
    echo "   Email: {$test_user['email']}\n";
    $test_user_id = $test_user['user_id'];
} else {
    echo "❌ No users found in database\n";
    exit;
}

// Test 3: Test database notification creation
echo "\n3. Testing database notification creation...\n";
$notification_data = array(
    'user_id' => $test_user_id,
    'type' => 'system',
    'title' => 'Test System Notification',
    'message' => 'This is a test notification to verify database insertion works.',
    'related_id' => null,
    'related_type' => null,
    'class_code' => null,
    'is_urgent' => 0
);

$notification_id = $CI->Notification_model->create_notification($notification_data);
if ($notification_id) {
    echo "✅ Database notification created successfully (ID: {$notification_id})\n";
} else {
    echo "❌ Failed to create database notification\n";
    echo "Error: " . $CI->db->last_query() . "\n";
}

// Test 4: Test email notification
echo "\n4. Testing email notification...\n";
if (function_exists('send_email_notification')) {
    echo "✅ send_email_notification function exists\n";
    
    $email_result = send_email_notification(
        $test_user_id,
        'system',
        'Test Email Notification',
        'This is a test email notification to verify SMTP is working.',
        null,
        null,
        null
    );
    
    if ($email_result) {
        echo "✅ Email notification sent successfully\n";
    } else {
        echo "❌ Email notification failed\n";
    }
} else {
    echo "❌ send_email_notification function not found\n";
}

// Test 5: Test the create_notification helper function
echo "\n5. Testing create_notification helper function...\n";
if (function_exists('create_notification')) {
    echo "✅ create_notification function exists\n";
    
    $helper_result = create_notification(
        $test_user_id,
        'system',
        'Test Helper Notification',
        'This is a test using the create_notification helper function.',
        null,
        null,
        null,
        false
    );
    
    if ($helper_result) {
        echo "✅ Helper notification created successfully (ID: {$helper_result})\n";
    } else {
        echo "❌ Helper notification failed\n";
    }
} else {
    echo "❌ create_notification function not found\n";
}

// Test 6: Check final notification count
echo "\n6. Final notification count...\n";
$final_count = $CI->db->count_all('notifications');
echo "   Total notifications in database: {$final_count}\n";

echo "\n=== Test Complete ===\n";
?>
