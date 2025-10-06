<?php
/**
 * Email Notification Debug Script
 * 
 * This script helps debug why notifications are saved to database
 * but emails are not being sent
 */

// Load CodeIgniter
require_once('index.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$CI =& get_instance();
$CI->load->database();
$CI->load->library('email');
$CI->config->load('email');

$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'status' => 'debugging',
    'tests' => []
];

// Test 1: Check if notifications exist in database
$response['tests']['database_notifications'] = [];
try {
    $CI->db->select('id, user_id, type, title, created_at, is_read');
    $CI->db->from('notifications');
    $CI->db->order_by('created_at', 'DESC');
    $CI->db->limit(10);
    $query = $CI->db->get();
    $notifications = $query->result_array();
    
    $response['tests']['database_notifications'] = [
        'status' => 'success',
        'count' => count($notifications),
        'notifications' => $notifications
    ];
} catch (Exception $e) {
    $response['tests']['database_notifications'] = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// Test 2: Check email configuration
$response['tests']['email_config'] = [
    'protocol' => $CI->config->item('protocol'),
    'smtp_host' => $CI->config->item('smtp_host'),
    'smtp_port' => $CI->config->item('smtp_port'),
    'smtp_user' => $CI->config->item('smtp_user'),
    'smtp_pass' => $CI->config->item('smtp_pass') ? 'SET' : 'NOT SET',
    'smtp_crypto' => $CI->config->item('smtp_crypto'),
    'smtp_timeout' => $CI->config->item('smtp_timeout')
];

// Test 3: Check environment variables
$response['tests']['environment_variables'] = [
    'SMTP_HOST' => getenv('SMTP_HOST') ?: 'NOT SET',
    'SMTP_PORT' => getenv('SMTP_PORT') ?: 'NOT SET',
    'SMTP_USER' => getenv('SMTP_USER') ?: 'NOT SET',
    'SMTP_PASS' => getenv('SMTP_PASS') ? 'SET' : 'NOT SET',
    'SMTP_CRYPTO' => getenv('SMTP_CRYPTO') ?: 'NOT SET',
    'RAILWAY_ENVIRONMENT' => getenv('RAILWAY_ENVIRONMENT') ?: 'NOT SET'
];

// Test 4: Test SMTP connectivity
$response['tests']['smtp_connectivity'] = [];
try {
    $smtp_host = $CI->config->item('smtp_host');
    $smtp_port = $CI->config->item('smtp_port');
    
    $connection = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
    if ($connection) {
        $response['tests']['smtp_connectivity'] = [
            'status' => 'success',
            'message' => "Connected to {$smtp_host}:{$smtp_port}"
        ];
        fclose($connection);
    } else {
        $response['tests']['smtp_connectivity'] = [
            'status' => 'error',
            'error' => "Cannot connect to {$smtp_host}:{$smtp_port} - {$errstr} ({$errno})"
        ];
    }
} catch (Exception $e) {
    $response['tests']['smtp_connectivity'] = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// Test 5: Test email sending directly
$response['tests']['direct_email_test'] = [];
try {
    $test_email = 'geferreras@gmail.com'; // Change this to your email
    
    // Clear any previous email data
    $CI->email->clear();
    
    // Configure email
    $from_email = getenv('SMTP_USER') ?: 'scmswebsitee@gmail.com';
    $from_name = getenv('SMTP_FROM_NAME') ?: 'SCMS Debug';
    
    $CI->email->from($from_email, $from_name);
    $CI->email->to($test_email);
    $CI->email->subject('SCMS Email Debug Test - ' . date('H:i:s'));
    $CI->email->message('This is a debug test email from SCMS system.');
    
    $result = $CI->email->send();
    
    if ($result) {
        $response['tests']['direct_email_test'] = [
            'status' => 'success',
            'message' => 'Email sent successfully',
            'to' => $test_email
        ];
    } else {
        $response['tests']['direct_email_test'] = [
            'status' => 'error',
            'error' => 'Email failed to send',
            'debug_info' => $CI->email->print_debugger()
        ];
    }
} catch (Exception $e) {
    $response['tests']['direct_email_test'] = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// Test 6: Test notification helper function
$response['tests']['notification_helper_test'] = [];
try {
    // Load the helper
    if (!function_exists('send_email_notification')) {
        require_once APPPATH . 'helpers/email_notification_helper.php';
    }
    
    // Test with a known user (you may need to adjust this)
    $test_user_id = 'STU001'; // Change this to a valid user ID
    
    // Get user email first
    $CI->load->model('User_model');
    $user = $CI->User_model->get_by_id($test_user_id);
    
    if ($user && isset($user['email'])) {
        $result = send_email_notification(
            $test_user_id,
            'test',
            'Debug Test Notification',
            'This is a test notification to debug email sending.',
            null,
            'test',
            null
        );
        
        $response['tests']['notification_helper_test'] = [
            'status' => $result ? 'success' : 'error',
            'user_id' => $test_user_id,
            'user_email' => $user['email'],
            'result' => $result ? 'Email sent' : 'Email failed'
        ];
    } else {
        $response['tests']['notification_helper_test'] = [
            'status' => 'error',
            'error' => "User {$test_user_id} not found or no email address"
        ];
    }
} catch (Exception $e) {
    $response['tests']['notification_helper_test'] = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// Test 7: Check recent error logs
$response['tests']['error_logs'] = [];
try {
    // Check if there are any recent email-related errors
    $CI->db->select('*');
    $CI->db->from('logs'); // Assuming you have a logs table
    $CI->db->like('message', 'email', 'both');
    $CI->db->or_like('message', 'smtp', 'both');
    $CI->db->order_by('created_at', 'DESC');
    $CI->db->limit(5);
    $query = $CI->db->get();
    $logs = $query->result_array();
    
    $response['tests']['error_logs'] = [
        'status' => 'success',
        'count' => count($logs),
        'logs' => $logs
    ];
} catch (Exception $e) {
    $response['tests']['error_logs'] = [
        'status' => 'error',
        'error' => 'No logs table or error: ' . $e->getMessage()
    ];
}

// Overall status
$has_errors = false;
foreach ($response['tests'] as $test) {
    if (isset($test['status']) && $test['status'] === 'error') {
        $has_errors = true;
        break;
    }
}

$response['overall_status'] = $has_errors ? 'issues_found' : 'all_tests_passed';
$response['recommendations'] = [];

// Add recommendations based on test results
if ($response['tests']['email_config']['smtp_pass'] === 'NOT SET') {
    $response['recommendations'][] = 'Set SMTP_PASS environment variable in Railway';
}

if (isset($response['tests']['smtp_connectivity']['status']) && 
    $response['tests']['smtp_connectivity']['status'] === 'error') {
    $response['recommendations'][] = 'Fix SMTP connectivity - check host and port';
}

if (isset($response['tests']['direct_email_test']['status']) && 
    $response['tests']['direct_email_test']['status'] === 'error') {
    $response['recommendations'][] = 'Check Gmail app password and SMTP credentials';
}

if (isset($response['tests']['notification_helper_test']['status']) && 
    $response['tests']['notification_helper_test']['status'] === 'error') {
    $response['recommendations'][] = 'Check notification helper function and user data';
}

echo json_encode($response, JSON_PRETTY_PRINT);
?>
