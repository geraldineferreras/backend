<?php
// Simple PHPMailer Test
header('Content-Type: text/plain');

echo "=== Simple PHPMailer Test ===\n\n";

// Check if PHPMailer is available
if (!file_exists('vendor/autoload.php')) {
    echo "❌ PHPMailer not found. Composer autoload missing.\n";
    exit;
}

echo "✅ PHPMailer autoload found\n";

// Load PHPMailer
require_once('vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

echo "✅ PHPMailer classes loaded\n";

// Get SMTP configuration
$smtp_host = getenv('SMTP_HOST') ? getenv('SMTP_HOST') : 'smtp.gmail.com';
$smtp_port = getenv('SMTP_PORT') ? getenv('SMTP_PORT') : 465;
$smtp_user = getenv('SMTP_USER') ? getenv('SMTP_USER') : 'scmswebsitee@gmail.com';
$smtp_pass = getenv('SMTP_PASS') ? getenv('SMTP_PASS') : 'zhrk blgg sukj wbbs';
$smtp_crypto = getenv('SMTP_CRYPTO') ? getenv('SMTP_CRYPTO') : 'ssl';

echo "SMTP Config:\n";
echo "Host: {$smtp_host}\n";
echo "Port: {$smtp_port}\n";
echo "User: {$smtp_user}\n";
echo "Crypto: {$smtp_crypto}\n\n";

$mail = new PHPMailer(true);

try {
    echo "Creating PHPMailer instance...\n";
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = $smtp_host;
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    $mail->SMTPSecure = $smtp_crypto === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtp_port;
    
    // Disable debug for cleaner output
    $mail->SMTPDebug = 0;
    
    echo "✅ SMTP settings configured\n";
    
    // Recipients
    $mail->setFrom($smtp_user, 'SCMS System');
    $mail->addAddress('grldnferreras@gmail.com');
    
    echo "✅ Recipients set\n";
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'SCMS Test - ' . date('H:i:s');
    $mail->Body = '<h1>Test Email</h1><p>This is a test from SCMS using PHPMailer.</p>';
    $mail->AltBody = 'This is a test from SCMS using PHPMailer.';
    
    echo "✅ Content set\n";
    echo "Attempting to send email...\n";
    
    // Send email
    $result = $mail->send();
    
    if ($result) {
        echo "✅ Email sent successfully!\n";
    } else {
        echo "❌ Email failed to send\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Error Info: " . $mail->ErrorInfo . "\n";
}

echo "\n=== Test Complete ===\n";
?>
