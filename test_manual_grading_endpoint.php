<?php
/**
 * Test Manual Grading Endpoint
 * This script tests if the manual grading API endpoint is working correctly
 */

// Test configuration
$base_url = 'http://localhost/scms_new_backup/index.php';
$task_id = 105;
$endpoint = "/api/tasks/{$task_id}/manual-grade";

// Test data - you'll need to update these with real values
$test_data = [
    'student_id' => 'STU68B3F6580EFD1284', // Replace with actual student ID
    'class_code' => 'A4V9TE',               // Replace with actual class code
    'grade' => 7,
    'feedback' => 'Excellent work! Your research is thorough and well-presented. Great use of citations.'
];

echo "🧪 Testing Manual Grading Endpoint\n";
echo "==================================\n";
echo "URL: {$base_url}{$endpoint}\n";
echo "Method: POST\n";
echo "Task ID: {$task_id}\n";
echo "Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Test 1: Check if endpoint exists (should not return 404)
echo "📋 Test 1: Endpoint Accessibility\n";
echo "--------------------------------\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 404) {
    echo "❌ FAILED: Endpoint returned 404 - Route not found\n";
    echo "   This means the route is not properly configured\n";
    echo "   Check if the route was added to routes.php\n\n";
} else {
    echo "✅ PASSED: Endpoint is accessible (HTTP {$http_code})\n";
    echo "   The route is properly configured\n\n";
}

// Test 2: Test with missing required fields
echo "📋 Test 2: Validation - Missing Required Fields\n";
echo "-----------------------------------------------\n";

$invalid_data = [
    'grade' => 7,
    'feedback' => 'Test feedback'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($invalid_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 400) {
    echo "✅ PASSED: API correctly rejected missing required fields (HTTP {$http_code})\n";
    echo "   Response: " . $response . "\n\n";
} else {
    echo "❌ FAILED: API should have rejected missing fields (HTTP {$http_code})\n";
    echo "   Response: " . $response . "\n\n";
}

// Test 3: Test with valid data (but no auth token)
echo "📋 Test 3: Authentication Check\n";
echo "-------------------------------\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url . $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 401 || $http_code == 403) {
    echo "✅ PASSED: API correctly requires authentication (HTTP {$http_code})\n";
    echo "   Response: " . $response . "\n\n";
} else {
    echo "❌ FAILED: API should require authentication (HTTP {$http_code})\n";
    echo "   Response: " . $response . "\n\n";
}

// Test 4: Check if the controller method exists
echo "📋 Test 4: Controller Method Check\n";
echo "----------------------------------\n";

$controller_file = 'application/controllers/api/TaskController.php';
if (file_exists($controller_file)) {
    $controller_content = file_get_contents($controller_file);
    if (strpos($controller_content, 'manual_grade_post') !== false) {
        echo "✅ PASSED: Controller method 'manual_grade_post' exists\n";
    } else {
        echo "❌ FAILED: Controller method 'manual_grade_post' not found\n";
    }
} else {
    echo "❌ FAILED: Controller file not found: {$controller_file}\n";
}

echo "\n";

// Test 5: Check routes configuration
echo "📋 Test 5: Routes Configuration Check\n";
echo "------------------------------------\n";

$routes_file = 'application/config/routes.php';
if (file_exists($routes_file)) {
    $routes_content = file_get_contents($routes_file);
    if (strpos($routes_content, 'manual-grade') !== false) {
        echo "✅ PASSED: Route for manual grading is configured\n";
    } else {
        echo "❌ FAILED: Route for manual grading is not configured\n";
    }
} else {
    echo "❌ FAILED: Routes file not found: {$routes_file}\n";
}

echo "\n";

// Summary
echo "📊 Summary\n";
echo "==========\n";
echo "To test the manual grading endpoint with authentication:\n";
echo "1. Get a valid teacher JWT token\n";
echo "2. Use this curl command:\n\n";

echo "curl -X POST {$base_url}{$endpoint} \\\n";
echo "  -H 'Content-Type: application/json' \\\n";
echo "  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \\\n";
echo "  -d '" . json_encode($test_data) . "'\n\n";

echo "Required fields:\n";
echo "- student_id: Student's unique identifier\n";
echo "- class_code: Class code where task is posted\n";
echo "- grade: Numerical grade (0 to max points)\n";
echo "- feedback: Optional feedback text\n\n";

echo "Make sure to replace the test data with actual values from your database!\n";
?>
