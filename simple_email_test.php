<?php
/**
 * Simple Email Test - Quick diagnosis
 */

// Load CodeIgniter
require_once('index.php');

header('Content-Type: text/plain');
echo "=== SIMPLE EMAIL TEST ===\n\n";

$CI =& get_instance();
$CI->load->library('email');
$CI->config->load('email');

// Test 1: Check environment variables
echo "1. Environment Variables:\n";
echo "SMTP_HOST: " . (getenv('SMTP_HOST') ?: 'NOT SET') . "\n";
echo "SMTP_PORT: " . (getenv('SMTP_PORT') ?: 'NOT SET') . "\n";
echo "SMTP_USER: " . (getenv('SMTP_USER') ?: 'NOT SET') . "\n";
echo "SMTP_PASS: " . (getenv('SMTP_PASS') ? 'SET' : 'NOT SET') . "\n";
echo "SMTP_CRYPTO: " . (getenv('SMTP_CRYPTO') ?: 'NOT SET') . "\n\n";

// Test 2: Check config values
echo "2. Email Configuration:\n";
echo "Protocol: " . $CI->config->item('protocol') . "\n";
echo "SMTP Host: " . $CI->config->item('smtp_host') . "\n";
echo "SMTP Port: " . $CI->config->item('smtp_port') . "\n";
echo "SMTP User: " . $CI->config->item('smtp_user') . "\n";
echo "SMTP Crypto: " . $CI->config->item('smtp_crypto') . "\n\n";

// Test 3: Try to send a simple email
echo "3. Testing Email Send:\n";
try {
    $CI->email->clear();
    $CI->email->from($CI->config->item('smtp_user'), 'SCMS Test');
    $CI->email->to('geferreras@gmail.com');
    $CI->email->subject('Simple Test - ' . date('H:i:s'));
    $CI->email->message('This is a simple test email.');
    
    $result = $CI->email->send();
    
    if ($result) {
        echo "✅ SUCCESS: Email sent!\n";
    } else {
        echo "❌ FAILED: Email not sent\n";
        echo "Error details:\n";
        echo $CI->email->print_debugger() . "\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
