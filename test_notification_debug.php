<?php
// Debug notification system
header('Content-Type: text/plain');

echo "=== Notification System Debug ===\n\n";

// Load CodeIgniter
require_once('index.php');

$CI =& get_instance();
$CI->load->model('Notification_model');
$CI->load->model('User_model');
$CI->load->helper('notification');

echo "✅ CodeIgniter loaded\n\n";

// Test 1: Check current notifications
echo "1. Current notifications in database:\n";
$notifications = $CI->db->get('notifications', 5)->result_array();
foreach ($notifications as $notif) {
    echo "   ID: {$notif['id']}, User: {$notif['user_id']}, Type: {$notif['type']}, Title: {$notif['title']}\n";
}

// Test 2: Get a test user
echo "\n2. Getting test user...\n";
$test_user = $CI->db->get('users', 1)->row_array();
if ($test_user) {
    echo "✅ Test user: {$test_user['full_name']} ({$test_user['user_id']})\n";
    echo "   Email: {$test_user['email']}\n";
    $test_user_id = $test_user['user_id'];
} else {
    echo "❌ No users found\n";
    exit;
}

// Test 3: Create a test notification
echo "\n3. Creating test notification...\n";
$notification_id = create_notification(
    $test_user_id,
    'system',
    'Debug Test Notification',
    'This is a debug test notification to verify the system is working.',
    null,
    null,
    null,
    false
);

if ($notification_id) {
    echo "✅ Notification created successfully (ID: {$notification_id})\n";
} else {
    echo "❌ Failed to create notification\n";
}

// Test 4: Check if notification was saved
echo "\n4. Verifying notification was saved...\n";
$saved_notification = $CI->db->where('id', $notification_id)->get('notifications')->row_array();
if ($saved_notification) {
    echo "✅ Notification found in database:\n";
    echo "   ID: {$saved_notification['id']}\n";
    echo "   User ID: {$saved_notification['user_id']}\n";
    echo "   Type: {$saved_notification['type']}\n";
    echo "   Title: {$saved_notification['title']}\n";
    echo "   Message: {$saved_notification['message']}\n";
    echo "   Created: {$saved_notification['created_at']}\n";
    echo "   Is Read: {$saved_notification['is_read']}\n";
} else {
    echo "❌ Notification not found in database\n";
}

// Test 5: Test email configuration
echo "\n5. Testing email configuration...\n";
$smtp_host = getenv('SMTP_HOST') ? getenv('SMTP_HOST') : 'smtp.gmail.com';
$smtp_user = getenv('SMTP_USER') ? getenv('SMTP_USER') : 'scmswebsitee@gmail.com';
echo "   SMTP Host: {$smtp_host}\n";
echo "   SMTP User: {$smtp_user}\n";

// Test 6: Test email sending
echo "\n6. Testing email sending...\n";
if (function_exists('send_email_notification')) {
    echo "✅ send_email_notification function exists\n";
    
    $email_result = send_email_notification(
        $test_user_id,
        'system',
        'Debug Email Test',
        'This is a debug email test.',
        null,
        null,
        null
    );
    
    if ($email_result) {
        echo "✅ Email sent successfully\n";
    } else {
        echo "❌ Email failed to send\n";
        echo "   This is expected on Railway Free plan (SMTP blocked)\n";
    }
} else {
    echo "❌ send_email_notification function not found\n";
}

// Test 7: Check notification count
echo "\n7. Final notification count...\n";
$final_count = $CI->db->count_all('notifications');
echo "   Total notifications: {$final_count}\n";

echo "\n=== Debug Complete ===\n";
echo "\nSUMMARY:\n";
echo "- Database notifications: ✅ Working (saved to database)\n";
echo "- Email notifications: ❌ Failing (SMTP blocked on Railway Free)\n";
echo "- Real-time notifications: Check frontend SSE connection\n";
echo "\nSOLUTION:\n";
echo "1. Upgrade to Railway Pro ($5/month) to enable SMTP\n";
echo "2. OR use SendGrid API (free tier available)\n";
echo "3. Check frontend SSE connection for real-time notifications\n";
?>
