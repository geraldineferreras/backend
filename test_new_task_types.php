<?php
/**
 * Test Script for New Task Types: midterm_exam and final_exam
 * 
 * This script tests the creation of tasks with the new task types
 * to ensure they are properly validated and accepted by the system.
 */

// Configuration
$base_url = 'http://localhost/scms_new_backup'; // Adjust to your local setup
$teacher_token = 'YOUR_TEACHER_TOKEN_HERE'; // Replace with actual token

// Test data for new task types
$test_tasks = [
    [
        'title' => 'Mathematics Midterm Examination',
        'type' => 'midterm_exam',
        'points' => 100,
        'instructions' => 'Complete the midterm examination covering algebra and calculus fundamentals.',
        'class_codes' => ['MATH101'],
        'assignment_type' => 'classroom',
        'allow_comments' => true,
        'is_draft' => false,
        'due_date' => '2025-03-15 14:00:00'
    ],
    [
        'title' => 'Computer Science Final Project',
        'type' => 'final_exam',
        'points' => 150,
        'instructions' => 'Submit your final project demonstrating all learned programming concepts.',
        'class_codes' => ['CS101'],
        'assignment_type' => 'classroom',
        'allow_comments' => true,
        'is_draft' => false,
        'due_date' => '2025-05-20 16:00:00'
    ]
];

/**
 * Test task creation with new types
 */
function testTaskCreation($base_url, $token, $task_data) {
    $url = $base_url . '/api/tasks/create';
    
    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($task_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'http_code' => $http_code,
        'response' => $response,
        'task_data' => $task_data
    ];
}

/**
 * Display test results
 */
function displayResults($results) {
    echo "=== Testing New Task Types ===\n\n";
    
    foreach ($results as $index => $result) {
        $task_type = $result['task_data']['type'];
        $task_title = $result['task_data']['title'];
        
        echo "Test " . ($index + 1) . ": {$task_type} - {$task_title}\n";
        echo "HTTP Code: " . $result['http_code'] . "\n";
        
        if ($result['http_code'] === 201) {
            echo "✅ SUCCESS: Task created successfully\n";
        } else {
            echo "❌ FAILED: Task creation failed\n";
        }
        
        echo "Response: " . $result['response'] . "\n";
        echo "---\n\n";
    }
}

/**
 * Test invalid task type (should fail)
 */
function testInvalidTaskType($base_url, $token) {
    $invalid_task = [
        'title' => 'Invalid Task Type Test',
        'type' => 'invalid_type',
        'points' => 50,
        'instructions' => 'This should fail validation.',
        'class_codes' => ['TEST101'],
        'assignment_type' => 'classroom',
        'allow_comments' => true,
        'is_draft' => false,
        'due_date' => '2025-01-30 23:59:00'
    ];
    
    $result = testTaskCreation($base_url, $token, $invalid_task);
    
    echo "=== Testing Invalid Task Type ===\n";
    echo "Task Type: invalid_type\n";
    echo "HTTP Code: " . $result['http_code'] . "\n";
    
    if ($result['http_code'] === 400) {
        echo "✅ SUCCESS: Invalid task type properly rejected\n";
    } else {
        echo "❌ FAILED: Invalid task type should have been rejected\n";
    }
    
    echo "Response: " . $result['response'] . "\n";
    echo "---\n\n";
}

// Main execution
if ($teacher_token === 'YOUR_TEACHER_TOKEN_HERE') {
    echo "⚠️  Please set a valid teacher token in the script before running.\n";
    echo "You can get a token by logging in as a teacher through the API.\n";
    exit(1);
}

echo "Starting tests for new task types...\n\n";

// Test valid new task types
$results = [];
foreach ($test_tasks as $task_data) {
    $results[] = testTaskCreation($base_url, $teacher_token, $task_data);
}

displayResults($results);

// Test invalid task type
testInvalidTaskType($base_url, $teacher_token);

echo "Testing completed!\n";
echo "\nTo run these tests:\n";
echo "1. Ensure your database has been updated with the new task types\n";
echo "2. Set a valid teacher token in the script\n";
echo "3. Run: php test_new_task_types.php\n";
echo "\nExpected results:\n";
echo "- midterm_exam and final_exam tasks should create successfully (HTTP 201)\n";
echo "- invalid_type should be rejected (HTTP 400)\n";
?>
