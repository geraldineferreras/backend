<?php
// Simple Gmail SMTP Test
header('Content-Type: text/plain');

echo "=== Gmail SMTP Test ===\n\n";

// Test environment variables
$smtp_host = getenv('SMTP_HOST') ? getenv('SMTP_HOST') : 'smtp.gmail.com';
$smtp_port = getenv('SMTP_PORT') ? getenv('SMTP_PORT') : 587; // Try port 587 instead of 465
$smtp_user = getenv('SMTP_USER') ? getenv('SMTP_USER') : 'scmswebsitee@gmail.com';
$smtp_pass = getenv('SMTP_PASS') ? getenv('SMTP_PASS') : 'zhrk blgg sukj wbbs';

echo "SMTP Config:\n";
echo "Host: {$smtp_host}\n";
echo "Port: {$smtp_port}\n";
echo "User: {$smtp_user}\n\n";

// Test 1: Try port 587 (TLS) instead of 465 (SSL)
echo "Testing connection to {$smtp_host}:{$smtp_port}...\n";

$socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
if ($socket) {
    echo "✅ Connection successful to port {$smtp_port}\n";
    fclose($socket);
} else {
    echo "❌ Connection failed to port {$smtp_port}: {$errstr} ({$errno})\n";
}

// Test 2: Try port 465 (SSL)
echo "\nTesting connection to {$smtp_host}:465 (SSL)...\n";
$socket = fsockopen('ssl://' . $smtp_host, 465, $errno, $errstr, 10);
if ($socket) {
    echo "✅ SSL connection successful to port 465\n";
    fclose($socket);
} else {
    echo "❌ SSL connection failed to port 465: {$errstr} ({$errno})\n";
}

// Test 3: Try port 25
echo "\nTesting connection to {$smtp_host}:25...\n";
$socket = fsockopen($smtp_host, 25, $errno, $errstr, 10);
if ($socket) {
    echo "✅ Connection successful to port 25\n";
    fclose($socket);
} else {
    echo "❌ Connection failed to port 25: {$errstr} ({$errno})\n";
}

echo "\n=== Test Complete ===\n";
echo "If all connections fail, Railway is blocking SMTP on your plan.\n";
echo "You'll need to either:\n";
echo "1. Upgrade Railway to Pro/Enterprise plan\n";
echo "2. Use an email service like SendGrid/Mailgun\n";
echo "3. Use Gmail API instead of SMTP\n";
?>
