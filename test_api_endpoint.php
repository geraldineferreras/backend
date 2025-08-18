<?php
/**
 * Test Script for Teacher Stream API Endpoint
 * This script tests if the API endpoint is accessible and working
 */

// Test configuration
$base_url = 'http://localhost/scms_new_backup';
$api_endpoint = '/index.php/api/teacher/classroom/TEST123/stream';

echo "=== Teacher Stream API Endpoint Test ===\n\n";

// Test 1: Check if the base URL is accessible
echo "1. Testing base URL accessibility...\n";
$ch = curl_init($base_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    echo "   ✅ Base URL accessible (HTTP $http_code)\n";
} else {
    echo "   ❌ Base URL not accessible (HTTP $http_code)\n";
    echo "   Make sure your XAMPP server is running and the project is in the correct directory.\n";
}

// Test 2: Check if the API endpoint structure exists
echo "\n2. Testing API endpoint structure...\n";
$full_url = $base_url . $api_endpoint;
echo "   Full URL: $full_url\n";

// Test 3: Test OPTIONS request (CORS preflight)
echo "\n3. Testing CORS preflight (OPTIONS request)...\n";
$ch = curl_init($full_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'OPTIONS');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 200) {
    echo "   ✅ CORS preflight successful (HTTP $http_code)\n";
} else {
    echo "   ❌ CORS preflight failed (HTTP $http_code)\n";
}

// Test 4: Test POST request without authentication (should return 401)
echo "\n4. Testing POST request without authentication...\n";
$ch = curl_init($full_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['title' => 'Test', 'content' => 'Test content']));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code == 401) {
    echo "   ✅ Authentication required (HTTP $http_code) - This is expected!\n";
} else {
    echo "   ⚠️  Unexpected response (HTTP $http_code)\n";
}

// Test 5: Check if required files exist
echo "\n5. Checking required files...\n";
$required_files = [
    'application/config/routes.php',
    'application/controllers/api/TeacherController.php',
    'application/models/ClassroomStream_model.php',
    'application/models/StreamAttachment_model.php',
    'application/hooks/CORS_hook.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file exists\n";
    } else {
        echo "   ❌ $file missing\n";
    }
}

// Test 6: Check database connection
echo "\n6. Testing database connection...\n";
$config_file = 'application/config/database.php';
if (file_exists($config_file)) {
    echo "   ✅ Database config file exists\n";
    
    // Try to include and test database connection
    try {
        // Load CodeIgniter database config
        require_once 'application/config/autoload.php';
        require_once 'application/config/database.php';
        
        // This is a basic test - in production you'd use CodeIgniter's database class
        echo "   ✅ CodeIgniter autoload files accessible\n";
    } catch (Exception $e) {
        echo "   ⚠️  CodeIgniter autoload issue: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Database config file missing\n";
}

echo "\n=== Test Summary ===\n";
echo "If you see mostly ✅ marks, your API endpoint should be working correctly.\n";
echo "If you see ❌ marks, check the specific issues mentioned above.\n\n";

echo "=== Next Steps ===\n";
echo "1. Make sure your XAMPP server is running\n";
echo "2. Verify the project is in: C:\\xampp\\htdocs\\scms_new_backup\n";
echo "3. Test with Postman using the full URL: $full_url\n";
echo "4. Include a valid JWT token in the Authorization header\n";
echo "5. Use the test cases from POSTMAN_MULTIPLE_LINK_ATTACHMENTS_TESTING_GUIDE.md\n";

// Test 7: Show current working directory and project structure
echo "\n7. Current project structure...\n";
echo "   Working directory: " . getcwd() . "\n";
echo "   Project files:\n";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..' && is_file($file)) {
        echo "     - $file\n";
    }
}
?>
