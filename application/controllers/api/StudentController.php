<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

class StudentController extends BaseController {

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['response', 'auth']);
        $this->load->model('Classroom_model');
        $this->load->model('User_model');
    }

    /**
     * Join a class using class code
     * POST /api/student/join-class
     */
    public function join_class() {
        // Require student authentication
        $user_data = require_student($this);
        if (!$user_data) return;
        
        // Get complete user data from database to access section_id
        $complete_user_data = $this->User_model->get_by_id($user_data['user_id']);
        if (!$complete_user_data) {
            return json_response(false, 'User data not found.', null, 404);
        }

        // Get JSON input
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }

        // Validate required fields
        if (empty($data['class_code'])) {
            return json_response(false, 'Class code is required', null, 400);
        }

        $class_code = trim($data['class_code']);
        
        // Get classroom by code
        $classroom = $this->Classroom_model->get_by_code($class_code);
        if (!$classroom) {
            return json_response(false, 'Class not found. Please check the class code.', null, 404);
        }

        // Check if student is already in this class
        $existing_enrollment = $this->db->get_where('classroom_enrollments', [
            'classroom_id' => $classroom['id'],
            'student_id' => $user_data['user_id']
        ])->row_array();

        if ($existing_enrollment) {
            return json_response(false, 'You are already enrolled in this class.', null, 409);
        }

        // Check if student is in the correct section for this class
        if (!isset($complete_user_data['section_id']) || empty($complete_user_data['section_id'])) {
            return json_response(false, 'Student section is not assigned. Please contact administrator.', null, 403);
        }
        
        if ($classroom['section_id'] != $complete_user_data['section_id']) {
            return json_response(false, 'You can only join classes for your assigned section.', null, 403);
        }

        // Enroll student in the class
        $enrollment_data = [
            'classroom_id' => $classroom['id'],
            'student_id' => $complete_user_data['user_id'],
            'enrolled_at' => date('Y-m-d H:i:s'),
            'status' => 'active'
        ];

        $this->db->insert('classroom_enrollments', $enrollment_data);
        
        if ($this->db->affected_rows() > 0) {
            // Get class details for response
            $this->load->model('Subject_model');
            $this->load->model('Section_model');
            
            $subject = $this->Subject_model->get_by_id($classroom['subject_id']);
            $section = $this->Section_model->get_by_id($classroom['section_id']);
            
            $response_data = [
                'class_code' => $classroom['class_code'],
                'subject_name' => $subject ? $subject['subject_name'] : '',
                'section_name' => $section ? $section['section_name'] : '',
                'semester' => $classroom['semester'],
                'school_year' => $classroom['school_year'],
                'teacher_name' => $classroom['teacher_name'],
                'enrolled_at' => $enrollment_data['enrolled_at']
            ];
            
            return json_response(true, 'Successfully joined the class!', $response_data, 201);
        } else {
            return json_response(false, 'Failed to join class. Please try again.', null, 500);
        }
    }

    /**
     * Get available subject offerings for student
     * GET /api/student/my-classes
     */
    public function my_classes() {
        // Require student authentication
        $user_data = require_student($this);
        if (!$user_data) return;
        
        // Get complete user data from database
        $complete_user_data = $this->User_model->get_by_id($user_data['user_id']);
        if (!$complete_user_data) {
            return json_response(false, 'User data not found.', null, 404);
        }

        // Get all available classes for the student's section
        $available_classes = $this->db->select('classes.*, users.full_name as teacher_name, subjects.subject_code, subjects.subject_name, sections.section_name')
            ->from('classes')
            ->join('users', 'classes.teacher_id = users.user_id')
            ->join('subjects', 'classes.subject_id = subjects.id')
            ->join('sections', 'classes.section_id = sections.section_id')
            ->where('classes.section_id', $complete_user_data['section_id'])
            ->where('classes.status', 'active')
            ->order_by('classes.date_created', 'DESC')
            ->get()->result_array();

        // Get student's enrolled class IDs for comparison
        $enrolled_class_ids = $this->db->select('classroom_id')
            ->from('classroom_enrollments')
            ->where('student_id', $complete_user_data['user_id'])
            ->where('status', 'active')
            ->get()->result_array();
        
        $enrolled_ids = array_column($enrolled_class_ids, 'classroom_id');

        $result = [];
        foreach ($available_classes as $class) {
            $result[] = [
                'class_id' => $class['class_id'],
                'subject_id' => $class['subject_id'],
                'teacher_id' => $class['teacher_id'],
                'section_id' => $class['section_id'],
                'semester' => $class['semester'],
                'school_year' => $class['school_year'],
                'status' => $class['status'],
                'date_created' => $class['date_created'],
                'is_active' => ($class['status'] === 'active') ? '1' : '0',
                'subject_code' => $class['subject_code'],
                'subject_name' => $class['subject_name'],
                'teacher_name' => $class['teacher_name'],
                'section_name' => $class['section_name'],
                'is_enrolled' => in_array($class['class_id'], $enrolled_ids)
            ];
        }

        return json_response(true, 'Subject offerings retrieved successfully', $result);
    }

    /**
     * Leave a class
     * DELETE /api/student/leave-class
     */
    public function leave_class() {
        // Require student authentication
        $user_data = require_student($this);
        if (!$user_data) return;
        
        // Get complete user data from database
        $complete_user_data = $this->User_model->get_by_id($user_data['user_id']);
        if (!$complete_user_data) {
            return json_response(false, 'User data not found.', null, 404);
        }

        // Get JSON input
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }

        // Validate required fields
        if (empty($data['class_code'])) {
            return json_response(false, 'Class code is required', null, 400);
        }

        $class_code = trim($data['class_code']);
        
        // Get classroom by code
        $classroom = $this->Classroom_model->get_by_code($class_code);
        if (!$classroom) {
            return json_response(false, 'Class not found.', null, 404);
        }

        // Check if student is enrolled in this class
        $enrollment = $this->db->get_where('classroom_enrollments', [
            'classroom_id' => $classroom['id'],
            'student_id' => $complete_user_data['user_id'],
            'status' => 'active'
        ])->row_array();

        if (!$enrollment) {
            return json_response(false, 'You are not enrolled in this class.', null, 404);
        }

        // Remove enrollment
        $this->db->where('id', $enrollment['id']);
        $this->db->delete('classroom_enrollments');
        
        if ($this->db->affected_rows() > 0) {
            return json_response(true, 'Successfully left the class.', null, 200);
        } else {
            return json_response(false, 'Failed to leave class. Please try again.', null, 500);
        }
    }
}
