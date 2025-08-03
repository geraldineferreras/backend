<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

class AttendanceLogController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['auth', 'attendance_log']);
        $this->load->library('Token_lib');
    }

    /**
     * Get attendance logs with filtering and pagination
     * Endpoint: GET /api/attendance-logs
     */
    public function logs_get()
    {
        $user_data = require_admin($this);
        if (!$user_data) return;

        try {
            // Get query parameters
            $student_id = $this->input->get('student_id');
            $teacher_id = $this->input->get('teacher_id');
            $section_id = $this->input->get('section_id');
            $subject_id = $this->input->get('subject_id');
            $attendance_status = $this->input->get('attendance_status');
            $excuse_status = $this->input->get('excuse_status');
            $date_from = $this->input->get('date_from');
            $date_to = $this->input->get('date_to');
            $recorded_by = $this->input->get('recorded_by');
            $program = $this->input->get('program');
            $limit = (int)($this->input->get('limit') ?: 50);
            $offset = (int)($this->input->get('offset') ?: 0);

            // Validate limit and offset
            if ($limit > 100) $limit = 100;
            if ($limit < 1) $limit = 50;
            if ($offset < 0) $offset = 0;

            // Build filters
            $filters = [];
            if ($student_id) $filters['student_id'] = $student_id;
            if ($teacher_id) $filters['teacher_id'] = $teacher_id;
            if ($section_id) $filters['section_id'] = $section_id;
            if ($subject_id) $filters['subject_id'] = $subject_id;
            if ($attendance_status) $filters['attendance_status'] = $attendance_status;
            if ($excuse_status) $filters['excuse_status'] = $excuse_status;
            if ($date_from) $filters['date_from'] = $date_from;
            if ($date_to) $filters['date_to'] = $date_to;
            if ($recorded_by) $filters['recorded_by'] = $recorded_by;
            if ($program) $filters['program'] = $program;

            // Get total count for pagination
            $total_records = $this->db->from('attendance_logs')->count_all_results();

            // Get attendance logs
            $logs = get_attendance_logs($filters, $limit, $offset);

            $this->send_success([
                'logs' => $logs,
                'pagination' => [
                    'total' => $total_records,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total_records
                ],
                'filters' => $filters
            ], 'Attendance logs retrieved successfully');

        } catch (Exception $e) {
            $this->send_error('Failed to retrieve attendance logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get specific attendance log by ID
     * Endpoint: GET /api/attendance-logs/{log_id}
     */
    public function log_get($log_id = null)
    {
        $user_data = require_admin($this);
        if (!$user_data) return;

        if (!$log_id) {
            $this->send_error('Log ID is required', 400);
            return;
        }

        try {
            $log = $this->db->where('log_id', $log_id)
                ->get('attendance_logs')
                ->row_array();

            if (!$log) {
                $this->send_error('Attendance log not found', 404);
                return;
            }

            $this->send_success($log, 'Attendance log retrieved successfully');

        } catch (Exception $e) {
            $this->send_error('Failed to retrieve attendance log: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export attendance logs to CSV
     * Endpoint: GET /api/attendance-logs/export
     */
    public function export_get()
    {
        $user_data = require_admin($this);
        if (!$user_data) return;

        try {
            // Get query parameters (same as logs_get)
            $student_id = $this->input->get('student_id');
            $teacher_id = $this->input->get('teacher_id');
            $section_id = $this->input->get('section_id');
            $subject_id = $this->input->get('subject_id');
            $attendance_status = $this->input->get('attendance_status');
            $excuse_status = $this->input->get('excuse_status');
            $date_from = $this->input->get('date_from');
            $date_to = $this->input->get('date_to');
            $recorded_by = $this->input->get('recorded_by');
            $program = $this->input->get('program');

            // Build filters
            $filters = [];
            if ($student_id) $filters['student_id'] = $student_id;
            if ($teacher_id) $filters['teacher_id'] = $teacher_id;
            if ($section_id) $filters['section_id'] = $section_id;
            if ($subject_id) $filters['subject_id'] = $subject_id;
            if ($attendance_status) $filters['attendance_status'] = $attendance_status;
            if ($excuse_status) $filters['excuse_status'] = $excuse_status;
            if ($date_from) $filters['date_from'] = $date_from;
            if ($date_to) $filters['date_to'] = $date_to;
            if ($recorded_by) $filters['recorded_by'] = $recorded_by;
            if ($program) $filters['program'] = $program;

            // Generate CSV
            $csv_content = export_attendance_logs_csv($filters);

            // Set headers for CSV download
            $filename = 'attendance_logs_' . date('Y-m-d_H-i-s') . '.csv';
            
            $this->output
                ->set_content_type('text/csv')
                ->set_header('Content-Disposition: attachment; filename="' . $filename . '"')
                ->set_output($csv_content);

        } catch (Exception $e) {
            $this->send_error('Failed to export attendance logs: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get attendance statistics
     * Endpoint: GET /api/attendance-logs/statistics
     */
    public function statistics_get()
    {
        $user_data = require_admin($this);
        if (!$user_data) return;

        try {
            // Get date range filters
            $date_from = $this->input->get('date_from');
            $date_to = $this->input->get('date_to');

            $this->db->select('
                COUNT(*) as total_logs,
                COUNT(DISTINCT student_id) as unique_students,
                COUNT(DISTINCT teacher_id) as unique_teachers,
                COUNT(DISTINCT section_id) as unique_sections,
                COUNT(DISTINCT subject_id) as unique_subjects
            ')
            ->from('attendance_logs');

            if ($date_from) {
                $this->db->where('date >=', $date_from);
            }
            if ($date_to) {
                $this->db->where('date <=', $date_to);
            }

            $overall_stats = $this->db->get()->row_array();

            // Get attendance status breakdown
            $status_breakdown = $this->db->select('attendance_status, COUNT(*) as count')
                ->from('attendance_logs')
                ->group_by('attendance_status')
                ->get()->result_array();

            // Get excuse status breakdown
            $excuse_breakdown = $this->db->select('excuse_status, COUNT(*) as count')
                ->from('attendance_logs')
                ->group_by('excuse_status')
                ->get()->result_array();

            // Get top sections by attendance count
            $top_sections = $this->db->select('section_name, COUNT(*) as count')
                ->from('attendance_logs')
                ->group_by('section_name')
                ->order_by('count', 'DESC')
                ->limit(10)
                ->get()->result_array();

            // Get top subjects by attendance count
            $top_subjects = $this->db->select('subject_name, COUNT(*) as count')
                ->from('attendance_logs')
                ->group_by('subject_name')
                ->order_by('count', 'DESC')
                ->limit(10)
                ->get()->result_array();

            $this->send_success([
                'overall' => $overall_stats,
                'status_breakdown' => $status_breakdown,
                'excuse_breakdown' => $excuse_breakdown,
                'top_sections' => $top_sections,
                'top_subjects' => $top_subjects
            ], 'Attendance statistics retrieved successfully');

        } catch (Exception $e) {
            $this->send_error('Failed to retrieve attendance statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get available filter options
     * Endpoint: GET /api/attendance-logs/filters
     */
    public function filters_get()
    {
        $user_data = require_admin($this);
        if (!$user_data) return;

        try {
            // Get unique sections
            $sections = $this->db->select('section_id, section_name')
                ->distinct()
                ->from('attendance_logs')
                ->where('section_name IS NOT NULL')
                ->where('section_name !=', '')
                ->order_by('section_name', 'ASC')
                ->get()->result_array();

            // Get unique subjects
            $subjects = $this->db->select('subject_id, subject_name, subject_code')
                ->distinct()
                ->from('attendance_logs')
                ->where('subject_name IS NOT NULL')
                ->where('subject_name !=', '')
                ->order_by('subject_name', 'ASC')
                ->get()->result_array();

            // Get unique teachers
            $teachers = $this->db->select('teacher_id, teacher_name')
                ->distinct()
                ->from('attendance_logs')
                ->where('teacher_name IS NOT NULL')
                ->where('teacher_name !=', '')
                ->order_by('teacher_name', 'ASC')
                ->get()->result_array();

            // Get unique attendance statuses
            $attendance_statuses = $this->db->select('attendance_status')
                ->distinct()
                ->from('attendance_logs')
                ->where('attendance_status IS NOT NULL')
                ->order_by('attendance_status', 'ASC')
                ->get()->result_array();

            // Get unique excuse statuses
            $excuse_statuses = $this->db->select('excuse_status')
                ->distinct()
                ->from('attendance_logs')
                ->where('excuse_status IS NOT NULL')
                ->order_by('excuse_status', 'ASC')
                ->get()->result_array();

            // Get available programs
            $programs = [
                ['program_id' => 'all', 'program_name' => 'All Programs'],
                ['program_id' => 'BSIT', 'program_name' => 'Bachelor of Science in Information Technology'],
                ['program_id' => 'BSIS', 'program_name' => 'Bachelor of Science in Information Systems'],
                ['program_id' => 'BSCS', 'program_name' => 'Bachelor of Science in Computer Science'],
                ['program_id' => 'ACT', 'program_name' => 'Associate in Computer Technology']
            ];

            $this->send_success([
                'sections' => $sections,
                'subjects' => $subjects,
                'teachers' => $teachers,
                'attendance_statuses' => $attendance_statuses,
                'excuse_statuses' => $excuse_statuses,
                'programs' => $programs
            ], 'Filter options retrieved successfully');

        } catch (Exception $e) {
            $this->send_error('Failed to retrieve filter options: ' . $e->getMessage(), 500);
        }
    }
} 