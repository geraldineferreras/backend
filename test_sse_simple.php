<?php
// Simple test to check SSE controller loading
echo "Testing SSE Controller...\n";

// Check if CodeIgniter is accessible
if (file_exists('index.php')) {
    echo "✓ CodeIgniter index.php found\n";
} else {
    echo "✗ CodeIgniter index.php not found\n";
}

// Check if SSE controller exists
if (file_exists('application/controllers/api/NotificationStreamController.php')) {
    echo "✓ SSE Controller file exists\n";
} else {
    echo "✗ SSE Controller file not found\n";
}

// Check if BaseController exists
if (file_exists('application/controllers/api/BaseController.php')) {
    echo "✓ BaseController file exists\n";
} else {
    echo "✗ BaseController file not found\n";
}

// Test direct access to SSE endpoint
echo "\nTesting direct SSE endpoint access...\n";
$url = 'http://localhost/scms_new_backup/api/notifications/stream';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Accept: text/event-stream\r\n" .
                   "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2...\r\n"
    ]
]);

$response = file_get_contents($url, false, $context);
if ($response === false) {
    echo "✗ Failed to access SSE endpoint\n";
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
} else {
    echo "✓ SSE endpoint accessible\n";
    echo "Response length: " . strlen($response) . " bytes\n";
    echo "First 200 chars: " . substr($response, 0, 200) . "\n";
}

echo "\nTest completed.\n";
?>
