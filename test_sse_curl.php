<?php
/**
 * SSE Test Script using cURL
 * Run this from command line: php test_sse_curl.php
 */

echo "🔔 SSE Notification Stream Test (cURL)\n";
echo "=====================================\n\n";

// Configuration
$base_url = 'http://localhost/scms_new_backup/index.php';
$jwt_token = ''; // Add your JWT token here

if (empty($jwt_token)) {
    echo "❌ Please add your JWT token to the script first!\n";
    echo "Edit the file and set \$jwt_token = 'your_token_here';\n\n";
    exit;
}

echo "📡 Testing SSE endpoint: {$base_url}/api/notifications/stream\n";
echo "🔑 Using JWT token: " . substr($jwt_token, 0, 20) . "...\n\n";

// Initialize cURL
$ch = curl_init();

// Set cURL options for SSE
curl_setopt_array($ch, [
    CURLOPT_URL => $base_url . '/api/notifications/stream',
    CURLOPT_RETURNTRANSFER => false, // Don't return, stream directly
    CURLOPT_HEADER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
        'Accept: text/event-stream',
        'Authorization: Bearer ' . $jwt_token,
        'Cache-Control: no-cache',
        'Connection: keep-alive'
    ],
    CURLOPT_TIMEOUT => 30, // 30 second timeout
    CURLOPT_WRITEFUNCTION => function($ch, $data) {
        echo $data;
        return strlen($data);
    }
]);

echo "🔄 Connecting to SSE stream...\n";
echo "⏱️  Will timeout after 30 seconds\n\n";

// Execute cURL request
$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo "\n❌ cURL Error: " . curl_error($ch) . "\n";
} else {
    echo "\n✅ SSE stream completed or timed out\n";
}

curl_close($ch);

echo "\n🏁 Test completed!\n";
?>
