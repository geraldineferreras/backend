<?php
/**
 * Simple SSE Test Script
 * Run this from command line: php test_sse_simple_php.php
 */

echo "🔔 Simple SSE Test\n";
echo "==================\n\n";

// Configuration
$base_url = 'http://localhost/scms_new_backup/index.php';
$jwt_token = ''; // Add your JWT token here

if (empty($jwt_token)) {
    echo "❌ Please add your JWT token to the script first!\n";
    echo "Edit the file and set \$jwt_token = 'your_token_here';\n\n";
    exit;
}

echo "📡 Testing: {$base_url}/api/notifications/stream\n";
echo "🔑 Token: " . substr($jwt_token, 0, 20) . "...\n\n";

// Create stream context
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Accept: text/event-stream',
            'Authorization: Bearer ' . $jwt_token,
            'Cache-Control: no-cache',
            'Connection: keep-alive'
        ],
        'timeout' => 30
    ]
]);

echo "🔄 Connecting...\n\n";

// Open the stream
$stream = fopen($base_url . '/api/notifications/stream', 'r', false, $context);

if (!$stream) {
    echo "❌ Failed to open stream\n";
    exit;
}

// Read the stream
$start_time = time();
$message_count = 0;

while (!feof($stream) && (time() - $start_time) < 30) {
    $line = fgets($stream);
    
    if ($line !== false) {
        echo trim($line) . "\n";
        
        if (strpos($line, 'data: ') === 0) {
            $message_count++;
        }
    }
    
    // Small delay to prevent CPU overuse
    usleep(100000); // 0.1 seconds
}

fclose($stream);

echo "\n✅ Stream completed!\n";
echo "📊 Total messages received: {$message_count}\n";
echo "⏱️  Time elapsed: " . (time() - $start_time) . " seconds\n";
?>
