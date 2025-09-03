<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Load PHPMailer
require_once(APPPATH . '../vendor/autoload.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email notification using PHPMailer
 */
function send_email_notification_phpmailer($user_id, $type, $title, $message, $related_id = null, $related_type = null, $class_code = null) {
    $CI =& get_instance();
    
    // Get user email
    $user_email = get_user_email($user_id);
    if (!$user_email) {
        return false;
    }
    
    // Get SMTP configuration from environment
    $smtp_host = getenv('SMTP_HOST') ? getenv('SMTP_HOST') : 'smtp.gmail.com';
    $smtp_port = getenv('SMTP_PORT') ? getenv('SMTP_PORT') : 465;
    $smtp_user = getenv('SMTP_USER') ? getenv('SMTP_USER') : 'scmswebsitee@gmail.com';
    $smtp_pass = getenv('SMTP_PASS') ? getenv('SMTP_PASS') : 'zhrk blgg sukj wbbs';
    $smtp_crypto = getenv('SMTP_CRYPTO') ? getenv('SMTP_CRYPTO') : 'ssl';
    
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
        
        // Enable verbose debug output (remove in production)
        $mail->SMTPDebug = 0; // Set to 2 for verbose debug
        
        // Recipients
        $mail->setFrom($smtp_user, 'SCMS System');
        $mail->addAddress($user_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $title;
        
        // Create HTML email content
        $html_content = create_email_html($type, $title, $message, $related_id, $related_type, $class_code);
        $mail->Body = $html_content;
        $mail->AltBody = strip_tags($message); // Plain text version
        
        // Send email
        $result = $mail->send();
        
        // Log email sending
        log_email_notification($user_id, $type, $title, $result);
        
        return $result;
        
    } catch (Exception $e) {
        // Log the error
        log_message('error', 'PHPMailer Error: ' . $mail->ErrorInfo);
        log_email_notification($user_id, $type, $title, false);
        
        return false;
    }
}

/**
 * Test email configuration using PHPMailer
 */
function test_email_configuration_phpmailer($to_email) {
    // Get SMTP configuration from environment
    $smtp_host = getenv('SMTP_HOST') ? getenv('SMTP_HOST') : 'smtp.gmail.com';
    $smtp_port = getenv('SMTP_PORT') ? getenv('SMTP_PORT') : 465;
    $smtp_user = getenv('SMTP_USER') ? getenv('SMTP_USER') : 'scmswebsitee@gmail.com';
    $smtp_pass = getenv('SMTP_PASS') ? getenv('SMTP_PASS') : 'zhrk blgg sukj wbbs';
    $smtp_crypto = getenv('SMTP_CRYPTO') ? getenv('SMTP_CRYPTO') : 'ssl';
    
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
        $mail->addAddress($to_email);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'SCMS Email Test - ' . date('Y-m-d H:i:s');
        $mail->Body = '<h1>SCMS Email Test</h1><p>This is a test email from SCMS System. If you receive this, email configuration is working correctly!</p>';
        $mail->AltBody = 'This is a test email from SCMS System. If you receive this, email configuration is working correctly!';
        
        // Send email
        $result = $mail->send();
        
        return array(
            'success' => $result,
            'debug_info' => $mail->ErrorInfo
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'debug_info' => $mail->ErrorInfo
        );
    }
}

/**
 * Send announcement email using PHPMailer
 */
function send_announcement_email_phpmailer($user_id, $announcement_id, $title, $message, $class_code = null) {
    return send_email_notification_phpmailer($user_id, 'announcement', $title, $message, $announcement_id, 'announcement', $class_code);
}

/**
 * Send task email using PHPMailer
 */
function send_task_email_phpmailer($user_id, $task_id, $title, $message, $class_code = null) {
    return send_email_notification_phpmailer($user_id, 'task', $title, $message, $task_id, 'task', $class_code);
}

/**
 * Send submission email using PHPMailer
 */
function send_submission_email_phpmailer($user_id, $submission_id, $title, $message, $class_code = null) {
    return send_email_notification_phpmailer($user_id, 'submission', $title, $message, $submission_id, 'submission', $class_code);
}

/**
 * Send excuse letter email using PHPMailer
 */
function send_excuse_letter_email_phpmailer($user_id, $excuse_id, $title, $message, $class_code = null) {
    return send_email_notification_phpmailer($user_id, 'excuse_letter', $title, $message, $excuse_id, 'excuse_letter', $class_code);
}

/**
 * Send grade email using PHPMailer
 */
function send_grade_email_phpmailer($user_id, $grade_id, $title, $message, $class_code = null) {
    return send_email_notification_phpmailer($user_id, 'grade', $title, $message, $grade_id, 'grade', $class_code);
}

/**
 * Send enrollment email using PHPMailer
 */
function send_enrollment_email_phpmailer($user_id, $enrollment_id, $title, $message, $class_code = null) {
    return send_email_notification_phpmailer($user_id, 'enrollment', $title, $message, $enrollment_id, 'enrollment', $class_code);
}

/**
 * Send system email using PHPMailer
 */
function send_system_email_phpmailer($user_id, $title, $message) {
    return send_email_notification_phpmailer($user_id, 'system', $title, $message);
}

/**
 * Send bulk email notifications using PHPMailer
 */
function send_bulk_email_notifications_phpmailer($user_ids, $type, $title, $message, $related_id = null, $related_type = null, $class_code = null) {
    $success_count = 0;
    $failure_count = 0;
    
    foreach ($user_ids as $user_id) {
        $result = send_email_notification_phpmailer($user_id, $type, $title, $message, $related_id, $related_type, $class_code);
        
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
