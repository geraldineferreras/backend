<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class AdminController extends BaseController {
    public function __construct() {
        parent::__construct();
        $this->load->model(['Section_model', 'User_model']);
        $this->load->helper(['response', 'auth']);
        $this->load->library('Token_lib');
        // CORS headers are already handled by BaseController
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
        $required = ['section_name', 'program', 'year_level', 'adviser_id', 'semester', 'academic_year'];
        foreach ($required as $field) {
            if (empty($data->$field)) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        
        // Validate adviser exists and is a teacher
        $adviser = $this->User_model->get_by_id($data->adviser_id);
        if (!$adviser || $adviser['role'] !== 'teacher') {
            return json_response(false, 'Invalid adviser: must be an active teacher', null, 400);
        }
        
        // Validate semester
        if (!in_array($data->semester, ['1st', '2nd'])) {
            return json_response(false, 'Invalid semester: must be "1st" or "2nd"', null, 400);
        }
        
        $insert_data = [
            'section_name' => $data->section_name,
            'program' => $data->program,
            'year_level' => $data->year_level,
            'adviser_id' => $data->adviser_id,
            'semester' => $data->semester,
            'academic_year' => $data->academic_year
        ];
        $section_id = $this->Section_model->insert($insert_data);
        if ($section_id) {
            // Assign students if provided
            $assigned_students = [];
            if (isset($data->student_ids) && is_array($data->student_ids) && !empty($data->student_ids)) {
                $assigned_students = $this->Section_model->assign_students_to_section($section_id, $data->student_ids);
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
        $required = ['section_name', 'program', 'year_level', 'adviser_id', 'semester', 'academic_year'];
        foreach ($required as $field) {
            if (empty($data->$field)) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        
        // Validate adviser exists and is a teacher
        $adviser = $this->User_model->get_by_id($data->adviser_id);
        if (!$adviser || $adviser['role'] !== 'teacher') {
            return json_response(false, 'Invalid adviser: must be an active teacher', null, 400);
        }
        
        // Validate semester
        if (!in_array($data->semester, ['1st', '2nd'])) {
            return json_response(false, 'Invalid semester: must be "1st" or "2nd"', null, 400);
        }
        
        $update_data = [
            'section_name' => $data->section_name,
            'program' => $data->program,
            'year_level' => $data->year_level,
            'adviser_id' => $data->adviser_id,
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
        
        // Prevent delete if students are linked
        if ($this->Section_model->is_section_linked($section_id)) {
            return json_response(false, 'Cannot delete section: students are still assigned', null, 400);
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
        $programs = $this->Section_model->get_programs();
        return json_response(true, 'Programs retrieved successfully', $programs);
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
            return json_response(true, 'Class created successfully', ['class_id' => $id], 201);
        } else {
            return json_response(false, 'Failed to create class', null, 500);
        }
    }

    public function classes_put($id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $this->load->model('Class_model');
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $success = $this->Class_model->update($id, $data);
        if ($success) {
            return json_response(true, 'Class updated successfully');
        } else {
            return json_response(false, 'Failed to update class', null, 500);
        }
    }

    public function classes_delete($id) {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $this->load->model('Class_model');
        $success = $this->Class_model->delete($id);
        if ($success) {
            return json_response(true, 'Class deleted successfully');
        } else {
            return json_response(false, 'Failed to delete class', null, 500);
        }
    }

    // --- Subject Management ---
    public function subjects_get() {
        $user_data = require_admin($this);
        if (!$user_data) return;
        $this->load->model('Subject_model');
        $subjects = $this->Subject_model->get_all();
        return json_response(true, 'Subjects retrieved successfully', $subjects);
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
        $id = $this->Subject_model->insert($data);
        if ($id) {
            return json_response(true, 'Subject created successfully', ['id' => $id], 201);
        } else {
            return json_response(false, 'Failed to create subject', null, 500);
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
}
