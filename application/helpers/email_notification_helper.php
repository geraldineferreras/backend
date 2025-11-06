<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Ensure PHPMailer is available (supports both Composer and manual include)
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    // Try common vendor path
    $phpmailerBase = APPPATH . '../vendor/phpmailer/phpmailer/src/';
    if (file_exists($phpmailerBase . 'PHPMailer.php')) {
        require_once $phpmailerBase . 'PHPMailer.php';
        require_once $phpmailerBase . 'SMTP.php';
        require_once $phpmailerBase . 'Exception.php';
    } else if (file_exists(APPPATH . 'third_party/PHPMailer/src/PHPMailer.php')) {
        // Fallback to third_party path (non-Composer)
        require_once APPPATH . 'third_party/PHPMailer/src/PHPMailer.php';
        require_once APPPATH . 'third_party/PHPMailer/src/SMTP.php';
        require_once APPPATH . 'third_party/PHPMailer/src/Exception.php';
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Define notification helper functions locally to avoid circular dependency
if (!function_exists('get_notification_icon')) {
    function get_notification_icon($type) {
        $icons = array(
            'announcement' => '📢',
            'task' => '📝',
            'submission' => '📤',
            'excuse_letter' => '📄',
            'grade' => '📊',
            'enrollment' => '👥',
            'system' => '⚙️'
        );
        
        return isset($icons[$type]) ? $icons[$type] : '🔔';
    }
}

if (!function_exists('get_notification_type_display')) {
    function get_notification_type_display($type) {
        $types = array(
            'announcement' => 'Announcement',
            'task' => 'Task',
            'submission' => 'Submission',
            'excuse_letter' => 'Excuse Letter',
            'grade' => 'Grade',
            'enrollment' => 'Enrollment',
            'system' => 'System'
        );
        
        return isset($types[$type]) ? $types[$type] : ucfirst($type);
    }
}

/**
 * Create and configure PHPMailer instance for Gmail SMTP
 */
function create_phpmailer(): PHPMailer {
    $mail = new PHPMailer(true);

    // SMTP configuration - defaults align with application/config/email.php (TLS:587)
    $smtpHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
    $smtpPort = getenv('SMTP_PORT') ?: 587;
    $smtpUser = getenv('SMTP_USER') ?: 'scmswebsitee@gmail.com';
    $smtpPass = getenv('SMTP_PASS') ?: 'zhrk blgg sukj wbbs';
    $smtpSecure = getenv('SMTP_CRYPTO') ?: 'tls';

    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUser;
    $mail->Password = $smtpPass;
    $mail->SMTPSecure = $smtpSecure;
    $mail->Port = (int)$smtpPort;
    $mail->Timeout = (int)(getenv('SMTP_TIMEOUT') ?: 60);
    $mail->CharSet = 'UTF-8';
    $mail->SMTPDebug = (int)(getenv('SMTP_DEBUG') ?: 0);
    $mail->Debugoutput = function($str, $level) {
        error_log("PHPMailer[$level]: " . trim($str));
    };

    // Optional Railway/cloud-friendly TLS options (only if explicitly allowed)
    if (filter_var(getenv('SMTP_ALLOW_SELF_SIGNED') ?: 'false', FILTER_VALIDATE_BOOLEAN)) {
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
    }

    // Optional explicit auth type if provider requires (e.g., 'LOGIN')
    if ($authType = getenv('SMTP_AUTH_TYPE')) {
        $mail->AuthType = $authType;
    }

    // From defaults
    $fromEmail = $smtpUser;
    $fromName = getenv('SMTP_FROM_NAME') ?: 'SCMS System';
    $mail->setFrom($fromEmail, $fromName);
    $mail->isHTML(true);

    return $mail;
}

/**
 * Unified helper to send an email using PHPMailer with CI Email fallback
 */
function send_email(string $to, string $subject, string $htmlMessage, ?string $toName = null): bool {
    // 1) HTTPS API providers first (works on platforms that block SMTP)
    // Resend API
    $resendApiKey = getenv('RESEND_API_KEY');
    if ($resendApiKey) {
        $fromName = getenv('SMTP_FROM_NAME') ?: (getenv('RESEND_FROM_NAME') ?: 'SCMS System');
        $fromEmail = getenv('RESEND_FROM_EMAIL') ?: (getenv('SMTP_USER') ?: 'scmswebsitee@gmail.com');
        $payload = array(
            'from' => $fromName . ' <' . $fromEmail . '>',
            'to' => array($to),
            'subject' => $subject,
            'html' => $htmlMessage
        );

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $resendApiKey,
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 second connection timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }
        log_message('error', 'Resend API send failed (HTTP ' . $httpCode . '): ' . ($response ?: $curlErr));
    }

    // SendGrid API
    $sendgridApiKey = getenv('SENDGRID_API_KEY');
    if ($sendgridApiKey) {
        $fromName = getenv('SMTP_FROM_NAME') ?: (getenv('SENDGRID_FROM_NAME') ?: 'SCMS System');
        $fromEmail = getenv('SENDGRID_FROM_EMAIL') ?: (getenv('SMTP_USER') ?: 'scmswebsitee@gmail.com');
        $payload = array(
            'personalizations' => array(array(
                'to' => array(array('email' => $to, 'name' => $toName ?: $to))
            )),
            'from' => array('email' => $fromEmail, 'name' => $fromName),
            'subject' => $subject,
            'content' => array(array('type' => 'text/html', 'value' => $htmlMessage))
        );

        $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $sendgridApiKey,
            'Content-Type: application/json'
        ));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 second timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 second connection timeout
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        // SendGrid returns 202 on success with empty body
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $curlErr = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($httpCode === 202) {
            return true;
        }
        
        // Extract response body (after headers) for better error messages
        $responseBody = '';
        if ($response && $headerSize) {
            $responseBody = substr($response, $headerSize);
        }
        
        $errorMsg = 'SendGrid API send failed';
        $errorMsg .= ' (HTTP ' . $httpCode . ')';
        if ($curlErrno !== 0) {
            $errorMsg .= ' - cURL Error: ' . $curlErr . ' (Code: ' . $curlErrno . ')';
        }
        if ($responseBody) {
            $errorMsg .= ' - Response: ' . $responseBody;
        }
        log_message('error', $errorMsg);
    }

    // 2) PHPMailer SMTP path (works locally; may be blocked on some PaaS)
    try {
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            $mail = create_phpmailer();
            $mail->clearAddresses();
            $mail->addAddress($to, $toName ?: $to);
            $mail->Subject = $subject;
            $mail->Body = $htmlMessage;
            $mail->AltBody = strip_tags($htmlMessage);

            $sent = $mail->send();
            if ($sent) {
                return true;
            }

            $envSummary = sprintf(
                'host=%s port=%s crypto=%s user=%s',
                getenv('SMTP_HOST') ?: 'smtp.gmail.com',
                getenv('SMTP_PORT') ?: '587',
                getenv('SMTP_CRYPTO') ?: 'tls',
                getenv('SMTP_USER') ?: 'scmswebsitee@gmail.com'
            );
            log_message('error', 'PHPMailer send failed. Env: ' . $envSummary . ' Error: ' . $mail->ErrorInfo);
        } else {
            log_message('error', 'PHPMailer not available. Install dependency or vendor path on Railway.');
        }
    } catch (Exception $e) {
        log_message('error', 'PHPMailer exception: ' . $e->getMessage());
    }

    return false;
}

/**
 * Send email notification (rewritten to use PHPMailer)
 */
function send_email_notification($user_id, $type, $title, $message, $related_id = null, $related_type = null, $class_code = null) {
    $CI =& get_instance();

    $user_email = get_user_email($user_id);
    if (!$user_email) {
        log_message('error', "Email notification failed: No email found for user {$user_id}");
        return false;
    }

    $html_content = create_email_html($type, $title, $message, $related_id, $related_type, $class_code);

    $result = send_email($user_email, $title, $html_content);

    log_email_notification($user_id, $type, $title, $result);
    return $result;
}

/**
 * Get user email by user_id
 */
function get_user_email($user_id) {
    $CI =& get_instance();
    $CI->load->model('User_model');
    
    $user = $CI->User_model->get_by_id($user_id);
    return $user ? $user['email'] : null;
}

/**
 * Get class name by class code
 */
function get_class_name($class_code) {
    $CI =& get_instance();
    $CI->load->model('Classroom_model');
    
    $classroom = $CI->Classroom_model->get_by_code($class_code);
    return $classroom ? $classroom['title'] : $class_code;
}

/**
 * Create HTML email content
 */
function create_email_html($type, $title, $message, $related_id = null, $related_type = null, $class_code = null) {
    $icon = get_notification_icon($type);
    $type_display = get_notification_type_display($type);
    $current_date = date('F j, Y g:i A');
    
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($title) . '</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f4f4f4;
            }
            .email-container {
                background-color: #ffffff;
                border-radius: 8px;
                padding: 30px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .header {
                text-align: center;
                border-bottom: 2px solid #007bff;
                padding-bottom: 20px;
                margin-bottom: 30px;
            }
            .notification-icon {
                font-size: 48px;
                margin-bottom: 10px;
            }
            .notification-type {
                color: #007bff;
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 5px;
            }
            .notification-title {
                font-size: 24px;
                font-weight: bold;
                color: #333;
                margin-bottom: 20px;
            }
            .notification-message {
                background-color: #f8f9fa;
                padding: 20px;
                border-radius: 5px;
                border-left: 4px solid #007bff;
                margin-bottom: 20px;
            }
            .notification-details {
                background-color: #e9ecef;
                padding: 15px;
                border-radius: 5px;
                font-size: 14px;
                color: #666;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                color: #666;
                font-size: 12px;
            }
            .btn {
                display: inline-block;
                padding: 10px 20px;
                background-color: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin-top: 15px;
            }
            .btn:hover {
                background-color: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <div class="notification-icon">' . $icon . '</div>
                <div class="notification-type">' . htmlspecialchars($type_display) . '</div>
                <div class="notification-title">' . htmlspecialchars($title) . '</div>
            </div>
            
            <div class="notification-message">
                ' . nl2br(htmlspecialchars($message)) . '
            </div>';
    
    // Add related information if available
    if ($related_id || $class_code) {
        $html .= '<div class="notification-details">';
        if ($class_code) {
            $class_name = get_class_name($class_code);
            $html .= '<strong>Class:</strong> ' . htmlspecialchars($class_name) . '<br>';
        }
        if ($related_id && $related_type) {
            $html .= '<strong>Reference ID:</strong> ' . htmlspecialchars($related_id) . ' (' . htmlspecialchars($related_type) . ')<br>';
        }
        $html .= '<strong>Date:</strong> ' . $current_date;
        $html .= '</div>';
    }
    
    $html .= '
            <div style="text-align: center; margin-top: 20px;">
                <a href="http://localhost/scms_new_backup/index.php" class="btn">View in SCMS</a>
            </div>
            
            <div class="footer">
                <p>This is an automated notification from the SCMS System.</p>
                <p>If you have any questions, please contact your system administrator.</p>
                <p>© ' . date('Y') . ' SCMS System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return $html;
}

/**
 * Create announcement email
 */
function send_announcement_email($user_id, $announcement_id, $title, $message, $class_code = null) {
    return send_email_notification($user_id, 'announcement', $title, $message, $announcement_id, 'announcement', $class_code);
}

/**
 * Create task email
 */
function send_task_email($user_id, $task_id, $title, $message, $class_code = null) {
    return send_email_notification($user_id, 'task', $title, $message, $task_id, 'task', $class_code);
}

/**
 * Create submission email
 */
function send_submission_email($user_id, $submission_id, $title, $message, $class_code = null) {
    return send_email_notification($user_id, 'submission', $title, $message, $submission_id, 'submission', $class_code);
}

/**
 * Create excuse letter email
 */
function send_excuse_letter_email($user_id, $excuse_id, $title, $message, $class_code = null) {
    return send_email_notification($user_id, 'excuse_letter', $title, $message, $excuse_id, 'excuse_letter', $class_code);
}

/**
 * Create grade email
 */
function send_grade_email($user_id, $grade_id, $title, $message, $class_code = null) {
    return send_email_notification($user_id, 'grade', $title, $message, $grade_id, 'grade', $class_code);
}

/**
 * Create enrollment email
 */
function send_enrollment_email($user_id, $enrollment_id, $title, $message, $class_code = null) {
    return send_email_notification($user_id, 'enrollment', $title, $message, $enrollment_id, 'enrollment', $class_code);
}

/**
 * Create system email
 */
function send_system_email($user_id, $title, $message) {
    return send_email_notification($user_id, 'system', $title, $message);
}

/**
 * Log email notification
 */
function log_email_notification($user_id, $type, $title, $success) {
    $CI =& get_instance();
    
    $log_data = array(
        'user_id' => $user_id,
        'type' => $type,
        'title' => $title,
        'success' => $success ? 1 : 0,
        'created_at' => date('Y-m-d H:i:s')
    );
    
    // You can create an email_logs table if you want to track email sending
    // $CI->db->insert('email_logs', $log_data);
}

/**
 * Test email configuration
 */
function test_email_configuration($to_email) {
    $subject = 'SCMS Email Test';
    $body = 'This is a test email from SCMS System. If you receive this, email configuration is working correctly.';
    return send_email($to_email, $subject, $body);
}

/**
 * Send bulk email notifications
 */
function send_bulk_email_notifications($user_ids, $type, $title, $message, $related_id = null, $related_type = null, $class_code = null) {
    $success_count = 0;
    $failure_count = 0;
    
    foreach ($user_ids as $user_id) {
        $result = send_email_notification($user_id, $type, $title, $message, $related_id, $related_type, $class_code);
        
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