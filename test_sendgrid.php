<?php
// SendGrid Email Test
header('Content-Type: text/plain');

echo "=== SendGrid Email Test ===\n\n";

// Check if SendGrid is available
if (!file_exists('vendor/autoload.php')) {
    echo "❌ Composer autoload not found.\n";
    exit;
}

echo "✅ Composer autoload found\n";

// Load SendGrid
require_once('vendor/autoload.php');

use SendGrid\Mail\Mail;
use SendGrid;

echo "✅ SendGrid classes loaded\n";

// Check environment variables
$sendgrid_api_key = getenv('SENDGRID_API_KEY');
$sender_email = getenv('SENDGRID_FROM_EMAIL') ? getenv('SENDGRID_FROM_EMAIL') : 'scmswebsitee@gmail.com';
$sender_name = getenv('SENDGRID_FROM_NAME') ? getenv('SENDGRID_FROM_NAME') : 'SCMS System';

echo "Environment Variables:\n";
echo "SENDGRID_API_KEY: " . ($sendgrid_api_key ? '[SET]' : '[NOT SET]') . "\n";
echo "SENDGRID_FROM_EMAIL: " . $sender_email . "\n";
echo "SENDGRID_FROM_NAME: " . $sender_name . "\n\n";

if (!$sendgrid_api_key) {
    echo "❌ SendGrid API key not found. Please set SENDGRID_API_KEY environment variable.\n";
    echo "\nTo get a SendGrid API key:\n";
    echo "1. Go to https://sendgrid.com\n";
    echo "2. Sign up for a free account\n";
    echo "3. Go to Settings > API Keys\n";
    echo "4. Create a new API key with 'Mail Send' permissions\n";
    echo "5. Add it to your Railway environment variables\n";
    exit;
}

echo "✅ SendGrid API key found\n";

try {
    echo "Creating SendGrid email...\n";
    
    $email = new Mail();
    $email->setFrom($sender_email, $sender_name);
    $email->setSubject('SCMS Test - ' . date('H:i:s'));
    $email->addTo('grldnferreras@gmail.com');
    $email->addContent("text/html", '<h1>SCMS Email Test</h1><p>This is a test email from SCMS System using SendGrid. If you receive this, email is working correctly!</p>');
    $email->addContent("text/plain", 'This is a test email from SCMS System using SendGrid. If you receive this, email is working correctly!');
    
    echo "✅ Email object created\n";
    echo "Sending email...\n";
    
    $sendgrid = new SendGrid($sendgrid_api_key);
    $response = $sendgrid->send($email);
    
    $status_code = $response->statusCode();
    $response_body = $response->body();
    
    echo "Response Status Code: " . $status_code . "\n";
    echo "Response Body: " . $response_body . "\n";
    
    if ($status_code >= 200 && $status_code < 300) {
        echo "✅ Email sent successfully via SendGrid!\n";
    } else {
        echo "❌ Email failed to send via SendGrid\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
