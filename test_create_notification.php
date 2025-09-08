<?php
/**
 * Test Create Notification Endpoint
 * This script tests the create-test endpoint
 */

echo "🔔 Test Create Notification Endpoint\n";
echo "===================================\n\n";

// Configuration
$base_url = 'https://scms-backend.up.railway.app'; // Your deployed URL
$jwt_token = ''; // Add your JWT token here

if (empty($jwt_token)) {
    echo "❌ Please add your JWT token to the script first!\n";
    echo "Edit the file and set \$jwt_token = 'your_token_here';\n\n";
    exit;
}

echo "📡 Base URL: {$base_url}\n";
echo "🔑 Token: " . substr($jwt_token, 0, 20) . "...\n\n";

// Test data
$notificationData = [
    'user_id' => 'STU001',
    'title' => 'Test Notification from PHP',
    'message' => 'This is a test notification created at ' . date('Y-m-d H:i:s'),
    'type' => 'test',
    'is_urgent' => false
];

echo "📝 Test Data:\n";
echo json_encode($notificationData, JSON_PRETTY_PRINT) . "\n\n";

// Make the request
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/notifications/create-test',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($notificationData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $jwt_token
    ],
    CURLOPT_TIMEOUT => 30
]);

echo "🔄 Sending request...\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($error) {
    echo "❌ cURL Error: " . $error . "\n";
} else {
    echo "📊 HTTP Status: " . $httpCode . "\n";
    echo "📋 Response:\n";
    echo $response . "\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "\n✅ SUCCESS! Notification created with ID: " . $data['data']['notification_id'] . "\n";
        } else {
            echo "\n❌ API returned error: " . ($data['error'] ?? 'Unknown error') . "\n";
        }
    } else {
        echo "\n❌ Request failed with HTTP " . $httpCode . "\n";
    }
}

echo "\n🏁 Test completed!\n";
?>
