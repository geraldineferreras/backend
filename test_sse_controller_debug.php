<?php
// Test SSE controller database logic directly
define('BASEPATH', '');
define('APPPATH', __DIR__ . '/application/');
define('ENVIRONMENT', 'development');

// Load CodeIgniter database
require_once APPPATH . 'config/database.php';
require_once APPPATH . 'config/config.php';

// Initialize database connection
$CI =& get_instance();
$CI->load->database();

echo "🔍 Testing SSE Controller Database Logic\n";
echo "=====================================\n";

// Test user ID
$userId = 'TEA68B79B40CDC7B244';

// Test 1: Check if user exists
$CI->db->select('COUNT(*) as count');
$CI->db->from('users');
$CI->db->where('user_id', $userId);
$query = $CI->db->get();
$result = $query->row();
echo "👤 User exists: " . ($result->count > 0 ? "YES" : "NO") . "\n";

// Test 2: Check notifications table structure
$CI->db->query("DESCRIBE notifications");
$columns = $CI->db->get()->result_array();
echo "📋 Notifications table columns:\n";
foreach ($columns as $column) {
    echo "  - {$column['Field']} ({$column['Type']})\n";
}

// Test 3: Check total notifications for user
$CI->db->select('COUNT(*) as count');
$CI->db->from('notifications');
$CI->db->where('user_id', $userId);
$query = $CI->db->get();
$result = $query->row();
echo "📊 Total notifications for user: {$result->count}\n";

// Test 4: Check unread notifications for user
$CI->db->select('COUNT(*) as count');
$CI->db->from('notifications');
$CI->db->where('user_id', $userId);
$CI->db->where('is_read', 0);
$query = $CI->db->get();
$result = $query->row();
echo "📬 Unread notifications for user: {$result->count}\n";

// Test 5: Get recent unread notifications
$CI->db->select('*');
$CI->db->from('notifications');
$CI->db->where('user_id', $userId);
$CI->db->where('is_read', 0);
$CI->db->order_by('created_at', 'DESC');
$CI->db->limit(5);
$query = $CI->db->get();
$notifications = $query->result_array();

echo "📋 Recent unread notifications:\n";
foreach ($notifications as $notification) {
    echo "  ID: {$notification['id']}, Title: {$notification['title']}, Created: {$notification['created_at']}, User ID: {$notification['user_id']}\n";
}

// Test 6: Test the exact SSE query (with time filter)
$since = time() - 3600; // 1 hour ago
$CI->db->select('*');
$CI->db->from('notifications');
$CI->db->where('user_id', $userId);
$CI->db->where('is_read', 0);
$CI->db->where('created_at >', date('Y-m-d H:i:s', $since));
$CI->db->order_by('created_at', 'ASC');
$CI->db->limit(10);

// Log the SQL query
$sql = $CI->db->get_compiled_select();
echo "🔍 SSE SQL Query: $sql\n";

$query = $CI->db->get();
$sseNotifications = $query->result_array();

echo "🔍 SSE query results (last hour): " . count($sseNotifications) . " notifications\n";
foreach ($sseNotifications as $notification) {
    echo "  ID: {$notification['id']}, Title: {$notification['title']}, Created: {$notification['created_at']}\n";
}

// Test 7: Test without time filter (all unread)
$CI->db->select('*');
$CI->db->from('notifications');
$CI->db->where('user_id', $userId);
$CI->db->where('is_read', 0);
$CI->db->order_by('created_at', 'ASC');
$CI->db->limit(10);

$sql = $CI->db->get_compiled_select();
echo "🔍 All unread SQL Query: $sql\n";

$query = $CI->db->get();
$allUnread = $query->result_array();

echo "🔍 All unread notifications: " . count($allUnread) . " notifications\n";
foreach ($allUnread as $notification) {
    echo "  ID: {$notification['id']}, Title: {$notification['title']}, Created: {$notification['created_at']}\n";
}

// Test 8: Check if there are any notifications with different user_id format
$CI->db->select('*');
$CI->db->from('notifications');
$CI->db->like('user_id', 'TEA68B79B40CDC7B244');
$CI->db->limit(5);
$query = $CI->db->get();
$similarNotifications = $query->result_array();

echo "🔍 Notifications with similar user_id:\n";
foreach ($similarNotifications as $notification) {
    echo "  ID: {$notification['id']}, Title: {$notification['title']}, User ID: {$notification['user_id']}\n";
}

echo "\n✅ Database test completed\n";
?>
