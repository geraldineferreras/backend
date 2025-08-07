<?php
// Test script to verify teacher notifications for task submissions
// This will make an actual API call to test the submission notification system

$base_url = 'http://localhost/scms_new_backup/index.php';

// Test data for submitting a task
$test_data = [
    'submission_content' => 'This is a test submission to verify that teachers receive notifications when students submit tasks.',
    'class_code' => 'J56NHD', // Use the actual class code from your database
    'attachment_type' => 'link',
    'attachment_url' => 'https://drive.google.com/file/d/test-submission/view'
];

echo "=== Testing Task Submission with Teacher Notifications ===\n";
echo "Base URL: {$base_url}\n";
echo "Test Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// First, we need to get a valid task ID and student token
echo "Step 1: Getting available tasks for testing...\n";

// Get tasks for student (this would normally require authentication)
$tasks_url = $base_url . '/api/tasks/student';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tasks_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer YOUR_STUDENT_TOKEN_HERE' // Replace with actual token
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Tasks Response (HTTP {$http_code}):\n";
echo $response . "\n\n";

// For testing purposes, let's use a known task ID
$task_id = 48; // Use the task ID from your previous test
echo "Using task ID: {$task_id}\n\n";

// Test task submission
$submit_url = $base_url . "/api/tasks/{$task_id}/submit";
echo "Step 2: Submitting task to trigger teacher notification...\n";
echo "Submit URL: {$submit_url}\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $submit_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer YOUR_STUDENT_TOKEN_HERE' // Replace with actual student token
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Submission Response (HTTP {$http_code}):\n";
echo $response . "\n\n";

// Parse response
$response_data = json_decode($response, true);

if ($response_data && isset($response_data['status']) && $response_data['status']) {
    echo "✅ Task submission successful!\n";
    echo "Submission ID: " . ($response_data['data']['submission_id'] ?? 'N/A') . "\n\n";
    
    echo "Step 3: Checking if teacher received notification...\n";
    echo "Note: You should check the teacher's email and notification panel to verify the notification was sent.\n";
    echo "Expected notification:\n";
    echo "- Type: Submission\n";
    echo "- Title: New Task Submission: [Task Title]\n";
    echo "- Message: [Student Name] has submitted the task '[Task Title]' for class [Class Name]\n\n";
    
    echo "Step 4: Verification steps:\n";
    echo "1. Check the teacher's email inbox for a notification email\n";
    echo "2. Check the teacher's notification panel in the application\n";
    echo "3. Verify the notification appears in the database\n\n";
    
} else {
    echo "❌ Task submission failed!\n";
    echo "Error: " . ($response_data['message'] ?? 'Unknown error') . "\n\n";
    
    echo "Possible issues:\n";
    echo "1. Invalid student token - make sure to use a valid student JWT token\n";
    echo "2. Task ID doesn't exist or student is not enrolled\n";
    echo "3. Student has already submitted this task\n";
    echo "4. Server error - check the application logs\n\n";
}

echo "=== Test Complete ===\n";
echo "To run this test with actual data:\n";
echo "1. Replace 'YOUR_STUDENT_TOKEN_HERE' with a valid student JWT token\n";
echo "2. Make sure the task ID exists and the student is enrolled in the class\n";
echo "3. Ensure the student hasn't already submitted this task\n";
echo "4. Check that the notification system is properly configured\n";
?>
