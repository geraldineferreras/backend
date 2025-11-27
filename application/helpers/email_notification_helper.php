<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Ensure PHPMailer is available (supports both Composer and manual include)
$phpmailerLoaded = false;
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    // Try common vendor path
    $phpmailerBase = APPPATH . '../vendor/phpmailer/phpmailer/src/';
    $phpmailerFile = $phpmailerBase . 'PHPMailer.php';
    if (file_exists($phpmailerFile) && is_readable($phpmailerFile)) {
        $error = false;
        $smtpFile = $phpmailerBase . 'SMTP.php';
        $exceptionFile = $phpmailerBase . 'Exception.php';
        
        if (file_exists($smtpFile) && file_exists($exceptionFile)) {
            // Suppress errors and check if require was successful
            @require_once $phpmailerFile;
            @require_once $smtpFile;
            @require_once $exceptionFile;
            
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $phpmailerLoaded = true;
            }
        }
    } else if (file_exists(APPPATH . 'third_party/PHPMailer/src/PHPMailer.php')) {
        // Fallback to third_party path (non-Composer)
        $thirdPartyBase = APPPATH . 'third_party/PHPMailer/src/';
        $phpmailerFile = $thirdPartyBase . 'PHPMailer.php';
        $smtpFile = $thirdPartyBase . 'SMTP.php';
        $exceptionFile = $thirdPartyBase . 'Exception.php';
        
        if (file_exists($smtpFile) && file_exists($exceptionFile) && is_readable($phpmailerFile)) {
            @require_once $phpmailerFile;
            @require_once $smtpFile;
            @require_once $exceptionFile;
            
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                $phpmailerLoaded = true;
            }
        }
    }
} else {
    $phpmailerLoaded = true;
}

// Note: PHPMailer use statements moved inside functions to avoid errors if classes don't exist

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
function create_phpmailer() {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        throw new Exception('PHPMailer is not available. Please install PHPMailer via Composer or manually.');
    }
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

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

    // Brevo API (formerly Sendinblue)
    $brevoApiKey = getenv('BREVO_API_KEY');
    if ($brevoApiKey) {
        $fromName = getenv('SMTP_FROM_NAME') ?: (getenv('BREVO_FROM_NAME') ?: 'SCMS System');
        $fromEmail = getenv('BREVO_FROM_EMAIL') ?: (getenv('SMTP_USER') ?: 'scmswebsitee@gmail.com');
        $payload = array(
            'sender' => array(
                'name' => $fromName,
                'email' => $fromEmail
            ),
            'to' => array(array(
                'email' => $to,
                'name' => $toName ?: $to
            )),
            'subject' => $subject,
            'htmlContent' => $htmlMessage
        );

        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'api-key: ' . $brevoApiKey,
            'Content-Type: application/json',
            'Accept: application/json'
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
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }
        
        $errorMsg = 'Brevo API send failed';
        $errorMsg .= ' (HTTP ' . $httpCode . ')';
        if ($curlErrno !== 0) {
            $errorMsg .= ' - cURL Error: ' . $curlErr . ' (Code: ' . $curlErrno . ')';
        }
        if ($response) {
            $errorMsg .= ' - Response: ' . $response;
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
function send_email_notification($user_id, $type, $title, $message, $related_id = null, $related_type = null, $class_code = null, $options = array()) {
    $CI =& get_instance();

    $user_email = get_user_email($user_id);
    if (!$user_email) {
        log_message('error', "Email notification failed: No email found for user {$user_id}");
        return false;
    }

    $html_content = create_email_html($type, $title, $message, $related_id, $related_type, $class_code, $options);

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
function create_email_html($type, $title, $message, $related_id = null, $related_type = null, $class_code = null, $options = array()) {
    $icon = get_notification_icon($type);
    $type_display = get_notification_type_display($type);
    $current_date = date('F j, Y g:i A');
    $default_cta_url = getenv('EMAIL_NOTIFICATION_DEFAULT_URL') ?: 'https://scmsupdatedbackup.vercel.app/';
    $cta_url = isset($options['action_url']) && !empty($options['action_url']) ? $options['action_url'] : $default_cta_url;
    $cta_text = isset($options['action_text']) && !empty($options['action_text']) ? $options['action_text'] : 'View in SCMS';
    
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
    
    if (!empty($cta_url)) {
        $html .= '
            <div style="text-align: center; margin-top: 20px;">
                <a href="' . htmlspecialchars($cta_url) . '" class="btn">' . htmlspecialchars($cta_text) . '</a>
            </div>';
    }
    
    $html .= '
            
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
 * Resolve default login URL for onboarding emails
 */
function get_scms_login_url() {
    $fallback = getenv('EMAIL_VERIFICATION_REDIRECT_URL') ?: 'https://scmsupdatedbackup.vercel.app/auth/login';
    $login_url = getenv('APP_LOGIN_URL') ?: $fallback;
    return rtrim($login_url, '/');
}

/**
 * Send "under review" email after verification
 */
function send_registration_under_review_email($full_name, $email, $role, $login_url = null) {
    $login_url = $login_url ?: get_scms_login_url();
    $subject = 'Your account is under review';
    $message = "Hi {$full_name},\n\nThank you for verifying your {$role} account. "
        . "Your registration is now under review by the administrator.\n\n"
        . "What happens next:\n"
        . "- An admin or chairperson will review and approve your details.\n"
        . "- You will receive another email once a decision has been made.\n\n"
        . "You can check back any time using the button below.\n\n"
        . "Login link: {$login_url}";

    $html = create_email_html('system', $subject, $message, null, null, null, [
        'action_text' => 'Go to login',
        'action_url' => $login_url
    ]);

    return send_email($email, $subject, $html, $full_name);
}

/**
 * Send approval email with temporary password
 */
function send_registration_approved_email($full_name, $email, $role, $temporary_password, $login_url = null) {
    $login_url = $login_url ?: get_scms_login_url();
    $subject = 'Your account has been approved';
    $message = "Hi {$full_name},\n\nGreat news! Your {$role} account has been approved.\n\n"
        . "Use the details below to sign in:\n"
        . "- Email: {$email}\n"
        . "- Temporary password: {$temporary_password}\n\n"
        . "Security reminders:\n"
        . "- Do not share this password.\n"
        . "- Please change your password immediately after login.\n\n"
        . "Login link: {$login_url}";

    $html = create_email_html('system', $subject, $message, null, null, null, [
        'action_text' => 'Login to SCMS',
        'action_url' => $login_url
    ]);

    return send_email($email, $subject, $html, $full_name);
}

/**
 * Send verification email for bulk-uploaded students
 */
function send_bulk_upload_verification_email($full_name, $email, $verification_link, $expires_at = null) {
    if (empty($verification_link)) {
        return false;
    }

    $subject = 'Verify your SCMS student account';
    $expiry_text = $expires_at ? date('F j, Y g:i A', strtotime($expires_at)) : null;
    $message = "Hi {$full_name},\n\nYou were added to SCMS by your administrator. "
        . "Please verify your email to activate your student account.\n\n"
        . "Click the button below to verify your email.";

    if ($expiry_text) {
        $message .= "\n\nThis link expires on {$expiry_text}.";
    }

    $message .= "\n\nIf the button doesn't work, copy and paste this link into your browser:\n{$verification_link}";

    $html = create_email_html('system', $subject, $message, null, null, null, [
        'action_text' => 'Verify Email',
        'action_url' => $verification_link
    ]);

    return send_email($email, $subject, $html, $full_name);
}

/**
 * Send credentials email after bulk verification
 */
function send_bulk_upload_credentials_email($full_name, $email, $temporary_password, $login_url = null) {
    if (empty($temporary_password)) {
        return false;
    }

    $login_url = $login_url ?: get_scms_login_url();
    $subject = 'Your SCMS temporary password';
    $message = "Hi {$full_name},\n\nYour student account has been verified and activated.\n\n"
        . "Use the details below to sign in:\n"
        . "- Email: {$email}\n"
        . "- Temporary password: {$temporary_password}\n\n"
        . "Security reminders:\n"
        . "- Change this password immediately after logging in.\n"
        . "- Do not share this password with anyone.\n\n"
        . "Login link: {$login_url}";

    $html = create_email_html('system', $subject, $message, null, null, null, [
        'action_text' => 'Login to SCMS',
        'action_url' => $login_url
    ]);

    return send_email($email, $subject, $html, $full_name);
}

/**
 * Send rejection email
 */
function send_registration_rejected_email($full_name, $email, $role, $login_url = null) {
    $login_url = $login_url ?: get_scms_login_url();
    $subject = 'Your account registration was rejected';
    $message = "Hi {$full_name},\n\nWe're sorry, but your {$role} account registration has been rejected. "
        . "Please contact the administrator or chairperson for assistance if you believe this was a mistake.\n\n"
        . "Login link: {$login_url}";

    $html = create_email_html('system', $subject, $message, null, null, null, [
        'action_text' => 'Contact administrator',
        'action_url' => $login_url
    ]);

    return send_email($email, $subject, $html, $full_name);
}

/**
 * Send email notification to all admins/chairpersons when a new registration needs approval
 * Also creates in-app system notifications (toaster) for each admin
 */
function notify_admins_pending_approval($pending_user_full_name, $pending_user_email, $pending_user_role, $pending_user_program = null) {
    $CI =& get_instance();
    $CI->load->model('User_model');
    $CI->load->helper('notification');
    
    $admins = $CI->User_model->get_admin_emails();
    
    if (empty($admins)) {
        log_message('warning', 'No active admins found to notify about pending registration');
        return false;
    }
    
    $approval_url = getenv('ADMIN_APPROVAL_URL') ?: (getenv('APP_LOGIN_URL') ?: get_scms_login_url());
    $approval_url = rtrim($approval_url, '/') . '/admin/account-approval';
    
    $program_text = $pending_user_program ? " ({$pending_user_program})" : '';
    $subject = "New {$pending_user_role} registration awaiting approval";
    $email_message = "Hello,\n\n"
        . "A new {$pending_user_role} account registration is pending your approval:\n\n"
        . "Name: {$pending_user_full_name}\n"
        . "Email: {$pending_user_email}\n"
        . "User Type: " . ucfirst($pending_user_role) . "{$program_text}\n"
        . "Submitted: " . date('F j, Y g:i A') . "\n\n"
        . "Please review and approve or reject this registration in the admin panel.\n\n"
        . "Approval URL: {$approval_url}";
    
    // In-app notification message (shorter for toaster)
    $notification_title = "New {$pending_user_role} registration pending";
    $notification_message = "{$pending_user_full_name} ({$pending_user_email})" . ($program_text ? $program_text : '') . " needs approval";
    
    $html = create_email_html('system', $subject, $email_message, null, null, null, [
        'action_text' => 'Review Registration',
        'action_url' => $approval_url
    ]);
    
    $email_success_count = 0;
    $email_failure_count = 0;
    $notification_success_count = 0;
    $notification_failure_count = 0;
    
    foreach ($admins as $admin) {
        // Send email notification
        try {
            $result = send_email($admin['email'], $subject, $html, $admin['name']);
            if ($result) {
                $email_success_count++;
                log_message('info', "Pending approval email sent to admin: {$admin['email']}");
            } else {
                $email_failure_count++;
                log_message('error', "Failed to send pending approval email to admin: {$admin['email']}");
            }
        } catch (Exception $e) {
            $email_failure_count++;
            log_message('error', "Exception sending pending approval email to {$admin['email']}: " . $e->getMessage());
        }
        
        // Create in-app system notification (toaster)
        try {
            if (function_exists('create_system_notification')) {
                create_system_notification(
                    $admin['user_id'],
                    $notification_title,
                    $notification_message,
                    true, // Mark as urgent
                    [
                        'action_text' => 'Review Now',
                        'action_url' => $approval_url
                    ]
                );
                $notification_success_count++;
                log_message('info', "Pending approval system notification created for admin: {$admin['user_id']} ({$admin['email']})");
            } else {
                log_message('warning', "create_system_notification function not available");
            }
        } catch (Exception $e) {
            $notification_failure_count++;
            log_message('error', "Exception creating system notification for admin {$admin['user_id']}: " . $e->getMessage());
        }
    }
    
    log_message('info', "Pending approval notifications - Emails: {$email_success_count} success, {$email_failure_count} failures | System notifications: {$notification_success_count} success, {$notification_failure_count} failures");
    
    return ($email_success_count > 0 || $notification_success_count > 0);
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