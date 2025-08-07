<?php
// Test script to verify teacher notifications for excuse letter submissions
// This will make an actual API call to test the excuse letter notification system

$base_url = 'http://localhost/scms_new_backup/index.php';

// Test data for submitting an excuse letter
$test_data = [
    'class_id' => '5', // Use an actual class ID from your database
    'date_absent' => '2025-01-07',
    'reason' => 'This is a test excuse letter to verify that teachers receive notifications when students submit excuse letters.'
];

echo "=== Testing Excuse Letter Submission with Teacher Notifications ===\n";
echo "Base URL: {$base_url}\n";
echo "Test Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Test excuse letter submission
$submit_url = $base_url . '/api/excuse-letters/submit';
echo "Step 1: Submitting excuse letter to trigger teacher notification...\n";
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
    echo "✅ Excuse letter submission successful!\n";
    echo "Letter ID: " . ($response_data['data']['letter_id'] ?? 'N/A') . "\n";
    echo "Student: " . ($response_data['data']['student_name'] ?? 'N/A') . "\n";
    echo "Subject: " . ($response_data['data']['subject_name'] ?? 'N/A') . "\n";
    echo "Date Absent: " . ($response_data['data']['date_absent'] ?? 'N/A') . "\n\n";
    
    echo "Step 2: Checking if teacher received notification...\n";
    echo "Note: You should check the teacher's email and notification panel to verify the notification was sent.\n";
    echo "Expected notification:\n";
    echo "- Type: Excuse Letter\n";
    echo "- Title: New Excuse Letter: [Subject Name]\n";
    echo "- Message: [Student Name] has submitted an excuse letter for [Subject Name] ([Section Name]) - Date: [Date]\n\n";
    
    echo "Step 3: Verification steps:\n";
    echo "1. Check the teacher's email inbox for a notification email\n";
    echo "2. Check the teacher's notification panel in the application\n";
    echo "3. Verify the notification appears in the database\n\n";
    
} else {
    echo "❌ Excuse letter submission failed!\n";
    echo "Error: " . ($response_data['message'] ?? 'Unknown error') . "\n\n";
    
    echo "Possible issues:\n";
    echo "1. Invalid student token - make sure to use a valid student JWT token\n";
    echo "2. Class ID doesn't exist or student is not enrolled\n";
    echo "3. Student has already submitted an excuse letter for this date and class\n";
    echo "4. Server error - check the application logs\n\n";
}

echo "=== Test Complete ===\n";
echo "To run this test with actual data:\n";
echo "1. Replace 'YOUR_STUDENT_TOKEN_HERE' with a valid student JWT token\n";
echo "2. Make sure the class ID exists and the student is enrolled in the class\n";
echo "3. Ensure the student hasn't already submitted an excuse letter for this date and class\n";
echo "4. Check that the notification system is properly configured\n";
?>
