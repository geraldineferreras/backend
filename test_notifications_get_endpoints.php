<?php
// Test script for the GET /api/notifications endpoints
// This will test the new Notifications_api controller

$base_url = 'https://scms-backend.up.railway.app';
$test_user_id = 'STU001'; // Replace with actual user ID
$jwt_token = 'YOUR_JWT_TOKEN_HERE'; // Replace with actual JWT token

echo "=== Testing GET /api/notifications Endpoints ===\n";
echo "Base URL: {$base_url}\n";
echo "Test User ID: {$test_user_id}\n\n";

// Test 1: GET /api/notifications (index)
echo "--- Test 1: GET /api/notifications ---\n";
$url1 = $base_url . '/api/notifications?userId=' . $test_user_id;
testGetEndpoint($url1, $jwt_token);

echo "\n";

// Test 2: GET /api/notifications/recent
echo "--- Test 2: GET /api/notifications/recent ---\n";
$url2 = $base_url . '/api/notifications/recent?userId=' . $test_user_id . '&limit=5';
testGetEndpoint($url2, $jwt_token);

echo "\n";

// Test 3: GET /api/notifications/unread-count
echo "--- Test 3: GET /api/notifications/unread-count ---\n";
$url3 = $base_url . '/api/notifications/unread-count?userId=' . $test_user_id;
testGetEndpoint($url3, $jwt_token);

echo "\n=== Test Complete ===\n";

function testGetEndpoint($url, $jwt_token) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Authorization: Bearer ' . $jwt_token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    echo "Making API call to: {$url}\n";

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    echo "HTTP Status Code: {$http_code}\n";
    if ($error) {
        echo "cURL Error: {$error}\n";
    }

    echo "Response:\n";
    echo $response . "\n";

    // Parse the response
    $response_data = json_decode($response, true);

    if ($http_code === 200) {
        echo "✅ SUCCESS: Endpoint is working!\n";
        if ($response_data && isset($response_data['success']) && $response_data['success'] === true) {
            echo "✅ Response format is correct\n";
        }
    } elseif ($http_code === 401) {
        echo "⚠️  UNAUTHORIZED: The endpoint exists but requires a valid JWT token\n";
        echo "This is expected behavior - the 404 error has been resolved!\n";
    } elseif ($http_code === 404) {
        echo "❌ NOT FOUND: The endpoint is still returning 404\n";
        echo "The fix may not have been deployed yet or there's still an issue\n";
    } else {
        echo "⚠️  UNEXPECTED STATUS: {$http_code}\n";
    }
}
?>
