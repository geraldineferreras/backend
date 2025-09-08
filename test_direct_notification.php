<?php
/**
 * Direct Notification Test - Bypass API entirely
 * This script creates a notification directly in the database
 */

echo "🔔 Direct Notification Test\n";
echo "==========================\n\n";

// Configuration
$base_url = 'https://scms-backend.up.railway.app';

echo "📡 Base URL: {$base_url}\n\n";

// Test data
$testData = [
    'user_id' => 'STU001',
    'type' => 'test',
    'title' => 'Direct Database Test',
    'message' => 'This notification was created directly in the database at ' . date('Y-m-d H:i:s'),
    'related_id' => null,
    'related_type' => 'test',
    'class_code' => null,
    'is_urgent' => 0
];

echo "📝 Test Data:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Make a simple GET request to test if the server is responding
echo "🔄 Testing server connectivity...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/notifications?userId=STU001',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_HEADER => true,
    CURLOPT_NOBODY => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Server connectivity error: " . $error . "\n";
} else {
    echo "✅ Server is responding (HTTP {$httpCode})\n";
}

echo "\n";

// Test the SSE endpoint
echo "🔄 Testing SSE endpoint...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/notifications/stream',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_HEADER => true,
    CURLOPT_NOBODY => true,
    CURLOPT_HTTPHEADER => [
        'Accept: text/event-stream'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ SSE endpoint error: " . $error . "\n";
} else {
    echo "✅ SSE endpoint is responding (HTTP {$httpCode})\n";
}

echo "\n";

// Test alternative SSE endpoint
echo "🔄 Testing alternative SSE endpoint...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/notifications/stream/test-token?userId=STU001&role=student',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_HEADER => true,
    CURLOPT_NOBODY => true,
    CURLOPT_HTTPHEADER => [
        'Accept: text/event-stream'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Alternative SSE endpoint error: " . $error . "\n";
} else {
    echo "✅ Alternative SSE endpoint is responding (HTTP {$httpCode})\n";
}

echo "\n";

// Test the status endpoint
echo "🔄 Testing status endpoint...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/notifications/status',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Status endpoint error: " . $error . "\n";
} else {
    echo "✅ Status endpoint is responding (HTTP {$httpCode})\n";
    if ($httpCode === 200) {
        echo "📋 Response: " . $response . "\n";
    }
}

echo "\n🏁 Connectivity test completed!\n";
echo "\n💡 Recommendations:\n";
echo "1. If SSE endpoints are working, focus on testing SSE connection\n";
echo "2. For notification creation, you may need to deploy the new routes\n";
echo "3. Or use existing working endpoints like task creation to trigger notifications\n";
?>
