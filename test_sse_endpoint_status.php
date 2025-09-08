<?php
/**
 * Quick SSE Endpoint Status Test
 * This script tests if the SSE endpoints are accessible and responding
 */

echo "🔔 SSE Endpoint Status Test\n";
echo "==========================\n\n";

// Configuration
$base_url = 'https://your-domain.com'; // Replace with your deployed URL
$jwt_token = ''; // Add your JWT token here

if (empty($jwt_token)) {
    echo "❌ Please add your JWT token to the script first!\n";
    echo "Edit the file and set \$jwt_token = 'your_token_here';\n\n";
    exit;
}

echo "📡 Base URL: {$base_url}\n";
echo "🔑 Token: " . substr($jwt_token, 0, 20) . "...\n\n";

// Function to test endpoint
function testEndpoint($url, $method = 'GET', $headers = []) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HEADER => true,
        CURLOPT_NOBODY => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    curl_close($ch);
    
    if ($error) {
        return ['error' => $error, 'http_code' => 0];
    }
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    return [
        'http_code' => $httpCode,
        'headers' => $headers,
        'body' => $body,
        'content_type' => curl_getinfo($ch, CURLINFO_CONTENT_TYPE)
    ];
}

// Test 1: Check if NotificationStreamController status endpoint works
echo "🧪 Test 1: NotificationStreamController Status\n";
echo "URL: {$base_url}/api/notifications/status\n";

$result = testEndpoint("{$base_url}/api/notifications/status");

if ($result['http_code'] === 200) {
    echo "✅ Status endpoint working!\n";
    echo "📋 Response: " . $result['body'] . "\n";
} else {
    echo "❌ Status endpoint failed (HTTP {$result['http_code']})\n";
    if (isset($result['error'])) {
        echo "📋 Error: " . $result['error'] . "\n";
    }
}

echo "\n";

// Test 2: Check if SSE endpoint is accessible (without full connection)
echo "🧪 Test 2: SSE Endpoint Accessibility\n";
echo "URL: {$base_url}/api/notifications/stream\n";

$result = testEndpoint("{$base_url}/api/notifications/stream", 'GET', [
    'Accept: text/event-stream',
    'Authorization: Bearer ' . $jwt_token,
    'Cache-Control: no-cache'
]);

if ($result['http_code'] === 200) {
    echo "✅ SSE endpoint accessible!\n";
    echo "📋 Content-Type: " . ($result['content_type'] ?? 'Not set') . "\n";
    
    // Check if it's actually SSE
    if (strpos($result['headers'], 'text/event-stream') !== false) {
        echo "✅ Correct SSE headers detected!\n";
    } else {
        echo "⚠️  SSE headers not detected in response\n";
    }
    
    // Show first few lines of response
    $lines = explode("\n", $result['body']);
    $firstLines = array_slice($lines, 0, 5);
    echo "📋 First few lines:\n";
    foreach ($firstLines as $line) {
        if (trim($line)) {
            echo "   " . $line . "\n";
        }
    }
} else {
    echo "❌ SSE endpoint failed (HTTP {$result['http_code']})\n";
    if (isset($result['error'])) {
        echo "📋 Error: " . $result['error'] . "\n";
    }
    echo "📋 Response: " . substr($result['body'], 0, 200) . "...\n";
}

echo "\n";

// Test 3: Check alternative SSE endpoint
echo "🧪 Test 3: Alternative SSE Endpoint\n";
echo "URL: {$base_url}/api/notifications/stream/{token}?userId=STU001&role=student\n";

$result = testEndpoint("{$base_url}/api/notifications/stream/{$jwt_token}?userId=STU001&role=student");

if ($result['http_code'] === 200) {
    echo "✅ Alternative SSE endpoint accessible!\n";
    echo "📋 Content-Type: " . ($result['content_type'] ?? 'Not set') . "\n";
} else {
    echo "❌ Alternative SSE endpoint failed (HTTP {$result['http_code']})\n";
    if (isset($result['error'])) {
        echo "📋 Error: " . $result['error'] . "\n";
    }
}

echo "\n";

// Test 4: Check regular notifications API
echo "🧪 Test 4: Regular Notifications API\n";
echo "URL: {$base_url}/api/notifications?userId=STU001\n";

$result = testEndpoint("{$base_url}/api/notifications?userId=STU001", 'GET', [
    'Authorization: Bearer ' . $jwt_token,
    'Content-Type: application/json'
]);

if ($result['http_code'] === 200) {
    echo "✅ Regular notifications API working!\n";
    $data = json_decode($result['body'], true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "📋 API Response: Success\n";
        if (isset($data['data'])) {
            echo "📋 Notifications count: " . count($data['data']) . "\n";
        }
    } else {
        echo "📋 API Response: " . $result['body'] . "\n";
    }
} else {
    echo "❌ Regular notifications API failed (HTTP {$result['http_code']})\n";
    echo "📋 Response: " . substr($result['body'], 0, 200) . "...\n";
}

echo "\n";

// Summary
echo "📊 SUMMARY\n";
echo "==========\n";
echo "✅ If all tests pass, your SSE endpoints are working!\n";
echo "🔗 Primary SSE endpoint: GET /api/notifications/stream\n";
echo "🔗 Alternative SSE endpoint: GET /api/notifications/stream/{token}?userId={id}&role={role}\n";
echo "📝 Create test notifications: POST /api/notifications/create-test\n";
echo "📋 Regular API: GET /api/notifications?userId={id}\n\n";

echo "🎯 Next steps:\n";
echo "1. Use the browser test page (test_sse_browser.html) for full testing\n";
echo "2. Implement EventSource in your frontend with the primary endpoint\n";
echo "3. Handle the 'connected', 'notification', and 'error' events\n";
?>
