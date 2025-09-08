<?php
/**
 * Complete SSE Test Script
 * This script tests both notification creation and SSE streaming
 * Run with: php test_sse_complete.php
 */

echo "🔔 Complete SSE Test Script\n";
echo "==========================\n\n";

// Configuration
$base_url = 'https://your-domain.com'; // Replace with your deployed URL
$jwt_token = ''; // Add your JWT token here
$test_user_id = 'STU001'; // Test user ID

if (empty($jwt_token)) {
    echo "❌ Please add your JWT token to the script first!\n";
    echo "Edit the file and set \$jwt_token = 'your_token_here';\n\n";
    exit;
}

echo "📡 Base URL: {$base_url}\n";
echo "🔑 Token: " . substr($jwt_token, 0, 20) . "...\n";
echo "👤 Test User ID: {$test_user_id}\n\n";

// Function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30
    ]);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    return [
        'data' => json_decode($response, true),
        'http_code' => $httpCode,
        'raw' => $response
    ];
}

// Test 1: Create a test notification
echo "🧪 Test 1: Creating test notification...\n";
$createUrl = "{$base_url}/api/notifications/create-test";
$notificationData = [
    'user_id' => $test_user_id,
    'title' => 'SSE Test Notification',
    'message' => 'This is a test notification created at ' . date('Y-m-d H:i:s'),
    'type' => 'test',
    'is_urgent' => false
];

$result = makeRequest($createUrl, 'POST', $notificationData, [
    'Authorization: Bearer ' . $jwt_token
]);

if ($result['http_code'] === 200 && isset($result['data']['success']) && $result['data']['success']) {
    echo "✅ Test notification created successfully!\n";
    echo "📋 Notification ID: " . $result['data']['notification_id'] . "\n\n";
} else {
    echo "❌ Failed to create test notification\n";
    echo "📋 Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
}

// Test 2: Test regular notifications API
echo "🧪 Test 2: Testing regular notifications API...\n";
$notificationsUrl = "{$base_url}/api/notifications?userId={$test_user_id}";

$result = makeRequest($notificationsUrl, 'GET', null, [
    'Authorization: Bearer ' . $jwt_token
]);

if ($result['http_code'] === 200) {
    echo "✅ Regular notifications API working!\n";
    $notifications = $result['data']['data'] ?? [];
    echo "📋 Found " . count($notifications) . " notifications\n";
    if (!empty($notifications)) {
        echo "📝 Latest notification: " . $notifications[0]['title'] . "\n";
    }
} else {
    echo "❌ Regular notifications API failed\n";
    echo "📋 Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
}

echo "\n";

// Test 3: Test SSE connection
echo "🧪 Test 3: Testing SSE connection...\n";
echo "🔄 Connecting to SSE stream (will run for 15 seconds)...\n\n";

$sseUrl = "{$base_url}/api/notifications/stream";
$headers = [
    'Accept: text/event-stream',
    'Authorization: Bearer ' . $jwt_token,
    'Cache-Control: no-cache'
];

// Initialize cURL for SSE
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $sseUrl,
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_HEADER => false,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_WRITEFUNCTION => function($ch, $data) {
        echo $data;
        return strlen($data);
    }
]);

$startTime = time();
$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo "\n❌ SSE cURL Error: " . curl_error($ch) . "\n";
} else {
    echo "\n✅ SSE stream completed or timed out\n";
}

curl_close($ch);

echo "\n🏁 All tests completed!\n";
echo "⏱️  Total time: " . (time() - $startTime) . " seconds\n";

// Test 4: Create another notification to test real-time updates
echo "\n🧪 Test 4: Creating another notification for real-time test...\n";
$notificationData2 = [
    'user_id' => $test_user_id,
    'title' => 'Real-time SSE Test',
    'message' => 'This notification should appear in real-time if SSE is working',
    'type' => 'realtime_test',
    'is_urgent' => true
];

$result = makeRequest($createUrl, 'POST', $notificationData2, [
    'Authorization: Bearer ' . $jwt_token
]);

if ($result['http_code'] === 200 && isset($result['data']['success']) && $result['data']['success']) {
    echo "✅ Real-time test notification created!\n";
    echo "📋 Notification ID: " . $result['data']['notification_id'] . "\n";
} else {
    echo "❌ Failed to create real-time test notification\n";
}

echo "\n🎯 Next steps:\n";
echo "1. Run this script to create test notifications\n";
echo "2. Open your SSE test page in browser\n";
echo "3. Connect to SSE stream\n";
echo "4. Run this script again to create more notifications\n";
echo "5. Watch for real-time updates in your browser\n";
?>
