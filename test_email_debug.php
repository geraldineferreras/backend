<?php
// Email Debug Test
header('Content-Type: text/plain');

echo "=== Email Configuration Debug ===\n\n";

// Test environment variables
$env_vars = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS', 'SMTP_CRYPTO'];
echo "Environment Variables:\n";
foreach ($env_vars as $var) {
    $value = getenv($var);
    echo sprintf("%-12s = %s\n", $var, $value ? $value : '(not set)');
}

echo "\n=== Direct SMTP Test ===\n";

// Test SMTP connection directly
$smtp_host = getenv('SMTP_HOST') ? getenv('SMTP_HOST') : 'smtp.gmail.com';
$smtp_port = getenv('SMTP_PORT') ? getenv('SMTP_PORT') : 465;
$smtp_user = getenv('SMTP_USER') ? getenv('SMTP_USER') : 'scmswebsitee@gmail.com';
$smtp_pass = getenv('SMTP_PASS') ? getenv('SMTP_PASS') : 'zhrk blgg sukj wbbs';

echo "Testing SMTP connection to {$smtp_host}:{$smtp_port}\n";
echo "Username: {$smtp_user}\n";

// Test 1: Check if we can connect to SMTP server
$socket = fsockopen('ssl://' . $smtp_host, $smtp_port, $errno, $errstr, 10);
if ($socket) {
    echo "✅ SMTP connection successful\n";
    fclose($socket);
} else {
    echo "❌ SMTP connection failed: {$errstr} ({$errno})\n";
}

echo "\n=== Test Email Send (Simple) ===\n";

// Test with basic mail() function
$to = 'grldnferreras@gmail.com';
$subject = 'SCMS Email Test - ' . date('Y-m-d H:i:s');
$message = 'This is a test email from SCMS. If you receive this, email is working!';
$headers = "From: {$smtp_user}\r\n";
$headers .= "Reply-To: {$smtp_user}\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "✅ Email sent successfully via mail() function!\n";
} else {
    echo "❌ Email failed to send via mail() function\n";
}

echo "\n=== PHP Mail Configuration ===\n";
echo "sendmail_path: " . ini_get('sendmail_path') . "\n";
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "smtp_port: " . ini_get('smtp_port') . "\n";
echo "sendmail_from: " . ini_get('sendmail_from') . "\n";
?>
