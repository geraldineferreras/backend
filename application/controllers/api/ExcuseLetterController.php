<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

class ExcuseLetterController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ExcuseLetter_model');
        $this->load->helper(['auth', 'audit', 'notification']);
        $this->load->library('Token_lib');
    }

    /**
     * Submit excuse letter (Student only)
     * Endpoint: POST /api/excuse-letters/submit
     * Supports both JSON and multipart form data with file uploads
     */
    public function submit_post()
    {
        $user_data = require_student($this);
        if (!$user_data) return;

        // Check if this is a multipart form data request (file upload)
        $content_type = $this->input->server('CONTENT_TYPE');
        $is_multipart = strpos($content_type, 'multipart/form-data') !== false;

        if ($is_multipart) {
            // Handle multipart form data with file upload
            $data = new stdClass();
            $data->class_id = $this->input->post('class_id');
            $data->date_absent = $this->input->post('date_absent');
            $data->reason = $this->input->post('reason');
            
            // Handle file upload
            $image_path = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_config = [
                    'upload_path' => './uploads/excuse_letters/',
                    'allowed_types' => 'gif|jpg|jpeg|png|pdf|doc|docx',
                    'max_size' => 5120, // 5MB
                    'encrypt_name' => true,
                    'overwrite' => false
                ];

                // Create upload directory if it doesn't exist
                if (!is_dir($upload_config['upload_path'])) {
                    mkdir($upload_config['upload_path'], 0755, true);
                }

                $this->load->library('upload', $upload_config);

                if ($this->upload->do_upload('attachment')) {
                    $upload_data = $this->upload->data();
                    $image_path = 'uploads/excuse_letters/' . $upload_data['file_name'];
                } else {
                    $this->send_error('File upload failed: ' . $this->upload->display_errors('', ''), 400);
                    return;
                }
            }
        } else {
            // Handle JSON request
            $data = $this->get_json_input();
            if (!$data) return;
        }

        // Validate required fields
        $required_fields = ['class_id', 'date_absent', 'reason'];
        if (!$this->validate_required_fields($data, $required_fields)) {
            return;
        }

        // Validate reason length
        if (strlen($data->reason) > 300) {
            $this->send_error('Reason must be 300 characters or less', 400);
            return;
        }

        try {
            // First, try to find the class in the classes table
            $class = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                ->from('classes')
                ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                ->join('sections', 'classes.section_id = sections.section_id', 'left')
                ->where('classes.class_id', $data->class_id)
                ->get()->row_array();

            // If not found in classes table, try to find corresponding classroom
            if (!$class) {
                // Check if this is a classroom ID instead of class ID
                $classroom = $this->db->select('classrooms.*, subjects.subject_name, sections.section_name')
                    ->from('classrooms')
                    ->join('subjects', 'classrooms.subject_id = subjects.id', 'left')
                    ->join('sections', 'classrooms.section_id = sections.section_id', 'left')
                    ->where('classrooms.id', $data->class_id)
                    ->get()->row_array();

                if ($classroom) {
                    // Find corresponding class in classes table based on subject and section
                    $class = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                        ->from('classes')
                        ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                        ->join('sections', 'classes.section_id = sections.section_id', 'left')
                        ->where('classes.subject_id', $classroom['subject_id'])
                        ->where('classes.section_id', $classroom['section_id'])
                        ->where('classes.teacher_id', $classroom['teacher_id'])
                        ->get()->row_array();

                    if (!$class) {
                        $this->send_error('No corresponding class found for this classroom', 404);
                        return;
                    }
                } else {
                    $this->send_error('Class not found', 404);
                    return;
                }
            }

            // Check if student is enrolled in this class using classroom_enrollments table
            $enrollment = $this->db->select('classroom_enrollments.*')
                ->from('classroom_enrollments')
                ->join('classrooms', 'classroom_enrollments.classroom_id = classrooms.id')
                ->where('classroom_enrollments.student_id', $user_data['user_id'])
                ->where('classrooms.subject_id', $class['subject_id'])
                ->where('classrooms.section_id', $class['section_id'])
                ->where('classroom_enrollments.status', 'active')
                ->get()->row_array();

            if (!$enrollment) {
                $this->send_error('You are not enrolled in this class', 400);
                return;
            }

            // Check if excuse letter already exists for this date and class
            $existing = $this->db->where('student_id', $user_data['user_id'])
                ->where('class_id', $data->class_id)
                ->where('date_absent', $data->date_absent)
                ->get('excuse_letters')->row_array();

            if ($existing) {
                $this->send_error('Excuse letter already submitted for this date and class', 400);
                return;
            }

            $excuse_data = [
                'student_id' => $user_data['user_id'],
                'class_id' => $data->class_id,
                'teacher_id' => $class['teacher_id'], // Add teacher_id from class
                'date_absent' => $data->date_absent,
                'reason' => $data->reason,
                'image_path' => $image_path ?? (isset($data->image_path) ? $data->image_path : null),
                'status' => 'pending',
                'teacher_notes' => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('excuse_letters', $excuse_data);
            $letter_id = $this->db->insert_id();

            // Log excuse letter submission
            $subject_name = $class['subject_name'] ?? 'Unknown Subject';
            $section_name = $class['section_name'] ?? 'Unknown Section';
            
            log_audit_event(
                'SUBMITTED EXCUSE LETTER',
                'EXCUSE LETTER MANAGEMENT',
                "Student submitted excuse letter for {$subject_name} ({$section_name}) - Date: {$data->date_absent}",
                [
                    'table_name' => 'excuse_letters',
                    'record_id' => $letter_id
                ]
            );

            // Send notification to teacher about the excuse letter submission
            $this->send_excuse_letter_notification($class, $user_data, $letter_id, $data->date_absent, $data->reason);

            // Get detailed excuse letter with class info
            $excuse_letter = $this->db->select('
                excuse_letters.*,
                subjects.subject_name,
                subjects.subject_code,
                sections.section_name,
                users.full_name as student_name
            ')
            ->from('excuse_letters')
            ->join('classes', 'excuse_letters.class_id = classes.class_id', 'left')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->join('users', 'excuse_letters.student_id = users.user_id', 'left')
            ->where('excuse_letters.letter_id', $letter_id)
            ->get()->row_array();

            $this->send_success($excuse_letter, 'Excuse letter submitted successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to submit excuse letter: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get student's submitted excuse letters
     * Endpoint: GET /api/excuse-letters/student
     */
    public function student_get()
    {
        $user_data = require_student($this);
        if (!$user_data) return;

        try {
            // Get query parameters
            $class_id = $this->input->get('class_id');
            $status = $this->input->get('status');
            $limit = (int)($this->input->get('limit') ?: 50);
            $offset = (int)($this->input->get('offset') ?: 0);

            // Validate limit and offset
            if ($limit > 100) $limit = 100;
            if ($limit < 1) $limit = 50;
            if ($offset < 0) $offset = 0;

            // Build query
            $this->db->select('
                excuse_letters.*,
                subjects.subject_name,
                subjects.subject_code,
                sections.section_name,
                users.full_name as teacher_name
            ')
            ->from('excuse_letters')
            ->join('classes', 'excuse_letters.class_id = classes.class_id', 'left')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->join('users', 'classes.teacher_id = users.user_id', 'left')
            ->where('excuse_letters.student_id', $user_data['user_id']);

            // Apply filters
            if ($class_id) {
                $this->db->where('excuse_letters.class_id', $class_id);
            }
            if ($status) {
                $this->db->where('excuse_letters.status', $status);
            }

            // Get total count for pagination
            $total_records = $this->db->count_all_results();

            // Rebuild query for actual data
            $this->db->select('
                excuse_letters.*,
                subjects.subject_name,
                subjects.subject_code,
                sections.section_name,
                users.full_name as teacher_name
            ')
            ->from('excuse_letters')
            ->join('classes', 'excuse_letters.class_id = classes.class_id', 'left')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->join('users', 'classes.teacher_id = users.user_id', 'left')
            ->where('excuse_letters.student_id', $user_data['user_id']);

            // Apply filters again
            if ($class_id) {
                $this->db->where('excuse_letters.class_id', $class_id);
            }
            if ($status) {
                $this->db->where('excuse_letters.status', $status);
            }

            // Apply pagination and ordering
            $this->db->limit($limit, $offset);
            $this->db->order_by('excuse_letters.created_at', 'DESC');

            $excuse_letters = $this->db->get()->result_array();

            // Calculate summary statistics
            $summary = [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0
            ];

            foreach ($excuse_letters as $letter) {
                $summary['total']++;
                switch (strtolower($letter['status'])) {
                    case 'pending':
                        $summary['pending']++;
                        break;
                    case 'approved':
                        $summary['approved']++;
                        break;
                    case 'rejected':
                        $summary['rejected']++;
                        break;
                }
            }

            // Get available classes for filtering
            $classes = $this->db->select('
                classes.class_id,
                subjects.subject_name,
                subjects.subject_code,
                sections.section_name
            ')
            ->from('classes')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->where('classes.teacher_id', '!=', $user_data['user_id']) // Classes where student is enrolled
            ->group_by('classes.class_id, subjects.subject_name, subjects.subject_code, sections.section_name')
            ->order_by('subjects.subject_name', 'ASC')
            ->get()->result_array();

            // Prepare response
            $response = [
                'excuse_letters' => $excuse_letters,
                'summary' => $summary,
                'available_classes' => $classes,
                'pagination' => [
                    'total_records' => $total_records,
                    'limit' => $limit,
                    'offset' => $offset,
                    'total_pages' => ceil($total_records / $limit),
                    'current_page' => floor($offset / $limit) + 1
                ],
                'filters' => [
                    'class_id' => $class_id,
                    'status' => $status
                ]
            ];

            $this->send_success($response, 'Student excuse letters retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve excuse letters: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get teacher's pending excuse letters to review
     * Endpoint: GET /api/excuse-letters/teacher
     */
    public function teacher_get()
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            // Get query parameters
            $class_id = $this->input->get('class_id');
            $status = $this->input->get('status');
            $limit = (int)($this->input->get('limit') ?: 50);
            $offset = (int)($this->input->get('offset') ?: 0);

            // Validate limit and offset
            if ($limit > 100) $limit = 100;
            if ($limit < 1) $limit = 50;
            if ($offset < 0) $offset = 0;

            // Build query
            $this->db->select('
                excuse_letters.*,
                subjects.subject_name,
                subjects.subject_code,
                sections.section_name,
                students.full_name as student_name,
                students.student_num,
                students.email as student_email
            ')
            ->from('excuse_letters')
            ->join('classes', 'excuse_letters.class_id = classes.class_id', 'left')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->join('users as students', 'excuse_letters.student_id = students.user_id', 'left')
            ->where('excuse_letters.teacher_id', $user_data['user_id']);

            // Apply filters
            if ($class_id) {
                $this->db->where('excuse_letters.class_id', $class_id);
            }
            if ($status) {
                $this->db->where('excuse_letters.status', $status);
            }

            // Get total count for pagination
            $total_records = $this->db->count_all_results();

            // Rebuild query for actual data
            $this->db->select('
                excuse_letters.*,
                subjects.subject_name,
                subjects.subject_code,
                sections.section_name,
                students.full_name as student_name,
                students.student_num,
                students.email as student_email
            ')
            ->from('excuse_letters')
            ->join('classes', 'excuse_letters.class_id = classes.class_id', 'left')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->join('users as students', 'excuse_letters.student_id = students.user_id', 'left')
            ->where('excuse_letters.teacher_id', $user_data['user_id']);

            // Apply filters again
            if ($class_id) {
                $this->db->where('excuse_letters.class_id', $class_id);
            }
            if ($status) {
                $this->db->where('excuse_letters.status', $status);
            }

            // Apply pagination and ordering
            $this->db->limit($limit, $offset);
            $this->db->order_by('excuse_letters.created_at', 'DESC');

            $excuse_letters = $this->db->get()->result_array();

            // Calculate summary statistics
            $summary = [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0
            ];

            foreach ($excuse_letters as $letter) {
                $summary['total']++;
                switch (strtolower($letter['status'])) {
                    case 'pending':
                        $summary['pending']++;
                        break;
                    case 'approved':
                        $summary['approved']++;
                        break;
                    case 'rejected':
                        $summary['rejected']++;
                        break;
                }
            }

            // Get teacher's classes for filtering
            $classes = $this->db->select('
                classes.class_id,
                subjects.subject_name,
                subjects.subject_code,
                sections.section_name
            ')
            ->from('classes')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->where('classes.teacher_id', $user_data['user_id'])
            ->where('classes.status', 'active')
            ->order_by('subjects.subject_name', 'ASC')
            ->get()->result_array();

            // Prepare response
            $response = [
                'excuse_letters' => $excuse_letters,
                'summary' => $summary,
                'available_classes' => $classes,
                'pagination' => [
                    'total_records' => $total_records,
                    'limit' => $limit,
                    'offset' => $offset,
                    'total_pages' => ceil($total_records / $limit),
                    'current_page' => floor($offset / $limit) + 1
                ],
                'filters' => [
                    'class_id' => $class_id,
                    'status' => $status
                ]
            ];

            $this->send_success($response, 'Teacher excuse letters retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve excuse letters: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update excuse letter status (Teacher only)
     * Endpoint: PUT /api/excuse-letters/update/{letter_id}
     */
    public function update_put($letter_id = null)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        if (!$letter_id) {
            $this->send_error('Letter ID is required', 400);
            return;
        }

        $data = $this->get_json_input();
        if (!$data) return;

        if (!isset($data->status)) {
            $this->send_error('Status is required', 400);
            return;
        }

        // Validate status
        $valid_statuses = ['pending', 'approved', 'rejected'];
        if (!in_array(strtolower($data->status), $valid_statuses)) {
            $this->send_error('Invalid status. Must be: pending, approved, or rejected', 400);
            return;
        }

        try {
            // Get excuse letter and verify teacher access
            $excuse_letter = $this->db->where('letter_id', $letter_id)
                ->where('teacher_id', $user_data['user_id'])
                ->get('excuse_letters')->row_array();

            if (!$excuse_letter) {
                $this->send_error('Excuse letter not found or access denied', 404);
                return;
            }

            $update_data = [
                'status' => strtolower($data->status),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (isset($data->teacher_notes)) {
                $update_data['teacher_notes'] = $data->teacher_notes;
            }

            $this->db->where('letter_id', $letter_id);
            $this->db->update('excuse_letters', $update_data);

            // If status is approved, automatically mark attendance as excused
            if (strtolower($data->status) === 'approved') {
                $this->mark_attendance_as_excused($excuse_letter);
            } elseif (strtolower($data->status) === 'rejected') {
                // If status is rejected, mark attendance as absent
                $this->mark_attendance_as_absent($excuse_letter);
            }

            // Send notification to student about the status update
            $this->send_excuse_letter_status_notification($excuse_letter, $data->status, $data->teacher_notes ?? null);

            $this->send_success(null, 'Excuse letter status updated successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to update excuse letter: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete excuse letter (Student only - if pending)
     * Endpoint: DELETE /api/excuse-letters/delete/{letter_id}
     */
    public function delete_delete($letter_id = null)
    {
        $user_data = require_student($this);
        if (!$user_data) return;

        if (!$letter_id) {
            $this->send_error('Letter ID is required', 400);
            return;
        }

        try {
            // Get excuse letter and verify student access
            $excuse_letter = $this->db->where('letter_id', $letter_id)
                ->where('student_id', $user_data['user_id'])
                ->get('excuse_letters')->row_array();

            if (!$excuse_letter) {
                $this->send_error('Excuse letter not found', 404);
                return;
            }

            // Only allow deletion if status is pending
            if ($excuse_letter['status'] !== 'pending') {
                $this->send_error('Can only delete pending excuse letters', 400);
                return;
            }

            $this->db->where('letter_id', $letter_id);
            $this->db->delete('excuse_letters');

            $this->send_success(null, 'Excuse letter deleted successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to delete excuse letter: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mark attendance as excused when excuse letter is approved
     */
    private function mark_attendance_as_excused($excuse_letter)
    {
        try {
            $student_id = $excuse_letter['student_id'];
            $class_id = $excuse_letter['class_id'];
            $date_absent = $excuse_letter['date_absent'];
            $teacher_id = $excuse_letter['teacher_id'];

            // First, try to find the class in the classes table
            $class = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                ->from('classes')
                ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                ->join('sections', 'classes.section_id = sections.section_id', 'left')
                ->where('classes.class_id', $class_id)
                ->get()->row_array();

            // If not found in classes table, try to find corresponding classroom
            if (!$class) {
                $classroom = $this->db->select('classrooms.*, subjects.subject_name, sections.section_name')
                    ->from('classrooms')
                    ->join('subjects', 'classrooms.subject_id = subjects.id', 'left')
                    ->join('sections', 'classrooms.section_id = sections.section_id', 'left')
                    ->where('classrooms.id', $class_id)
                    ->get()->row_array();

                if ($classroom) {
                    // Find corresponding class in classes table based on subject and section
                    $class = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                        ->from('classes')
                        ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                        ->join('sections', 'classes.section_id = sections.section_id', 'left')
                        ->where('classes.subject_id', $classroom['subject_id'])
                        ->where('classes.section_id', $classroom['section_id'])
                        ->where('classes.teacher_id', $teacher_id)
                        ->get()->row_array();
                }
            }

            if (!$class) {
                log_message('error', 'Class not found for excuse letter attendance marking. Class ID: ' . $class_id);
                return;
            }

            // Check if attendance record already exists for this student, class, and date
            $existing_attendance = $this->db->where('student_id', $student_id)
                ->where('class_id', $class['class_id'])
                ->where('date', $date_absent)
                ->get('attendance')
                ->row_array();

            if ($existing_attendance) {
                // Update existing attendance record
                $this->db->where('attendance_id', $existing_attendance['attendance_id']);
                $this->db->update('attendance', [
                    'status' => 'Excused',
                    'notes' => 'Automatically marked as excused due to approved excuse letter',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Create new attendance record
                $this->db->insert('attendance', [
                    'student_id' => $student_id,
                    'subject_id' => $class['subject_id'],
                    'class_id' => $class['class_id'],
                    'date' => $date_absent,
                    'time_in' => date('H:i:s'),
                    'status' => 'Excused',
                    'notes' => 'Automatically marked as excused due to approved excuse letter',
                    'teacher_id' => $teacher_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            log_message('info', 'Attendance marked as excused for student ' . $student_id . ' on ' . $date_absent);
        } catch (Exception $e) {
            log_message('error', 'Failed to mark attendance as excused: ' . $e->getMessage());
        }
    }

    /**
     * Mark attendance as absent when excuse letter is rejected
     */
    private function mark_attendance_as_absent($excuse_letter)
    {
        try {
            $student_id = $excuse_letter['student_id'];
            $class_id = $excuse_letter['class_id'];
            $date_absent = $excuse_letter['date_absent'];
            $teacher_id = $excuse_letter['teacher_id'];

            // First, try to find the class in the classes table
            $class = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                ->from('classes')
                ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                ->join('sections', 'classes.section_id = sections.section_id', 'left')
                ->where('classes.class_id', $class_id)
                ->get()->row_array();

            // If not found in classes table, try to find corresponding classroom
            if (!$class) {
                $classroom = $this->db->select('classrooms.*, subjects.subject_name, sections.section_name')
                    ->from('classrooms')
                    ->join('subjects', 'classrooms.subject_id = subjects.id', 'left')
                    ->join('sections', 'classrooms.section_id = sections.section_id', 'left')
                    ->where('classrooms.id', $class_id)
                    ->get()->row_array();

                if ($classroom) {
                    // Find corresponding class in classes table based on subject and section
                    $class = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                        ->from('classes')
                        ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                        ->join('sections', 'classes.section_id = sections.section_id', 'left')
                        ->where('classes.subject_id', $classroom['subject_id'])
                        ->where('classes.section_id', $classroom['section_id'])
                        ->where('classes.teacher_id', $teacher_id)
                        ->get()->row_array();
                }
            }

            if (!$class) {
                log_message('error', 'Class not found for excuse letter attendance marking. Class ID: ' . $class_id);
                return;
            }

            // Check if attendance record already exists for this student, class, and date
            $existing_attendance = $this->db->where('student_id', $student_id)
                ->where('class_id', $class['class_id'])
                ->where('date', $date_absent)
                ->get('attendance')
                ->row_array();

            if ($existing_attendance) {
                // Update existing attendance record
                $this->db->where('attendance_id', $existing_attendance['attendance_id']);
                $this->db->update('attendance', [
                    'status' => 'Absent',
                    'notes' => 'Automatically marked as absent due to rejected excuse letter',
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Create new attendance record
                $this->db->insert('attendance', [
                    'student_id' => $student_id,
                    'subject_id' => $class['subject_id'],
                    'class_id' => $class['class_id'],
                    'date' => $date_absent,
                    'time_in' => date('H:i:s'),
                    'status' => 'Absent',
                    'notes' => 'Automatically marked as absent due to rejected excuse letter',
                    'teacher_id' => $teacher_id,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }

            log_message('info', 'Attendance marked as absent for student ' . $student_id . ' on ' . $date_absent);
        } catch (Exception $e) {
            log_message('error', 'Failed to mark attendance as absent: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to teacher when student submits an excuse letter
     */
    private function send_excuse_letter_notification($class, $student_data, $letter_id, $date_absent, $reason)
    {
        try {
            $student_name = $student_data['full_name'];
            $teacher_id = $class['teacher_id'];
            $subject_name = $class['subject_name'] ?? 'Unknown Subject';
            $section_name = $class['section_name'] ?? 'Unknown Section';
            
            $title = "New Excuse Letter: {$subject_name}";
            $message = "{$student_name} has submitted an excuse letter for {$subject_name} ({$section_name}) - Date: {$date_absent}";
            
            // Create notification for the teacher (without class_code since it's not available in classes table)
            create_excuse_letter_notification(
                $teacher_id,
                $letter_id,
                $title,
                $message,
                null // No class_code available from classes table
            );
            
            // Log notification sending
            log_message('info', "Excuse letter notification sent to teacher {$teacher_id} for letter {$letter_id}");
            
        } catch (Exception $e) {
            // Log error but don't fail the submission
            log_message('error', "Failed to send excuse letter notification: " . $e->getMessage());
        }
    }

    /**
     * Send notification to student when teacher updates excuse letter status
     */
    private function send_excuse_letter_status_notification($excuse_letter, $status, $teacher_notes = null)
    {
        try {
            $student_id = $excuse_letter['student_id'];
            $letter_id = $excuse_letter['letter_id'];
            $date_absent = $excuse_letter['date_absent'];
            $reason = $excuse_letter['reason'];
            
            // Get class and subject information
            $class_info = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                ->from('classes')
                ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                ->join('sections', 'classes.section_id = sections.section_id', 'left')
                ->where('classes.class_id', $excuse_letter['class_id'])
                ->get()->row_array();
            
            $subject_name = $class_info['subject_name'] ?? 'Unknown Subject';
            $section_name = $class_info['section_name'] ?? 'Unknown Section';
            
            // Create status-specific title and message
            $status_display = ucfirst($status);
            $title = "Excuse Letter {$status_display}: {$subject_name}";
            
            $message = "Your excuse letter for {$subject_name} ({$section_name}) has been {$status}.";
            $message .= " Date: {$date_absent}";
            
            if ($teacher_notes) {
                $message .= "\n\nTeacher Notes: {$teacher_notes}";
            }
            
            // Create notification for the student
            create_excuse_letter_notification(
                $student_id,
                $letter_id,
                $title,
                $message,
                null // No class_code available from classes table
            );
            
            // Log notification sending
            log_message('info', "Excuse letter status notification sent to student {$student_id} for letter {$letter_id} - Status: {$status}");
            
        } catch (Exception $e) {
            // Log error but don't fail the update
            log_message('error', "Failed to send excuse letter status notification: " . $e->getMessage());
        }
    }
} 