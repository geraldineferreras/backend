<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class QRGradingController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->model('Task_model');
        $this->load->model('Notification_model');
    }
    
    /**
     * Quick grade student via QR code scan (Teacher only)
     * POST /api/qr-grading/quick-grade
     * 
     * This endpoint is perfect for face-to-face classroom activities where teachers
     * want to quickly grade students using QR codes
     */
    public function quick_grade_post() {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        $data = $this->get_json_input();
        if (!$data) {
            $this->send_error('Invalid request data', 400);
            return;
        }
        
        // Validate required fields
        $required_fields = ['qr_data', 'grade', 'task_id'];
        if (!$this->validate_required_fields($data, $required_fields)) {
            return;
        }
        
        try {
            // Parse QR data (expected format: "IDNo: 2021305973\nFull Name: ANJELA SOFIA G. SARMIENTO\nProgram: Bachelor of Science in Information Technology")
            $student_info = $this->parse_qr_data($data->qr_data);
            if (!$student_info) {
                $this->send_error('Invalid QR code format. Expected format: IDNo, Full Name, Program', 400);
                return;
            }
            
            // Find student by student number (IDNo)
            $student = $this->find_student_by_number($student_info['idno'], $data->task_id, $user_data['user_id']);
            if (!$student) {
                $this->send_error('Student not found or not enrolled in your class', 404);
                return;
            }
            
            // Get or create submission for this student and task
            $submission = $this->get_or_create_submission($student['student_id'], $data->task_id, $student['class_code']);
            if (!$submission) {
                $this->send_error('Failed to get or create submission', 500);
                return;
            }
            
            // Grade the submission
            $success = $this->Task_model->grade_submission(
                $submission['submission_id'], 
                $data->grade, 
                $data->feedback ?? null
            );
            
            if ($success) {
                // Send notification to student about the grade
                $this->send_grade_notification($submission, $data->grade, $data->feedback ?? null, $student);
                
                $this->send_success([
                    'submission_id' => $submission['submission_id'],
                    'student_name' => $student['full_name'],
                    'student_id' => $student['student_id'],
                    'student_number' => $student['student_num'],
                    'program' => $student_info['program'], // Use from QR data since it's not in DB
                    'grade' => $data->grade,
                    'feedback' => $data->feedback ?? null,
                    'graded_at' => date('c')
                ], 'Student graded successfully via QR code');
            } else {
                $this->send_error('Failed to grade submission', 500);
            }
            
        } catch (Exception $e) {
            $this->send_error('QR grading failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Bulk quick grade multiple students via QR codes (Teacher only)
     * POST /api/qr-grading/bulk-quick-grade
     * 
     * For when you want to grade multiple students quickly in sequence
     */
    public function bulk_quick_grade_post() {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        $data = $this->get_json_input();
        if (!$data || !isset($data->grades) || !is_array($data->grades)) {
            $this->send_error('Grades array is required', 400);
            return;
        }
        
        try {
            $results = [];
            $success_count = 0;
            $errors = [];
            
            foreach ($data->grades as $grade_data) {
                if (!isset($grade_data->qr_data) || !isset($grade_data->grade) || !isset($grade_data->task_id)) {
                    $errors[] = 'Missing qr_data, grade, or task_id for one or more grades';
                    continue;
                }
                
                // Parse QR data (new format)
                $student_info = $this->parse_qr_data($grade_data->qr_data);
                if (!$student_info) {
                    $errors[] = 'Invalid QR code format for one or more students';
                    continue;
                }
                
                // Find student by student number (IDNo)
                $student = $this->find_student_by_number($student_info['idno'], $grade_data->task_id, $user_data['user_id']);
                if (!$student) {
                    $errors[] = "Student {$student_info['idno']} not found or not enrolled in your class";
                    continue;
                }
                
                // Get or create submission
                $submission = $this->get_or_create_submission($student['student_id'], $grade_data->task_id, $student['class_code']);
                if (!$submission) {
                    $errors[] = "Failed to get or create submission for student {$student_info['idno']}";
                    continue;
                }
                
                // Grade the submission
                $success = $this->Task_model->grade_submission(
                    $submission['submission_id'], 
                    $grade_data->grade, 
                    $grade_data->feedback ?? null
                );
                
                if ($success) {
                    // Send notification
                    $this->send_grade_notification($submission, $grade_data->grade, $grade_data->feedback ?? null, $student);
                    
                    $results[] = [
                        'submission_id' => $submission['submission_id'],
                        'student_name' => $student['full_name'],
                        'student_id' => $student['student_id'],
                        'student_number' => $student['student_num'],
                        'program' => $student_info['program'], // Use from QR data
                        'grade' => $grade_data->grade,
                        'feedback' => $grade_data->feedback ?? null,
                        'graded_at' => date('c')
                    ];
                    $success_count++;
                } else {
                    $errors[] = "Failed to grade submission for student {$student_info['idno']}";
                }
            }
            
            if ($success_count > 0) {
                $this->send_success([
                    'graded_count' => $success_count,
                    'results' => $results,
                    'errors' => $errors
                ], "Successfully graded {$success_count} students via QR codes");
            } else {
                $this->send_error('Failed to grade any students: ' . implode(', ', $errors), 500);
            }
            
        } catch (Exception $e) {
            $this->send_error('Bulk QR grading failed: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get QR code data for a student (Teacher only)
     * GET /api/qr-grading/student-qr/{student_id}?class_code={class_code}
     * 
     * Generate QR code data for a specific student in your class
     */
    public function student_qr_get($student_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        $class_code = $this->input->get('class_code');
        if (!$class_code) {
            $this->send_error('Class code is required', 400);
            return;
        }
        
        try {
            // Validate student enrollment
            $student = $this->validate_student_enrollment($student_id, null, $user_data['user_id'], $class_code);
            if (!$student) {
                $this->send_error('Student not found or not enrolled in your class', 404);
                return;
            }
            
            // Generate QR data in new format (use placeholder for program since it's not in DB)
            $qr_data = "IDNo: {$student['student_num']}\nFull Name: {$student['full_name']}\nProgram: Not Specified";
            
            $this->send_success([
                'student_id' => $student_id,
                'student_name' => $student['full_name'],
                'student_number' => $student['student_num'],
                'program' => 'Not Specified', // Placeholder since program column doesn't exist
                'class_code' => $class_code,
                'qr_data' => $qr_data,
                'qr_text' => $qr_data
            ], 'QR code data generated successfully');
            
        } catch (Exception $e) {
            $this->send_error('Failed to generate QR data: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get all students with QR codes for a class (Teacher only)
     * GET /api/qr-grading/class-qr/{class_code}
     * 
     * Get QR code data for all students in a specific class
     */
    public function class_qr_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        try {
            // Verify teacher owns this class by checking if they have tasks for this class
            $task_exists = $this->db->select('task_id')
                ->from('class_tasks')
                ->where('teacher_id', $user_data['user_id'])
                ->where("JSON_CONTAINS(class_codes, ?)", json_encode($class_code))
                ->where('status', 'active')
                ->get()->row_array();
                
            if (!$task_exists) {
                $this->send_error('Class not found or access denied', 404);
                return;
            }
            
            // Get all students enrolled in classes where this teacher has tasks
            // Since we don't have a proper classrooms table, we'll get students from the task's class_codes
            $students = $this->db->select('u.user_id as student_id, u.full_name, u.student_num')
                ->from('users u')
                ->where('u.role', 'student')
                ->where('u.status', 'active')
                ->order_by('u.full_name', 'ASC')
                ->get()->result_array();
            
            $qr_codes = [];
            foreach ($students as $student) {
                $qr_data = "IDNo: {$student['student_num']}\nFull Name: {$student['full_name']}\nProgram: Not Specified";
                $qr_codes[] = [
                    'student_id' => $student['student_id'],
                    'student_name' => $student['full_name'],
                    'student_num' => $student['student_num'],
                    'program' => 'Not Specified', // Placeholder
                    'class_code' => $class_code,
                    'qr_data' => $qr_data,
                    'qr_text' => $qr_data
                ];
            }
            
            $this->send_success([
                'class_code' => $class_code,
                'class_title' => 'Class ' . $class_code,
                'student_count' => count($qr_codes),
                'qr_codes' => $qr_codes
            ], 'QR codes generated for all students in class');
            
        } catch (Exception $e) {
            $this->send_error('Failed to generate class QR codes: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Validate student enrollment in teacher's class
     * Simplified version that works with existing database structure
     */
    private function validate_student_enrollment($student_id, $task_id = null, $teacher_id, $class_code = null) {
        // Since we don't have proper classroom enrollment, we'll check if the student exists
        // and if the teacher has tasks for the given class
        $student = $this->db->select('user_id as student_id, full_name, student_num, email')
            ->from('users')
            ->where('user_id', $student_id)
            ->where('role', 'student')
            ->where('status', 'active')
            ->get()->row_array();
            
        if (!$student) {
            return null;
        }
        
        // Check if teacher has tasks for this class
        if ($class_code) {
            $task_exists = $this->db->select('task_id')
                ->from('class_tasks')
                ->where('teacher_id', $teacher_id)
                ->where("JSON_CONTAINS(class_codes, ?)", json_encode($class_code))
                ->where('status', 'active')
                ->get()->row_array();
                
            if (!$task_exists) {
                return null;
            }
        }
        
        // Use the provided class_code or get it from the task
        if ($class_code) {
            $student['class_code'] = $class_code;
        } elseif ($task_id) {
            $task = $this->db->select('class_codes')
                ->from('class_tasks')
                ->where('task_id', $task_id)
                ->where('teacher_id', $teacher_id)
                ->where('status', 'active')
                ->get()->row_array();
                
            if ($task) {
                $class_codes = json_decode($task['class_codes'], true);
                $student['class_code'] = !empty($class_codes) ? $class_codes[0] : 'DEFAULT';
            } else {
                $student['class_code'] = 'DEFAULT';
            }
        } else {
            $student['class_code'] = 'DEFAULT';
        }
        
        return $student;
    }
    
    /**
     * Parse QR code data to extract student information
     * Expected format: "IDNo: 2021305973\nFull Name: ANJELA SOFIA G. SARMIENTO\nProgram: Bachelor of Science in Information Technology"
     */
    private function parse_qr_data($qr_data) {
        $lines = explode("\n", $qr_data);
        $student_info = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (strpos($line, 'IDNo:') === 0) {
                $student_info['idno'] = trim(substr($line, 5));
            } elseif (strpos($line, 'Full Name:') === 0) {
                $student_info['full_name'] = trim(substr($line, 11));
            } elseif (strpos($line, 'Program:') === 0) {
                $student_info['program'] = trim(substr($line, 9));
            }
        }
        
        // Validate that we have all required fields
        if (isset($student_info['idno']) && isset($student_info['full_name']) && isset($student_info['program'])) {
            return $student_info;
        }
        
        return null;
    }
    
    /**
     * Find student by student number (IDNo) in teacher's class
     * Simplified version that works with existing database structure
     */
    private function find_student_by_number($student_number, $task_id, $teacher_id) {
        // Find student by student number
        $student = $this->db->select('user_id as student_id, full_name, student_num, email')
            ->from('users')
            ->where('student_num', $student_number)
            ->where('role', 'student')
            ->where('status', 'active')
            ->get()->row_array();
            
        if (!$student) {
            return null;
        }
        
        // Check if teacher has tasks for this student and get the class code
        if ($task_id) {
            $task = $this->db->select('task_id, class_codes')
                ->from('class_tasks')
                ->where('task_id', $task_id)
                ->where('teacher_id', $teacher_id)
                ->where('status', 'active')
                ->get()->row_array();
                
            if (!$task) {
                return null;
            }
            
            // Extract class code from the task's class_codes JSON
            $class_codes = json_decode($task['class_codes'], true);
            if (empty($class_codes)) {
                return null;
            }
            
            // Use the first class code (or you could validate against a specific one)
            $student['class_code'] = $class_codes[0];
        } else {
            $student['class_code'] = 'DEFAULT';
        }
        
        return $student;
    }
    
    /**
     * Get or create submission for student and task
     */
    private function get_or_create_submission($student_id, $task_id, $class_code) {
        // First try to get existing submission
        $submission = $this->db->select('*')
            ->from('task_submissions')
            ->where('student_id', $student_id)
            ->where('task_id', $task_id)
            ->get()->row_array();
            
        if ($submission) {
            return $submission;
        }
        
        // Create new submission if none exists
        $submission_data = [
            'student_id' => $student_id,
            'task_id' => $task_id,
            'class_code' => $class_code,
            'status' => 'submitted',
            'submitted_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('task_submissions', $submission_data);
        $submission_id = $this->db->insert_id();
        
        if ($submission_id) {
            $submission_data['submission_id'] = $submission_id;
            return $submission_data;
        }
        
        return null;
    }
    
    /**
     * Send grade notification to student
     */
    private function send_grade_notification($submission, $grade, $feedback, $student) {
        try {
            // Get task details
            $task = $this->db->select('title, type')
                ->from('class_tasks')
                ->where('task_id', $submission['task_id'])
                ->get()->row_array();
                
            if ($task) {
                $notification_data = [
                    'user_id' => $submission['student_id'],
                    'type' => 'grade',
                    'title' => "Task Graded: {$task['title']}",
                    'message' => "Your submission for '{$task['title']}' has been graded. Grade: {$grade}/100",
                    'related_id' => $submission['submission_id'],
                    'related_type' => 'grade',
                    'class_code' => $submission['class_code'],
                    'is_urgent' => false
                ];
                
                if ($feedback) {
                    $notification_data['message'] .= "\n\nFeedback: {$feedback}";
                }
                
                $this->Notification_model->create_notification($notification_data);
            }
        } catch (Exception $e) {
            // Log error but don't fail the grading process
            log_message('error', 'Failed to send grade notification: ' . $e->getMessage());
        }
    }
    
    /**
     * Validate required fields in request data
     */
    private function validate_required_fields($data, $required_fields) {
        foreach ($required_fields as $field) {
            if (!isset($data->$field) || empty($data->$field)) {
                $this->send_error("Field '{$field}' is required", 400);
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get JSON input from request
     */
    private function get_json_input() {
        $input = file_get_contents('php://input');
        return json_decode($input);
    }
    
    /**
     * Send success response
     */
    private function send_success($data = null, $message = 'Success') {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
    
    /**
     * Send error response
     */
    private function send_error($message, $code = 400) {
        $this->output
            ->set_status_header($code)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => false,
                'message' => $message,
                'code' => $code
            ]));
    }
}
