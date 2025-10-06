<?php
/**
 * Check Backend Status
 */

header('Content-Type: text/plain');
echo "=== BACKEND STATUS CHECK ===\n\n";

// Test 1: Check if CodeIgniter loads
echo "1. Testing CodeIgniter loading...\n";
try {
    require_once('index.php');
    echo "✅ CodeIgniter loaded successfully\n\n";
} catch (Exception $e) {
    echo "❌ CodeIgniter failed to load: " . $e->getMessage() . "\n\n";
    exit;
}

// Test 2: Check database connection
echo "2. Testing database connection...\n";
try {
    $CI =& get_instance();
    $CI->load->database();
    echo "✅ Database connected successfully\n\n";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n\n";
}

// Test 3: Check if API routes work
echo "3. Testing API routes...\n";
$base_url = 'https://scms-backend.up.railway.app';

$endpoints = [
    '/api/auth/debug-email',
    '/api/notifications/debug/STU001',
    '/simple_email_test.php'
];

foreach ($endpoints as $endpoint) {
    $url = $base_url . $endpoint;
    echo "Testing: {$url}\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'method' => 'GET'
        ]
    ]);
    
    $result = @file_get_contents($url, false, $context);
    
    if ($result !== false) {
        echo "✅ Endpoint accessible\n";
    } else {
        echo "❌ Endpoint not accessible\n";
    }
    echo "\n";
}

echo "=== STATUS CHECK COMPLETE ===\n";
?>
