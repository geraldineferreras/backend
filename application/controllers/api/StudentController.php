<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class StudentController extends BaseController {

    public function __construct() {
        parent::__construct();
        $this->load->model(['Section_model', 'User_model']);
        $this->load->helper(['response', 'auth']);
    }

    /**
     * Get current user profile - form fields only
     * GET /api/student/profile
     */
    public function profile_get() {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Access denied. Students only.']));
            return;
        }

        try {
            // Get complete user data from database
            $user = $this->User_model->get_by_id($user_data['user_id']);
            
            if (!$user) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
                return;
            }

            // Get section information if user has a section_id
            $section_info = null;
            if (!empty($user['section_id'])) {
                $section_info = $this->Section_model->get_by_id($user['section_id']);
            }

            // Extract only the form fields that are shown on the Student Profile Settings form
            $form_data = [
                // User Information Section
                'role' => $user['role'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                
                // Student Information Section
                'address' => $user['address'],
                'student_num' => $user['student_num'],
                'contact_num' => $user['contact_num'],
                'program' => $user['program'],
                'year_level' => $section_info ? $section_info['year_level'] : null,
                'section_id' => $user['section_id'],
                'section_name' => $section_info ? $section_info['section_name'] : null,
                
                // QR Code Data Section
                'qr_code' => $user['qr_code']
            ];
            
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Student profile form data retrieved successfully',
                    'data' => $form_data
                ]));

        } catch (Exception $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to retrieve profile data: ' . $e->getMessage()]));
        }
    }

    /**
     * Get available programs for students to choose from
     * GET /api/student/programs
     */
    public function programs_get() {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Access denied. Students only.']));
            return;
        }

        try {
            $programs = $this->Section_model->get_programs();
            
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Programs retrieved successfully',
                    'data' => $programs
                ]));

        } catch (Exception $e) {
            log_message('error', 'Get programs error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to retrieve programs']));
        }
    }

    /**
     * Get available year levels for a specific program
     * GET /api/student/programs/{program}/years
     */
    public function programs_years_get($program = null) {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Access denied. Students only.']));
            return;
        }

        if (!$program) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Program parameter is required']));
            return;
        }

        try {
            // Map short program name to full program name for database lookup
            $full_program_name = map_program_name($program);
            
            // Get sections for the specific program
            $sections = $this->Section_model->get_by_program($full_program_name);
            
            // Extract unique year levels
            $year_levels = [];
            foreach ($sections as $section) {
                $year_level = $section['year_level'];
                if (!empty($year_level) && !in_array($year_level, $year_levels)) {
                    $year_levels[] = $year_level;
                }
            }
            
            // Sort year levels
            sort($year_levels);
            
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => "Year levels for $program retrieved successfully",
                    'data' => $year_levels
                ]));

        } catch (Exception $e) {
            log_message('error', 'Get year levels error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to retrieve year levels']));
        }
    }

    /**
     * Get available sections for a specific program and year level
     * GET /api/student/programs/{program}/years/{year}/sections
     */
    public function programs_years_sections_get($program = null, $year_level = null) {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Access denied. Students only.']));
            return;
        }

        if (!$program || !$year_level) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Program and year level parameters are required']));
            return;
        }

        try {
            // Map short program name to full program name for database lookup
            $full_program_name = map_program_name($program);
            
            // Get sections for the specific program and year level
            $sections = $this->Section_model->get_by_program_and_year_level($full_program_name, $year_level);
            
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => "Sections for $program $year_level year retrieved successfully",
                    'data' => $sections
                ]));
            
        } catch (Exception $e) {
            log_message('error', 'Get sections error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to retrieve sections']));
        }
    }

    /**
     * Get all available options for student profile update
     * GET /api/student/profile-options
     */
    public function profile_options_get() {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Access denied. Students only.']));
            return;
        }

        try {
            // Get all programs, year levels, semesters, and academic years
            $programs = $this->Section_model->get_programs();
            $year_levels = $this->Section_model->get_year_levels();
            $semesters = $this->Section_model->get_semesters();
            $academic_years = $this->Section_model->get_academic_years();

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Profile options retrieved successfully',
                    'data' => [
                        'programs' => $programs,
                        'year_levels' => $year_levels,
                        'semesters' => $semesters,
                        'academic_years' => $academic_years
                    ]
                ]));
            
        } catch (Exception $e) {
            log_message('error', 'Get profile options error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to retrieve profile options']));
        }
    }

    /**
     * Get sections grouped by program and year level for easy selection
     * GET /api/student/sections-grouped
     */
    public function sections_grouped_get() {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Access denied. Students only.'
                ]));
            return;
        }

        try {
            $sections = $this->Section_model->get_sections_grouped_by_program_year();
            
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Sections grouped by program and year level retrieved successfully',
                    'data' => $sections
                ]));
        } catch (Exception $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Failed to retrieve grouped sections: ' . $e->getMessage()
                ]));
        }
    }

    /**
     * Update student's complete profile information
     * PUT /api/student/profile/update
     */
    public function profile_update_put() {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Access denied. Students only.'
                ]));
            return;
        }

        // Get input data
        $raw_input = file_get_contents('php://input');
        
        // Debug: Log the raw input
        log_message('debug', 'Profile update raw input: ' . $raw_input);
        
        if (empty($raw_input)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Request body is empty. Please provide JSON data.',
                    'debug' => [
                        'raw_input' => $raw_input,
                        'content_length' => strlen($raw_input),
                        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Not set'
                    ]
                ]));
            return;
        }
        
        $input = json_decode($raw_input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Invalid JSON format: ' . json_last_error_msg(),
                    'debug' => [
                        'raw_input' => $raw_input,
                        'json_error' => json_last_error_msg(),
                        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Not set'
                    ]
                ]));
            return;
        }
        
        if (!$input) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'JSON data could not be parsed',
                    'debug' => [
                        'raw_input' => $raw_input,
                        'json_error' => json_last_error_msg()
                    ]
                ]));
            return;
        }

        try {
            // Validate required fields (year_level is not stored in users table, it comes from sections)
            $required_fields = ['full_name', 'email', 'student_num', 'program', 'section_id'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (!isset($input[$field]) || empty(trim($input[$field]))) {
                    $missing_fields[] = $field;
                }
            }
            
            if (!empty($missing_fields)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Missing required fields: ' . implode(', ', $missing_fields),
                        'debug' => [
                            'missing_fields' => $missing_fields,
                            'provided_fields' => array_keys($input)
                        ]
                    ]));
                return;
            }
            
            // Validate that section_id exists and get year_level from sections table
            $section = $this->Section_model->get_by_id($input['section_id']);
            if (!$section) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Invalid section_id: Section not found'
                    ]));
                return;
            }
            
            // Validate email uniqueness if it's being changed
            if (isset($input['email']) && $input['email'] !== $user_data['email']) {
                $existing_user = $this->User_model->get_by_email($input['email']);
                if ($existing_user && $existing_user['user_id'] !== $user_data['user_id']) {
                    $this->output
                        ->set_status_header(400)
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'status' => false,
                            'message' => 'Email address is already in use by another user. Please choose a different email or keep your current email.',
                            'debug' => [
                                'current_email' => $user_data['email'],
                                'requested_email' => $input['email'],
                                'existing_user_id' => $existing_user['user_id'],
                                'suggestion' => 'Keep your current email or choose a different one that is not in use'
                            ]
                        ]));
                    return;
                }
            }
            
            // Validate student number uniqueness if it's being changed
            if (isset($user_data['student_num']) && isset($input['student_num']) && $input['student_num'] !== $user_data['student_num']) {
                $existing_user = $this->User_model->get_by_student_num($input['student_num']);
                if ($existing_user && $existing_user['user_id'] !== $user_data['user_id']) {
                    $this->output
                        ->set_status_header(400)
                        ->set_content_type('application/json')
                        ->set_output(json_encode([
                            'status' => false,
                            'message' => 'Student number is already in use by another user. Please choose a different student number or keep your current one.',
                            'debug' => [
                                'current_student_num' => $user_data['student_num'],
                                'requested_student_num' => $input['student_num'],
                                'existing_user_id' => $existing_user['user_id'],
                                'suggestion' => 'Keep your current student number or choose a different one that is not in use'
                            ]
                        ]));
                    return;
                }
            }
            
            // Prepare update data - only update fields that are provided
            $update_data = [];
            
            // Common fields
            if (isset($input['full_name'])) $update_data['full_name'] = trim($input['full_name']);
            if (isset($input['email'])) $update_data['email'] = trim($input['email']);
            if (isset($input['address'])) $update_data['address'] = trim($input['address']);
            if (isset($input['contact_num'])) $update_data['contact_num'] = trim($input['contact_num']);
            
            // Student-specific fields
            if (isset($input['student_num'])) $update_data['student_num'] = trim($input['student_num']);
            if (isset($input['program'])) $update_data['program'] = $input['program'];
            // Note: year_level is not stored in users table, it's retrieved from sections table
            if (isset($input['section_id'])) $update_data['section_id'] = $input['section_id'];
            
            // Handle password update if provided
            if (isset($input['password']) && !empty($input['password'])) {
                $update_data['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            }

            // Generate QR code based on student information if student_num is being updated
            if (isset($input['student_num']) && isset($input['full_name']) && isset($input['program'])) {
                $qr_data = "IDNo: {$input['student_num']}\nFull Name: {$input['full_name']}\nProgram: {$input['program']}";
                $update_data['qr_code'] = $qr_data;
            } elseif (isset($input['student_num']) || isset($input['full_name']) || isset($input['program'])) {
                // If any of the QR components are being updated, regenerate QR
                $current_user = $this->User_model->get_by_id($user_data['user_id']);
                $student_num = $input['student_num'] ?? $current_user['student_num'];
                $full_name = $input['full_name'] ?? $current_user['full_name'];
                $program = $input['program'] ?? $current_user['program'];
                
                if ($student_num && $full_name && $program) {
                    $qr_data = "IDNo: {$student_num}\nFull Name: {$full_name}\nProgram: {$program}";
                    $update_data['qr_code'] = $qr_data;
                }
            }

            // Update timestamp
            $update_data['updated_at'] = date('Y-m-d H:i:s');

            // Log the update data for debugging
            log_message('debug', 'Profile update data: ' . json_encode($update_data));
            
            // Update the user's profile
            $update_result = $this->User_model->update($user_data['user_id'], $update_data);
            
            if (!$update_result) {
                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Failed to update profile'
                    ]));
                return;
            }

            // Get updated user data
            $updated_user = $this->User_model->get_by_id($user_data['user_id']);
            
            // Get section information for complete profile data
            $section_info = null;
            if (!empty($updated_user['section_id'])) {
                $section_info = $this->Section_model->get_by_id($updated_user['section_id']);
            }
            
            // Add section information to response
            $response_data = $updated_user;
            if ($section_info) {
                $response_data['year_level'] = $section_info['year_level'];
                $response_data['section_name'] = $section_info['section_name'];
            }

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Profile updated successfully. Note: year_level is retrieved from your assigned section.',
                    'data' => $response_data
                ]));

        } catch (Exception $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Failed to update profile: ' . $e->getMessage()
                ]));
        }
    }

    /**
     * Update student profile with academic information (POST method for compatibility)
     * POST /api/student/profile/update
     */
    public function profile_update_post() {
        $this->profile_update_put();
    }

    // Handle OPTIONS preflight requests (CORS)
    public function options() {
        // The BaseController constructor handles CORS and exits for OPTIONS requests.
    }

    /**
     * Get student's enrolled classes
     * GET /api/student/my-classes
     */
    public function my_classes() {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Access denied. Students only.']));
            return;
        }

        try {
            // Get enrolled classes for the student
            $enrolled_classes = $this->db->select('
                c.class_code,
                c.id as classroom_id,
                s.subject_name,
                sec.section_name,
                c.semester,
                c.school_year,
                u.full_name as teacher_name,
                ce.enrolled_at,
                ce.status as enrollment_status
            ')
            ->from('classroom_enrollments ce')
            ->join('classrooms c', 'ce.classroom_id = c.id')
            ->join('subjects s', 'c.subject_id = s.id')
            ->join('sections sec', 'c.section_id = sec.section_id')
            ->join('users u', 'c.teacher_id = u.user_id')
            ->where('ce.student_id', $user_data['user_id'])
            ->where('ce.status', 'active')
            ->where('c.is_active', 1)
            ->order_by('c.school_year', 'DESC')
            ->order_by('c.semester', 'ASC')
            ->order_by('s.subject_name', 'ASC')
            ->get()->result_array();

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Enrolled classes retrieved successfully',
                    'data' => $enrolled_classes
                ]));

        } catch (Exception $e) {
            log_message('error', 'Get enrolled classes error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to retrieve enrolled classes']));
        }
    }

    /**
     * Join a class using class code
     * POST /api/student/join-class
     */
    public function join_class() {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Access denied. Students only.']));
            return;
        }

        // Get input data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['class_code'])) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Class code is required']));
            return;
        }

        $class_code = trim($input['class_code']);

        try {
            // Check if class exists and is active
            $classroom = $this->db->select('
                c.*,
                s.subject_name,
                sec.section_name,
                u.full_name as teacher_name
            ')
            ->from('classrooms c')
            ->join('subjects s', 'c.subject_id = s.id')
            ->join('sections sec', 'c.section_id = sec.section_id')
            ->join('users u', 'c.teacher_id = u.user_id')
            ->where('c.class_code', $class_code)
            ->where('c.is_active', 1)
            ->get()->row_array();

            if (!$classroom) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Class not found']));
                return;
            }

            // Check if student is already enrolled
            $existing_enrollment = $this->db->where('classroom_id', $classroom['id'])
                ->where('student_id', $user_data['user_id'])
                ->get('classroom_enrollments')->row_array();

            if ($existing_enrollment) {
                $this->output
                    ->set_status_header(409)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Already enrolled in this class']));
                return;
            }

            // Check if student is in the correct section for this class
            if ($user_data['section_id'] != $classroom['section_id']) {
                $this->output
                    ->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'You can only join classes for your assigned section']));
                return;
            }

            // Enroll the student
            $enrollment_data = [
                'classroom_id' => $classroom['id'],
                'student_id' => $user_data['user_id'],
                'enrolled_at' => date('Y-m-d H:i:s'),
                'status' => 'active'
            ];

            $this->db->insert('classroom_enrollments', $enrollment_data);

            // Log the enrollment activity
            $this->load->model('Audit_model');
            if (class_exists('Audit_model')) {
                $this->Audit_model->log_activity(
                    $user_data['user_id'],
                    'class_enrollment',
                    'Student joined class',
                    [
                        'class_code' => $class_code,
                        'subject_name' => $classroom['subject_name'],
                        'section_name' => $classroom['section_name']
                    ]
                );
            }

            $this->output
                ->set_status_header(201)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Successfully joined the class!',
                    'data' => [
                        'class_code' => $classroom['class_code'],
                        'subject_name' => $classroom['subject_name'],
                        'section_name' => $classroom['section_name'],
                        'semester' => $classroom['semester'],
                        'school_year' => $classroom['school_year'],
                        'teacher_name' => $classroom['teacher_name'],
                        'enrolled_at' => $enrollment_data['enrolled_at']
                    ]
                ]));

        } catch (Exception $e) {
            log_message('error', 'Join class error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to join class']));
        }
    }

    /**
     * Leave a class
     * DELETE /api/student/leave-class
     */
    public function leave_class() {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Access denied. Students only.']));
            return;
        }

        // Get input data
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['class_code'])) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Class code is required']));
            return;
        }

        $class_code = trim($input['class_code']);

        try {
            // Find the classroom
            $classroom = $this->db->where('class_code', $class_code)
                ->where('is_active', 1)
                ->get('classrooms')->row_array();

            if (!$classroom) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Class not found']));
                return;
            }

            // Check if student is enrolled
            $enrollment = $this->db->where('classroom_id', $classroom['id'])
                ->where('student_id', $user_data['user_id'])
                ->where('status', 'active')
                ->get('classroom_enrollments')->row_array();

            if (!$enrollment) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Not enrolled in this class']));
                return;
            }

            // Update enrollment status to dropped
            $this->db->where('id', $enrollment['id'])
                ->update('classroom_enrollments', ['status' => 'dropped']);

            // Log the activity
            $this->load->model('Audit_model');
            if (class_exists('Audit_model')) {
                $this->Audit_model->log_activity(
                    $user_data['user_id'],
                    'class_drop',
                    'Student left class',
                    [
                        'class_code' => $class_code,
                        'classroom_id' => $classroom['id']
                    ]
                );
            }

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Successfully left the class.',
                    'data' => null
                ]));

        } catch (Exception $e) {
            log_message('error', 'Leave class error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to leave class']));
        }
    }

    /**
     * Get people in a classroom (teacher and students)
     * GET /api/student/classroom/{class_code}/people
     */
    public function classroom_people_get($class_code) {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Access denied. Students only.']));
            return;
        }

        try {
            // Get classroom details
            $classroom = $this->db->select('
                c.*,
                s.subject_name,
                sec.section_name
            ')
            ->from('classrooms c')
            ->join('subjects s', 'c.subject_id = s.id')
            ->join('sections sec', 'c.section_id = sec.section_id')
            ->where('c.class_code', $class_code)
            ->where('c.is_active', 1)
            ->get()->row_array();

            if (!$classroom) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Classroom not found']));
                return;
            }

            // Check if student is enrolled in this classroom
            $enrollment = $this->db->where('classroom_id', $classroom['id'])
                ->where('student_id', $user_data['user_id'])
                ->where('status', 'active')
                ->get('classroom_enrollments')->row_array();

            if (!$enrollment) {
                $this->output
                    ->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Access denied. You are not enrolled in this class']));
                return;
            }

            // Get teacher information
            $teacher = $this->db->select('
                user_id,
                full_name,
                email,
                profile_pic
            ')
            ->from('users')
            ->where('user_id', $classroom['teacher_id'])
            ->where('role', 'teacher')
            ->where('status', 'active')
            ->get()->row_array();

            // Get enrolled students
            $students = $this->db->select('
                u.user_id,
                u.full_name,
                u.email,
                u.student_num,
                u.contact_num,
                u.program,
                u.profile_pic,
                ce.enrolled_at,
                ce.status as enrollment_status
            ')
            ->from('classroom_enrollments ce')
            ->join('users u', 'ce.student_id = u.user_id')
            ->where('ce.classroom_id', $classroom['id'])
            ->where('ce.status', 'active')
            ->where('u.role', 'student')
            ->where('u.status', 'active')
            ->order_by('u.full_name', 'ASC')
            ->get()->result_array();

            // Format teacher data
            $formatted_teacher = [
                'user_id' => $teacher['user_id'],
                'full_name' => $teacher['full_name'],
                'email' => $teacher['email'],
                'profile_pic' => $teacher['profile_pic'],
                'role' => 'Primary Instructor',
                'status' => 'Active'
            ];

            // Format students data
            $formatted_students = array_map(function($student) {
                return [
                    'user_id' => $student['user_id'],
                    'full_name' => $student['full_name'],
                    'email' => $student['email'],
                    'student_num' => $student['student_num'],
                    'contact_num' => $student['contact_num'],
                    'program' => $student['program'],
                    'profile_pic' => $student['profile_pic'],
                    'role' => 'Class Member',
                    'status' => 'Enrolled',
                    'enrolled_at' => $student['enrolled_at'],
                    'enrollment_status' => $student['enrollment_status']
                ];
            }, $students);

            // Format classroom data
            $formatted_classroom = [
                'id' => $classroom['id'],
                'class_code' => $classroom['class_code'],
                'title' => $classroom['subject_name'],
                'semester' => $classroom['semester'],
                'school_year' => $classroom['school_year'],
                'section_name' => $classroom['section_name']
            ];

            // Calculate statistics
            $total_members = count($formatted_students) + 1; // +1 for teacher
            $total_teachers = 1;
            $total_students = count($formatted_students);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Classroom members retrieved successfully',
                    'data' => [
                        'classroom' => $formatted_classroom,
                        'teacher' => $formatted_teacher,
                        'students' => $formatted_students,
                        'statistics' => [
                            'total_members' => $total_members,
                            'total_teachers' => $total_teachers,
                            'total_students' => $total_students
                        ]
                    ]
                ]));

        } catch (Exception $e) {
            log_message('error', 'Get classroom people error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to retrieve classroom members']));
        }
    }

    /**
     * Get classroom stream posts
     * GET /api/student/classroom/{class_code}/stream
     */
    public function classroom_stream_get($class_code) {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Access denied. Students only.']));
            return;
        }

        try {
            // Get classroom details
            $classroom = $this->db->where('class_code', $class_code)
                ->where('is_active', 1)
                ->get('classrooms')->row_array();

            if (!$classroom) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Classroom not found']));
                return;
            }

            // Check if student is enrolled in this classroom
            $enrollment = $this->db->where('classroom_id', $classroom['id'])
                ->where('student_id', $user_data['user_id'])
                ->where('status', 'active')
                ->get('classroom_enrollments')->row_array();

            if (!$enrollment) {
                $this->output
                    ->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Access denied. You are not enrolled in this class']));
                return;
            }

            // Get stream posts (only published posts, not drafts or scheduled)
            $posts = $this->db->select('
                cs.*,
                u.full_name as user_name,
                u.profile_pic
            ')
            ->from('classroom_stream cs')
            ->join('users u', 'cs.user_id = u.user_id')
            ->where('cs.classroom_id', $classroom['id'])
            ->where('cs.is_draft', 0)
            ->where('cs.is_scheduled', 0)
            ->where('cs.status', 'published')
            ->order_by('cs.created_at', 'DESC')
            ->get()->result_array();

            // Format posts
            $formatted_posts = array_map(function($post) {
                return [
                    'id' => $post['id'],
                    'user_name' => $post['user_name'],
                    'profile_pic' => $post['profile_pic'],
                    'title' => $post['title'],
                    'content' => $post['content'],
                    'attachment_type' => $post['attachment_type'],
                    'attachment_url' => $post['attachment_url'],
                    'allow_comments' => (bool)$post['allow_comments'],
                    'is_pinned' => (bool)$post['is_pinned'],
                    'created_at' => $post['created_at'],
                    'updated_at' => $post['updated_at']
                ];
            }, $posts);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Stream posts retrieved successfully',
                    'data' => $formatted_posts
                ]));

        } catch (Exception $e) {
            log_message('error', 'Get classroom stream error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to retrieve stream posts']));
        }
    }

    /**
     * Create a post in classroom stream
     * POST /api/student/classroom/{class_code}/stream
     */
    public function classroom_stream_post($class_code) {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Access denied. Students only.']));
            return;
        }

        try {
            // Get classroom details
            $classroom = $this->db->where('class_code', $class_code)
                ->where('is_active', 1)
                ->get('classrooms')->row_array();

            if (!$classroom) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Classroom not found']));
                return;
            }

            // Check if student is enrolled in this classroom
            $enrollment = $this->db->where('classroom_id', $classroom['id'])
                ->where('student_id', $user_data['user_id'])
                ->where('status', 'active')
                ->get('classroom_enrollments')->row_array();

            if (!$enrollment) {
                $this->output
                    ->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Access denied. You are not enrolled in this class']));
                return;
            }

            // Get input data
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON data provided']));
                return;
            }

            // Validate required fields
            if (empty($input['title']) || empty($input['content'])) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Title and content are required']));
                return;
            }

            // Prepare post data
            $post_data = [
                'classroom_id' => $classroom['id'],
                'user_id' => $user_data['user_id'],
                'title' => trim($input['title']),
                'content' => trim($input['content']),
                'is_draft' => isset($input['is_draft']) ? (int)$input['is_draft'] : 0,
                'is_scheduled' => isset($input['is_scheduled']) ? (int)$input['is_scheduled'] : 0,
                'scheduled_at' => isset($input['scheduled_at']) ? $input['scheduled_at'] : null,
                'allow_comments' => isset($input['allow_comments']) ? (int)$input['allow_comments'] : 1,
                'attachment_type' => isset($input['attachment_type']) ? $input['attachment_type'] : null,
                'attachment_url' => isset($input['attachment_url']) ? $input['attachment_url'] : null,
                'status' => 'published',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Insert the post
            $this->db->insert('classroom_stream', $post_data);
            $post_id = $this->db->insert_id();

            // Log the activity
            $this->load->model('Audit_model');
            if (class_exists('Audit_model')) {
                $this->Audit_model->log_activity(
                    $user_data['user_id'],
                    'stream_post_create',
                    'Student created stream post',
                    [
                        'class_code' => $class_code,
                        'post_id' => $post_id,
                        'title' => $post_data['title']
                    ]
                );
            }

            $this->output
                ->set_status_header(201)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Post created successfully',
                    'data' => [
                        'id' => $post_id,
                        'title' => $post_data['title'],
                        'content' => $post_data['content'],
                        'created_at' => $post_data['created_at']
                    ]
                ]));

        } catch (Exception $e) {
            log_message('error', 'Create stream post error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to create post']));
        }
    }

    /**
     * Debug classes - temporary method for troubleshooting
     * GET /api/student/debug-classes
     */
    public function debug_classes() {
        // Require student authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }

        // Verify user is a student
        if ($user_data['role'] !== 'student') {
            $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Access denied. Students only.']));
            return;
        }

        try {
            // Get all classrooms
            $classrooms = $this->db->select('
                c.*,
                s.subject_name,
                sec.section_name,
                u.full_name as teacher_name
            ')
            ->from('classrooms c')
            ->join('subjects s', 'c.subject_id = s.id')
            ->join('sections sec', 'c.section_id = sec.section_id')
            ->join('users u', 'c.teacher_id = u.user_id')
            ->where('c.is_active', 1)
            ->get()->result_array();

            // Get student's section
            $student_section = $this->db->select('section_id, program, year_level')
                ->from('users')
                ->where('user_id', $user_data['user_id'])
                ->get()->row_array();

            // Get enrollments
            $enrollments = $this->db->select('*')
                ->from('classroom_enrollments')
                ->where('student_id', $user_data['user_id'])
                ->get()->result_array();

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Debug information retrieved',
                    'data' => [
                        'student_info' => [
                            'user_id' => $user_data['user_id'],
                            'section_id' => $student_section['section_id'] ?? 'Not set',
                            'program' => $student_section['program'] ?? 'Not set',
                            'year_level' => $student_section['year_level'] ?? 'Not set'
                        ],
                        'available_classrooms' => $classrooms,
                        'current_enrollments' => $enrollments,
                        'total_classrooms' => count($classrooms),
                        'total_enrollments' => count($enrollments)
                    ]
                ]));

        } catch (Exception $e) {
            log_message('error', 'Debug classes error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to retrieve debug information']));
        }
    }

    // Helper methods

    /**
     * Get short name for a program
     */
    private function get_program_short_name($program_name) {
        $short_names = [
            'Bachelor of Science in Information Technology' => 'BSIT',
            'Bachelor of Science in Information Systems' => 'BSIS',
            'Bachelor of Science in Computer Science' => 'BSCS',
            'Associate in Computer Technology' => 'ACT'
        ];
        
        return $short_names[$program_name] ?? $program_name;
    }

    /**
     * Format year level to readable format
     */
    private function format_year_level($year_level) {
        if (empty($year_level)) {
            return null;
        }
        
        $year_level = trim($year_level);
        
        // If it's already in ordinal format, return as is
        if (preg_match('/^\d+(st|nd|rd|th)\s+year$/i', $year_level)) {
            return $year_level;
        }
        
        // If it's just a number, convert to ordinal
        if (is_numeric($year_level)) {
            $number = (int)$year_level;
            switch ($number) {
                case 1: return '1st year';
                case 2: return '2nd year';
                case 3: return '3rd year';
                case 4: return '4th year';
                case 5: return '5th year';
                default: return $number . 'th year';
            }
        }
        
        // If it contains a number, extract and format it
        if (preg_match('/(\d+)/', $year_level, $matches)) {
            $number = (int)$matches[1];
            switch ($number) {
                case 1: return '1st year';
                case 2: return '2nd year';
                case 3: return '3rd year';
                case 4: return '4th year';
                case 5: return '5th year';
                default: return $number . 'th year';
            }
        }
        
        return $year_level;
    }

    /**
     * Extract numeric value from year level
     */
    private function extract_numeric_year($year_level) {
        if (preg_match('/(\d+)/', $year_level, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    /**
     * Format semester to readable format
     */
    private function format_semester($semester) {
        $semester = trim($semester);
        
        if (preg_match('/^\d+(st|nd|rd|th)\s+semester$/i', $semester)) {
            return $semester;
        }
        
        if (is_numeric($semester)) {
            $number = (int)$semester;
            switch ($number) {
                case 1: return '1st semester';
                case 2: return '2nd semester';
                default: return $number . 'th semester';
            }
        }
        
        return $semester;
    }
}
