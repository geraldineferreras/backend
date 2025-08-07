<?php
// Simple email test script
// This tests the email configuration without CodeIgniter

echo "=== Simple Email Test ===\n";

// Test Gmail SMTP connection
$smtp_host = 'smtp.gmail.com';
$smtp_port = 465;
$smtp_user = 'grldnferreras@gmail.com';
$smtp_pass = 'ucek fffw ccfe siny';

echo "Testing SMTP connection to {$smtp_host}:{$smtp_port}\n";
echo "Username: {$smtp_user}\n\n";

// Test 1: Check if we can connect to SMTP server
$socket = fsockopen('ssl://' . $smtp_host, $smtp_port, $errno, $errstr, 10);
if ($socket) {
    echo "✅ SMTP connection successful\n";
    fclose($socket);
} else {
    echo "❌ SMTP connection failed: {$errstr} ({$errno})\n";
}

// Test 2: Test with PHPMailer if available
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "\nTesting with PHPMailer...\n";
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = $smtp_port;
        
        $mail->setFrom($smtp_user, 'SCMS System');
        $mail->addAddress('test@example.com', 'Test User');
        $mail->Subject = 'Test Email from SCMS';
        $mail->Body = '<h1>Test Email</h1><p>This is a test email from the SCMS system.</p>';
        $mail->isHTML(true);
        
        $mail->send();
        echo "✅ PHPMailer test email sent successfully\n";
    } catch (Exception $e) {
        echo "❌ PHPMailer test failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "\nPHPMailer not available, testing with basic mail() function...\n";
    
    $to = 'test@example.com';
    $subject = 'Test Email from SCMS';
    $message = 'This is a test email from the SCMS system.';
    $headers = 'From: ' . $smtp_user . "\r\n" .
               'Reply-To: ' . $smtp_user . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    if (mail($to, $subject, $message, $headers)) {
        echo "✅ Basic mail() function test successful\n";
    } else {
        echo "❌ Basic mail() function test failed\n";
    }
}

echo "\n=== Email test completed ===\n";
?>
