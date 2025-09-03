<?php
// Simple SMTP Test
header('Content-Type: text/plain');

echo "=== SMTP Email Test ===\n\n";

// Load CodeIgniter
require_once('index.php');

$CI =& get_instance();
$CI->load->library('email');

echo "✅ CodeIgniter loaded\n";
echo "✅ Email library loaded\n\n";

// Test environment variables
$smtp_host = getenv('SMTP_HOST') ? getenv('SMTP_HOST') : 'smtp.gmail.com';
$smtp_port = getenv('SMTP_PORT') ? getenv('SMTP_PORT') : 465;
$smtp_user = getenv('SMTP_USER') ? getenv('SMTP_USER') : 'scmswebsitee@gmail.com';
$smtp_pass = getenv('SMTP_PASS') ? getenv('SMTP_PASS') : 'zhrk blgg sukj wbbs';
$smtp_crypto = getenv('SMTP_CRYPTO') ? getenv('SMTP_CRYPTO') : 'ssl';

echo "SMTP Configuration:\n";
echo "Host: {$smtp_host}\n";
echo "Port: {$smtp_port}\n";
echo "User: {$smtp_user}\n";
echo "Crypto: {$smtp_crypto}\n\n";

// Configure email
$CI->email->from($smtp_user, 'SCMS System');
$CI->email->to('grldnferreras@gmail.com');
$CI->email->subject('SCMS SMTP Test - ' . date('H:i:s'));
$CI->email->message('This is a test email from SCMS System using SMTP. If you receive this, SMTP is working correctly!');

echo "✅ Email configured\n";
echo "Sending email...\n";

// Send email
$result = $CI->email->send();

if ($result) {
    echo "✅ Email sent successfully via SMTP!\n";
} else {
    echo "❌ Email failed to send via SMTP\n";
    echo "Error: " . $CI->email->print_debugger() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
