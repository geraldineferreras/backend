<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Load SendGrid
require_once(APPPATH . '../vendor/autoload.php');

use SendGrid\Mail\Mail;
use SendGrid;

/**
 * Send email notification using SendGrid
 */
function send_email_notification_sendgrid($user_id, $type, $title, $message, $related_id = null, $related_type = null, $class_code = null) {
    $CI =& get_instance();
    
    // Get user email
    $user_email = get_user_email($user_id);
    if (!$user_email) {
        return false;
    }
    
    // Get SendGrid API key from environment
    $sendgrid_api_key = getenv('SENDGRID_API_KEY');
    if (!$sendgrid_api_key) {
        log_message('error', 'SendGrid API key not found in environment variables');
        return false;
    }
    
    // Get sender email from environment
    $sender_email = getenv('SENDGRID_FROM_EMAIL') ? getenv('SENDGRID_FROM_EMAIL') : 'scmswebsitee@gmail.com';
    $sender_name = getenv('SENDGRID_FROM_NAME') ? getenv('SENDGRID_FROM_NAME') : 'SCMS System';
    
    try {
        $email = new Mail();
        $email->setFrom($sender_email, $sender_name);
        $email->setSubject($title);
        $email->addTo($user_email);
        
        // Create HTML email content
        $html_content = create_email_html($type, $title, $message, $related_id, $related_type, $class_code);
        $email->addContent("text/html", $html_content);
        $email->addContent("text/plain", strip_tags($message));
        
        $sendgrid = new SendGrid($sendgrid_api_key);
        $response = $sendgrid->send($email);
        
        // Log email sending
        $success = $response->statusCode() >= 200 && $response->statusCode() < 300;
        log_email_notification($user_id, $type, $title, $success);
        
        if ($success) {
            log_message('info', 'Email sent successfully via SendGrid to: ' . $user_email);
        } else {
            log_message('error', 'SendGrid email failed: ' . $response->body());
        }
        
        return $success;
        
    } catch (Exception $e) {
        log_message('error', 'SendGrid Exception: ' . $e->getMessage());
        log_email_notification($user_id, $type, $title, false);
        return false;
    }
}

/**
 * Test email configuration using SendGrid
 */
function test_email_configuration_sendgrid($to_email) {
    // Get SendGrid API key from environment
    $sendgrid_api_key = getenv('SENDGRID_API_KEY');
    if (!$sendgrid_api_key) {
        return array(
            'success' => false,
            'debug_info' => 'SendGrid API key not found in environment variables'
        );
    }
    
    // Get sender email from environment
    $sender_email = getenv('SENDGRID_FROM_EMAIL') ? getenv('SENDGRID_FROM_EMAIL') : 'scmswebsitee@gmail.com';
    $sender_name = getenv('SENDGRID_FROM_NAME') ? getenv('SENDGRID_FROM_NAME') : 'SCMS System';
    
    try {
        $email = new Mail();
        $email->setFrom($sender_email, $sender_name);
        $email->setSubject('SCMS Email Test - ' . date('Y-m-d H:i:s'));
        $email->addTo($to_email);
        $email->addContent("text/html", '<h1>SCMS Email Test</h1><p>This is a test email from SCMS System using SendGrid. If you receive this, email configuration is working correctly!</p>');
        $email->addContent("text/plain", 'This is a test email from SCMS System using SendGrid. If you receive this, email configuration is working correctly!');
        
        $sendgrid = new SendGrid($sendgrid_api_key);
        $response = $sendgrid->send($email);
        
        $success = $response->statusCode() >= 200 && $response->statusCode() < 300;
        
        return array(
            'success' => $success,
            'debug_info' => 'Status Code: ' . $response->statusCode() . ', Body: ' . $response->body()
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'debug_info' => 'Exception: ' . $e->getMessage()
        );
    }
}

/**
 * Send announcement email using SendGrid
 */
function send_announcement_email_sendgrid($user_id, $announcement_id, $title, $message, $class_code = null) {
    return send_email_notification_sendgrid($user_id, 'announcement', $title, $message, $announcement_id, 'announcement', $class_code);
}

/**
 * Send task email using SendGrid
 */
function send_task_email_sendgrid($user_id, $task_id, $title, $message, $class_code = null) {
    return send_email_notification_sendgrid($user_id, 'task', $title, $message, $task_id, 'task', $class_code);
}

/**
 * Send submission email using SendGrid
 */
function send_submission_email_sendgrid($user_id, $submission_id, $title, $message, $class_code = null) {
    return send_email_notification_sendgrid($user_id, 'submission', $title, $message, $submission_id, 'submission', $class_code);
}

/**
 * Send excuse letter email using SendGrid
 */
function send_excuse_letter_email_sendgrid($user_id, $excuse_id, $title, $message, $class_code = null) {
    return send_email_notification_sendgrid($user_id, 'excuse_letter', $title, $message, $excuse_id, 'excuse_letter', $class_code);
}

/**
 * Send grade email using SendGrid
 */
function send_grade_email_sendgrid($user_id, $grade_id, $title, $message, $class_code = null) {
    return send_email_notification_sendgrid($user_id, 'grade', $title, $message, $grade_id, 'grade', $class_code);
}

/**
 * Send enrollment email using SendGrid
 */
function send_enrollment_email_sendgrid($user_id, $enrollment_id, $title, $message, $class_code = null) {
    return send_email_notification_sendgrid($user_id, 'enrollment', $title, $message, $enrollment_id, 'enrollment', $class_code);
}

/**
 * Send system email using SendGrid
 */
function send_system_email_sendgrid($user_id, $title, $message) {
    return send_email_notification_sendgrid($user_id, 'system', $title, $message);
}

/**
 * Send bulk email notifications using SendGrid
 */
function send_bulk_email_notifications_sendgrid($user_ids, $type, $title, $message, $related_id = null, $related_type = null, $class_code = null) {
    $success_count = 0;
    $failure_count = 0;
    
    foreach ($user_ids as $user_id) {
        $result = send_email_notification_sendgrid($user_id, $type, $title, $message, $related_id, $related_type, $class_code);
        
        if ($result) {
            $success_count++;
        } else {
            $failure_count++;
        }
    }
    
    return array(
        'success_count' => $success_count,
        'failure_count' => $failure_count,
        'total_count' => count($user_ids)
    );
}
