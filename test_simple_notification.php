<?php
/**
 * Simple Notification Test - Direct Database Insert
 * This script creates a notification directly in the database for testing
 */

echo "🔔 Simple Notification Test\n";
echo "==========================\n\n";

// Configuration
$base_url = 'https://scms-backend.up.railway.app';
$jwt_token = ''; // Add your JWT token here

if (empty($jwt_token)) {
    echo "❌ Please add your JWT token to the script first!\n";
    echo "Edit the file and set \$jwt_token = 'your_token_here';\n\n";
    exit;
}

echo "📡 Base URL: {$base_url}\n";
echo "🔑 Token: " . substr($jwt_token, 0, 20) . "...\n\n";

// Test data - using the exact format expected by the existing API
$notificationData = [
    'recipient_id' => 'STU001',
    'title' => 'Direct Test Notification',
    'message' => 'This notification was created directly for SSE testing at ' . date('Y-m-d H:i:s'),
    'type' => 'test',
    'is_urgent' => false
];

echo "📝 Test Data:\n";
echo json_encode($notificationData, JSON_PRETTY_PRINT) . "\n\n";

// Make the request
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/notifications',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($notificationData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $jwt_token
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => true
]);

echo "🔄 Sending request...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

// Get more detailed error info
$info = curl_getinfo($ch);

curl_close($ch);

echo "📊 HTTP Status: " . $httpCode . "\n";
echo "📊 Content Type: " . ($info['content_type'] ?? 'Not set') . "\n";
echo "📊 Total Time: " . ($info['total_time'] ?? 'Unknown') . " seconds\n\n";

if ($error) {
    echo "❌ cURL Error: " . $error . "\n";
} else {
    echo "📋 Raw Response:\n";
    echo $response . "\n\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✅ SUCCESS! Notification created with ID: " . ($data['data']['id'] ?? 'Unknown') . "\n";
        } else {
            echo "❌ API returned error: " . ($data['error'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "❌ Request failed with HTTP " . $httpCode . "\n";
        
        // Try to parse error response
        $errorData = json_decode($response, true);
        if ($errorData && isset($errorData['error'])) {
            echo "📋 Error details: " . $errorData['error'] . "\n";
        }
    }
}

echo "\n🏁 Test completed!\n";
?>