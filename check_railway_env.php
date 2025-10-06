<?php
/**
 * Check Railway Environment Variables
 */

header('Content-Type: text/plain');
echo "=== RAILWAY ENVIRONMENT CHECK ===\n\n";

// Check Railway-specific environment variables
$railway_vars = [
    'RAILWAY_ENVIRONMENT' => getenv('RAILWAY_ENVIRONMENT'),
    'RAILWAY_PUBLIC_DOMAIN' => getenv('RAILWAY_PUBLIC_DOMAIN'),
    'RAILWAY_PRIVATE_DOMAIN' => getenv('RAILWAY_PRIVATE_DOMAIN'),
    'RAILWAY_TCP_PROXY_DOMAIN' => getenv('RAILWAY_TCP_PROXY_DOMAIN'),
    'RAILWAY_TCP_PROXY_PORT' => getenv('RAILWAY_TCP_PROXY_PORT')
];

echo "1. Railway Environment Variables:\n";
foreach ($railway_vars as $key => $value) {
    echo "{$key}: " . ($value ?: 'NOT SET') . "\n";
}
echo "\n";

// Check SMTP environment variables
$smtp_vars = [
    'SMTP_HOST' => getenv('SMTP_HOST'),
    'SMTP_PORT' => getenv('SMTP_PORT'),
    'SMTP_USER' => getenv('SMTP_USER'),
    'SMTP_PASS' => getenv('SMTP_PASS'),
    'SMTP_CRYPTO' => getenv('SMTP_CRYPTO'),
    'SMTP_FROM_NAME' => getenv('SMTP_FROM_NAME')
];

echo "2. SMTP Environment Variables:\n";
foreach ($smtp_vars as $key => $value) {
    if ($key === 'SMTP_PASS') {
        echo "{$key}: " . ($value ? 'SET (' . strlen($value) . ' chars)' : 'NOT SET') . "\n";
    } else {
        echo "{$key}: " . ($value ?: 'NOT SET') . "\n";
    }
}
echo "\n";

// Check database environment variables
$db_vars = [
    'DB_HOST' => getenv('DB_HOST'),
    'DB_PORT' => getenv('DB_PORT'),
    'DB_NAME' => getenv('DB_NAME'),
    'DB_USER' => getenv('DB_USER'),
    'DB_PASS' => getenv('DB_PASS')
];

echo "3. Database Environment Variables:\n";
foreach ($db_vars as $key => $value) {
    if ($key === 'DB_PASS') {
        echo "{$key}: " . ($value ? 'SET (' . strlen($value) . ' chars)' : 'NOT SET') . "\n";
    } else {
        echo "{$key}: " . ($value ?: 'NOT SET') . "\n";
    }
}
echo "\n";

// Recommendations
echo "4. Recommendations:\n";
$missing_smtp = array_filter($smtp_vars, function($value) { return empty($value); });
if (!empty($missing_smtp)) {
    echo "❌ Missing SMTP environment variables. Set these in Railway:\n";
    foreach ($missing_smtp as $key => $value) {
        echo "   - {$key}\n";
    }
} else {
    echo "✅ All SMTP environment variables are set\n";
}

echo "\n=== ENVIRONMENT CHECK COMPLETE ===\n";
?>
