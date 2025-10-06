<?php
// Debug forgot password functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Forgot Password Debug ===\n\n";

// Load CodeIgniter
require_once('index.php');

$CI =& get_instance();
$CI->load->database();
$CI->load->config('email');

echo "✅ CodeIgniter loaded\n";
echo "✅ Database loaded\n";
echo "✅ Email config loaded\n\n";

// Test 1: Check if user exists
$email = 'geferreras@gmail.com';
echo "Testing email: {$email}\n";

$user = $CI->User_model->get_by_email($email);
if ($user) {
    echo "✅ User found: " . $user['full_name'] . "\n";
} else {
    echo "❌ User not found\n";
    exit;
}

// Test 2: Check email configuration
echo "\n--- Email Configuration ---\n";
$smtp_host = $CI->config->item('smtp_host');
$smtp_port = $CI->config->item('smtp_port');
$smtp_user = $CI->config->item('smtp_user');
$smtp_pass = $CI->config->item('smtp_pass');
$smtp_crypto = $CI->config->item('smtp_crypto');

echo "SMTP Host: {$smtp_host}\n";
echo "SMTP Port: {$smtp_port}\n";
echo "SMTP User: {$smtp_user}\n";
echo "SMTP Pass: " . (strlen($smtp_pass) > 0 ? str_repeat('*', strlen($smtp_pass)) : 'not set') . "\n";
echo "SMTP Crypto: {$smtp_crypto}\n\n";

// Test 3: Check if PHPMailer is available
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✅ PHPMailer is available\n\n";
} else {
    echo "❌ PHPMailer is not available\n\n";
}

// Test 4: Test email sending directly
echo "--- Testing Email Sending ---\n";

try {
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "Using PHPMailer...\n";
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = $smtp_crypto;
        $mail->Port = $smtp_port;
        $mail->Timeout = 30;
        
        $mail->setFrom($smtp_user, 'SCMS System');
        $mail->addAddress($email, $user['full_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Debug Test - ' . date('H:i:s');
        
        $mail->Body = '<h1>Debug Test</h1><p>This is a debug test email.</p>';
        $mail->AltBody = 'Debug Test - This is a debug test email.';
        
        echo "Sending test email...\n";
        $result = $mail->send();
        
        if ($result) {
            echo "✅ Email sent successfully using PHPMailer!\n";
        } else {
            echo "❌ Email failed to send using PHPMailer\n";
        }
        
    } else {
        echo "PHPMailer not available, testing CodeIgniter email...\n";
        
        $CI->load->library('email');
        
        $CI->email->from('noreply@scms.com', 'SCMS System');
        $CI->email->to($email);
        $CI->email->subject('Debug Test - ' . date('H:i:s'));
        $CI->email->message('<h1>Debug Test</h1><p>This is a debug test email.</p>');
        
        echo "Sending test email...\n";
        $result = $CI->email->send();
        
        if ($result) {
            echo "✅ Email sent successfully using CodeIgniter Email!\n";
        } else {
            echo "❌ Email failed to send using CodeIgniter Email\n";
            echo "Debug info: " . $CI->email->print_debugger() . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
