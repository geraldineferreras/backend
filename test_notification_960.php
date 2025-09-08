<?php
/**
 * Test Notification ID 960
 * This script checks if notification ID 960 exists and should be picked up by SSE
 */

echo "🔔 Test Notification ID 960\n";
echo "============================\n\n";

// Configuration
$base_url = 'https://scms-backend.up.railway.app';
$jwt_token = ''; // Add your JWT token here
$user_id = 'STD001'; // Replace with actual student ID who should receive the notification

if (empty($jwt_token)) {
    echo "❌ Please add your JWT token to the script first!\n";
    echo "Edit the file and set \$jwt_token = 'your_token_here';\n\n";
    exit;
}

echo "📡 Base URL: {$base_url}\n";
echo "🔑 Token: " . substr($jwt_token, 0, 20) . "...\n";
echo "👤 User ID: {$user_id}\n\n";

// Test 1: Check if notification ID 960 exists for this user
echo "🧪 Test 1: Check if notification ID 960 exists for user {$user_id}\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/notifications?userId=' . $user_id,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $jwt_token,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📊 HTTP Status: " . $httpCode . "\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['data'])) {
        $notifications = $data['data'];
        echo "✅ Found " . count($notifications) . " notifications for user {$user_id}\n";
        
        // Look for notification ID 960
        $notification_960 = null;
        foreach ($notifications as $notif) {
            if ($notif['id'] == 960) {
                $notification_960 = $notif;
                break;
            }
        }
        
        if ($notification_960) {
            echo "✅ Found notification ID 960!\n";
            echo "   - Title: {$notification_960['title']}\n";
            echo "   - Message: {$notification_960['message']}\n";
            echo "   - Type: {$notification_960['type']}\n";
            echo "   - Created: {$notification_960['created_at']}\n";
            echo "   - Read: " . ($notification_960['is_read'] ? 'Yes' : 'No') . "\n";
            echo "   - User ID: {$notification_960['user_id']}\n";
            
            if ($notification_960['user_id'] == $user_id) {
                echo "✅ Notification belongs to user {$user_id}\n";
                
                if (!$notification_960['is_read']) {
                    echo "✅ Notification is unread - should be picked up by SSE!\n";
                } else {
                    echo "❌ Notification is already read - won't be picked up by SSE\n";
                }
            } else {
                echo "❌ Notification belongs to user {$notification_960['user_id']}, not {$user_id}\n";
            }
        } else {
            echo "❌ Notification ID 960 not found for user {$user_id}\n";
            echo "📋 Available notification IDs: ";
            $ids = array_column($notifications, 'id');
            echo implode(', ', $ids) . "\n";
        }
    } else {
        echo "❌ No notifications found or invalid response\n";
    }
} else {
    echo "❌ Failed to get notifications (HTTP {$httpCode})\n";
    echo "📋 Response: " . $response . "\n";
}

echo "\n";

// Test 2: Test SSE connection to see if it picks up the notification
echo "🧪 Test 2: Test SSE connection for 10 seconds\n";

$sse_url = $base_url . '/api/notifications/stream/' . $jwt_token . '?userId=' . $user_id . '&role=student';

echo "🔗 SSE URL: {$sse_url}\n";
echo "⏱️  Testing for 10 seconds...\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $sse_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: text/event-stream',
        'Cache-Control: no-cache'
    ],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_WRITEFUNCTION => function($curl, $data) {
        $timestamp = date('H:i:s');
        echo "[{$timestamp}] " . $data;
        return strlen($data);
    }
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\n📊 SSE HTTP Status: " . $httpCode . "\n";

if ($httpCode === 200) {
    echo "✅ SSE connection successful\n";
} else {
    echo "❌ SSE connection failed (HTTP {$httpCode})\n";
}

echo "\n🏁 Test completed!\n";
echo "\n💡 What to look for:\n";
echo "1. If notification 960 exists and is unread, SSE should send it immediately\n";
echo "2. Look for 'event: notification' with data containing ID 960\n";
echo "3. Check Railway logs for debug messages about notification delivery\n";
?>
