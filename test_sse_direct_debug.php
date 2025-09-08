<?php
/**
 * Direct SSE Debug Test
 * This script directly tests the SSE endpoint to see what's happening
 */

// Test parameters
$baseUrl = 'https://scms-backend.up.railway.app';
$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoiVEVBNjhCNzlCNDBDREM3QjI0NCIsInJvbGUiOiJ0ZWFjaGVyIiwiZW1haWwiOiJzYXJtaWVudG9hbmplbGEwMDNAZ21haWwuY29tIiwiZnVsbF9uYW1lIjoiQW5qZWxhIFNvZmlhIEcuIFNhcm1pZW50byIsImlhdCI6MTc1NzMzNTk5NywiZXhwIjoxNzU3MzM5NTk3LCJuYmYiOjE3NTczMzU5OTd9.BCDT1zYLc2AX4bGeNiFdwyKKz5RMfKmuq3jxh9uHxI4';
$userId = 'TEA68B79B40CDC7B244';
$userRole = 'teacher';

echo "<h1>🔍 Direct SSE Debug Test</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .log{background:#f0f0f0;padding:10px;margin:5px 0;border-radius:3px;font-family:monospace;font-size:12px;} .success{background:#d4edda;color:#155724;} .error{background:#f8d7da;color:#721c24;} .info{background:#d1ecf1;color:#0c5460;}</style>\n";

echo "<div class='log info'>🔍 Testing SSE endpoint directly for user: {$userId}</div>\n";

// Test 1: Check if notifications exist for this user
echo "<h2>Test 1: Check Notifications for User</h2>\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/api/notifications?userId={$userId}");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

if ($error) {
    echo "<div class='log error'>❌ cURL Error: {$error}</div>\n";
} else {
    echo "<div class='log info'>📡 HTTP Status Code: {$httpCode}</div>\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            $notifications = $data['data']['notifications'] ?? [];
            echo "<div class='log success'>✅ Found " . count($notifications) . " total notifications</div>\n";
            
            $unreadNotifications = array_filter($notifications, function($n) { return !$n['is_read']; });
            echo "<div class='log info'>📊 Unread notifications: " . count($unreadNotifications) . "</div>\n";
            
            if (count($unreadNotifications) > 0) {
                echo "<div class='log success'>✅ Unread notifications found:</div>\n";
                foreach (array_slice($unreadNotifications, 0, 5) as $index => $notification) {
                    echo "<div class='log info'>  " . ($index + 1) . ". ID: {$notification['id']}, Title: {$notification['title']}, Created: {$notification['created_at']}</div>\n";
                }
            } else {
                echo "<div class='log error'>❌ No unread notifications found</div>\n";
            }
        } else {
            echo "<div class='log error'>❌ API returned error: " . htmlspecialchars($response) . "</div>\n";
        }
    } else {
        echo "<div class='log error'>❌ HTTP Error {$httpCode}: " . htmlspecialchars($response) . "</div>\n";
    }
}

// Test 2: Create a test notification
echo "<h2>Test 2: Create Test Notification</h2>\n";

$notificationData = [
    'recipient_id' => $userId,
    'title' => 'Direct SSE Debug Test Notification',
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
    echo "<div class='log info'>📡 HTTP Status Code: {$httpCode}</div>\n";
    echo "<div class='log info'>📨 Response: " . htmlspecialchars($response) . "</div>\n";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            $notificationId = $data['data']['id'] ?? 'Unknown';
            echo "<div class='log success'>✅ Test notification created! ID: {$notificationId}</div>\n";
        } else {
            echo "<div class='log error'>❌ Failed to create notification</div>\n";
        }
    } else {
        echo "<div class='log error'>❌ HTTP Error {$httpCode}</div>\n";
    }
}

// Test 3: Test SSE endpoint directly
echo "<h2>Test 3: Test SSE Endpoint Directly</h2>\n";

$sseUrl = "{$baseUrl}/api/notifications/stream/{$token}?userId={$userId}&role={$userRole}";
echo "<div class='log info'>🔍 Testing SSE URL: {$sseUrl}</div>\n";

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
        echo "<div class='log info'>📨 Stream content (first 2000 characters):</div>\n";
        echo "<div class='log info'>" . htmlspecialchars(substr($response, 0, 2000)) . "</div>\n";
        
        if (strlen($response) > 2000) {
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
            $eventType = $event['event'] ?? 'message';
            $eventData = $event['data'] ?? 'none';
            
            if ($eventType === 'notification') {
                echo "<div class='log success'>  " . ($index + 1) . ". Event: {$eventType}, Data: {$eventData}</div>\n";
            } else {
                echo "<div class='log info'>  " . ($index + 1) . ". Event: {$eventType}, Data: {$eventData}</div>\n";
            }
        }
        
        // Check if any notification events were found
        $notificationEvents = array_filter($events, function($event) {
            return ($event['event'] ?? '') === 'notification';
        });
        
        if (count($notificationEvents) > 0) {
            echo "<div class='log success'>✅ Found " . count($notificationEvents) . " notification events in SSE stream!</div>\n";
        } else {
            echo "<div class='log error'>❌ No notification events found in SSE stream</div>\n";
            echo "<div class='log error'>This confirms the issue is in the SSE notification sending logic</div>\n";
        }
        
    } else {
        echo "<div class='log error'>❌ SSE stream returned HTTP {$httpCode}</div>\n";
        echo "<div class='log error'>Response: " . htmlspecialchars($response) . "</div>\n";
    }
}

echo "<div class='log info'>🔍 Direct SSE debug test completed at " . date('Y-m-d H:i:s') . "</div>\n";
?>
