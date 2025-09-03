<?php
// Test Notification System
header('Content-Type: text/plain');

echo "=== SCMS Notification System Test ===\n\n";

// Load CodeIgniter
require_once('index.php');

$CI =& get_instance();
$CI->load->helper('email_notification');

echo "✅ CodeIgniter loaded\n";
echo "✅ Email notification helper loaded\n\n";

// Test 1: Test email configuration
echo "=== Test 1: Email Configuration ===\n";
$test_result = test_email_configuration('grldnferreras@gmail.com');
if ($test_result) {
    echo "✅ Email configuration test passed\n";
} else {
    echo "❌ Email configuration test failed\n";
}

echo "\n=== Test 2: System Email Notification ===\n";

// Test 2: Send a system notification
$result = send_system_email('ADM68B6D4CD8E64D216', 'SCMS System Test', 'This is a test system notification from SCMS. If you receive this, the notification system is working correctly!');

if ($result) {
    echo "✅ System email notification sent successfully!\n";
} else {
    echo "❌ System email notification failed\n";
}

echo "\n=== Test Complete ===\n";
echo "Check your email (grldnferreras@gmail.com) for the test notification.\n";
?>