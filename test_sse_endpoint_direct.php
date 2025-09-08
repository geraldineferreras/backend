<?php
/**
 * Direct SSE Endpoint Test
 * This script tests the SSE endpoint directly to see what it returns
 */

// Test parameters - CHANGE THESE TO MATCH YOUR SETUP
$baseUrl = 'https://scms-backend.up.railway.app';
$token = 'YOUR_JWT_TOKEN_HERE'; // Replace with your actual JWT token
$userId = 'STU001';
$userRole = 'student';

echo "<h1>🔍 Direct SSE Endpoint Test</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .log{background:#f0f0f0;padding:10px;margin:5px 0;border-radius:3px;font-family:monospace;font-size:12px;} .success{background:#d4edda;color:#155724;} .error{background:#f8d7da;color:#721c24;} .info{background:#d1ecf1;color:#0c5460;}</style>\n";

if ($token === 'YOUR_JWT_TOKEN_HERE') {
    echo "<div class='log error'>❌ Please update the token variable in this script with your actual JWT token</div>\n";
    echo "<div class='log info'>💡 You can get your JWT token by logging into your application and checking the browser's localStorage or network requests</div>\n";
    exit;
}

// Test 1: Check if the SSE endpoint is accessible
echo "<h2>Test 1: SSE Endpoint Accessibility</h2>\n";

$sseUrl = "{$baseUrl}/api/notifications/stream/{$token}?userId={$userId}&role={$userRole}";
echo "<div class='log info'>🔍 Testing SSE URL: {$sseUrl}</div>\n";

// Use cURL to test the endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $sseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/event-stream',
    'Authorization: Bearer ' . $token,
    'Cache-Control: no-cache'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);

curl_close($ch);

echo "<div class='log info'>📡 HTTP Status Code: {$httpCode}</div>\n";
echo "<div class='log info'>📡 Response Headers:</div>\n";
echo "<div class='log info'>{$headers}</div>\n";

if ($httpCode === 200) {
    echo "<div class='log success'>✅ SSE endpoint is accessible</div>\n";
} else {
    echo "<div class='log error'>❌ SSE endpoint returned HTTP {$httpCode}</div>\n";
}

// Test 2: Try to get a small sample of the SSE stream
echo "<h2>Test 2: SSE Stream Sample</h2>\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $sseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 15 second timeout
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/event-stream',
    'Authorization: Bearer ' . $token,
    'Cache-Control: no-cache'
]);

echo "<div class='log info'>🔍 Attempting to read SSE stream for 15 seconds...</div>\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($error) {
    echo "<div class='log error'>❌ cURL Error: {$error}</div>\n";
} else {
    echo "<div class='log info'>📡 HTTP Status Code: {$httpCode}</div>\n";
    
    if ($httpCode === 200) {
        echo "<div class='log success'>✅ SSE stream response received</div>\n";
        echo "<div class='log info'>📨 Stream content (first 1000 characters):</div>\n";
        echo "<div class='log info'>" . htmlspecialchars(substr($response, 0, 1000)) . "</div>\n";
        
        if (strlen($response) > 1000) {
            echo "<div class='log info'>... (truncated, total length: " . strlen($response) . " characters)</div>\n";
        }
        
        // Parse SSE events
        $lines = explode("\n", $response);
        $events = [];
        $currentEvent = [];
        
        foreach ($lines as $line) {
            if (trim($line) === '') {
                if (!empty($currentEvent)) {
                    $events[] = $currentEvent;
                    $currentEvent = [];
                }
            } elseif (strpos($line, 'event: ') === 0) {
                $currentEvent['event'] = substr($line, 7);
            } elseif (strpos($line, 'data: ') === 0) {
                $currentEvent['data'] = substr($line, 6);
            }
        }
        
        if (!empty($currentEvent)) {
            $events[] = $currentEvent;
        }
        
        echo "<div class='log info'>📊 Parsed " . count($events) . " SSE events:</div>\n";
        foreach ($events as $index => $event) {
            echo "<div class='log info'>  " . ($index + 1) . ". Event: " . ($event['event'] ?? 'message') . ", Data: " . ($event['data'] ?? 'none') . "</div>\n";
        }
        
    } else {
        echo "<div class='log error'>❌ SSE stream returned HTTP {$httpCode}</div>\n";
        echo "<div class='log error'>Response: " . htmlspecialchars($response) . "</div>\n";
    }
}

// Test 3: Test notification creation endpoint
echo "<h2>Test 3: Notification Creation Endpoint</h2>\n";

$notificationData = [
    'recipient_id' => $userId,
    'title' => 'SSE Direct Test Notification',
    'message' => 'This is a test notification created at ' . date('Y-m-d H:i:s'),
    'type' => 'test',
    'is_urgent' => false
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/api/notifications");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($notificationData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($error) {
    echo "<div class='log error'>❌ cURL Error: {$error}</div>\n";
} else {
    echo "<div class='log info'>📡 Notification creation HTTP Status Code: {$httpCode}</div>\n";
    echo "<div class='log info'>📨 Notification creation response:</div>\n";
    echo "<div class='log info'>" . htmlspecialchars($response) . "</div>\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "<div class='log success'>✅ Notification created successfully</div>\n";
            
            // Wait a moment and then test SSE again
            echo "<div class='log info'>⏳ Waiting 3 seconds before testing SSE again...</div>\n";
            sleep(3);
            
            // Test SSE again to see if the new notification appears
            echo "<h2>Test 4: SSE Stream After Notification Creation</h2>\n";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $sseUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: text/event-stream',
                'Authorization: Bearer ' . $token,
                'Cache-Control: no-cache'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            if (!$error && $httpCode === 200) {
                echo "<div class='log success'>✅ SSE stream still accessible after notification creation</div>\n";
                echo "<div class='log info'>📨 Stream content (first 500 characters):</div>\n";
                echo "<div class='log info'>" . htmlspecialchars(substr($response, 0, 500)) . "</div>\n";
            } else {
                echo "<div class='log error'>❌ SSE stream error after notification creation: {$error}</div>\n";
            }
        } else {
            echo "<div class='log error'>❌ Notification creation failed</div>\n";
        }
    } else {
        echo "<div class='log error'>❌ Notification creation returned HTTP {$httpCode}</div>\n";
    }
}

echo "<div class='log info'>🔍 Direct SSE endpoint test completed at " . date('Y-m-d H:i:s') . "</div>\n";
?>
