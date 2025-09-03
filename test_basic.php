<?php
// Basic test to verify Railway deployment
header('Content-Type: text/plain');

echo "=== Basic Railway Test ===\n\n";

echo "✅ PHP is working\n";
echo "✅ File is accessible\n";
echo "✅ Current time: " . date('Y-m-d H:i:s') . "\n";

// Test environment variables
echo "\n=== Environment Variables ===\n";
$env_vars = [
    'SMTP_HOST',
    'SMTP_PORT', 
    'SMTP_USER',
    'SMTP_PASS',
    'SMTP_CRYPTO',
    'DB_HOST',
    'DB_NAME',
    'BASE_URL'
];

foreach ($env_vars as $var) {
    $value = getenv($var);
    if ($value) {
        echo "✅ {$var}: {$value}\n";
    } else {
        echo "❌ {$var}: (not set)\n";
    }
}

echo "\n=== Test Complete ===\n";
?>
