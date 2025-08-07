<?php
// Test script to verify student notifications for task grading
// This will make an actual API call to test the task grading notification system

$base_url = 'http://localhost/scms_new_backup/index.php';

// Test data for grading a task submission
$test_data = [
    'grade' => 85,
    'feedback' => 'This is a test grade with feedback to verify that students receive notifications when teachers grade their task submissions.'
];

echo "=== Testing Task Grading with Student Notifications ===\n";
echo "Base URL: {$base_url}\n";
echo "Test Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Test task grading
$submission_id = '5'; // Use an actual submission ID from your database
$grade_url = $base_url . "/api/tasks/submissions/{$submission_id}/grade";
echo "Step 1: Grading task submission to trigger student notification...\n";
echo "Grade URL: {$grade_url}\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $grade_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer YOUR_TEACHER_TOKEN_HERE' // Replace with actual teacher token
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Grade Response (HTTP {$http_code}):\n";
echo $response . "\n\n";

// Parse response
$response_data = json_decode($response, true);

if ($response_data && isset($response_data['status']) && $response_data['status']) {
    echo "✅ Task grading successful!\n";
    echo "Grade: " . $test_data['grade'] . "\n";
    echo "Submission ID: {$submission_id}\n\n";
    
    echo "Step 2: Checking if student received notification...\n";
    echo "Note: You should check the student's email and notification panel to verify the notification was sent.\n";
    echo "Expected notification:\n";
    echo "- Type: Grade\n";
    echo "- Title: Task Graded: [Task Title]\n";
    echo "- Message: Your submission for '[Task Title]' has been graded by [Teacher Name]. Grade: [Grade] (Class: [Class Name])\n";
    if ($test_data['feedback']) {
        echo "- Feedback: " . $test_data['feedback'] . "\n";
    }
    echo "\n";
    
    echo "Step 3: Verification steps:\n";
    echo "1. Check the student's email inbox for a notification email\n";
    echo "2. Check the student's notification panel in the application\n";
    echo "3. Verify the notification appears in the database\n\n";
    
} else {
    echo "❌ Task grading failed!\n";
    echo "Error: " . ($response_data['message'] ?? 'Unknown error') . "\n\n";
    
    echo "Possible issues:\n";
    echo "1. Invalid teacher token - make sure to use a valid teacher JWT token\n";
    echo "2. Submission ID doesn't exist or teacher doesn't have access\n";
    echo "3. Invalid grade value\n";
    echo "4. Server error - check the application logs\n\n";
}

echo "=== Test Complete ===\n";
echo "To run this test with actual data:\n";
echo "1. Replace 'YOUR_TEACHER_TOKEN_HERE' with a valid teacher JWT token\n";
echo "2. Make sure the submission ID exists and the teacher has access to it\n";
echo "3. Ensure the submission is in 'submitted' status\n";
echo "4. Check that the notification system is properly configured\n";
echo "5. Test with different grades and feedback messages\n";
?>
