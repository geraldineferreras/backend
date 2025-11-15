<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// Load email notification helper
if (!function_exists('send_email_notification')) {
    $email_helper_path = APPPATH . 'helpers/email_notification_helper.php';
    if (file_exists($email_helper_path)) {
        require_once $email_helper_path;
    }
}

/**
 * Create a notification for a user
 */
function create_notification($user_id, $type, $title, $message, $related_id = null, $related_type = null, $class_code = null, $is_urgent = false) {
    $CI =& get_instance();
    $CI->load->model('Notification_model');
    
    $notification_data = array(
        'user_id' => $user_id,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'related_id' => $related_id,
        'related_type' => $related_type,
        'class_code' => $class_code,
        'is_urgent' => $is_urgent ? 1 : 0
    );
    
    $notification_id = $CI->Notification_model->create_notification($notification_data);
    
    // Send email notification (always enabled for now)
    if (function_exists('send_email_notification')) {
        try {
            send_email_notification($user_id, $type, $title, $message, $related_id, $related_type, $class_code);
        } catch (Exception $e) {
            // Log email error but don't fail the notification creation
            error_log("Email notification failed: " . $e->getMessage());
        }
    }
    
    // Broadcast real-time notification if helper is available
    if (function_exists('broadcast_notification')) {
        $CI->load->helper('notification_broadcast');
        
        $broadcast_data = array(
            'id' => $notification_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $related_id,
            'related_type' => $related_type,
            'class_code' => $class_code,
            'is_urgent' => $is_urgent,
            'created_at' => date('c')
        );
        
        broadcast_notification($broadcast_data, $user_id, 'notification');
    }
    
    return $notification_id;
}

/**
 * Create notifications for multiple users
 */
function create_notifications_for_users($user_ids, $type, $title, $message, $related_id = null, $related_type = null, $class_code = null, $is_urgent = false) {
    $CI =& get_instance();
    $CI->load->model('Notification_model');
    
    error_log("create_notifications_for_users called with " . count($user_ids) . " users");
    error_log("Type: " . $type . ", Title: " . $title . ", Related ID: " . $related_id);
    
    $notification_ids = array();
    
    foreach ($user_ids as $user_id) {
        $notification_data = array(
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $related_id,
            'related_type' => $related_type,
            'class_code' => $class_code,
            'is_urgent' => $is_urgent ? 1 : 0
        );
        
        error_log("Creating notification for user: " . $user_id);
        $notification_id = $CI->Notification_model->create_notification($notification_data);
        error_log("Notification created with ID: " . $notification_id);
        $notification_ids[] = $notification_id;
        
        // Send email notification (always enabled for now)
        if (function_exists('send_email_notification')) {
            try {
                send_email_notification($user_id, $type, $title, $message, $related_id, $related_type, $class_code);
            } catch (Exception $e) {
                // Log email error but don't fail the notification creation
                error_log("Email notification failed: " . $e->getMessage());
            }
        }
    }
    
    // Broadcast real-time notification to all users if helper is available
    if (function_exists('broadcast_notification') && !empty($notification_ids)) {
        $CI->load->helper('notification_broadcast');
        
        $broadcast_data = array(
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $related_id,
            'related_type' => $related_type,
            'class_code' => $class_code,
            'is_urgent' => $is_urgent,
            'created_at' => date('c'),
            'affected_users' => count($user_ids)
        );
        
        broadcast_notification($broadcast_data, $user_ids, 'bulk_notification');
    }
    
    return $notification_ids;
}

/**
 * Create announcement notification
 */
function create_announcement_notification($user_id, $announcement_id, $title, $message, $class_code = null) {
    return create_notification(
        $user_id,
        'announcement',
        $title,
        $message,
        $announcement_id,
        'announcement',
        $class_code,
        false
    );
}

/**
 * Create task notification
 */
function create_task_notification($user_id, $task_id, $title, $message, $class_code = null, $is_urgent = false) {
    return create_notification(
        $user_id,
        'task',
        $title,
        $message,
        $task_id,
        'task',
        $class_code,
        $is_urgent
    );
}

/**
 * Create submission notification
 */
function create_submission_notification($user_id, $submission_id, $title, $message, $class_code = null) {
    return create_notification(
        $user_id,
        'submission',
        $title,
        $message,
        $submission_id,
        'submission',
        $class_code,
        false
    );
}

/**
 * Create excuse letter notification
 */
function create_excuse_letter_notification($user_id, $excuse_id, $title, $message, $class_code = null) {
    return create_notification(
        $user_id,
        'excuse_letter',
        $title,
        $message,
        $excuse_id,
        'excuse_letter',
        $class_code,
        false
    );
}

/**
 * Create grade notification
 */
function create_grade_notification($user_id, $grade_id, $title, $message, $class_code = null) {
    return create_notification(
        $user_id,
        'grade',
        $title,
        $message,
        $grade_id,
        'grade',
        $class_code,
        false
    );
}

/**
 * Create enrollment notification
 */
function create_enrollment_notification($user_id, $enrollment_id, $title, $message, $class_code = null) {
    return create_notification(
        $user_id,
        'enrollment',
        $title,
        $message,
        $enrollment_id,
        'enrollment',
        $class_code,
        false
    );
}

/**
 * Create system notification
 */
function create_system_notification($user_id, $title, $message, $is_urgent = false) {
    return create_notification(
        $user_id,
        'system',
        $title,
        $message,
        null,
        null,
        null,
        $is_urgent
    );
}

/**
 * Get notification type display name
 */
function get_notification_type_display($type) {
    $types = array(
        'announcement' => 'Announcement',
        'task' => 'Task',
        'submission' => 'Submission',
        'excuse_letter' => 'Excuse Letter',
        'grade' => 'Grade',
        'enrollment' => 'Enrollment',
        'attendance' => 'Attendance',
        'system' => 'System'
    );
    
    return isset($types[$type]) ? $types[$type] : ucfirst($type);
}

/**
 * Get notification icon based on type
 */
function get_notification_icon($type) {
    $icons = array(
        'announcement' => '📢',
        'task' => '📝',
        'submission' => '📤',
        'excuse_letter' => '📄',
        'grade' => '📊',
        'enrollment' => '👥',
        'attendance' => '📋',
        'system' => '⚙️'
    );
    
    return isset($icons[$type]) ? $icons[$type] : '🔔';
}

/**
 * Format notification message for display
 */
function format_notification_message($notification) {
    $icon = get_notification_icon($notification->type);
    $type_display = get_notification_type_display($notification->type);
    
    return array(
        'id' => $notification->id,
        'type' => $notification->type,
        'type_display' => $type_display,
        'icon' => $icon,
        'title' => $notification->title,
        'message' => $notification->message,
        'related_id' => $notification->related_id,
        'related_type' => $notification->related_type,
        'class_code' => $notification->class_code,
        'is_read' => (bool)$notification->is_read,
        'is_urgent' => (bool)$notification->is_urgent,
        'created_at' => $notification->created_at,
        'updated_at' => $notification->updated_at
    );
}

/**
 * Get students in a class for notifications
 */
function get_class_students($class_code) {
    $CI =& get_instance();
    $CI->load->model('Classroom_model');
    
    // Get classroom by code
    $classroom = $CI->Classroom_model->get_by_code($class_code);
    if (!$classroom) {
        return [];
    }
    
    // Get enrolled students with their details
    $query = "SELECT 
                u.user_id,
                u.full_name,
                u.email
            FROM classroom_enrollments ce
            JOIN users u ON ce.student_id = u.user_id COLLATE utf8mb4_unicode_ci
            WHERE ce.classroom_id = ?
            AND ce.status = 'active'
            ORDER BY u.full_name ASC";
    
    return $CI->db->query($query, [$classroom['id']])->result_array();
}

/**
 * Get teacher of a class for notifications
 */
function get_class_teacher($class_code) {
    $CI =& get_instance();
    $CI->load->model('Teacher_model');
    
    return $CI->Teacher_model->get_teacher_by_class($class_code);
}

/**
 * Create attendance notification for student
 */
function create_attendance_notification($student_id, $attendance_id, $status, $subject_name, $section_name, $date, $time_in = null, $notes = null) {
    $CI =& get_instance();
    
    // Get student details
    $student = $CI->db->select('full_name, email')
        ->from('users')
        ->where('user_id', $student_id)
        ->get()->row_array();
    
    if (!$student) {
        return false;
    }
    
    // Determine notification title and message based on status
    $status_display = ucfirst($status);
    $title = "Attendance Recorded - {$status_display}";
    
    $message = "Your attendance has been recorded for {$subject_name} ({$section_name}) on {$date}.";
    
    if ($time_in) {
        $message .= " Time in: {$time_in}";
    }
    
    if ($notes) {
        $message .= " Notes: {$notes}";
    }
    
    // Add status-specific information
    switch (strtolower($status)) {
        case 'present':
            $message .= " You were marked as present.";
            break;
        case 'late':
            $message .= " You were marked as late.";
            break;
        case 'absent':
            $message .= " You were marked as absent.";
            break;
        case 'excused':
            $message .= " You were marked as excused (approved excuse letter).";
            break;
    }
    
    return create_notification(
        $student_id,
        'attendance',
        $title,
        $message,
        $attendance_id,
        'attendance',
        null,
        false
    );
}

/**
 * Create attendance update notification for student
 */
function create_attendance_update_notification($student_id, $attendance_id, $old_status, $new_status, $subject_name, $section_name, $date, $time_in = null, $notes = null) {
    $CI =& get_instance();
    
    // Get student details
    $student = $CI->db->select('full_name, email')
        ->from('users')
        ->where('user_id', $student_id)
        ->get()->row_array();
    
    if (!$student) {
        return false;
    }
    
    $old_status_display = ucfirst($old_status);
    $new_status_display = ucfirst($new_status);
    $title = "Attendance Updated - {$new_status_display}";
    
    $message = "Your attendance has been updated for {$subject_name} ({$section_name}) on {$date}.";
    $message .= " Status changed from {$old_status_display} to {$new_status_display}.";
    
    if ($time_in) {
        $message .= " Time in: {$time_in}";
    }
    
    if ($notes) {
        $message .= " Notes: {$notes}";
    }
    
    return create_notification(
        $student_id,
        'attendance',
        $title,
        $message,
        $attendance_id,
        'attendance',
        null,
        false
    );
} 