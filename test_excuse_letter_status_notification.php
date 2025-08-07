<?php
// Test script to verify student notifications for excuse letter status updates
// This will make an actual API call to test the excuse letter status notification system

$base_url = 'http://localhost/scms_new_backup/index.php';

// Test data for updating an excuse letter status
$test_data = [
    'status' => 'approved', // or 'rejected'
    'teacher_notes' => 'This is a test approval with teacher notes to verify that students receive notifications when teachers update excuse letter status.'
];

echo "=== Testing Excuse Letter Status Update with Student Notifications ===\n";
echo "Base URL: {$base_url}\n";
echo "Test Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Test excuse letter status update
$letter_id = '16'; // Use an actual letter ID from your database
$update_url = $base_url . "/api/excuse-letters/update/{$letter_id}";
echo "Step 1: Updating excuse letter status to trigger student notification...\n";
echo "Update URL: {$update_url}\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $update_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer YOUR_TEACHER_TOKEN_HERE' // Replace with actual teacher token
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Update Response (HTTP {$http_code}):\n";
echo $response . "\n\n";

// Parse response
$response_data = json_decode($response, true);

if ($response_data && isset($response_data['status']) && $response_data['status']) {
    echo "✅ Excuse letter status update successful!\n";
    echo "Status: " . $test_data['status'] . "\n";
    echo "Letter ID: {$letter_id}\n\n";
    
    echo "Step 2: Checking if student received notification...\n";
    echo "Note: You should check the student's email and notification panel to verify the notification was sent.\n";
    echo "Expected notification:\n";
    echo "- Type: Excuse Letter\n";
    echo "- Title: Excuse Letter [Status]: [Subject Name]\n";
    echo "- Message: Your excuse letter for [Subject Name] ([Section Name]) has been [status]. Date: [Date]\n";
    if ($test_data['teacher_notes']) {
        echo "- Teacher Notes: " . $test_data['teacher_notes'] . "\n";
    }
    echo "\n";
    
    echo "Step 3: Verification steps:\n";
    echo "1. Check the student's email inbox for a notification email\n";
    echo "2. Check the student's notification panel in the application\n";
    echo "3. Verify the notification appears in the database\n\n";
    
} else {
    echo "❌ Excuse letter status update failed!\n";
    echo "Error: " . ($response_data['message'] ?? 'Unknown error') . "\n\n";
    
    echo "Possible issues:\n";
    echo "1. Invalid teacher token - make sure to use a valid teacher JWT token\n";
    echo "2. Letter ID doesn't exist or teacher doesn't have access\n";
    echo "3. Invalid status value\n";
    echo "4. Server error - check the application logs\n\n";
}

echo "=== Test Complete ===\n";
echo "To run this test with actual data:\n";
echo "1. Replace 'YOUR_TEACHER_TOKEN_HERE' with a valid teacher JWT token\n";
echo "2. Make sure the letter ID exists and the teacher has access to it\n";
echo "3. Ensure the excuse letter is in 'pending' status\n";
echo "4. Check that the notification system is properly configured\n";
echo "5. Test both 'approved' and 'rejected' statuses\n";
?>
