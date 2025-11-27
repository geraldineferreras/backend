<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class AdminController extends BaseController {
    public function __construct() {
        parent::__construct();
        $this->load->model(['Section_model', 'User_model', 'Program_model', 'AcademicYear_model']);
        $this->load->helper(['response', 'auth', 'notification', 'utility', 'email_notification', 'audit']);
        $this->load->library('Token_lib');
        // CORS headers are already handled by BaseController
    }

    /**
     * List pending teacher/student registrations waiting for approval
     */
    public function registrations_pending_get() {
        $user_data = require_role($this, ['admin', 'chairperson']);
        if (!$user_data) {
            return;
        }

        $role_filter = strtolower($this->input->get('role'));
        if ($role_filter && !in_array($role_filter, ['teacher', 'student'])) {
            return json_response(false, 'Role filter must be teacher or student', null, 400);
        }

        $registrations = $this->User_model->get_pending_registrations($role_filter ?: null);
        $payload = array_map(function($user) {
            $program = $user['program'] ?? null;
            return [
                'user_id' => $user['user_id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'program' => $program,
                'submitted_at' => $user['created_at'] ?? null
            ];
        }, $registrations);

        return json_response(true, 'Pending registrations retrieved successfully', $payload);
    }

    /**
     * Approve a pending teacher/student registration
     */
    public function registrations_approve_post($user_id = null) {
        $user_data = require_role($this, ['admin', 'chairperson']);
        if (!$user_data) {
            return;
        }

        if (empty($user_id)) {
            return json_response(false, 'User ID is required', null, 400);
        }

        $user = $this->User_model->get_by_id($user_id);
        if (!$user || !in_array($user['role'], ['teacher', 'student'])) {
            return json_response(false, 'Pending registration not found for this user', null, 404);
        }

        if ($user['status'] !== 'pending_approval') {
            return json_response(false, 'User is not awaiting approval', null, 409);
        }

        if (($user['email_verification_status'] ?? 'pending') !== 'verified') {
            return json_response(false, 'User must verify their email before approval', null, 409);
        }

        $temporary_password = $this->generate_temporary_password();
        $update_data = [
            'status' => 'active',
            'password' => password_hash($temporary_password, PASSWORD_BCRYPT)
        ];

        $success = $this->User_model->update($user_id, $update_data);
        if (!$success) {
            return json_response(false, 'Failed to approve registration', null, 500);
        }

        $login_url = function_exists('get_scms_login_url')
            ? get_scms_login_url()
            : (getenv('APP_LOGIN_URL') ?: site_url('auth/login'));

        if (function_exists('send_registration_approved_email')) {
            try {
                send_registration_approved_email($user['full_name'], $user['email'], $user['role'], $temporary_password, $login_url);
            } catch (Exception $e) {
                log_message('error', 'Failed to send approval email: ' . $e->getMessage());
            }
        }

        try {
            $title = 'Account approved';
            $message = "Hello {$user['full_name']}, your {$user['role']} account has been approved. "
                . "Use the temporary password sent to your email to sign in and update your password.";
            create_system_notification($user_id, $title, $message, false);
        } catch (Exception $e) {
            log_message('error', 'Failed to create approval notification: ' . $e->getMessage());
        }

        return json_response(true, 'Registration approved successfully');
    }

    /**
     * Reject a pending teacher/student registration
     */
    public function registrations_reject_post($user_id = null) {
        $user_data = require_role($this, ['admin', 'chairperson']);
        if (!$user_data) {
            return;
        }

        if (empty($user_id)) {
            return json_response(false, 'User ID is required', null, 400);
        }

        $user = $this->User_model->get_by_id($user_id);
        if (!$user || !in_array($user['role'], ['teacher', 'student'])) {
            return json_response(false, 'Pending registration not found for this user', null, 404);
        }

        if ($user['status'] !== 'pending_approval') {
            return json_response(false, 'User is not awaiting approval', null, 409);
        }

        $data = json_decode(file_get_contents('php://input'));
        $reason = null;
        if (json_last_error() === JSON_ERROR_NONE && isset($data->reason)) {
            $reason = trim($data->reason);
        }

        if (function_exists('send_registration_rejected_email')) {
            try {
                send_registration_rejected_email($user['full_name'], $user['email'], $user['role']);
            } catch (Exception $e) {
                log_message('error', 'Failed to send rejection email: ' . $e->getMessage());
            }
        }

        $deleted = $this->User_model->delete($user_id);
        if (!$deleted) {
            return json_response(false, 'Failed to remove rejected registration', null, 500);
        }

        log_audit_event(
            'REGISTRATION REJECTED',
            'ADMINISTRATION',
            "Registration for {$user['full_name']} ({$user['email']}) was rejected and removed.",
            [
                'user_id' => $user_id,
                'role' => $user['role'],
                'reason' => $reason,
                'action_by' => $user_data['user_id']
            ]
        );

        return json_response(true, 'Registration rejected successfully');
    }

    /**
     * Get class join request logs (View-only)
     * GET /api/admin/class-join-request-logs
     * 
     * Returns all class join attempts with details:
     * - Student Name, Section, Status (Regular/Irregular)
     * - Class/Subject Name, Teacher Name
     * - Result: Approved/Rejected/Pending
     * - Date & Time, Who approved/rejected
     * 
     * Admin sees ALL programs
     * Chairperson sees only their program
     */
    public function class_join_request_logs_get() {
        $user_data = require_role($this, ['admin', 'chairperson']);
        if (!$user_data) {
            return;
        }

        try {
            // Get query parameters for pagination
            $page = $this->input->get('page') ?: 1;
            $limit = $this->input->get('limit') ?: 50;
            $offset = ($page - 1) * $limit;

            // Get filters
            $status_filter = $this->input->get('status'); // pending, active, rejected, etc.
            $program_filter = $this->input->get('program'); // optional program filter

            // Build count query first - reset query builder first
            $this->db->reset_query();
            $this->db->select('COUNT(*) as total', false);
            $this->db->from('classroom_enrollments ce');
            // Use join with false to prevent escaping, allowing COLLATE to work
            $this->db->join('users student', 'ce.student_id = student.user_id COLLATE utf8mb4_unicode_ci', 'inner', false);
            $this->db->join('sections student_section', 'student.section_id = student_section.section_id', 'left');
            $this->db->join('classrooms c', 'ce.classroom_id = c.id', 'inner');
            $this->db->join('subjects subject', 'c.subject_id = subject.id', 'left');
            $this->db->join('sections class_section', 'c.section_id = class_section.section_id', 'left');
            $this->db->join('users teacher', 'c.teacher_id = teacher.user_id', 'left');
            
            // Apply filters for count query
            if ($user_data['role'] === 'chairperson' && !empty($user_data['program'])) {
                $this->db->where('class_section.program', $user_data['program']);
            }
            if (!empty($program_filter) && $user_data['role'] === 'admin') {
                $this->db->where('class_section.program', $program_filter);
            }
            if (!empty($status_filter)) {
                $this->db->where('ce.status', $status_filter);
            }
            
            $count_result = $this->db->get()->row_array();
            $total_count = $count_result['total'] ?? 0;

            // Reset query builder for main query
            $this->db->reset_query();

            // Build main query with all selects
            $this->db->select('
                ce.id as enrollment_id,
                ce.student_id,
                ce.status as request_status,
                ce.enrolled_at as requested_at,
                ce.updated_at as last_updated_at,
                ce.created_at,
                student.full_name as student_name,
                student.student_num,
                student.student_type,
                student_section.section_name as student_section,
                student_section.program as student_program,
                c.class_code,
                c.id as classroom_id,
                subject.subject_name as class_subject_name,
                class_section.section_name as class_section_name,
                class_section.program as class_program,
                teacher.user_id as teacher_id,
                teacher.full_name as teacher_name,
                teacher.email as teacher_email
            ');
            $this->db->from('classroom_enrollments ce');
            // Use join with false to prevent escaping, allowing COLLATE to work
            $this->db->join('users student', 'ce.student_id = student.user_id COLLATE utf8mb4_unicode_ci', 'inner', false);
            $this->db->join('sections student_section', 'student.section_id = student_section.section_id', 'left');
            $this->db->join('classrooms c', 'ce.classroom_id = c.id', 'inner');
            $this->db->join('subjects subject', 'c.subject_id = subject.id', 'left');
            $this->db->join('sections class_section', 'c.section_id = class_section.section_id', 'left');
            $this->db->join('users teacher', 'c.teacher_id = teacher.user_id', 'left');
            
            // Apply filters for main query
            if ($user_data['role'] === 'chairperson' && !empty($user_data['program'])) {
                $this->db->where('class_section.program', $user_data['program']);
            }
            if (!empty($program_filter) && $user_data['role'] === 'admin') {
                $this->db->where('class_section.program', $program_filter);
            }
            if (!empty($status_filter)) {
                $this->db->where('ce.status', $status_filter);
            }

            // Order by most recent first
            $this->db->order_by('ce.enrolled_at', 'DESC');

            // Apply pagination
            if ($limit > 0) {
                $this->db->limit($limit, $offset);
            }

            $logs = $this->db->get()->result_array();

            // Format the response data
            $formatted_logs = array_map(function($log) {
                // Determine result based on status
                $result = 'Pending';
                if ($log['request_status'] === 'active') {
                    $result = 'Approved';
                } elseif ($log['request_status'] === 'rejected') {
                    $result = 'Rejected';
                } elseif (in_array(strtolower($log['request_status'] ?? ''), ['inactive', 'dropped'])) {
                    $result = ucfirst(strtolower($log['request_status']));
                }

                // Determine student status
                $student_status = 'Regular';
                if (!empty($log['student_type']) && strtolower($log['student_type']) === 'irregular') {
                    $student_status = 'Irregular';
                }

                // Format date/time
                $requested_at = $log['requested_at'] 
                    ? date('Y-m-d H:i:s', strtotime($log['requested_at'])) 
                    : null;
                $requested_at_iso = $log['requested_at'] 
                    ? date('c', strtotime($log['requested_at'])) 
                    : null;
                $last_updated_at = $log['last_updated_at'] 
                    ? date('Y-m-d H:i:s', strtotime($log['last_updated_at'])) 
                    : null;
                $last_updated_at_iso = $log['last_updated_at'] 
                    ? date('c', strtotime($log['last_updated_at'])) 
                    : null;

                // Determine who approved/rejected
                $action_by = null;
                if ($result === 'Approved' || $result === 'Rejected') {
                    $action_by = $log['teacher_name'] ?? 'Unknown Teacher';
                }

                return [
                    'enrollment_id' => $log['enrollment_id'],
                    'student' => [
                        'student_id' => $log['student_id'],
                        'student_name' => $log['student_name'],
                        'student_num' => $log['student_num'],
                        'student_section' => $log['student_section'],
                        'student_status' => $student_status,
                        'student_program' => $log['student_program']
                    ],
                    'class' => [
                        'classroom_id' => $log['classroom_id'],
                        'class_code' => $log['class_code'],
                        'subject_name' => $log['class_subject_name'],
                        'section_name' => $log['class_section_name'],
                        'program' => $log['class_program']
                    ],
                    'teacher' => [
                        'teacher_id' => $log['teacher_id'],
                        'teacher_name' => $log['teacher_name'],
                        'teacher_email' => $log['teacher_email']
                    ],
                    'request' => [
                        'status' => $log['request_status'],
                        'result' => $result,
                        'requested_at' => $requested_at,
                        'requested_at_iso' => $requested_at_iso,
                        'last_updated_at' => $last_updated_at,
                        'last_updated_at_iso' => $last_updated_at_iso,
                        'action_by' => $action_by
                    ]
                ];
            }, $logs);

            // Prepare response
            $response_data = [
                'logs' => $formatted_logs,
                'pagination' => [
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'total' => (int)$total_count,
                    'total_pages' => $limit > 0 ? ceil($total_count / $limit) : 1
                ]
            ];

            return json_response(true, 'Class join request logs retrieved successfully', $response_data);

        } catch (Exception $e) {
            log_message('error', 'Get class join request logs error: ' . $e->getMessage());
            return json_response(false, 'Failed to retrieve class join request logs: ' . $e->getMessage(), null, 500);
        }
    }

    // List all sections
    public function sections_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $sections = $this->Section_model->get_all();
        
        // Format sections for frontend
        $formatted_sections = array_map(function($section) {
            return [
                'id' => $section['section_id'],
                'name' => $section['section_name'],
                'section_name' => $section['section_name'],
                'program' => $section['program'],
                'course' => $section['program'],
                'year_level' => $section['year_level'],
                'year' => $section['year_level'],
                'adviser_id' => $section['adviser_id'],
                'semester' => $section['semester'],
                'academic_year' => $section['academic_year'],
                'enrolled_count' => (int)$section['enrolled_count'],
                'student_count' => (int)$section['enrolled_count'],
                'enrolled' => (int)$section['enrolled_count'],
                'adviserDetails' => [
                    'name' => $section['adviser_name'] ?: 'No Adviser',
                    'email' => $section['adviser_email'] ?: 'No Email',
                    'profile_picture' => $section['adviser_profile_pic'] ?: null
                ],
                'adviser_details' => [
                    'name' => $section['adviser_name'] ?: 'No Adviser',
                    'email' => $section['adviser_email'] ?: 'No Email',
                    'profile_picture' => $section['adviser_profile_pic'] ?: null
                ]
            ];
        }, $sections);
        
        return json_response(true, 'Sections retrieved successfully', $formatted_sections);
    }

    // Get a specific section
    public function section_get($section_id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $section = $this->Section_model->get_by_id($section_id);
        if (!$section) {
            return json_response(false, 'Section not found', null, 404);
        }
        return json_response(true, 'Section retrieved successfully', $section);
    }

    // Create a new section
    public function sections_post() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $data = json_decode(file_get_contents('php://input'));
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $required = ['section_name', 'program', 'year_level', 'semester', 'academic_year'];
        foreach ($required as $field) {
            if (empty($data->$field)) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        
        // Handle adviser_id - make it optional (allow null, empty, or 0)
        $adviser_id = null;
        if (isset($data->adviser_id) && !empty($data->adviser_id) && $data->adviser_id != '0' && $data->adviser_id != 0) {
            $adviser_id = $data->adviser_id;
            // Validate adviser exists and is a teacher
            $adviser = $this->User_model->get_by_id($adviser_id);
            if (!$adviser || $adviser['role'] !== 'teacher') {
                return json_response(false, 'Invalid adviser: must be an active teacher', null, 400);
            }
        }
        
        // Validate semester
        if (!in_array($data->semester, ['1st', '2nd'])) {
            return json_response(false, 'Invalid semester: must be "1st" or "2nd"', null, 400);
        }
        
        // Validate year_level is numeric
        if (!is_numeric($data->year_level) || $data->year_level < 1 || $data->year_level > 4) {
            return json_response(false, 'Invalid year_level: must be a number between 1 and 4', null, 400);
        }
        
        // Standardize program name to shortcut format
        $program_shortcut = $this->standardize_program_name($data->program);
        if (!$program_shortcut) {
            return json_response(false, 'Invalid program. Must be BSIT, BSIS, BSCS, or ACT', null, 400);
        }
        
        $insert_data = [
            'section_name' => $data->section_name,
            'program' => $program_shortcut, // Always save as shortcut
            'year_level' => $data->year_level,
            'adviser_id' => $adviser_id, // Can be null
            'semester' => $data->semester,
            'academic_year' => $data->academic_year
        ];
        $section_id = $this->Section_model->insert($insert_data);
        if ($section_id) {
            // Send system notification to adviser about new section assignment (only if adviser is assigned)
            if ($adviser_id) {
                $this->send_section_assignment_notification($adviser_id, $data->section_name, $data->program, $data->year_level);
            }
            
            // Assign students if provided
            $assigned_students = [];
            if (isset($data->student_ids) && is_array($data->student_ids) && !empty($data->student_ids)) {
                $assigned_students = $this->Section_model->assign_students_to_section($section_id, $data->student_ids);
                
                // Send system notifications to assigned students
                $this->send_student_section_assignment_notifications($data->student_ids, $data->section_name, $data->program, $data->year_level);
            }
            
            $response_data = [
                'section_id' => $section_id,
                'assigned_students_count' => count($assigned_students),
                'assigned_students' => $assigned_students
            ];
            
            return json_response(true, 'Section created successfully', $response_data, 201);
        } else {
            return json_response(false, 'Failed to create section', null, 500);
        }
    }

    // Update section
    public function sections_put($section_id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $data = json_decode(file_get_contents('php://input'));
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        
        // Check if section exists
        $existing_section = $this->Section_model->get_by_id($section_id);
        if (!$existing_section) {
            return json_response(false, 'Section not found', null, 404);
        }
        
        // Validate all required fields are present
        $required = ['section_name', 'program', 'year_level', 'semester', 'academic_year'];
        foreach ($required as $field) {
            if (empty($data->$field)) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        
        // Handle adviser_id - make it optional (allow null, empty, or 0)
        $adviser_id = null;
        if (isset($data->adviser_id) && !empty($data->adviser_id) && $data->adviser_id != '0' && $data->adviser_id != 0) {
            $adviser_id = $data->adviser_id;
            // Validate adviser exists and is a teacher
            $adviser = $this->User_model->get_by_id($adviser_id);
            if (!$adviser || $adviser['role'] !== 'teacher') {
                return json_response(false, 'Invalid adviser: must be an active teacher', null, 400);
            }
        }
        
        // Validate semester
        if (!in_array($data->semester, ['1st', '2nd'])) {
            return json_response(false, 'Invalid semester: must be "1st" or "2nd"', null, 400);
        }
        
        // Validate year_level is numeric
        if (!is_numeric($data->year_level) || $data->year_level < 1 || $data->year_level > 4) {
            return json_response(false, 'Invalid year_level: must be a number between 1 and 4', null, 400);
        }
        
        // Standardize program name to shortcut format
        $program_shortcut = $this->standardize_program_name($data->program);
        if (!$program_shortcut) {
            return json_response(false, 'Invalid program. Must be BSIT, BSIS, BSCS, or ACT', null, 400);
        }
        
        $update_data = [
            'section_name' => $data->section_name,
            'program' => $program_shortcut, // Always save as shortcut
            'year_level' => $data->year_level,
            'adviser_id' => $adviser_id, // Can be null
            'semester' => $data->semester,
            'academic_year' => $data->academic_year
        ];
        
        $success = $this->Section_model->update($section_id, $update_data);
        if ($success) {
            // Handle student assignments if provided
            $assigned_students = [];
            if (isset($data->student_ids) && is_array($data->student_ids)) {
                // First, remove all current students from this section
                $current_students = $this->Section_model->get_students($section_id);
                $current_student_ids = array_column($current_students, 'user_id');
                if (!empty($current_student_ids)) {
                    $this->Section_model->remove_students_from_section($section_id, $current_student_ids);
                }
                
                // Then assign the new students
                if (!empty($data->student_ids)) {
                    $assigned_students = $this->Section_model->assign_students_to_section($section_id, $data->student_ids);
                }
            }
            
            $response_data = [
                'section_updated' => true,
                'assigned_students_count' => count($assigned_students),
                'assigned_students' => $assigned_students
            ];
            
            return json_response(true, 'Section updated successfully', $response_data);
        } else {
            return json_response(false, 'Failed to update section', null, 500);
        }
    }

    // Delete section
    public function sections_delete($section_id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        // Check if section exists
        $existing_section = $this->Section_model->get_by_id($section_id);
        if (!$existing_section) {
            return json_response(false, 'Section not found', null, 404);
        }
        
        // Support force delete via query param (?force=true|1)
        $force = $this->input->get('force');
        $force_delete = ($force === 'true' || $force === '1');

        // Prevent delete if students are linked, unless forced
        if ($this->Section_model->is_section_linked($section_id)) {
            if (!$force_delete) {
                return json_response(false, 'Cannot delete section: students are still assigned', null, 400);
            }

            // Force path: unassign all students from this section before delete
            $current_students = $this->Section_model->get_students($section_id);
            $current_student_ids = array_column($current_students, 'user_id');
            if (!empty($current_student_ids)) {
                $this->Section_model->remove_students_from_section($section_id, $current_student_ids);
            }
        }
        $success = $this->Section_model->delete($section_id);
        if ($success) {
            return json_response(true, 'Section deleted successfully');
        } else {
            return json_response(false, 'Failed to delete section', null, 500);
        }
    }

    // Get students in a section
    public function section_students_get($section_id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        // Check if section exists
        $existing_section = $this->Section_model->get_by_id($section_id);
        if (!$existing_section) {
            return json_response(false, 'Section not found', null, 404);
        }
        
        $students = $this->Section_model->get_students($section_id);
        $adviser = [
            'adviser_id' => $existing_section['adviser_id'],
            'adviser_name' => $existing_section['adviser_name'],
            'adviser_email' => $existing_section['adviser_email']
        ];
        $response = [
            'adviser' => $adviser,
            'students' => $students
        ];
        return json_response(true, 'Students and adviser retrieved successfully', $response);
    }

    // Get available advisers (teachers) for section assignment
    public function advisers_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $advisers = $this->Section_model->get_available_advisers();
        return json_response(true, 'Available advisers retrieved successfully', $advisers);
    }

    // Get all programs
    public function programs_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;

        $include_archived = filter_var($this->input->get('include_archived'), FILTER_VALIDATE_BOOLEAN);
        $with_usage = filter_var($this->input->get('with_usage'), FILTER_VALIDATE_BOOLEAN);
        $status = $this->input->get('status');
        $search = $this->input->get('search');

        $options = [
            'include_archived' => $include_archived,
            'with_usage' => $with_usage
        ];

        if (!empty($status)) {
            $options['status'] = strtolower($status);
        }

        if (!empty($search)) {
            $options['search'] = $search;
        }

        $programs = $this->Program_model->get_all($options);
        return json_response(true, 'Programs retrieved successfully', $programs);
    }

    // Create a new program
    public function programs_post() {
        $user_data = require_admin($this);
        if (!$user_data) return;

        $payload = json_decode(file_get_contents('php://input'));
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON body', null, 400);
        }

        $code = isset($payload->code) ? $this->format_program_code($payload->code) : null;
        $name = isset($payload->name) ? trim($payload->name) : null;
        $description = isset($payload->description) ? trim($payload->description) : null;
        if ($description === '') {
            $description = null;
        }

        if (!$code && $name) {
            $code = $this->format_program_code($name);
        }

        if (!$name && $code) {
            $name = $code;
        }

        if (empty($code) || empty($name)) {
            return json_response(false, 'Program code or name is required', null, 422);
        }

        if ($this->Program_model->exists_by_code($code)) {
            return json_response(false, 'Program code already exists', null, 409);
        }

        $program = $this->Program_model->create([
            'code' => $code,
            'name' => $name,
            'description' => $description
        ]);

        if (!$program) {
            return json_response(false, 'Failed to create program', null, 500);
        }

        log_audit_event(
            'PROGRAM CREATED',
            'ADMINISTRATION',
            "Program {$program['code']} created by {$user_data['user_id']}",
            [
                'program' => $program,
                'action_by' => $user_data['user_id']
            ]
        );

        return json_response(true, 'Program created successfully', $program, 201);
    }

    // Update program
    public function programs_put($program_id = null) {
        $user_data = require_admin($this);
        if (!$user_data) return;

        if (empty($program_id)) {
            return json_response(false, 'Program ID is required', null, 400);
        }

        $program = $this->Program_model->get_by_id($program_id);
        if (!$program) {
            return json_response(false, 'Program not found', null, 404);
        }

        $payload = json_decode(file_get_contents('php://input'));
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON body', null, 400);
        }

        $updates = [];

        if (isset($payload->code)) {
            $new_code = $this->format_program_code($payload->code);
            if (empty($new_code)) {
                return json_response(false, 'Program code cannot be empty', null, 422);
            }
            if ($this->Program_model->exists_by_code($new_code, $program_id)) {
                return json_response(false, 'Program code already exists', null, 409);
            }
            $updates['code'] = $new_code;
        }

        if (isset($payload->name)) {
            $new_name = trim($payload->name);
            if (empty($new_name)) {
                return json_response(false, 'Program name cannot be empty', null, 422);
            }
            $updates['name'] = $new_name;
        }

        if (isset($payload->description)) {
            $desc = trim($payload->description);
            $updates['description'] = $desc === '' ? null : $desc;
        }

        if (empty($updates)) {
            return json_response(false, 'No changes provided', null, 400);
        }

        $updated_program = $this->Program_model->update($program_id, $updates);
        if (!$updated_program) {
            return json_response(false, 'Failed to update program', null, 500);
        }

        log_audit_event(
            'PROGRAM UPDATED',
            'ADMINISTRATION',
            "Program {$program['code']} updated by {$user_data['user_id']}",
            [
                'program_before' => $program,
                'program_after' => $updated_program,
                'action_by' => $user_data['user_id']
            ]
        );

        return json_response(true, 'Program updated successfully', $updated_program);
    }

    // Archive program
    public function programs_archive_post($program_id = null) {
        $user_data = require_admin($this);
        if (!$user_data) return;

        if (empty($program_id)) {
            return json_response(false, 'Program ID is required', null, 400);
        }

        $program = $this->Program_model->get_by_id($program_id);
        if (!$program) {
            return json_response(false, 'Program not found', null, 404);
        }

        if ($program['status'] === 'archived') {
            return json_response(false, 'Program is already archived', null, 409);
        }

        $archived = $this->Program_model->archive($program_id);
        if (!$archived) {
            return json_response(false, 'Failed to archive program', null, 500);
        }

        log_audit_event(
            'PROGRAM ARCHIVED',
            'ADMINISTRATION',
            "Program {$program['code']} archived by {$user_data['user_id']}",
            [
                'program' => $archived,
                'action_by' => $user_data['user_id']
            ]
        );

        return json_response(true, 'Program archived successfully', $archived);
    }

    // Restore program
    public function programs_restore_post($program_id = null) {
        $user_data = require_admin($this);
        if (!$user_data) return;

        if (empty($program_id)) {
            return json_response(false, 'Program ID is required', null, 400);
        }

        $program = $this->Program_model->get_by_id($program_id);
        if (!$program) {
            return json_response(false, 'Program not found', null, 404);
        }

        if ($program['status'] === 'active') {
            return json_response(false, 'Program is already active', null, 409);
        }

        $restored = $this->Program_model->restore($program_id);
        if (!$restored) {
            return json_response(false, 'Failed to restore program', null, 500);
        }

        log_audit_event(
            'PROGRAM RESTORED',
            'ADMINISTRATION',
            "Program {$program['code']} restored by {$user_data['user_id']}",
            [
                'program' => $restored,
                'action_by' => $user_data['user_id']
            ]
        );

        return json_response(true, 'Program restored successfully', $restored);
    }

    // Get all year levels
    public function year_levels_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $year_levels = $this->Section_model->get_year_levels();
        return json_response(true, 'Year levels retrieved successfully', $year_levels);
    }

    // Get all semesters
    public function semesters_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $semesters = $this->Section_model->get_semesters();
        return json_response(true, 'Semesters retrieved successfully', $semesters);
    }

    // Get all academic years
    public function academic_years_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $academic_years = $this->Section_model->get_academic_years();
        return json_response(true, 'Academic years retrieved successfully', $academic_years);
    }

    // Get sections by year level
    public function sections_by_year_get($year_level = null) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        // If year_level is not provided in URL, check query parameter
        if (!$year_level) {
            $year_level = $this->input->get('year_level');
        }
        
        // URL decode the year_level parameter
        if ($year_level) {
            $year_level = urldecode($year_level);
        }
        
        $sections = $this->Section_model->get_by_year_level($year_level);
        $message = $year_level && $year_level !== 'all' 
            ? "Sections for $year_level retrieved successfully" 
            : 'All sections retrieved successfully';
        
        return json_response(true, $message, $sections);
    }

    // Debug endpoint to see all sections and their year levels
    public function sections_debug_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        $all_sections = $this->Section_model->get_all();
        $year_levels = $this->Section_model->get_year_levels();
        
        $debug_data = [
            'all_sections' => $all_sections,
            'available_year_levels' => $year_levels,
            'total_sections' => count($all_sections)
        ];
        
        return json_response(true, 'Debug information retrieved', $debug_data);
    }

    // Assign students to a section
    public function assign_students_post($section_id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        // Check if section exists
        $existing_section = $this->Section_model->get_by_id($section_id);
        if (!$existing_section) {
            return json_response(false, 'Section not found', null, 404);
        }
        
        $data = json_decode(file_get_contents('php://input'));
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        
        if (!isset($data->student_ids) || !is_array($data->student_ids)) {
            return json_response(false, 'student_ids array is required', null, 400);
        }
        
        $assigned_students = $this->Section_model->assign_students_to_section($section_id, $data->student_ids);
        
        return json_response(true, 'Students assigned successfully', [
            'assigned_students_count' => count($assigned_students),
            'assigned_students' => $assigned_students
        ]);
    }

    // Remove students from a section
    public function remove_students_post($section_id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        // Check if section exists
        $existing_section = $this->Section_model->get_by_id($section_id);
        if (!$existing_section) {
            return json_response(false, 'Section not found', null, 404);
        }
        
        $data = json_decode(file_get_contents('php://input'));
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        
        if (!isset($data->student_ids) || !is_array($data->student_ids)) {
            return json_response(false, 'student_ids array is required', null, 400);
        }
        
        $removed_students = $this->Section_model->remove_students_from_section($section_id, $data->student_ids);
        
        return json_response(true, 'Students removed successfully', [
            'removed_students_count' => count($removed_students),
            'removed_students' => $removed_students
        ]);
    }

    // Get available students (not assigned to any section)
    public function available_students_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        $students = $this->Section_model->get_available_students();
        return json_response(true, 'Available students retrieved successfully', $students);
    }

    // Get all students with their section assignments
    public function all_students_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        $students = $this->Section_model->get_all_students_with_sections();
        return json_response(true, 'All students retrieved successfully', $students);
    }

    // Get sections by semester and academic year
    public function sections_by_semester_year_get($semester = null, $academic_year = null) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        // If parameters are not provided in URL, check query parameters
        if (!$semester) {
            $semester = $this->input->get('semester');
        }
        if (!$academic_year) {
            $academic_year = $this->input->get('academic_year');
        }
        
        // URL decode the parameters
        if ($semester) {
            $semester = urldecode($semester);
        }
        if ($academic_year) {
            $academic_year = urldecode($academic_year);
        }
        
        $sections = $this->Section_model->get_by_semester_and_year($semester, $academic_year);
        
        $message = 'Sections retrieved successfully';
        if ($semester && $semester !== 'all') {
            $message = "Sections for $semester semester";
            if ($academic_year && $academic_year !== 'all') {
                $message .= " $academic_year";
            }
            $message .= " retrieved successfully";
        } elseif ($academic_year && $academic_year !== 'all') {
            $message = "Sections for academic year $academic_year retrieved successfully";
        }
        
        return json_response(true, $message, $sections);
    }

    // Get all sections grouped by program
    public function sections_by_program_get($program = null) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        // Allow program from URL or query string
        if (!$program) {
            $program = $this->input->get('program');
        }
        if (!$program) {
            return json_response(false, 'Program is required', null, 400);
        }
        $program = urldecode($program);
        // Map shortcuts to full program names
        $shortcut_map = [
            'BSIT' => 'Bachelor of Science in Information Technology',
            'BSIS' => 'Bachelor of Science in Information Systems',
            'BSCS' => 'Bachelor of Science in Computer Science',
            'ACT'  => 'Associate in Computer Technology',
        ];
        if (isset($shortcut_map[$program])) {
            $program = $shortcut_map[$program];
        }
        $sections = $this->Section_model->get_by_program($program);
        
        // Format sections for frontend
        $formatted_sections = array_map(function($section) {
            return [
                'id' => $section['section_id'],
                'name' => $section['section_name'],
                'section_name' => $section['section_name'],
                'program' => $section['program'],
                'course' => $section['program'],
                'year_level' => $section['year_level'],
                'year' => $section['year_level'],
                'adviser_id' => $section['adviser_id'],
                'semester' => $section['semester'],
                'academic_year' => $section['academic_year'],
                'enrolled_count' => (int)$section['enrolled_count'],
                'student_count' => (int)$section['enrolled_count'],
                'enrolled' => (int)$section['enrolled_count'],
                'adviserDetails' => [
                    'name' => $section['adviser_name'] ?: 'No Adviser',
                    'email' => $section['adviser_email'] ?: 'No Email',
                    'profile_picture' => $section['adviser_profile_pic'] ?: null
                ],
                'adviser_details' => [
                    'name' => $section['adviser_name'] ?: 'No Adviser',
                    'email' => $section['adviser_email'] ?: 'No Email',
                    'profile_picture' => $section['adviser_profile_pic'] ?: null
                ]
            ];
        }, $sections);
        
        return json_response(true, 'Sections for program retrieved successfully', $formatted_sections);
    }

    // Get sections grouped by year level for a specific program
    public function sections_by_program_year_get($program = null) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        // Allow program from URL or query string
        if (!$program) {
            $program = $this->input->get('program');
        }
        if (!$program) {
            return json_response(false, 'Program is required', null, 400);
        }
        
        $program = urldecode($program);
        
        // Map shortcuts to full program names
        $shortcut_map = [
            'BSIT' => 'Bachelor of Science in Information Technology',
            'BSIS' => 'Bachelor of Science in Information Systems',
            'BSCS' => 'Bachelor of Science in Computer Science',
            'ACT'  => 'Associate in Computer Technology',
        ];
        
        if (isset($shortcut_map[$program])) {
            $program = $shortcut_map[$program];
        }
        
        $grouped_sections = $this->Section_model->get_by_program_grouped_by_year($program);
        
        // Format response with program info
        $response = [
            'program' => $program,
            'program_short' => array_search($program, $shortcut_map) ?: $program,
            'year_levels' => $grouped_sections,
            'total_year_levels' => count($grouped_sections),
            'total_sections' => array_sum(array_map('count', $grouped_sections))
        ];
        
        return json_response(true, 'Sections grouped by year level retrieved successfully', $response);
    }

    // Get sections by program and specific year level
    public function sections_by_program_year_specific_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        // Get parameters from query string
        $program = $this->input->get('program');
        $year_level = $this->input->get('year_level');
        
        if (!$program) {
            return json_response(false, 'Program is required', null, 400);
        }
        
        $program = urldecode($program);
        $year_level = $year_level ? urldecode($year_level) : null;
        
        // Map shortcuts to full program names
        $shortcut_map = [
            'BSIT' => 'Bachelor of Science in Information Technology',
            'BSIS' => 'Bachelor of Science in Information Systems',
            'BSCS' => 'Bachelor of Science in Computer Science',
            'ACT'  => 'Associate in Computer Technology',
        ];
        
        if (isset($shortcut_map[$program])) {
            $program = $shortcut_map[$program];
        }
        
        $sections = $this->Section_model->get_by_program_and_year_level($program, $year_level);
        
        // Format sections for frontend
        $formatted_sections = array_map(function($section) {
            return [
                'id' => $section['section_id'],
                'name' => $section['section_name'],
                'section_name' => $section['section_name'],
                'program' => $section['program'],
                'course' => $section['program'],
                'year_level' => $section['year_level'],
                'year' => $section['year_level'],
                'adviser_id' => $section['adviser_id'],
                'semester' => $section['semester'],
                'academic_year' => $section['academic_year'],
                'enrolled_count' => (int)$section['enrolled_count'],
                'student_count' => (int)$section['enrolled_count'],
                'enrolled' => (int)$section['enrolled_count'],
                'adviserDetails' => [
                    'name' => $section['adviser_name'] ?: 'No Adviser',
                    'email' => $section['adviser_email'] ?: 'No Email',
                    'profile_picture' => $section['adviser_profile_pic'] ?: null
                ],
                'adviser_details' => [
                    'name' => $section['adviser_name'] ?: 'No Adviser',
                    'email' => $section['adviser_email'] ?: 'No Email',
                    'profile_picture' => $section['adviser_profile_pic'] ?: null
                ]
            ];
        }, $sections);
        
        // Format response
        $response = [
            'program' => $program,
            'program_short' => array_search($program, $shortcut_map) ?: $program,
            'year_level' => $year_level ?: 'all',
            'sections' => $formatted_sections,
            'total_sections' => count($formatted_sections)
        ];
        
        $message = "Sections for $program";
        if ($year_level && $year_level !== 'all') {
            $message .= " $year_level year";
        } else {
            $message .= " all years";
        }
        $message .= " retrieved successfully";
        
        return json_response(true, $message, $response);
    }

    // --- Classes (Subject Offerings) Management ---
    public function classes_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $this->load->model('Class_model');
        $filters = $this->input->get();
        if (!empty($filters)) {
            $classes = $this->Class_model->get_filtered($filters);
        } else {
            $classes = $this->Class_model->get_all();
        }
        return json_response(true, 'Classes retrieved successfully', $classes);
    }

    public function class_get($id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $this->load->model('Class_model');
        $class = $this->Class_model->get_by_id($id);
        if (!$class) {
            return json_response(false, 'Class not found', null, 404);
        }
        return json_response(true, 'Class retrieved successfully', $class);
    }

    public function classes_post() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $this->load->model('Class_model');
        $this->load->model('Subject_model');
        $this->load->model('Section_model');
        $this->load->model('User_model');
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $required = ['subject_id', 'teacher_id', 'section_id', 'semester', 'school_year'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        $id = $this->Class_model->insert($data);
        if ($id) {
            // Send notification to teacher about new subject assignment
            $this->send_teacher_subject_assignment_notification($data['teacher_id'], $data['subject_id'], $data['section_id'], $data['semester'], $data['school_year']);
            
            return json_response(true, 'Class created successfully', ['class_id' => $id], 201);
        } else {
            return json_response(false, 'Failed to create class', null, 500);
        }
    }

    public function classes_put($id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $this->load->model('Class_model');
        $this->load->model('Subject_model');
        $this->load->model('Section_model');
        $this->load->model('User_model');
        
        // Get current class data before update
        $current_class = $this->Class_model->get_by_id($id);
        if (!$current_class) {
            return json_response(false, 'Class not found', null, 404);
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $success = $this->Class_model->update($id, $data);
        if ($success) {
            // Send notification if teacher assignment changed
            if (isset($data['teacher_id']) && $data['teacher_id'] !== $current_class['teacher_id']) {
                $this->send_teacher_subject_assignment_notification($data['teacher_id'], $data['subject_id'] ?? $current_class['subject_id'], $data['section_id'] ?? $current_class['section_id'], $data['semester'] ?? $current_class['semester'], $data['school_year'] ?? $current_class['school_year']);
            }
            
            return json_response(true, 'Class updated successfully');
        } else {
            return json_response(false, 'Failed to update class', null, 500);
        }
    }

    public function classes_delete($id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $this->load->model('Class_model');
        $this->load->model('Subject_model');
        $this->load->model('Section_model');
        $this->load->model('User_model');
        
        // Get class data before deletion
        $class = $this->Class_model->get_by_id($id);
        if (!$class) {
            return json_response(false, 'Class not found', null, 404);
        }
        
        try {
            // Start transaction to ensure data consistency
            $this->db->trans_start();
            
            // Clean up related data before deleting the class
            
            // 1. Delete attendance records for this class
            $this->db->where('class_id', $id)->delete('attendance');
            
            // 2. Delete excuse letters for this class
            $this->db->where('class_id', $id)->delete('excuse_letters');
            
            // 3. Find all classrooms that correspond to this class (subject offering)
            // and clean up their related data
            $classrooms = $this->db->select('id, class_code')
                ->from('classrooms')
                ->where('subject_id', $class['subject_id'])
                ->where('section_id', $class['section_id'])
                ->where('teacher_id', $class['teacher_id'])
                ->get()->result_array();
            
            foreach ($classrooms as $classroom) {
                $class_code = $classroom['class_code'];
                
                // 3a. Update class_tasks to remove this class_code from class_codes JSON
                $this->db->query("
                    UPDATE class_tasks 
                    SET class_codes = JSON_REMOVE(class_codes, JSON_UNQUOTE(JSON_SEARCH(class_codes, 'one', ?)))
                    WHERE JSON_CONTAINS(class_codes, ?)
                ", [$class_code, json_encode($class_code)]);
                
                // 3b. Delete task submissions for this class_code
                $this->db->where('class_code', $class_code)->delete('task_submissions');
                
                // 3c. Delete task student assignments for this class_code
                $this->db->where('class_code', $class_code)->delete('task_student_assignments');
                
                // 3d. Delete classroom stream posts for this class_code
                $this->db->where('class_code', $class_code)->delete('classroom_stream');
                
                // 3e. Delete classroom enrollments for this classroom
                $this->db->where('classroom_id', $classroom['id'])->delete('classroom_enrollments');
                
                // 3f. Delete the classroom itself
                $this->db->where('id', $classroom['id'])->delete('classrooms');
            }
            
            // 4. Now delete the class (subject offering)
            $success = $this->Class_model->delete($id);
            
            if ($success) {
                // Commit transaction
                $this->db->trans_complete();
                
                // Send notification to teacher about subject assignment removal
                $this->send_teacher_subject_removal_notification($class['teacher_id'], $class['subject_id'], $class['section_id'], $class['semester'], $class['school_year']);
                
                return json_response(true, 'Class deleted successfully');
            } else {
                // Rollback transaction
                $this->db->trans_rollback();
                return json_response(false, 'Failed to delete class', null, 500);
            }
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->db->trans_rollback();
            log_message('error', 'Error deleting class: ' . $e->getMessage());
            return json_response(false, 'Error deleting class: ' . $e->getMessage(), null, 500);
        }
    }

    // --- Subject Management ---
    public function subjects_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $this->load->model('Subject_model');
        
        try {
            $subjects = $this->Subject_model->get_all();
            return json_response(true, 'Subjects retrieved successfully', $subjects);
        } catch (Exception $e) {
            log_message('error', 'Error fetching subjects: ' . $e->getMessage());
            $db_error = $this->db->error();
            $error_message = 'Failed to retrieve subjects';
            if (!empty($db_error['message'])) {
                $error_message .= ': ' . $db_error['message'];
            } else {
                $error_message .= ': ' . $e->getMessage();
            }
            return json_response(false, $error_message, null, 500);
        }
    }

    public function subjects_post() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $this->load->model('Subject_model');
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $required = ['subject_code', 'subject_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        
        // Automatically set created_by from authenticated user's JWT token
        $data['created_by'] = $user_data['user_id'];
        
        try {
            $id = $this->Subject_model->insert($data);
            if ($id) {
                return json_response(true, 'Subject created successfully', ['id' => $id], 201);
            } else {
                // Get database error if available
                $db_error = $this->db->error();
                $error_message = 'Failed to create subject';
                if (!empty($db_error['message'])) {
                    $error_message .= ': ' . $db_error['message'];
                    log_message('error', 'Subject creation failed: ' . $db_error['message']);
                }
                return json_response(false, $error_message, null, 500);
            }
        } catch (Exception $e) {
            log_message('error', 'Subject creation exception: ' . $e->getMessage());
            return json_response(false, 'Failed to create subject: ' . $e->getMessage(), null, 500);
        }
    }

    public function subjects_put($id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $this->load->model('Subject_model');
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $success = $this->Subject_model->update($id, $data);
        if ($success) {
            return json_response(true, 'Subject updated successfully');
        } else {
            return json_response(false, 'Failed to update subject', null, 500);
        }
    }

    public function subjects_delete($id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $this->load->model('Subject_model');
        $success = $this->Subject_model->delete($id);
        if ($success) {
            return json_response(true, 'Subject deleted successfully');
        } else {
            return json_response(false, 'Failed to delete subject', null, 500);
        }
    }

    // --- Audit Log Management ---
    public function audit_logs_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        $this->load->model('Audit_model');
        
        // Get query parameters
        $page = $this->input->get('page') ?: 1;
        $limit = $this->input->get('limit') ?: 50;
        $offset = ($page - 1) * $limit;
        
        // Get filters
        $filters = [
            'user_id' => $this->input->get('user_id'),
            'user_role' => $this->input->get('user_role'),
            'action' => $this->input->get('action'),
            'module' => $this->input->get('module'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to')
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        $logs = $this->Audit_model->get_audit_logs($filters, $limit, $offset);
        $total = $this->Audit_model->get_audit_logs($filters, 0, 0);
        $total_count = count($total);
        
        $response = [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_records' => $total_count,
                'total_pages' => ceil($total_count / $limit)
            ]
        ];
        
        return json_response(true, 'Audit logs retrieved successfully', $response);
    }

    public function audit_log_get($log_id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        $this->load->model('Audit_model');
        $log = $this->Audit_model->get_audit_log($log_id);
        
        if ($log) {
            return json_response(true, 'Audit log retrieved successfully', $log);
        } else {
            return json_response(false, 'Audit log not found', null, 404);
        }
    }

    public function audit_logs_modules_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        $this->load->model('Audit_model');
        $modules = $this->Audit_model->get_modules();
        
        return json_response(true, 'Modules retrieved successfully', $modules);
    }

    public function audit_logs_roles_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        $this->load->model('Audit_model');
        $roles = $this->Audit_model->get_roles();
        
        return json_response(true, 'Roles retrieved successfully', $roles);
    }

    // --- Role-specific Audit Log Endpoints ---
    public function audit_logs_admin_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        $this->load->model('Audit_model');
        
        // Get query parameters
        $page = $this->input->get('page') ?: 1;
        $limit = $this->input->get('limit') ?: 50;
        $offset = ($page - 1) * $limit;
        
        // Get filters
        $filters = [
            'user_role' => 'admin',
            'action' => $this->input->get('action'),
            'module' => $this->input->get('module'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to')
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        $logs = $this->Audit_model->get_audit_logs($filters, $limit, $offset);
        $total = $this->Audit_model->get_audit_logs($filters, 0, 0);
        $total_count = count($total);
        
        $response = [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_records' => $total_count,
                'total_pages' => ceil($total_count / $limit)
            ],
            'filter' => 'admin'
        ];
        
        return json_response(true, 'Admin audit logs retrieved successfully', $response);
    }

    public function audit_logs_teacher_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        $this->load->model('Audit_model');
        
        // Get query parameters
        $page = $this->input->get('page') ?: 1;
        $limit = $this->input->get('limit') ?: 50;
        $offset = ($page - 1) * $limit;
        
        // Get filters
        $filters = [
            'user_role' => 'teacher',
            'action' => $this->input->get('action'),
            'module' => $this->input->get('module'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to')
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        $logs = $this->Audit_model->get_audit_logs($filters, $limit, $offset);
        $total = $this->Audit_model->get_audit_logs($filters, 0, 0);
        $total_count = count($total);
        
        $response = [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_records' => $total_count,
                'total_pages' => ceil($total_count / $limit)
            ],
            'filter' => 'teacher'
        ];
        
        return json_response(true, 'Teacher audit logs retrieved successfully', $response);
    }

    public function audit_logs_student_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        $this->load->model('Audit_model');
        
        // Get query parameters
        $page = $this->input->get('page') ?: 1;
        $limit = $this->input->get('limit') ?: 50;
        $offset = ($page - 1) * $limit;
        
        // Get filters
        $filters = [
            'user_role' => 'student',
            'action' => $this->input->get('action'),
            'module' => $this->input->get('module'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to')
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        $logs = $this->Audit_model->get_audit_logs($filters, $limit, $offset);
        $total = $this->Audit_model->get_audit_logs($filters, 0, 0);
        $total_count = count($total);
        
        $response = [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total_records' => $total_count,
                'total_pages' => ceil($total_count / $limit)
            ],
            'filter' => 'student'
        ];
        
        return json_response(true, 'Student audit logs retrieved successfully', $response);
    }

    public function audit_logs_export_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        
        $this->load->model('Audit_model');
        
        // Get filters
        $filters = [
            'user_id' => $this->input->get('user_id'),
            'user_role' => $this->input->get('user_role'),
            'action' => $this->input->get('action'),
            'module' => $this->input->get('module'),
            'date_from' => $this->input->get('date_from'),
            'date_to' => $this->input->get('date_to')
        ];
        
        // Remove empty filters
        $filters = array_filter($filters, function($value) {
            return $value !== null && $value !== '';
        });
        
        $csv_content = $this->Audit_model->export_csv($filters);
        
        // Set headers for CSV download
        $filename = 'audit_logs_' . date('Y-m-d_H-i-s') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo $csv_content;
        exit;
    }

    /**
     * Send system notification to adviser about new section assignment
     */
    private function send_section_assignment_notification($adviser_id, $section_name, $program, $year_level) {
        try {
            $adviser = $this->User_model->get_by_id($adviser_id);
            if (!$adviser) {
                log_message('error', "Adviser not found for section assignment notification: {$adviser_id}");
                return;
            }

            $title = "New Section Assignment";
            $message = "Hello {$adviser['full_name']}, you have been assigned as the adviser for ";
            $message .= "Section {$section_name} ({$program} - Year {$year_level}). ";
            $message .= "You can now manage this section and its students.";

            create_system_notification($adviser_id, $title, $message, false);

            log_message('info', "Section assignment notification sent to adviser {$adviser_id} for section {$section_name}");

        } catch (Exception $e) {
            log_message('error', "Failed to send section assignment notification: " . $e->getMessage());
        }
    }

    /**
     * Send system notifications to students about section assignment
     */
    private function send_student_section_assignment_notifications($student_ids, $section_name, $program, $year_level) {
        try {
            foreach ($student_ids as $student_id) {
                $student = $this->User_model->get_by_id($student_id);
                if (!$student) {
                    log_message('error', "Student not found for section assignment notification: {$student_id}");
                    continue;
                }

                $title = "Section Assignment";
                $message = "Hello {$student['full_name']}, you have been assigned to ";
                $message .= "Section {$section_name} ({$program} - Year {$year_level}). ";
                $message .= "Your section adviser will contact you with further details.";

                create_system_notification($student_id, $title, $message, false);

                log_message('info', "Section assignment notification sent to student {$student_id} for section {$section_name}");
            }

        } catch (Exception $e) {
            log_message('error', "Failed to send student section assignment notifications: " . $e->getMessage());
        }
    }

    /**
     * Send notification to teacher about new subject assignment
     */
    private function send_teacher_subject_assignment_notification($teacher_id, $subject_id, $section_id, $semester, $school_year) {
        try {
            $this->load->helper('notification');
            
            // Get teacher details
            $teacher = $this->User_model->get_by_id($teacher_id);
            if (!$teacher) {
                log_message('error', "Teacher not found for subject assignment notification: {$teacher_id}");
                return;
            }
            
            // Get subject details
            $subject = $this->Subject_model->get_by_id($subject_id);
            if (!$subject) {
                log_message('error', "Subject not found for assignment notification: {$subject_id}");
                return;
            }
            
            // Get section details
            $section = $this->Section_model->get_by_id($section_id);
            if (!$section) {
                log_message('error', "Section not found for assignment notification: {$section_id}");
                return;
            }
            
            $title = "New Subject Assignment";
            $message = "Hello {$teacher['full_name']}, you have been assigned to teach ";
            $message .= "{$subject['subject_name']} ({$subject['subject_code']}) ";
            $message .= "for Section {$section['section_name']} ";
            $message .= "({$semester} Semester, {$school_year}). ";
            $message .= "You can now create classrooms and manage this subject offering.";
            
            create_system_notification($teacher_id, $title, $message, false);
            
            log_message('info', "Subject assignment notification sent to teacher {$teacher_id} for subject {$subject['subject_name']}");
            
        } catch (Exception $e) {
            log_message('error', "Failed to send teacher subject assignment notification: " . $e->getMessage());
        }
    }
    
    /**
     * Send notification to teacher about subject assignment removal
     */
    private function send_teacher_subject_removal_notification($teacher_id, $subject_id, $section_id, $semester, $school_year) {
        try {
            $this->load->helper('notification');
            
            // Get teacher details
            $teacher = $this->User_model->get_by_id($teacher_id);
            if (!$teacher) {
                log_message('error', "Teacher not found for subject removal notification: {$teacher_id}");
                return;
            }
            
            // Get subject details
            $subject = $this->Subject_model->get_by_id($subject_id);
            if (!$subject) {
                log_message('error', "Subject not found for removal notification: {$subject_id}");
                return;
            }
            
            // Get section details
            $section = $this->Section_model->get_by_id($section_id);
            if (!$section) {
                log_message('error', "Section not found for removal notification: {$section_id}");
                return;
            }
            
            $title = "Subject Assignment Removed";
            $message = "Hello {$teacher['full_name']}, your assignment for ";
            $message .= "{$subject['subject_name']} ({$subject['subject_code']}) ";
            $message .= "Section {$section['section_name']} ";
            $message .= "({$semester} Semester, {$school_year}) ";
            $message .= "has been removed by the administrator.";
            
            create_system_notification($teacher_id, $title, $message, false);
            
            log_message('info', "Subject removal notification sent to teacher {$teacher_id} for subject {$subject['subject_name']}");
            
        } catch (Exception $e) {
            log_message('error', "Failed to send teacher subject removal notification: " . $e->getMessage());
        }
    }

    /**
     * Send system notification for maintenance or system updates
     */
    private function send_system_maintenance_notification($user_ids, $title, $message, $is_urgent = false) {
        try {
            foreach ($user_ids as $user_id) {
                create_system_notification($user_id, $title, $message, $is_urgent);
            }

            log_message('info', "System maintenance notification sent to " . count($user_ids) . " users");

        } catch (Exception $e) {
            log_message('error', "Failed to send system maintenance notification: " . $e->getMessage());
        }
    }

    /**
     * Get dashboard statistics for admin
     * Endpoint: GET /api/admin/dashboard/stats
     */
    public function dashboard_stats_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;

        try {
            // Get user counts by role
            $user_counts = $this->db->select('role, COUNT(*) as count')
                ->from('users')
                ->where('status', 'active')
                ->group_by('role')
                ->get()
                ->result_array();

            $user_stats = [
                'total_users' => 0,
                'students' => 0,
                'teachers' => 0,
                'admins' => 0
            ];

            foreach ($user_counts as $count) {
                $user_stats['total_users'] += $count['count'];
                switch ($count['role']) {
                    case 'student':
                        $user_stats['students'] = $count['count'];
                        break;
                    case 'teacher':
                        $user_stats['teachers'] = $count['count'];
                        break;
                    case 'admin':
                        $user_stats['admins'] = $count['count'];
                        break;
                }
            }

            // Get section counts
            $section_stats = $this->db->select('
                COUNT(*) as total_sections,
                COUNT(CASE WHEN adviser_id IS NOT NULL THEN 1 END) as sections_with_advisers,
                COUNT(CASE WHEN adviser_id IS NULL THEN 1 END) as sections_without_advisers
            ')
            ->from('sections')
            ->get()
            ->row_array();

            // Get program counts
            $program_stats = $this->db->select('program, COUNT(*) as count')
                ->from('sections')
                ->group_by('program')
                ->get()
                ->result_array();

            // Get year level counts
            $year_stats = $this->db->select('year_level, COUNT(*) as count')
                ->from('sections')
                ->group_by('year_level')
                ->get()
                ->result_array();

            // Get semester counts
            $semester_stats = $this->db->select('semester, COUNT(*) as count')
                ->from('sections')
                ->group_by('semester')
                ->get()
                ->result_array();

            // Get academic year counts
            $academic_year_stats = $this->db->select('academic_year, COUNT(*) as count')
                ->from('sections')
                ->group_by('academic_year')
                ->get()
                ->result_array();

            // Get total enrolled students across all sections by counting students in sections
            $total_enrolled = $this->db->select('COUNT(*) as total_enrolled')
                ->from('users')
                ->where('role', 'student')
                ->where('status', 'active')
                ->where('section_id IS NOT NULL')
                ->get()
                ->row_array();

            $dashboard_stats = [
                'user_statistics' => $user_stats,
                'section_statistics' => [
                    'total_sections' => (int)$section_stats['total_sections'],
                    'sections_with_advisers' => (int)$section_stats['sections_with_advisers'],
                    'sections_without_advisers' => (int)$section_stats['sections_without_advisers'],
                    'total_enrolled_students' => (int)$total_enrolled['total_enrolled']
                ],
                'program_distribution' => $program_stats,
                'year_level_distribution' => $year_stats,
                'semester_distribution' => $semester_stats,
                'academic_year_distribution' => $academic_year_stats
            ];

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Dashboard statistics retrieved successfully',
                    'data' => $dashboard_stats
                ]));

        } catch (Exception $e) {
            log_message('error', 'Dashboard stats error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Failed to retrieve dashboard statistics: ' . $e->getMessage()
                ]));
        }
    }

    /**
     * Get user count summary for admin
     * Endpoint: GET /api/admin/users/count
     */
    public function users_count_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;

        try {
            // Get total user count
            $total_users = $this->db->where('status', 'active')->count_all_results('users');

            // Get user counts by role
            $user_counts = $this->db->select('role, COUNT(*) as count')
                ->from('users')
                ->where('status', 'active')
                ->group_by('role')
                ->get()
                ->result_array();

            // Get recent user registrations (last 30 days)
            $recent_users = $this->db->select('role, COUNT(*) as count')
                ->from('users')
                ->where('status', 'active')
                ->where('created_at >=', date('Y-m-d', strtotime('-30 days')))
                ->group_by('role')
                ->get()
                ->result_array();

            $user_summary = [
                'total_users' => $total_users,
                'by_role' => $user_counts,
                'recent_registrations' => $recent_users,
                'last_updated' => date('Y-m-d H:i:s')
            ];

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'User count summary retrieved successfully',
                    'data' => $user_summary
                ]));

        } catch (Exception $e) {
            log_message('error', 'User count error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Failed to retrieve user count summary: ' . $e->getMessage()
                ]));
        }
    }

    /**
     * Get section count summary for admin
     * Endpoint: GET /api/admin/sections/count
     */
    public function sections_count_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;

        try {
            // Get total section count
            $total_sections = $this->db->count_all_results('sections');

            // Get sections by program
            $sections_by_program = $this->db->select('program, COUNT(*) as count')
                ->from('sections')
                ->group_by('program')
                ->get()
                ->result_array();

            // Get sections by year level
            $sections_by_year = $this->db->select('year_level, COUNT(*) as count')
                ->from('sections')
                ->group_by('year_level')
                ->get()
                ->result_array();

            // Get sections by semester
            $sections_by_semester = $this->db->select('semester, COUNT(*) as count')
                ->from('sections')
                ->group_by('semester')
                ->get()
                ->result_array();

            // Get sections by academic year
            $sections_by_academic_year = $this->db->select('academic_year, COUNT(*) as count')
                ->from('sections')
                ->group_by('academic_year')
                ->get()
                ->result_array();

            // Get total enrolled students by counting students in sections
            $total_enrolled = $this->db->select('COUNT(*) as total_enrolled')
                ->from('users')
                ->where('role', 'student')
                ->where('status', 'active')
                ->where('section_id IS NOT NULL')
                ->get()
                ->row_array();

            // Get sections with/without advisers
            $adviser_stats = $this->db->select('
                COUNT(CASE WHEN adviser_id IS NOT NULL THEN 1 END) as with_advisers,
                COUNT(CASE WHEN adviser_id IS NULL THEN 1 END) as without_advisers
            ')
            ->from('sections')
            ->get()
            ->row_array();

            $section_summary = [
                'total_sections' => $total_sections,
                'total_enrolled_students' => (int)$total_enrolled['total_enrolled'],
                'by_program' => $sections_by_program,
                'by_year_level' => $sections_by_year,
                'by_semester' => $sections_by_semester,
                'by_academic_year' => $sections_by_academic_year,
                'adviser_coverage' => [
                    'with_advisers' => (int)$adviser_stats['with_advisers'],
                    'without_advisers' => (int)$adviser_stats['without_advisers']
                ],
                'last_updated' => date('Y-m-d H:i:s')
            ];

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Section count summary retrieved successfully',
                    'data' => $section_summary
                ]));

        } catch (Exception $e) {
            log_message('error', 'Section count error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Failed to retrieve section count summary: ' . $e->getMessage()
                ]));
        }
    }
    
    /**
     * Auto-create sections for all active programs and year levels (Admin only)
     * POST /api/admin/sections/auto-create
     * Creates sections for every active program, assigns random advisers, and pre-fills active academic year & semester
     */
    public function auto_create_sections_post() {
        $user_data = require_admin($this);
        if (!$user_data) return;

        try {
            $active_context = $this->get_active_academic_context();
            if (empty($active_context['academic_year']) || empty($active_context['semester'])) {
                $this->send_error('No active academic year/semester found. Activate an academic year first.', 409);
                return;
            }

            $sections_has_academic_year_id = $this->db->field_exists('academic_year_id', 'sections');

            $programs = $this->get_active_program_codes();
            if (empty($programs)) {
                $this->send_error('No active programs found. Please add programs in Program Management first.', 409);
                return;
            }

            $adviser_pool = $this->build_adviser_pool();
            $programs_without_advisers = [];
            $programs_with_fallback_advisers = [];
            $global_adviser_shortage = empty($adviser_pool['all']);

            $year_levels = [1, 2, 3, 4]; // Numeric values
            $sections = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];
            $target_section_total = count($programs) * count($year_levels) * count($sections);
            
            $created_count = 0;
            $existing_count = 0;
            $errors = [];
            $warnings = [];
            
            // Start transaction
            $this->db->trans_start();
            
            foreach ($programs as $program) {
                foreach ($year_levels as $year_level) {
                    foreach ($sections as $section_letter) {
                        $section_name = $program . ' ' . $year_level . $section_letter;
                        
                        // Check if section already exists for this academic year and semester
                        $this->db->where('section_name', $section_name);
                        $this->db->where('academic_year', $active_context['academic_year']);
                        $this->db->where('semester', $active_context['semester']);
                        $existing = $this->db->get('sections')->row_array();
                        
                        if (!$existing) {
                            $adviser_pick = $this->select_random_adviser($program, $adviser_pool);
                            if ($adviser_pick['strategy'] === 'none') {
                                $programs_without_advisers[$program] = true;
                            } elseif ($adviser_pick['strategy'] === 'global') {
                                $programs_with_fallback_advisers[$program] = true;
                            }

                            // Create section with active academic context
                            $section_data = [
                                'section_name' => $section_name,
                                'program' => $program,
                                'year_level' => $year_level,
                                'adviser_id' => $adviser_pick['adviser_id'], // Can be null
                                'semester' => $active_context['semester'],
                                'academic_year' => $active_context['academic_year']
                            ];

                            if ($sections_has_academic_year_id) {
                                $section_data['academic_year_id'] = $active_context['academic_year_id'];
                            }
                            
                            $this->db->insert('sections', $section_data);
                            
                            if ($this->db->affected_rows() > 0) {
                                $created_count++;
                            } else {
                                $db_error = $this->db->error();
                                $error_msg = "Failed to create section: $section_name";
                                if (!empty($db_error['message'])) {
                                    $error_msg .= " - " . $db_error['message'];
                                }
                                $errors[] = $error_msg;
                                log_message('error', $error_msg);
                            }
                        } else {
                            $existing_count++;
                        }
                    }
                }
            }
            
            // Complete transaction
            if ($this->db->trans_status() === FALSE) {
                $db_error = $this->db->error();
                $error_msg = 'Failed to create sections. Transaction rolled back.';
                if (!empty($db_error['message'])) {
                    $error_msg .= " Database error: " . $db_error['message'];
                }
                $this->db->trans_rollback();
                log_message('error', $error_msg);
                $this->send_error($error_msg, 500);
                return;
            }
            
            $this->db->trans_commit();
            
            $total_sections = $created_count + $existing_count;
            if ($created_count === 0) {
                $warnings[] = 'All sections for the target academic term already exist. No new sections were created.';
            }

            if ($global_adviser_shortage) {
                $warnings[] = 'No active teachers were found, so adviser assignment was skipped.';
            } elseif (!empty($programs_without_advisers)) {
                $warnings[] = 'Programs without available advisers: ' . implode(', ', array_keys($programs_without_advisers)) . '. Sections were created without advisers.';
            }

            if (!empty($programs_with_fallback_advisers)) {
                $warnings[] = 'Programs using cross-program advisers: ' . implode(', ', array_keys($programs_with_fallback_advisers)) . '.';
            }

            foreach ($warnings as &$warning) {
                $warning = trim($warning);
            }
            unset($warning);
            $warnings = array_values(array_filter(array_unique($warnings)));

            $target_term_label = sprintf(
                'A.Y. %s %s',
                $active_context['academic_year'],
                $active_context['semester']
            );

            $response_data = [
                'created_sections' => $created_count,
                'existing_sections' => $existing_count,
                'total_sections' => $total_sections,
                'academic_year' => $active_context['academic_year'],
                'semester' => $active_context['semester'],
                'target_term' => $target_term_label,
                'programs' => $programs,
                'year_levels' => $year_levels, // Now contains [1, 2, 3, 4]
                'sections_per_year' => count($sections),
                'target_section_total' => $target_section_total,
                'adviser_stats' => [
                    'teachers_considered' => count($adviser_pool['all']),
                    'programs_without_advisers' => array_keys($programs_without_advisers),
                    'programs_with_fallback_advisers' => array_keys($programs_with_fallback_advisers)
                ],
                'warnings' => $warnings,
                'errors' => $errors,
                'alert_level' => $created_count > 0 ? 'success' : 'warning',
                'summary' => sprintf(
                    'This generated up to %d sections (%d program(s) × %d year levels × %d sections) for %s. Each new section received a random adviser when available.',
                    $target_section_total,
                    count($programs),
                    count($year_levels),
                    count($sections),
                    $target_term_label
                )
            ];
            
            $message = $created_count > 0
                ? "Successfully created $created_count new sections and linked advisers where available. $existing_count sections already existed."
                : "No new sections were created; existing sections already cover {$target_term_label}.";
            if (!empty($warnings)) {
                $message .= ' Please review warnings.';
            }

            $this->send_success($response_data, $message);
            
        } catch (Exception $e) {
            $this->db->trans_rollback();
            $this->send_error('Failed to auto-create sections: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Normalize user input into a valid program code
     */
    private function standardize_program_name($program_name, $allow_archived = false) {
        if (empty($program_name)) {
            return false;
        }

        $program = $this->Program_model->normalize_program_input($program_name, $allow_archived);
        return $program ? $program['code'] : false;
    }

    /**
     * Format program code strings (uppercased, whitespace stripped)
     */
    private function format_program_code($code) {
        if ($code === null) {
            return null;
        }

        $formatted = strtoupper(trim($code));
        $formatted = preg_replace('/\s+/', '', $formatted);
        return $formatted ?: null;
    }

    private function generate_temporary_password($length = 12) {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789@$!?';
        $password = '';
        $maxIndex = strlen($alphabet) - 1;

        try {
            for ($i = 0; $i < $length; $i++) {
                $password .= $alphabet[random_int(0, $maxIndex)];
            }
        } catch (Exception $e) {
            for ($i = 0; $i < $length; $i++) {
                $password .= $alphabet[mt_rand(0, $maxIndex)];
            }
        }

        return $password;
    }

    private function get_active_program_codes() {
        $program_rows = $this->Program_model->get_all([
            'status' => 'active',
            'include_archived' => false
        ]);

        if (empty($program_rows)) {
            return [];
        }

        $codes = [];
        foreach ($program_rows as $program) {
            $code = strtoupper($program['code'] ?? '');
            if (!empty($code)) {
                $codes[$code] = true;
            }
        }

        return array_keys($codes);
    }

    private function build_adviser_pool() {
        $pool = [
            'by_program' => [],
            'all' => []
        ];

        $teachers = $this->db->select('user_id, program')
            ->from('users')
            ->where('role', 'teacher')
            ->where('status', 'active')
            ->get()
            ->result_array();

        foreach ($teachers as $teacher) {
            $pool['all'][] = $teacher['user_id'];

            $program_code = strtoupper($teacher['program'] ?? '');
            if (!empty($program_code)) {
                if (!isset($pool['by_program'][$program_code])) {
                    $pool['by_program'][$program_code] = [];
                }
                $pool['by_program'][$program_code][] = $teacher['user_id'];
            }
        }

        return $pool;
    }

    private function select_random_adviser($program_code, array $pool) {
        $result = [
            'adviser_id' => null,
            'strategy' => 'none'
        ];

        $normalized_program = strtoupper($program_code);

        if (!empty($pool['by_program'][$normalized_program])) {
            $choices = $pool['by_program'][$normalized_program];
            $result['adviser_id'] = $choices[array_rand($choices)];
            $result['strategy'] = 'program';
            return $result;
        }

        if (!empty($pool['all'])) {
            $result['adviser_id'] = $pool['all'][array_rand($pool['all'])];
            $result['strategy'] = 'global';
            return $result;
        }

        return $result;
    }

    private function get_active_academic_context() {
        try {
            $active_year = $this->AcademicYear_model->get_active_year();
        } catch (Exception $e) {
            log_message('error', 'Failed to fetch active academic year: ' . $e->getMessage());
            $active_year = null;
        }

        if (!$active_year) {
            return [
                'academic_year' => null,
                'academic_year_id' => null,
                'semester' => null
            ];
        }

        return [
            'academic_year' => $active_year['name'] ?? null,
            'academic_year_id' => $active_year['id'] ?? null,
            'semester' => $this->determine_active_semester_label($active_year)
        ];
    }

    private function determine_active_semester_label(array $year) {
        foreach (['current_semester', 'active_semester'] as $key) {
            if (!empty($year[$key])) {
                return $year[$key];
            }
        }

        $today = strtotime(date('Y-m-d'));
        $sem1_start = !empty($year['sem1_start_date']) ? strtotime($year['sem1_start_date']) : null;
        $sem1_end = !empty($year['sem1_end_date']) ? strtotime($year['sem1_end_date']) : null;
        $sem2_start = !empty($year['sem2_start_date']) ? strtotime($year['sem2_start_date']) : null;
        $sem2_end = !empty($year['sem2_end_date']) ? strtotime($year['sem2_end_date']) : null;

        if ($sem1_start && $sem1_end && $today >= $sem1_start && $today <= $sem1_end) {
            return '1st Semester';
        }

        if ($sem2_start && $sem2_end && $today >= $sem2_start && $today <= $sem2_end) {
            return '2nd Semester';
        }

        if ($sem1_start && $today < $sem1_start) {
            return '1st Semester';
        }

        if ($sem2_start && $sem1_end && $today > $sem1_end && $today < $sem2_start) {
            return '2nd Semester';
        }

        if ($sem2_end && $today > $sem2_end) {
            return '2nd Semester';
        }

        if ($sem1_end && $today > $sem1_end && !$sem2_start) {
            return '1st Semester';
        }

        if ($sem2_start && !$sem2_end) {
            return '2nd Semester';
        }

        return '1st Semester';
    }

    /**
     * Bulk upload students
     * POST /api/admin/students/bulk-upload
     */
    public function students_bulk_upload_post() {
        $user_data = require_role($this, ['admin', 'chairperson']);
        if (!$user_data) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }

        if (!isset($data['students']) || !is_array($data['students']) || empty($data['students'])) {
            return json_response(false, 'students array is required and must not be empty', null, 400);
        }

        $students = $data['students'];
        $total = count($students);
        $imported = 0;
        $skipped = 0;
        $errors = [];

        // Start transaction
        $this->db->trans_start();

        // Get user's assigned program if chairperson
        $user_program = null;
        if ($user_data['role'] === 'chairperson') {
            $user_program = $user_data['program'] ?? null;
            if (!$user_program) {
                $this->db->trans_rollback();
                return json_response(false, 'Chairperson must have an assigned program', null, 403);
            }
        }

        // Pre-fetch existing student numbers and emails for batch checking
        $student_numbers = array_filter(array_map('trim', array_column($students, 'student_num')));
        $emails = array_filter(array_map('trim', array_column($students, 'email')));
        $existing_student_nums = $this->get_existing_student_numbers($student_numbers);
        $existing_emails = $this->get_existing_emails($emails);

        // Track processed student numbers and emails within this batch to prevent duplicates
        $batch_student_nums = [];
        $batch_emails = [];

        // Pre-fetch all programs for validation
        $programs = $this->Program_model->get_all(['include_archived' => false]);
        $valid_programs = array_column($programs, 'code');

        foreach ($students as $index => $student) {
            $row = $index + 1;
            
            // Normalize student data keys (convert Excel column names to expected format)
            $student = $this->normalize_student_data_keys($student);
            
            $student_num = isset($student['student_num']) ? trim($student['student_num']) : '';
            $email = isset($student['email']) ? trim($student['email']) : '';

            // Check for duplicates within the batch
            $combined_student_nums = array_merge($existing_student_nums, $batch_student_nums);
            $combined_emails = array_merge($existing_emails, $batch_emails);

            $validation_result = $this->validate_student_data($student, $row, $user_program, $valid_programs, $combined_student_nums, $combined_emails);

            if (!$validation_result['valid']) {
                $errors[] = [
                    'row' => $row,
                    'student_number' => $student_num ?: 'N/A',
                    'error' => $validation_result['error']
                ];
                $skipped++;
                continue;
            }

            // Extract atomic name fields
            $first_name = trim($student['first_name']);
            $middle_name = isset($student['middle_name']) ? trim($student['middle_name']) : null;
            $last_name = trim($student['last_name']);
            
            // Generate full_name from atomic fields with middle name as initial
            $this->load->helper('utility');
            $full_name = generate_full_name($first_name, $middle_name, $last_name, false);
            
            // Generate QR code if not provided
            if (empty($student['qr_code'])) {
                $student['qr_code'] = $this->generate_qr_code($student_num, $full_name, $student['program']);
            }

            // Generate unique user_id for student
            $user_id = generate_user_id('STD');

            // Prepare student data for insertion
            $student_data = [
                'user_id' => $user_id,
                'role' => 'student',
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'full_name' => $full_name,
                'email' => $email,
                'student_num' => $student_num,
                'program' => $this->standardize_program_name($student['program']),
                'student_type' => isset($student['student_type']) ? strtolower(trim($student['student_type'])) : 'regular',
                'qr_code' => $student['qr_code'],
                'status' => isset($student['status']) ? strtolower(trim($student['status'])) : 'active',
                'password' => password_hash($this->generate_temporary_password(), PASSWORD_BCRYPT),
                'created_at' => date('Y-m-d H:i:s')
            ];

            // Add section_id if provided
            if (!empty($student['section_id'])) {
                $section = $this->Section_model->get_by_id($student['section_id']);
                if ($section) {
                    // Validate section matches program
                    $section_program = $this->standardize_program_name($section['program']);
                    if ($section_program === $student_data['program']) {
                        $student_data['section_id'] = $student['section_id'];
                    } else {
                        $errors[] = [
                            'row' => $row,
                            'student_number' => $student_num,
                            'error' => 'Section does not match program'
                        ];
                        $skipped++;
                        continue;
                    }
                } else {
                    $errors[] = [
                        'row' => $row,
                        'student_number' => $student_num,
                        'error' => 'Section not found'
                    ];
                    $skipped++;
                    continue;
                }
            }

            // Insert student
            if ($this->User_model->insert($student_data)) {
                $imported++;
                // Add to batch arrays to prevent duplicates within the same batch
                $batch_student_nums[] = $student_data['student_num'];
                $batch_emails[] = $student_data['email'];
            } else {
                $db_error = $this->db->error();
                $errors[] = [
                    'row' => $row,
                    'student_number' => $student_num,
                    'error' => 'Database error: ' . ($db_error['message'] ?? 'Unknown error')
                ];
                $skipped++;
            }
        }

        // Complete transaction
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return json_response(false, 'Transaction failed. No students were imported.', null, 500);
        }

        $this->db->trans_commit();

        // Log audit event
        log_audit_event(
            'BULK STUDENT UPLOAD',
            'ADMINISTRATION',
            "Bulk upload performed: {$imported} imported, {$skipped} skipped out of {$total} total",
            [
                'uploader_id' => $user_data['user_id'],
                'uploader_role' => $user_data['role'],
                'total_rows' => $total,
                'imported' => $imported,
                'skipped' => $skipped,
                'error_count' => count($errors),
                'programs_affected' => array_unique(array_column($students, 'program'))
            ]
        );

        return json_response(true, 'Bulk upload completed', [
            'total' => $total,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ]);
    }

    /**
     * Check for duplicate student numbers
     * POST /api/admin/students/check-duplicates
     */
    public function students_check_duplicates_post() {
        $user_data = require_role($this, ['admin', 'chairperson']);
        if (!$user_data) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }

        if (!isset($data['student_numbers']) || !is_array($data['student_numbers'])) {
            return json_response(false, 'student_numbers array is required', null, 400);
        }

        $student_numbers = array_filter(array_map('trim', $data['student_numbers']));
        if (empty($student_numbers)) {
            return json_response(true, 'No student numbers to check', ['duplicates' => []]);
        }

        $duplicates = $this->get_existing_student_numbers($student_numbers);

        return json_response(true, 'Duplicate check completed', [
            'duplicates' => $duplicates
        ]);
    }

    /**
     * Check for duplicate emails
     * POST /api/admin/students/check-duplicate-emails
     */
    public function students_check_duplicate_emails_post() {
        $user_data = require_role($this, ['admin', 'chairperson']);
        if (!$user_data) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }

        if (!isset($data['emails']) || !is_array($data['emails'])) {
            return json_response(false, 'emails array is required', null, 400);
        }

        $emails = array_filter(array_map('trim', $data['emails']));
        if (empty($emails)) {
            return json_response(true, 'No emails to check', ['duplicates' => []]);
        }

        $duplicates = $this->get_existing_emails($emails);

        return json_response(true, 'Duplicate email check completed', [
            'duplicates' => $duplicates
        ]);
    }

    /**
     * Validate bulk upload data without inserting
     * POST /api/admin/students/validate-bulk-upload
     */
    public function students_validate_bulk_upload_post() {
        $user_data = require_role($this, ['admin', 'chairperson']);
        if (!$user_data) {
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }

        if (!isset($data['students']) || !is_array($data['students']) || empty($data['students'])) {
            return json_response(false, 'students array is required and must not be empty', null, 400);
        }

        $students = $data['students'];
        $errors = [];

        // Get user's assigned program if chairperson
        $user_program = null;
        if ($user_data['role'] === 'chairperson') {
            $user_program = $user_data['program'] ?? null;
            if (!$user_program) {
                return json_response(false, 'Chairperson must have an assigned program', null, 403);
            }
        }

        // Pre-fetch existing student numbers and emails for batch checking
        $student_numbers = array_filter(array_map('trim', array_column($students, 'student_num')));
        $emails = array_filter(array_map('trim', array_column($students, 'email')));
        $existing_student_nums = $this->get_existing_student_numbers($student_numbers);
        $existing_emails = $this->get_existing_emails($emails);

        // Track processed student numbers and emails within this batch to prevent duplicates
        $batch_student_nums = [];
        $batch_emails = [];

        // Pre-fetch all programs for validation
        $programs = $this->Program_model->get_all(['include_archived' => false]);
        $valid_programs = array_column($programs, 'code');

        foreach ($students as $index => $student) {
            $row = $index + 1;
            
            // Normalize student data keys (convert Excel column names to expected format)
            $student = $this->normalize_student_data_keys($student);
            
            // Check for duplicates within the batch
            $combined_student_nums = array_merge($existing_student_nums, $batch_student_nums);
            $combined_emails = array_merge($existing_emails, $batch_emails);

            $validation_result = $this->validate_student_data($student, $row, $user_program, $valid_programs, $combined_student_nums, $combined_emails);

            if (!$validation_result['valid']) {
                $errors[] = [
                    'row' => $row,
                    'field' => $validation_result['field'] ?? 'general',
                    'error' => $validation_result['error']
                ];
            } else {
                // Add to batch arrays if valid (to catch duplicates within batch)
                $student_num = isset($student['student_num']) ? trim($student['student_num']) : '';
                $email = isset($student['email']) ? trim($student['email']) : '';
                if ($student_num) {
                    $batch_student_nums[] = $student_num;
                }
                if ($email) {
                    $batch_emails[] = $email;
                }
            }
        }

        $valid = empty($errors);

        return json_response(true, $valid ? 'All students are valid' : 'Validation completed with errors', [
            'valid' => $valid,
            'errors' => $errors,
            'total' => count($students),
            'valid_count' => count($students) - count($errors),
            'error_count' => count($errors)
        ]);
    }

    /**
     * Normalize student data keys from Excel column names to expected format
     * @param array $student
     * @return array
     */
    private function normalize_student_data_keys($student) {
        // Mapping of Excel column names to expected keys
        $key_mapping = [
            'First Name' => 'first_name',
            'first name' => 'first_name',
            'FirstName' => 'first_name',
            'firstname' => 'first_name',
            'Middle Name' => 'middle_name',
            'middle name' => 'middle_name',
            'MiddleName' => 'middle_name',
            'middlename' => 'middle_name',
            'Middle Na' => 'middle_name', // Handle truncated headers
            'Last Name' => 'last_name',
            'last name' => 'last_name',
            'LastName' => 'last_name',
            'lastname' => 'last_name',
            'Student ID / LRN' => 'student_num',
            'Student ID/LRN' => 'student_num',
            'student id / lrn' => 'student_num',
            'Student ID' => 'student_num',
            'student id' => 'student_num',
            'StudentID' => 'student_num',
            'studentid' => 'student_num',
            'LRN' => 'student_num',
            'lrn' => 'student_num',
            'Email' => 'email',
            'email' => 'email',
            'Program' => 'program',
            'program' => 'program',
            'Year Level' => 'year_level',
            'year level' => 'year_level',
            'YearLevel' => 'year_level',
            'yearlevel' => 'year_level',
            'Section' => 'section',
            'section' => 'section',
            'Student Status' => 'student_type',
            'Student Sta' => 'student_type', // Handle truncated headers
            'student status' => 'student_type',
            'StudentStatus' => 'student_type',
            'studentstatus' => 'student_type',
        ];
        
        $normalized = [];
        foreach ($student as $key => $value) {
            // Trim the key to handle whitespace issues
            $trimmed_key = trim($key);
            
            // Check if we have a mapping for this key (exact match)
            if (isset($key_mapping[$trimmed_key])) {
                $normalized[$key_mapping[$trimmed_key]] = $value;
                continue;
            }
            
            // Try case-insensitive matching
            $lower_key = strtolower($trimmed_key);
            $found_mapping = false;
            foreach ($key_mapping as $map_key => $map_value) {
                if (strtolower($map_key) === $lower_key) {
                    $normalized[$map_value] = $value;
                    $found_mapping = true;
                    break;
                }
            }
            
            if ($found_mapping) {
                continue;
            }
            
            // If no mapping, try to convert common patterns
            // Convert "First Name" style to "first_name" style
            $normalized_key = strtolower(str_replace([' ', '/'], ['_', '_'], $trimmed_key));
            // Remove special characters but keep underscores
            $normalized_key = preg_replace('/[^a-z0-9_]/', '', $normalized_key);
            
            // Handle special cases
            if (preg_match('/student.*id.*lrn|lrn|student.*num/i', $trimmed_key)) {
                $normalized_key = 'student_num';
            } elseif (preg_match('/first.*name/i', $trimmed_key)) {
                $normalized_key = 'first_name';
            } elseif (preg_match('/middle.*name/i', $trimmed_key)) {
                $normalized_key = 'middle_name';
            } elseif (preg_match('/last.*name/i', $trimmed_key)) {
                $normalized_key = 'last_name';
            } elseif (preg_match('/student.*status|student.*sta/i', $trimmed_key)) {
                $normalized_key = 'student_type';
            } elseif (preg_match('/year.*level/i', $trimmed_key)) {
                $normalized_key = 'year_level';
            }
            
            // Only use normalized key if it's a valid field name
            if (in_array($normalized_key, ['first_name', 'middle_name', 'last_name', 'student_num', 'email', 'program', 'year_level', 'section', 'student_type', 'section_id'])) {
                $normalized[$normalized_key] = $value;
            } else {
                // Keep original key if we can't normalize it
                $normalized[$trimmed_key] = $value;
            }
        }
        
        return $normalized;
    }

    /**
     * Validate student data
     * @param array $student
     * @param int $row
     * @param string|null $user_program
     * @param array $valid_programs
     * @param array $existing_student_nums
     * @param array $existing_emails
     * @return array
     */
    private function validate_student_data($student, $row, $user_program, $valid_programs, $existing_student_nums, $existing_emails) {
        // Normalize student data keys first
        $student = $this->normalize_student_data_keys($student);
        
        // Validate required fields - use atomic name fields
        $required_fields = ['first_name', 'last_name', 'email', 'student_num', 'program'];
        foreach ($required_fields as $field) {
            if (!isset($student[$field]) || empty(trim($student[$field]))) {
                return [
                    'valid' => false,
                    'field' => $field,
                    'error' => ucfirst(str_replace('_', ' ', $field)) . ' is required'
                ];
            }
        }

        $student_num = trim($student['student_num']);
        $email = trim($student['email']);
        $program = trim($student['program']);

        // Validate student number format (exactly 10 digits)
        if (!preg_match('/^\d{10}$/', $student_num)) {
            return [
                'valid' => false,
                'field' => 'student_num',
                'error' => 'Student number must be exactly 10 digits'
            ];
        }

        // Check for duplicate student number
        if (in_array($student_num, $existing_student_nums)) {
            return [
                'valid' => false,
                'field' => 'student_num',
                'error' => 'Student number already exists'
            ];
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'field' => 'email',
                'error' => 'Invalid email format'
            ];
        }

        // Check for duplicate email (case-insensitive)
        $email_lower = strtolower($email);
        $existing_emails_lower = array_map('strtolower', $existing_emails);
        if (in_array($email_lower, $existing_emails_lower)) {
            return [
                'valid' => false,
                'field' => 'email',
                'error' => 'Email already registered'
            ];
        }

        // Validate program
        $program_shortcut = $this->standardize_program_name($program);
        if (!$program_shortcut || !in_array($program_shortcut, $valid_programs)) {
            return [
                'valid' => false,
                'field' => 'program',
                'error' => 'Program does not exist'
            ];
        }

        // Check permission: Chairperson can only upload for their assigned program
        if ($user_program && $program_shortcut !== $user_program) {
            return [
                'valid' => false,
                'field' => 'program',
                'error' => 'You can only upload students for your assigned program'
            ];
        }

        // Validate student_type if provided
        if (isset($student['student_type']) && !empty($student['student_type'])) {
            $student_type = strtolower(trim($student['student_type']));
            if (!in_array($student_type, ['regular', 'irregular'])) {
                return [
                    'valid' => false,
                    'field' => 'student_type',
                    'error' => 'Student type must be either "regular" or "irregular"'
                ];
            }
        }

        // Validate section_id if provided
        if (!empty($student['section_id'])) {
            $section = $this->Section_model->get_by_id($student['section_id']);
            if (!$section) {
                return [
                    'valid' => false,
                    'field' => 'section_id',
                    'error' => 'Section not found'
                ];
            }

            // Validate section belongs to the program
            $section_program = $this->standardize_program_name($section['program']);
            if ($section_program !== $program_shortcut) {
                return [
                    'valid' => false,
                    'field' => 'section_id',
                    'error' => 'Section not found for this program/year'
                ];
            }
        }

        return ['valid' => true];
    }

    /**
     * Get existing student numbers from database
     * @param array $student_numbers
     * @return array
     */
    private function get_existing_student_numbers($student_numbers) {
        if (empty($student_numbers)) {
            return [];
        }

        $this->db->select('student_num')
            ->from('users')
            ->where('role', 'student')
            ->where_in('student_num', $student_numbers);

        $results = $this->db->get()->result_array();
        return array_column($results, 'student_num');
    }

    /**
     * Get existing emails from database
     * @param array $emails
     * @return array
     */
    private function get_existing_emails($emails) {
        if (empty($emails)) {
            return [];
        }

        // Use case-insensitive comparison
        $lower_emails = array_map('strtolower', $emails);
        $this->db->select('email')
            ->from('users');
        
        $this->db->group_start();
        foreach ($lower_emails as $email) {
            $this->db->or_where('LOWER(email)', $email);
        }
        $this->db->group_end();

        $results = $this->db->get()->result_array();
        return array_column($results, 'email');
    }

    /**
     * Generate QR code string for student
     * @param string $student_num
     * @param string $full_name
     * @param string $program
     * @return string
     */
    private function generate_qr_code($student_num, $full_name, $program) {
        $program_shortcut = $this->standardize_program_name($program);
        return "IDNo: {$student_num}\nFull Name: {$full_name}\nProgram: {$program_shortcut}";
    }

}
