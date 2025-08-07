<?php
/**
 * Test Gmail Email Configuration
 * 
 * This script tests the Gmail SMTP configuration for the SCMS system.
 * Run this script to verify that email notifications are working correctly.
 */

// Load CodeIgniter
require_once 'index.php';

// Get CI instance
$CI =& get_instance();

// Load email library
$CI->load->library('email');

// Test email configuration
$to_email = 'test@example.com'; // Replace with your test email
$subject = 'SCMS Email Test - ' . date('Y-m-d H:i:s');
$message = '
<html>
<head>
    <title>SCMS Email Test</title>
</head>
<body>
    <h2>SCMS Email Test</h2>
    <p>This is a test email from the SCMS System.</p>
    <p>If you receive this email, the Gmail SMTP configuration is working correctly.</p>
    <p><strong>Test Date:</strong> ' . date('Y-m-d H:i:s') . '</p>
    <p><strong>System:</strong> SCMS Notification System</p>
    <hr>
    <p style="font-size: 12px; color: #666;">
        This is an automated test email. Please do not reply to this message.
    </p>
</body>
</html>';

// Configure email
$CI->email->from('grldnferreras@gmail.com', 'SCMS System');
$CI->email->to($to_email);
$CI->email->subject($subject);
$CI->email->message($message);
$CI->email->set_mailtype('html');

// Send email
$result = $CI->email->send();

// Display result
echo "<h2>SCMS Email Test Results</h2>";
echo "<p><strong>To:</strong> $to_email</p>";
echo "<p><strong>Subject:</strong> $subject</p>";
echo "<p><strong>Result:</strong> " . ($result ? 'SUCCESS' : 'FAILED') . "</p>";

if ($result) {
    echo "<p style='color: green;'>✅ Email sent successfully! Gmail SMTP configuration is working.</p>";
} else {
    echo "<p style='color: red;'>❌ Email failed to send. Please check your configuration.</p>";
    echo "<p><strong>Error:</strong> " . $CI->email->print_debugger() . "</p>";
}

echo "<hr>";
echo "<p><strong>Configuration Details:</strong></p>";
echo "<ul>";
echo "<li>SMTP Host: smtp.gmail.com</li>";
echo "<li>SMTP Port: 465</li>";
echo "<li>SMTP User: grldnferreras@gmail.com</li>";
echo "<li>Encryption: SSL</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> Make sure to replace 'test@example.com' with your actual test email address.</p>";
?> 