<?php
/**
 * Test Student Profile Options Endpoints
 * This script tests the new student endpoints for viewing available courses, years, and sections
 */

echo "🧪 Testing Student Profile Options Endpoints...\n\n";

// Test 1: Get all available programs
echo "🔍 Test 1: Getting available programs...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/scms_new_backup/index.php/api/student/programs');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer YOUR_JWT_TOKEN_HERE' // Replace with actual token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: {$http_code}\n";
if ($error) {
    echo "cURL Error: {$error}\n";
}
echo "Response: {$response}\n\n";

// Test 2: Get year levels for a specific program
echo "🔍 Test 2: Getting year levels for BSIT...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/scms_new_backup/index.php/api/student/programs/Bachelor%20of%20Science%20in%20Information%20Technology/years');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer YOUR_JWT_TOKEN_HERE' // Replace with actual token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: {$http_code}\n";
if ($error) {
    echo "cURL Error: {$error}\n";
}
echo "Response: {$response}\n\n";

// Test 3: Get sections for a specific program and year
echo "🔍 Test 3: Getting sections for BSIT 1st year...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/scms_new_backup/index.php/api/student/programs/Bachelor%20of%20Science%20in%20Information%20Technology/years/1st%20year/sections');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer YOUR_JWT_TOKEN_HERE' // Replace with actual token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: {$http_code}\n";
if ($error) {
    echo "cURL Error: {$error}\n";
}
echo "Response: {$response}\n\n";

// Test 4: Get all profile options
echo "🔍 Test 4: Getting all profile options...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/scms_new_backup/index.php/api/student/profile-options');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer YOUR_JWT_TOKEN_HERE' // Replace with actual token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: {$http_code}\n";
if ($error) {
    echo "cURL Error: {$error}\n";
}
echo "Response: {$response}\n\n";

// Test 5: Get sections grouped by program and year
echo "🔍 Test 5: Getting sections grouped by program and year...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/scms_new_backup/index.php/api/student/sections-grouped');
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer YOUR_JWT_TOKEN_HERE' // Replace with actual token
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: {$http_code}\n";
if ($error) {
    echo "cURL Error: {$error}\n";
}
echo "Response: {$response}\n\n";

echo "🧪 Test completed!\n";
echo "\n💡 Note: These endpoints require authentication with a valid JWT token.\n";
echo "   Replace 'YOUR_JWT_TOKEN_HERE' with an actual token from a student login.\n";
echo "\n📋 Available Endpoints:\n";
echo "1. GET /api/student/programs - View all available programs\n";
echo "2. GET /api/student/programs/{program}/years - View year levels for a program\n";
echo "3. GET /api/student/programs/{program}/years/{year}/sections - View sections for program/year\n";
echo "4. GET /api/student/profile-options - View all profile options at once\n";
echo "5. GET /api/student/sections-grouped - View sections grouped by program and year\n";
?>
