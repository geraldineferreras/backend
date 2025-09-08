<?php
/**
 * Complete Notification Flow Test
 * This script tests the entire notification flow from creation to SSE delivery
 */

echo "🔔 Complete Notification Flow Test\n";
echo "==================================\n\n";

// Configuration
$base_url = 'https://scms-backend.up.railway.app';
$jwt_token = ''; // Add your JWT token here
$class_code = '9C4K8N'; // The class code from your database

if (empty($jwt_token)) {
    echo "❌ Please add your JWT token to the script first!\n";
    echo "Edit the file and set \$jwt_token = 'your_token_here';\n\n";
    exit;
}

echo "📡 Base URL: {$base_url}\n";
echo "🔑 Token: " . substr($jwt_token, 0, 20) . "...\n";
echo "🏫 Class Code: {$class_code}\n\n";

// Test 1: Check current notifications in database
echo "🧪 Test 1: Check current notifications in database\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/notifications?userId=STD001', // Replace with actual student ID
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $jwt_token,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📊 HTTP Status: " . $httpCode . "\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['data'])) {
        $notifications = $data['data'];
        echo "✅ Found " . count($notifications) . " notifications in database\n";
        
        // Show recent notifications
        $recent_notifications = array_slice($notifications, 0, 5);
        foreach ($recent_notifications as $notif) {
            echo "   - ID: {$notif['id']}, Type: {$notif['type']}, Title: {$notif['title']}\n";
            echo "     Created: {$notif['created_at']}, Read: " . ($notif['is_read'] ? 'Yes' : 'No') . "\n";
        }
    } else {
        echo "❌ No notifications found or invalid response\n";
    }
} else {
    echo "❌ Failed to get notifications (HTTP {$httpCode})\n";
    echo "📋 Response: " . $response . "\n";
}

echo "\n";

// Test 2: Create a test notification directly
echo "🧪 Test 2: Create test notification directly\n";

$notificationData = [
    'user_id' => 'STD001', // Replace with actual student ID
    'type' => 'announcement',
    'title' => 'Direct Test Notification',
    'message' => 'This is a direct test notification to verify SSE delivery.',
    'related_id' => 999,
    'related_type' => 'announcement',
    'class_code' => $class_code,
    'is_urgent' => false
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/notifications',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($notificationData),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $jwt_token,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📊 HTTP Status: " . $httpCode . "\n";

if ($httpCode === 201) {
    $data = json_decode($response, true);
    if ($data && isset($data['data']['id'])) {
        $notification_id = $data['data']['id'];
        echo "✅ Direct notification created successfully! ID: {$notification_id}\n";
    } else {
        echo "❌ Failed to create direct notification - invalid response\n";
        echo "📋 Response: " . $response . "\n";
    }
} else {
    echo "❌ Failed to create direct notification (HTTP {$httpCode})\n";
    echo "📋 Response: " . $response . "\n";
}

echo "\n";

// Test 3: Test SSE connection
echo "🧪 Test 3: Test SSE connection\n";

echo "🔗 SSE URL: {$base_url}/api/notifications/stream/{$jwt_token}?userId=STD001&role=student\n";
echo "⏱️  Testing SSE connection for 10 seconds...\n";

$sse_url = $base_url . '/api/notifications/stream/' . $jwt_token . '?userId=STD001&role=student';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $sse_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: text/event-stream',
        'Cache-Control: no-cache'
    ],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_WRITEFUNCTION => function($curl, $data) {
        echo "📡 SSE Data: " . $data;
        return strlen($data);
    }
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\n📊 SSE HTTP Status: " . $httpCode . "\n";

if ($httpCode === 200) {
    echo "✅ SSE connection successful\n";
} else {
    echo "❌ SSE connection failed (HTTP {$httpCode})\n";
}

echo "\n";

// Test 4: Create announcement and check for notifications
echo "🧪 Test 4: Create announcement and check for notifications\n";

$announcementData = [
    'title' => 'SSE Test Announcement',
    'content' => 'This announcement should trigger notifications for all students in the class.',
    'is_draft' => 0,
    'allow_comments' => 1
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/teacher/classroom/' . $class_code . '/stream',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($announcementData),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $jwt_token,
        'Content-Type: application/json'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📊 HTTP Status: " . $httpCode . "\n";

if ($httpCode === 201) {
    $data = json_decode($response, true);
    if ($data && isset($data['data']['id'])) {
        $announcement_id = $data['data']['id'];
        echo "✅ Announcement created successfully! ID: {$announcement_id}\n";
        
        // Wait for notifications to be created
        echo "⏱️  Waiting 3 seconds for notifications to be created...\n";
        sleep(3);
        
        // Check if notifications were created
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $base_url . '/api/notifications?userId=STD001',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $jwt_token,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $notifResponse = curl_exec($ch);
        $notifHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($notifHttpCode === 200) {
            $notifData = json_decode($notifResponse, true);
            if ($notifData && isset($notifData['data'])) {
                $notifications = $notifData['data'];
                $recent_notifications = array_filter($notifications, function($notif) use ($announcement_id) {
                    return $notif['related_id'] == $announcement_id && $notif['type'] === 'announcement';
                });
                
                if (!empty($recent_notifications)) {
                    echo "✅ Notifications created for announcement!\n";
                    foreach ($recent_notifications as $notif) {
                        echo "   - ID: {$notif['id']}, Title: {$notif['title']}\n";
                    }
                } else {
                    echo "❌ No notifications found for announcement\n";
                }
            }
        }
        
    } else {
        echo "❌ Failed to create announcement - invalid response\n";
        echo "📋 Response: " . $response . "\n";
    }
} else {
    echo "❌ Failed to create announcement (HTTP {$httpCode})\n";
    echo "📋 Response: " . $response . "\n";
}

echo "\n🏁 Test completed!\n";
echo "\n💡 Next steps:\n";
echo "1. Check Railway server logs for debug messages\n";
echo "2. Verify notification creation in database\n";
echo "3. Test SSE connection in browser\n";
echo "4. Check if notifications are being marked as read too quickly\n";
?>
