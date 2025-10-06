<?php
/**
 * Railway Email Test Script
 * 
 * This script tests email functionality specifically for Railway deployment
 * Run this to diagnose email issues on Railway
 */

// Load CodeIgniter
require_once('index.php');

header('Content-Type: text/plain');
echo "=== Railway Email Test ===\n\n";

$CI =& get_instance();
$CI->load->library('email');
$CI->config->load('email');

echo "✅ CodeIgniter loaded\n";
echo "✅ Email library loaded\n";
echo "✅ Email configuration loaded\n\n";

// Test environment variables
echo "=== Environment Variables ===\n";
$env_vars = [
    'SMTP_HOST' => getenv('SMTP_HOST'),
    'SMTP_PORT' => getenv('SMTP_PORT'),
    'SMTP_USER' => getenv('SMTP_USER'),
    'SMTP_PASS' => getenv('SMTP_PASS'),
    'SMTP_CRYPTO' => getenv('SMTP_CRYPTO'),
    'SMTP_FROM_NAME' => getenv('SMTP_FROM_NAME'),
    'RAILWAY_ENVIRONMENT' => getenv('RAILWAY_ENVIRONMENT'),
    'RAILWAY_PUBLIC_DOMAIN' => getenv('RAILWAY_PUBLIC_DOMAIN')
];

foreach ($env_vars as $key => $value) {
    $display_value = ($key === 'SMTP_PASS' && $value) ? str_repeat('*', strlen($value)) : $value;
    echo "{$key}: " . ($display_value ?: 'NOT SET') . "\n";
}

echo "\n=== Email Configuration ===\n";
$config_items = [
    'protocol' => $CI->config->item('protocol'),
    'smtp_host' => $CI->config->item('smtp_host'),
    'smtp_port' => $CI->config->item('smtp_port'),
    'smtp_user' => $CI->config->item('smtp_user'),
    'smtp_pass' => $CI->config->item('smtp_pass') ? str_repeat('*', strlen($CI->config->item('smtp_pass'))) : 'NOT SET',
    'smtp_crypto' => $CI->config->item('smtp_crypto'),
    'smtp_timeout' => $CI->config->item('smtp_timeout')
];

foreach ($config_items as $key => $value) {
    echo "{$key}: {$value}\n";
}

echo "\n=== Testing Email Sending ===\n";

// Test email sending
$test_email = 'geferreras@gmail.com'; // Change this to your test email
$from_email = getenv('SMTP_USER') ?: 'scmswebsitee@gmail.com';
$from_name = getenv('SMTP_FROM_NAME') ?: 'SCMS System';

echo "Sending test email to: {$test_email}\n";
echo "From: {$from_name} <{$from_email}>\n\n";

// Clear any previous email data
$CI->email->clear();

// Configure email
$CI->email->from($from_email, $from_name);
$CI->email->to($test_email);
$CI->email->subject('Railway Email Test - ' . date('Y-m-d H:i:s'));
$CI->email->message('
    <h2>Railway Email Test</h2>
    <p>This is a test email from your SCMS system deployed on Railway.</p>
    <p><strong>Test Details:</strong></p>
    <ul>
        <li>Timestamp: ' . date('Y-m-d H:i:s') . '</li>
        <li>Environment: ' . (getenv('RAILWAY_ENVIRONMENT') ?: 'Unknown') . '</li>
        <li>Domain: ' . (getenv('RAILWAY_PUBLIC_DOMAIN') ?: 'Unknown') . '</li>
    </ul>
    <p>If you receive this email, your SMTP configuration is working correctly!</p>
');
$CI->email->set_mailtype('html');

echo "✅ Email configured\n";
echo "Sending email...\n";

// Send email
$result = $CI->email->send();

if ($result) {
    echo "✅ Email sent successfully!\n";
    echo "Check your inbox for the test email.\n";
} else {
    echo "❌ Email failed to send\n";
    echo "Error details:\n";
    echo $CI->email->print_debugger() . "\n";
    
    echo "\n=== Troubleshooting Tips ===\n";
    echo "1. Check if SMTP credentials are correct\n";
    echo "2. Verify Gmail app password is valid\n";
    echo "3. Check if Railway has outbound email restrictions\n";
    echo "4. Try using different SMTP settings\n";
}

echo "\n=== Network Connectivity Test ===\n";

// Test SMTP connectivity
$smtp_host = $CI->config->item('smtp_host');
$smtp_port = $CI->config->item('smtp_port');

echo "Testing connection to {$smtp_host}:{$smtp_port}...\n";

$connection = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
if ($connection) {
    echo "✅ SMTP server is reachable\n";
    fclose($connection);
} else {
    echo "❌ Cannot connect to SMTP server\n";
    echo "Error: {$errstr} ({$errno})\n";
}

echo "\n=== Test Complete ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
?>
