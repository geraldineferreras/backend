<?php
/**
 * Direct SSE Test
 * This script tests the SSE endpoint directly to see what's happening
 */

echo "🔔 Direct SSE Test\n";
echo "==================\n\n";

// Configuration
$base_url = 'https://scms-backend.up.railway.app';
$jwt_token = ''; // Add your JWT token here
$user_id = 'STD001'; // Replace with actual student ID
$role = 'student';

if (empty($jwt_token)) {
    echo "❌ Please add your JWT token to the script first!\n";
    echo "Edit the file and set \$jwt_token = 'your_token_here';\n\n";
    exit;
}

echo "📡 Base URL: {$base_url}\n";
echo "🔑 Token: " . substr($jwt_token, 0, 20) . "...\n";
echo "👤 User ID: {$user_id}\n";
echo "🎭 Role: {$role}\n\n";

// Test SSE endpoint
echo "🧪 Testing SSE endpoint...\n";

$sse_url = $base_url . '/api/notifications/stream/' . $jwt_token . '?userId=' . $user_id . '&role=' . $role;

echo "🔗 SSE URL: {$sse_url}\n";
echo "⏱️  Testing for 15 seconds...\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $sse_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: text/event-stream',
        'Cache-Control: no-cache'
    ],
    CURLOPT_TIMEOUT => 15,
    CURLOPT_WRITEFUNCTION => function($curl, $data) {
        $timestamp = date('H:i:s');
        echo "[{$timestamp}] " . $data;
        return strlen($data);
    }
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "\n📊 SSE HTTP Status: " . $httpCode . "\n";

if ($error) {
    echo "❌ cURL Error: " . $error . "\n";
}

if ($httpCode === 200) {
    echo "✅ SSE connection successful\n";
} else {
    echo "❌ SSE connection failed (HTTP {$httpCode})\n";
}

echo "\n🏁 Test completed!\n";
echo "\n💡 What to look for:\n";
echo "1. 'event: connected' - SSE connection established\n";
echo "2. 'event: notification' - Real notifications received\n";
echo "3. 'event: heartbeat' - Keep-alive messages\n";
echo "4. Any error messages\n";
?>
