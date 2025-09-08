<?php
/**
 * Test Announcement Notification Creation
 * This script tests if notifications are created when announcements are posted
 */

echo "🔔 Test Announcement Notification Creation\n";
echo "==========================================\n\n";

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

// Test 1: Check if there are students in the class
echo "🧪 Test 1: Check students in class {$class_code}\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/teacher/classroom/' . $class_code . '/students',
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

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['data'])) {
        $students = $data['data'];
        echo "✅ Found " . count($students) . " students in class\n";
        foreach ($students as $student) {
            echo "   - {$student['full_name']} ({$student['user_id']})\n";
        }
    } else {
        echo "❌ No students found or invalid response\n";
    }
} else {
    echo "❌ Failed to get students (HTTP {$httpCode})\n";
    echo "📋 Response: " . $response . "\n";
}

echo "\n";

// Test 2: Create a test announcement
echo "🧪 Test 2: Create test announcement\n";

$announcementData = [
    'title' => 'Test Notification Announcement',
    'content' => 'This is a test announcement to check if notifications are created for students.',
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
        
        // Test 3: Check if notifications were created
        echo "\n🧪 Test 3: Check if notifications were created\n";
        
        // Wait a moment for notifications to be created
        sleep(2);
        
        // Check notifications for each student
        if (isset($students)) {
            foreach ($students as $student) {
                $student_id = $student['user_id'];
                
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $base_url . '/api/notifications?userId=' . $student_id,
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
                            echo "✅ Notification created for {$student['full_name']}\n";
                        } else {
                            echo "❌ No notification found for {$student['full_name']}\n";
                        }
                    }
                } else {
                    echo "❌ Failed to get notifications for {$student['full_name']} (HTTP {$notifHttpCode})\n";
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
echo "\n💡 If notifications are not being created, check:\n";
echo "1. Notification helper functions are loaded\n";
echo "2. get_class_students function returns correct data\n";
echo "3. create_notifications_for_users function works\n";
echo "4. Database connection is working\n";
echo "5. Notification_model is properly loaded\n";
?>
