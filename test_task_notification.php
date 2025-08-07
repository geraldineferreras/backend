<?php
// Simple test script to test task creation with notifications
// This will make an actual API call to test the notification system

$base_url = 'http://localhost/scms_new_backup/index.php';

// Test data for creating a task
$test_data = [
    'title' => 'Test Task with Notifications',
    'type' => 'assignment',
    'points' => 50,
    'instructions' => 'This is a test task to verify that notifications are being sent when tasks are created.',
    'class_codes' => ['J56NHD'], // Use the actual class code from your database
    'assignment_type' => 'classroom',
    'allow_comments' => 1,
    'is_draft' => 0,
    'is_scheduled' => 0,
    'due_date' => '2025-01-30 23:59:00'
];

echo "=== Testing Task Creation with Notifications ===\n";
echo "Base URL: {$base_url}\n";
echo "Test Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Make the API call
$url = $base_url . '/api/tasks/create';

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
echo "Note: You'll need to replace 'YOUR_JWT_TOKEN_HERE' with a valid JWT token from a teacher account\n\n";

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

if ($response_data && isset($response_data['status']) && $response_data['status'] === true) {
    echo "✅ Task created successfully!\n";
    echo "Task ID: " . ($response_data['data']['task_id'] ?? 'Unknown') . "\n";
    echo "Title: " . ($response_data['data']['title'] ?? 'Unknown') . "\n";
    echo "\n📧 Check your email inbox for notifications!\n";
    echo "📱 Check the notifications in your app!\n";
} else {
    echo "❌ Task creation failed or returned error\n";
    if (isset($response_data['message'])) {
        echo "Error: " . $response_data['message'] . "\n";
    }
}

echo "\n=== Test completed ===\n";
?>
