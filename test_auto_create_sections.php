<?php
/**
 * Test Auto-Create Sections Functionality
 * This script tests the new auto-create sections endpoint
 */

// Configuration
$base_url = 'http://localhost/scms_new_backup/index.php/api';
$admin_email = 'admin@school.com';
$admin_password = 'password';

echo "<h1>🧪 Test Auto-Create Sections - SCMS</h1>\n";
echo "<p>Testing the new auto-create sections endpoint that creates sections without advisers, academic year, or semester.</p>\n";

// Function to make HTTP requests
function makeRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    if ($data && in_array($method, ['POST', 'PUT'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLOPT_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => $error];
    }
    
    return [
        'success' => true,
        'status_code' => $http_code,
        'response' => json_decode($response, true),
        'raw_response' => $response
    ];
}

// Step 1: Login as admin
echo "<h2>🔐 Step 1: Admin Login</h2>\n";
$login_data = [
    'email' => $admin_email,
    'password' => $admin_password
];

$login_result = makeRequest(
    $base_url . '/auth/login',
    'POST',
    $login_data,
    ['Content-Type: application/json']
);

if (!$login_result['success']) {
    echo "<p style='color: red;'>❌ Login failed: " . $login_result['error'] . "</p>\n";
    exit;
}

if ($login_result['status_code'] !== 200) {
    echo "<p style='color: red;'>❌ Login failed with status code: " . $login_result['status_code'] . "</p>\n";
    echo "<p>Response: " . htmlspecialchars($login_result['raw_response']) . "</p>\n";
    exit;
}

$login_response = $login_result['response'];
if (!$login_response['status']) {
    echo "<p style='color: red;'>❌ Login failed: " . $login_response['message'] . "</p>\n";
    exit;
}

$admin_token = $login_response['data']['token'];
echo "<p style='color: green;'>✅ Login successful!</p>\n";
echo "<p>Token: " . substr($admin_token, 0, 50) . "...</p>\n";

// Step 2: Get current section count
echo "<h2>📊 Step 2: Get Current Section Count</h2>\n";
$count_result = makeRequest(
    $base_url . '/admin/sections/count',
    'GET',
    null,
    ['Authorization: Bearer ' . $admin_token]
);

if ($count_result['success'] && $count_result['status_code'] === 200) {
    $count_data = $count_result['response']['data'];
    echo "<p>📚 Current Total Sections: " . $count_data['total_sections'] . "</p>\n";
    echo "<p>👥 Total Enrolled Students: " . $count_data['total_enrolled_students'] . "</p>\n";
    echo "<p>👨‍🏫 Sections with Advisers: " . $count_data['adviser_coverage']['with_advisers'] . "</p>\n";
    echo "<p>👨‍🏫 Sections without Advisers: " . $count_data['adviser_coverage']['without_advisers'] . "</p>\n";
} else {
    echo "<p style='color: orange;'>⚠️ Could not get current section count</p>\n";
}

// Step 3: Auto-create sections
echo "<h2>🚀 Step 3: Auto-Create Sections</h2>\n";
echo "<p>This will create 176 sections (4 programs × 4 years × 11 sections) without advisers, academic year, or semester.</p>\n";

$auto_create_result = makeRequest(
    $base_url . '/admin/sections/auto-create',
    'POST',
    null,
    ['Authorization: Bearer ' . $admin_token, 'Content-Type: application/json']
);

if (!$auto_create_result['success']) {
    echo "<p style='color: red;'>❌ Auto-create failed: " . $auto_create_result['error'] . "</p>\n";
    exit;
}

if ($auto_create_result['status_code'] !== 200) {
    echo "<p style='color: red;'>❌ Auto-create failed with status code: " . $auto_create_result['status_code'] . "</p>\n";
    echo "<p>Response: " . htmlspecialchars($auto_create_result['raw_response']) . "</p>\n";
    exit;
}

$auto_create_response = $auto_create_result['response'];
if (!$auto_create_response['status']) {
    echo "<p style='color: red;'>❌ Auto-create failed: " . $auto_create_response['message'] . "</p>\n";
    exit;
}

$auto_create_data = $auto_create_response['data'];
echo "<p style='color: green;'>✅ Sections created successfully!</p>\n";
echo "<p><strong>📊 Summary:</strong></p>\n";
echo "<ul>\n";
echo "<li>Created: " . $auto_create_data['created_sections'] . " new sections</li>\n";
echo "<li>Existing: " . $auto_create_data['existing_sections'] . " sections already existed</li>\n";
echo "<li>Total: " . $auto_create_data['total_sections'] . " sections</li>\n";
echo "<li>Programs: " . implode(', ', $auto_create_data['programs']) . "</li>\n";
echo "<li>Year Levels: " . implode(', ', $auto_create_data['year_levels']) . "</li>\n";
echo "<li>Sections per year: " . $auto_create_data['sections_per_year'] . "</li>\n";
echo "</ul>\n";

// Step 4: Get updated section count
echo "<h2>📊 Step 4: Get Updated Section Count</h2>\n";
$updated_count_result = makeRequest(
    $base_url . '/admin/sections/count',
    'GET',
    null,
    ['Authorization: Bearer ' . $admin_token]
);

if ($updated_count_result['success'] && $updated_count_result['status_code'] === 200) {
    $updated_count_data = $updated_count_result['response']['data'];
    echo "<p>📚 Updated Total Sections: " . $updated_count_data['total_sections'] . "</p>\n";
    echo "<p>👥 Total Enrolled Students: " . $updated_count_data['total_enrolled_students'] . "</p>\n";
    echo "<p>👨‍🏫 Sections with Advisers: " . $updated_count_data['adviser_coverage']['with_advisers'] . "</p>\n";
    echo "<p>👨‍🏫 Sections without Advisers: " . $updated_count_data['adviser_coverage']['without_advisers'] . "</p>\n";
} else {
    echo "<p style='color: orange;'>⚠️ Could not get updated section count</p>\n";
}

// Step 5: Show some sample sections
echo "<h2>🔍 Step 5: Sample Created Sections</h2>\n";
$sections_result = makeRequest(
    $base_url . '/admin/sections',
    'GET',
    null,
    ['Authorization: Bearer ' . $admin_token]
);

if ($sections_result['success'] && $sections_result['status_code'] === 200) {
    $sections_data = $sections_result['response']['data'];
    $total_sections = count($sections_data);
    
    echo "<p>📚 Total Sections in System: " . $total_sections . "</p>\n";
    
    // Show first 10 sections as examples
    echo "<p><strong>Sample Sections (first 10):</strong></p>\n";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr style='background-color: #f0f0f0;'>\n";
    echo "<th>Section Name</th><th>Program</th><th>Year Level</th><th>Semester</th><th>Academic Year</th><th>Adviser</th><th>Students</th>\n";
    echo "</tr>\n";
    
    $count = 0;
    foreach ($sections_data as $section) {
        if ($count >= 10) break;
        
        echo "<tr>\n";
        echo "<td>" . htmlspecialchars($section['section_name']) . "</td>\n";
        echo "<td>" . htmlspecialchars($section['program']) . "</td>\n";
        echo "<td>" . htmlspecialchars($section['year_level'] ?? 'N/A') . "</td>\n";
        echo "<td>" . htmlspecialchars($section['semester'] ?? 'N/A') . "</td>\n";
        echo "<td>" . htmlspecialchars($section['academic_year'] ?? 'N/A') . "</td>\n";
        echo "<td>" . htmlspecialchars($section['adviserDetails']['name'] ?? 'No Adviser') . "</td>\n";
        echo "<td>" . $section['enrolled_count'] . "</td>\n";
        echo "</tr>\n";
        
        $count++;
    }
    
    echo "</table>\n";
    
    if ($total_sections > 10) {
        echo "<p><em>... and " . ($total_sections - 10) . " more sections</em></p>\n";
    }
} else {
    echo "<p style='color: orange;'>⚠️ Could not retrieve sections</p>\n";
}

echo "<h2>✅ Test Complete!</h2>\n";
echo "<p>The auto-create sections functionality has been successfully tested.</p>\n";
echo "<p>You can now use this endpoint in your frontend to automatically create all the sections you need.</p>\n";
?>
