<?php
/**
 * Test script to verify profile picture upload functionality
 * This will test the /api/register endpoint with profile picture upload
 */

// Test data for student registration
$test_data = [
    'role' => 'student',
    'full_name' => 'Test Student Profile Pic',
    'email' => 'testprofilepic' . time() . '@example.com',
    'password' => 'password123',
    'contact_num' => '09123456789',
    'address' => 'Test Address',
    'program' => 'BSIT',
    'student_num' => '2024' . time(),
    'qr_code' => 'QR' . time(),
    'section_id' => '1'
];

echo "=== TESTING PROFILE PICTURE UPLOAD ===\n";
echo "Endpoint: POST /api/register\n";
echo "Test Data:\n";
foreach ($test_data as $key => $value) {
    echo "  {$key}: {$value}\n";
}
echo "\n";

// Create a test image file (1x1 pixel PNG)
$test_image_data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
$test_image_path = 'test_profile_pic.png';
file_put_contents($test_image_path, $test_image_data);

echo "Created test image: {$test_image_path}\n";

// Prepare the multipart form data
$boundary = '----WebKitFormBoundary' . uniqid();
$post_data = '';

// Add form fields
foreach ($test_data as $key => $value) {
    $post_data .= "--{$boundary}\r\n";
    $post_data .= "Content-Disposition: form-data; name=\"{$key}\"\r\n\r\n";
    $post_data .= "{$value}\r\n";
}

// Add profile picture file
$post_data .= "--{$boundary}\r\n";
$post_data .= "Content-Disposition: form-data; name=\"profile_pic\"; filename=\"test_profile_pic.png\"\r\n";
$post_data .= "Content-Type: image/png\r\n\r\n";
$post_data .= $test_image_data . "\r\n";

// Add cover picture file
$post_data .= "--{$boundary}\r\n";
$post_data .= "Content-Disposition: form-data; name=\"cover_pic\"; filename=\"test_cover_pic.png\"\r\n";
$post_data .= "Content-Type: image/png\r\n\r\n";
$post_data .= $test_image_data . "\r\n";

$post_data .= "--{$boundary}--\r\n";

// Make the HTTP request
$url = 'http://localhost/scms_new_backup/api/register';
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post_data,
    CURLOPT_HTTPHEADER => [
        "Content-Type: multipart/form-data; boundary={$boundary}",
        "Content-Length: " . strlen($post_data)
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true
]);

echo "Making request to: {$url}\n";
echo "Content-Type: multipart/form-data; boundary={$boundary}\n";
echo "Content-Length: " . strlen($post_data) . "\n\n";

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

// Clean up test image
unlink($test_image_path);

if ($error) {
    echo "cURL Error: {$error}\n";
    exit(1);
}

echo "=== RESPONSE ===\n";
echo "HTTP Code: {$http_code}\n";
echo "Response:\n{$response}\n";

// Parse response
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $header_size);
$response_data = json_decode($body, true);

if ($response_data) {
    echo "\n=== PARSED RESPONSE ===\n";
    echo "Status: " . ($response_data['status'] ? 'true' : 'false') . "\n";
    echo "Message: " . $response_data['message'] . "\n";
    
    if (isset($response_data['data'])) {
        echo "Data:\n";
        foreach ($response_data['data'] as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
    }
    
    // Check if profile_pic and cover_pic are properly saved
    if (isset($response_data['data']['profile_pic']) && !empty($response_data['data']['profile_pic'])) {
        echo "✅ Profile picture saved successfully: " . $response_data['data']['profile_pic'] . "\n";
    } else {
        echo "❌ Profile picture not saved or empty\n";
    }
    
    if (isset($response_data['data']['cover_pic']) && !empty($response_data['data']['cover_pic'])) {
        echo "✅ Cover picture saved successfully: " . $response_data['data']['cover_pic'] . "\n";
    } else {
        echo "❌ Cover picture not saved or empty\n";
    }
} else {
    echo "❌ Failed to parse JSON response\n";
}

echo "\n=== TEST COMPLETED ===\n";
?>
