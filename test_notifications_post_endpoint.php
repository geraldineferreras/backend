<?php
// Test script for the new POST /api/notifications endpoint
// This will test if the 404 error has been resolved

$base_url = 'https://scms-backend.up.railway.app';

// Test data for sending a notification (matching frontend expectations)
$test_data = [
    'recipient_id' => 'STU001', // Required field
    'message' => 'This is a test notification to verify the POST endpoint is working.', // Required field
    'type' => 'announcement',
    'title' => 'Test Notification',
    'related_id' => 123,
    'related_type' => 'test',
    'class_code' => 'TEST001',
    'is_urgent' => false
];

echo "=== Testing POST /api/notifications Endpoint ===\n";
echo "Base URL: {$base_url}\n";
echo "Test Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Make the API call
$url = $base_url . '/api/notifications';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer YOUR_JWT_TOKEN_HERE' // You'll need to replace this with a valid token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "Making API call to: {$url}\n";
echo "Note: You'll need to replace 'YOUR_JWT_TOKEN_HERE' with a valid JWT token\n\n";

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Status Code: {$http_code}\n";
if ($error) {
    echo "cURL Error: {$error}\n";
}

echo "Response:\n";
echo $response . "\n\n";

// Parse the response
$response_data = json_decode($response, true);

if ($http_code === 200 || $http_code === 201) {
    echo "✅ SUCCESS: POST /api/notifications endpoint is working!\n";
    if ($response_data && isset($response_data['success']) && $response_data['success'] === true) {
        echo "✅ Notification created successfully\n";
    }
} elseif ($http_code === 401) {
    echo "⚠️  UNAUTHORIZED: The endpoint exists but requires a valid JWT token\n";
    echo "This is expected behavior - the 404 error has been resolved!\n";
} elseif ($http_code === 404) {
    echo "❌ NOT FOUND: The endpoint is still returning 404\n";
    echo "The fix may not have been deployed yet or there's still an issue\n";
} else {
    echo "⚠️  UNEXPECTED STATUS: {$http_code}\n";
    echo "Response: {$response}\n";
}

echo "\n=== Test Complete ===\n";
?>
