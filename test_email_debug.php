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

echo "\n=== CodeIgniter Email Config ===\n";

// Load CodeIgniter
require_once('index.php');

$CI =& get_instance();
$CI->load->library('email');

// Show current email config
echo "SMTP Host: " . $CI->config->item('smtp_host') . "\n";
echo "SMTP Port: " . $CI->config->item('smtp_port') . "\n";
echo "SMTP User: " . $CI->config->item('smtp_user') . "\n";
echo "SMTP Pass: " . (strlen($CI->config->item('smtp_pass')) > 0 ? '[SET]' : '[NOT SET]') . "\n";
echo "SMTP Crypto: " . $CI->config->item('smtp_crypto') . "\n";

echo "\n=== Test Email Send ===\n";

// Test email sending
$CI->email->from($CI->config->item('smtp_user'), 'SCMS Test');
$CI->email->to('grldnferreras@gmail.com'); // Send to yourself for testing
$CI->email->subject('SCMS Email Test - ' . date('Y-m-d H:i:s'));
$CI->email->message('This is a test email from SCMS. If you receive this, email is working!');

$result = $CI->email->send();

if ($result) {
    echo "✅ Email sent successfully!\n";
} else {
    echo "❌ Email failed to send\n";
    echo "Error: " . $CI->email->print_debugger() . "\n";
}

echo "\n=== Email Debug Info ===\n";
echo $CI->email->print_debugger();
?>
