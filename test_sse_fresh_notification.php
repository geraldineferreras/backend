<?php
/**
 * Test SSE with Fresh Notification
 * This script creates a fresh notification and immediately tests SSE to see if it's delivered
 */

echo "🔔 Test SSE with Fresh Notification\n";
echo "===================================\n\n";

// Configuration
$base_url = 'https://scms-backend.up.railway.app';
$jwt_token = ''; // Add your JWT token here
$user_id = 'STD001'; // Replace with actual student ID

if (empty($jwt_token)) {
    echo "❌ Please add your JWT token to the script first!\n";
    echo "Edit the file and set \$jwt_token = 'your_token_here';\n\n";
    exit;
}

echo "📡 Base URL: {$base_url}\n";
echo "🔑 Token: " . substr($jwt_token, 0, 20) . "...\n";
echo "👤 User ID: {$user_id}\n\n";

// Step 1: Create a fresh notification
echo "🧪 Step 1: Create a fresh notification\n";

$notificationData = [
    'user_id' => $user_id,
    'type' => 'announcement',
    'title' => 'Fresh SSE Test Notification',
    'message' => 'This notification was created just now to test SSE delivery.',
    'related_id' => 9999,
    'related_type' => 'announcement',
    'class_code' => '9C4K8N',
    'is_urgent' => false
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/notifications',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($notificationData),
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

$notification_id = null;
if ($httpCode === 201) {
    $data = json_decode($response, true);
    if ($data && isset($data['data']['id'])) {
        $notification_id = $data['data']['id'];
        echo "✅ Fresh notification created successfully! ID: {$notification_id}\n";
    } else {
        echo "❌ Failed to create notification - invalid response\n";
        echo "📋 Response: " . $response . "\n";
        exit;
    }
} else {
    echo "❌ Failed to create notification (HTTP {$httpCode})\n";
    echo "📋 Response: " . $response . "\n";
    exit;
}

echo "\n";

// Step 2: Wait a moment for the notification to be fully processed
echo "⏱️  Waiting 2 seconds for notification to be processed...\n";
sleep(2);

// Step 3: Test SSE connection immediately
echo "🧪 Step 2: Test SSE connection immediately\n";

$sse_url = $base_url . '/api/notifications/stream/' . $jwt_token . '?userId=' . $user_id . '&role=student';

echo "🔗 SSE URL: {$sse_url}\n";
echo "⏱️  Testing for 15 seconds...\n";
echo "🎯 Looking for notification ID: {$notification_id}\n\n";

$notification_found = false;
$start_time = time();

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $sse_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: text/event-stream',
        'Cache-Control: no-cache'
    ],
    CURLOPT_TIMEOUT => 15,
    CURLOPT_WRITEFUNCTION => function($curl, $data) use (&$notification_found, $notification_id) {
        $timestamp = date('H:i:s');
        echo "[{$timestamp}] " . $data;
        
        // Check if this is our notification
        if (strpos($data, '"id":' . $notification_id) !== false) {
            $notification_found = true;
            echo "\n🎉 FOUND OUR NOTIFICATION ID {$notification_id}!\n";
        }
        
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

echo "\n";

// Step 4: Results
echo "🏁 Test Results:\n";
if ($notification_found) {
    echo "✅ SUCCESS: Fresh notification was delivered via SSE!\n";
    echo "✅ The SSE system is working correctly!\n";
} else {
    echo "❌ FAILED: Fresh notification was NOT delivered via SSE\n";
    echo "❌ There's still an issue with the SSE system\n";
    echo "\n💡 Possible issues:\n";
    echo "1. SSE endpoint not reading from database\n";
    echo "2. Notification marked as read too quickly\n";
    echo "3. User ID mismatch\n";
    echo "4. Database query issues\n";
}

echo "\n💡 Next steps:\n";
echo "1. Check Railway server logs for debug messages\n";
echo "2. Verify the notification exists in database\n";
echo "3. Check if notification is marked as read\n";
echo "4. Test with different user IDs\n";
?>
