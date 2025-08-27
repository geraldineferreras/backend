<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Attendance Log Helper Functions
 * Provides functions for logging attendance events to the dedicated attendance_logs table
 */

if (!function_exists('log_attendance_event')) {
    /**
     * Log an attendance event to the dedicated attendance_logs table
     * 
     * @param array $attendance_data The attendance record data
     * @param string $action_type The type of action (RECORDED, UPDATED, DELETED)
     * @param array $user_data The user performing the action
     * @param array $additional_data Additional data for the log
     * @return int|false The log ID on success, false on failure
     */
    function log_attendance_event($attendance_data, $action_type, $user_data, $additional_data = []) {
        $CI =& get_instance();
        
        try {
            // Get student information
            $student = $CI->db->select('users.full_name, users.student_num')
                ->from('users')
                ->where('users.user_id', $attendance_data['student_id'])
                ->get()->row_array();
            
            // Get class information
            $class = $CI->db->select('
                classes.*, 
                subjects.subject_name, 
                subjects.subject_code,
                sections.section_name,
                sections.program,
                teachers.full_name as teacher_name
            ')
            ->from('classes')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->join('users as teachers', 'classes.teacher_id = teachers.user_id', 'left')
            ->where('classes.class_id', $attendance_data['class_id'])
            ->get()->row_array();
            
            // Calculate late minutes if applicable
            $late_minutes = null;
            if (isset($attendance_data['time_in']) && $class) {
                // You can add logic here to calculate late minutes based on class schedule
                // For now, we'll set it to null
            }
            
            // Determine excuse status
            $excuse_status = 'N/A';
            if (isset($additional_data['excuse_letter'])) {
                $excuse_status = $additional_data['excuse_letter']['status'];
            }
            
            // Prepare log data
            $log_data = [
                'attendance_id' => $attendance_data['attendance_id'] ?? null,
                'student_id' => $attendance_data['student_id'],
                'student_name' => $student['full_name'] ?? 'Unknown Student',
                'student_id_number' => $student['student_num'] ?? '',
                'section_id' => $class['section_id'] ?? null,
                'section_name' => $class['section_name'] ?? 'Unknown Section',
                'program' => $class['program'] ?? null,
                'subject_id' => $class['subject_id'] ?? null,
                'subject_name' => $class['subject_name'] ?? 'Unknown Subject',
                'subject_code' => $class['subject_code'] ?? '',
                'teacher_id' => $class['teacher_id'] ?? '',
                'teacher_name' => $class['teacher_name'] ?? 'Unknown Teacher',
                'date' => $attendance_data['date'],
                'time_in' => $attendance_data['time_in'] ?? null,
                'time_out' => $attendance_data['time_out'] ?? null,
                'attendance_status' => $attendance_data['status'],
                'excuse_status' => $excuse_status,
                'late_minutes' => $late_minutes,
                'notes' => $attendance_data['notes'] ?? null,
                'remarks' => $additional_data['remarks'] ?? null,
                'recorded_by' => $user_data['user_id'],
                'recorded_by_name' => $user_data['full_name'] ?? $user_data['name'] ?? 'Unknown User',
                'ip_address' => $CI->input->ip_address(),
                'device_info' => $CI->input->user_agent(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Suppress DB debug so duplicate key won't render a fatal page
            $prevDebug = $CI->db->db_debug;
            $CI->db->db_debug = false;
            $CI->db->insert('attendance_logs', $log_data);
            $dbErr = $CI->db->error();
            // Restore previous debug setting
            $CI->db->db_debug = $prevDebug;
            if (!empty($dbErr['code'])) {
                // Gracefully ignore duplicate key errors
                if ((int)$dbErr['code'] === 1062) {
                    log_message('debug', 'attendance_logs duplicate ignored: ' . ($dbErr['message'] ?? ''));
                    return false;
                }
                // Re-throw other DB errors
                throw new Exception('DB error inserting attendance_logs: ' . $dbErr['message']);
            }
            return $CI->db->insert_id();
            
        } catch (Exception $e) {
            log_message('error', 'Attendance log failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('get_attendance_logs')) {
    /**
     * Get attendance logs with filtering and pagination
     * 
     * @param array $filters Filter criteria
     * @param int $limit Number of records to return
     * @param int $offset Starting offset
     * @return array Array of attendance logs
     */
    function get_attendance_logs($filters = [], $limit = 50, $offset = 0) {
        $CI =& get_instance();
        
        $CI->db->select('attendance_logs.*, sections.program')
            ->from('attendance_logs')
            ->join('sections', 'attendance_logs.section_id = sections.section_id', 'left');
        
        $CI->db->order_by('attendance_logs.created_at', 'DESC');
        
        // Apply filters
        if (isset($filters['student_id'])) {
            $CI->db->where('attendance_logs.student_id', $filters['student_id']);
        }
        if (isset($filters['teacher_id'])) {
            $CI->db->where('attendance_logs.teacher_id', $filters['teacher_id']);
        }
        if (isset($filters['section_id'])) {
            $CI->db->where('attendance_logs.section_id', $filters['section_id']);
        }
        if (isset($filters['subject_id'])) {
            $CI->db->where('attendance_logs.subject_id', $filters['subject_id']);
        }
        if (isset($filters['attendance_status'])) {
            $CI->db->where('attendance_logs.attendance_status', $filters['attendance_status']);
        }
        if (isset($filters['excuse_status'])) {
            $CI->db->where('attendance_logs.excuse_status', $filters['excuse_status']);
        }
        if (isset($filters['date_from'])) {
            $CI->db->where('attendance_logs.date >=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $CI->db->where('attendance_logs.date <=', $filters['date_to']);
        }
        if (isset($filters['recorded_by'])) {
            $CI->db->where('attendance_logs.recorded_by', $filters['recorded_by']);
        }
        if (isset($filters['program'])) {
            // Map program shortcuts to full names
            $program_map = [
                'BSIT' => 'Bachelor of Science in Information Technology',
                'BSIS' => 'Bachelor of Science in Information Systems',
                'BSCS' => 'Bachelor of Science in Computer Science',
                'ACT'  => 'Associate in Computer Technology',
            ];
            
            $program = $filters['program'];
            if (isset($program_map[$program])) {
                $program = $program_map[$program];
            }
            
            $CI->db->where('sections.program', $program);
        }
        
        // Apply pagination
        if ($limit > 0) {
            $CI->db->limit($limit, $offset);
        }
        
        return $CI->db->get()->result_array();
    }
}

if (!function_exists('export_attendance_logs_csv')) {
    /**
     * Export attendance logs to CSV format
     * 
     * @param array $filters Filter criteria
     * @return string CSV content
     */
    function export_attendance_logs_csv($filters = []) {
        $CI =& get_instance();
        
        $logs = get_attendance_logs($filters, 0, 0); // Get all logs
        
        $csv = "Log ID,Student Name,Student ID,Section,Program,Subject,Teacher,Date,Time In,Time Out,Attendance Status,Excuse Status,Late Minutes,Notes,Recorded By,IP Address,Created At\n";
        
        foreach ($logs as $log) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $log['log_id'],
                $log['student_name'],
                $log['student_id_number'],
                $log['section_name'],
                $log['program'] ?? '',
                $log['subject_name'],
                $log['teacher_name'],
                $log['date'],
                $log['time_in'] ?? '',
                $log['time_out'] ?? '',
                $log['attendance_status'],
                $log['excuse_status'],
                $log['late_minutes'] ?? '',
                str_replace('"', '""', $log['notes'] ?? ''),
                $log['recorded_by_name'],
                $log['ip_address'],
                $log['created_at']
            );
        }
        
        return $csv;
    }
} 