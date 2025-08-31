<?php
/**
 * Test Admin API Teacher Assignment Notifications
 * This script tests the admin API endpoints to verify teacher assignment notifications
 */

// Base URL for the API
$base_url = 'http://localhost/scms_new_backup/index.php/api';

echo "🧪 Testing Admin API Teacher Assignment Notifications...\n\n";

// Function to make API requests
function makeApiRequest($method, $endpoint, $data = null, $token = null) {
    global $base_url;
    
    $url = $base_url . $endpoint;
    $headers = ['Content-Type: application/json'];
    
    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $http_code,
        'response' => json_decode($response, true)
    ];
}

// Function to get admin token (you'll need to provide valid admin credentials)
function getAdminToken() {
    // This is a placeholder - you need to implement actual admin login
    // For testing purposes, you can manually get a token from the admin login
    echo "⚠️  Please provide a valid admin JWT token for testing\n";
    echo "You can get this by logging in as admin in the web interface\n";
    return null;
}

// Test data
$test_data = [
    'subject_id' => 1, // Advanced OOP
    'teacher_id' => 'TEA6860CA834786E482', // Joel Quiambao
    'section_id' => 1, // BSIT 4C
    'semester' => '1st',
    'school_year' => '2024-2025'
];

echo "📋 Test Data:\n";
echo "   Subject ID: {$test_data['subject_id']}\n";
echo "   Teacher ID: {$test_data['teacher_id']}\n";
echo "   Section ID: {$test_data['section_id']}\n";
echo "   Semester: {$test_data['semester']}\n";
echo "   School Year: {$test_data['school_year']}\n\n";

// Get admin token
$admin_token = getAdminToken();

if (!$admin_token) {
    echo "❌ No admin token provided. Cannot test API endpoints.\n";
    echo "\n🔧 To test manually:\n";
    echo "1. Login as admin in the web interface\n";
    echo "2. Get your JWT token from browser dev tools\n";
    echo "3. Use Postman or similar tool to test:\n";
    echo "   POST {$base_url}/admin/classes\n";
    echo "   Body: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n";
    echo "   Headers: Authorization: Bearer YOUR_TOKEN\n\n";
    
    echo "📝 Expected notification for teacher:\n";
    echo "   Title: 'New Subject Assignment'\n";
    echo "   Message: 'Hello Joel Quiambao, you have been assigned to teach Advanced OOP (CS101) for Section BSIT 4C (1st Semester, 2024-2025). You can now create classrooms and manage this subject offering.'\n";
    
    exit(1);
}

echo "🔑 Admin token obtained. Testing API endpoints...\n\n";

// Test 1: Create class assignment
echo "🧪 Test 1: Creating class assignment...\n";
$result = makeApiRequest('POST', '/admin/classes', $test_data, $admin_token);

echo "Status Code: {$result['status_code']}\n";
if ($result['status_code'] === 201) {
    echo "✅ Class assignment created successfully!\n";
    $class_id = $result['response']['data']['class_id'] ?? null;
    echo "Class ID: $class_id\n";
    
    // Check if notification was sent (this would require checking the database)
    echo "\n📊 To verify notification was sent:\n";
    echo "1. Login as the teacher (Joel Quiambao)\n";
    echo "2. Check their notifications panel\n";
    echo "3. Look for 'New Subject Assignment' notification\n";
    
} else {
    echo "❌ Failed to create class assignment\n";
    echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

// Test 2: Update class assignment (if class was created)
if (isset($class_id)) {
    echo "🧪 Test 2: Updating class assignment...\n";
    $update_data = [
        'subject_id' => 2, // System Analysis and Design
        'teacher_id' => 'TEA68611875DCD81472', // Different teacher
        'section_id' => 3, // BSIT 4B
        'semester' => '2nd',
        'school_year' => '2024-2025'
    ];
    
    $result = makeApiRequest('PUT', "/admin/classes/$class_id", $update_data, $admin_token);
    
    echo "Status Code: {$result['status_code']}\n";
    if ($result['status_code'] === 200) {
        echo "✅ Class assignment updated successfully!\n";
        echo "\n📊 To verify notification was sent:\n";
        echo "1. Login as the new teacher (Ronnel Delos Santos)\n";
        echo "2. Check their notifications panel\n";
        echo "3. Look for 'New Subject Assignment' notification\n";
        
    } else {
        echo "❌ Failed to update class assignment\n";
        echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    
    // Test 3: Delete class assignment
    echo "🧪 Test 3: Deleting class assignment...\n";
    $result = makeApiRequest('DELETE', "/admin/classes/$class_id", null, $admin_token);
    
    echo "Status Code: {$result['status_code']}\n";
    if ($result['status_code'] === 200) {
        echo "✅ Class assignment deleted successfully!\n";
        echo "\n📊 To verify notification was sent:\n";
        echo "1. Login as the teacher (Ronnel Delos Santos)\n";
        echo "2. Check their notifications panel\n";
        echo "3. Look for 'Subject Assignment Removed' notification\n";
        
    } else {
        echo "❌ Failed to delete class assignment\n";
        echo "Response: " . json_encode($result['response'], JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n🎉 Admin API notification tests completed!\n";
echo "✅ The system should now send notifications when:\n";
echo "   1. Admin assigns a teacher to a subject (POST /admin/classes)\n";
echo "   2. Admin updates teacher assignments (PUT /admin/classes/{id})\n";
echo "   3. Admin removes teacher assignments (DELETE /admin/classes/{id})\n";

echo "\n📝 Notification Details:\n";
echo "   - Type: system\n";
echo "   - Title: 'New Subject Assignment' or 'Subject Assignment Removed'\n";
echo "   - Recipient: The assigned/removed teacher\n";
echo "   - Includes: Subject name, section, semester, school year\n";
?>
