<?php
// Email Debug Test with PHPMailer
header('Content-Type: text/plain');

echo "=== Email Configuration Debug (PHPMailer) ===\n\n";

// Test environment variables
$env_vars = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USER', 'SMTP_PASS', 'SMTP_CRYPTO'];
echo "Environment Variables:\n";
foreach ($env_vars as $var) {
    $value = getenv($var);
    echo sprintf("%-12s = %s\n", $var, $value ? $value : '(not set)');
}

echo "\n=== PHPMailer Test ===\n";

// Load PHPMailer
require_once('vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Get SMTP configuration
$smtp_host = getenv('SMTP_HOST') ? getenv('SMTP_HOST') : 'smtp.gmail.com';
$smtp_port = getenv('SMTP_PORT') ? getenv('SMTP_PORT') : 465;
$smtp_user = getenv('SMTP_USER') ? getenv('SMTP_USER') : 'scmswebsitee@gmail.com';
$smtp_pass = getenv('SMTP_PASS') ? getenv('SMTP_PASS') : 'zhrk blgg sukj wbbs';
$smtp_crypto = getenv('SMTP_CRYPTO') ? getenv('SMTP_CRYPTO') : 'ssl';

echo "Testing PHPMailer with:\n";
echo "Host: {$smtp_host}\n";
echo "Port: {$smtp_port}\n";
echo "User: {$smtp_user}\n";
echo "Crypto: {$smtp_crypto}\n\n";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    $mail->SMTPSecure = $smtp_crypto === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtp_port;
    
    // Enable verbose debug output
    $mail->SMTPDebug = 2;
    
    // Recipients
    $mail->setFrom($smtp_user, 'SCMS System');
    $mail->addAddress('grldnferreras@gmail.com');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'SCMS Email Test - ' . date('Y-m-d H:i:s');
    $mail->Body = '<h1>SCMS Email Test</h1><p>This is a test email from SCMS System using PHPMailer. If you receive this, email is working correctly!</p>';
    $mail->AltBody = 'This is a test email from SCMS System using PHPMailer. If you receive this, email is working correctly!';
    
    echo "Attempting to send email...\n";
    echo "SMTP Debug Output:\n";
    echo "==================\n";
    
    // Capture debug output
    ob_start();
    $result = $mail->send();
    $debug_output = ob_get_clean();
    
    echo $debug_output;
    echo "==================\n";
    
    if ($result) {
        echo "✅ Email sent successfully via PHPMailer!\n";
    } else {
        echo "❌ Email failed to send via PHPMailer\n";
    }
    
} catch (Exception $e) {
    echo "❌ PHPMailer Exception: " . $e->getMessage() . "\n";
    echo "Debug Info: " . $mail->ErrorInfo . "\n";
}

echo "\n=== PHP Configuration ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'Enabled' : 'Disabled') . "\n";
echo "cURL: " . (extension_loaded('curl') ? 'Enabled' : 'Disabled') . "\n";
echo "Socket: " . (extension_loaded('sockets') ? 'Enabled' : 'Disabled') . "\n";
?>
