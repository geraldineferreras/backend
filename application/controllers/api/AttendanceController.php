
<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

class AttendanceController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Attendance_model');
        $this->load->model('Class_model');
        $this->load->model('Student_model');
        $this->load->model('Subject_model');
        $this->load->model('Section_model');
        $this->load->helper(['auth', 'audit', 'attendance_log']);
        $this->load->library('Token_lib');
    }

    /**
     * Get teacher's assigned classes for attendance
     * Endpoint: GET /api/attendance/classes
     */
    public function classes_get()
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            // Get classes assigned to this teacher
            $classes = $this->db->select('
                classes.*, 
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
            ->order_by('sections.section_name', 'ASC')
            ->get()->result_array();

            $this->send_success($classes, 'Classes retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve classes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get students enrolled in a specific class with excuse letter status for a date
     * Endpoint: GET /api/attendance/students/{class_id}/{date}
     */
    public function students_get($class_id = null, $date = null)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        if (!$class_id) {
            $this->send_error('Class ID is required', 400);
            return;
        }

        try {
            // Verify teacher has access to this class
            $class = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                ->from('classes')
                ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                ->join('sections', 'classes.section_id = sections.section_id', 'left')
                ->where('classes.class_id', $class_id)
                ->where('classes.teacher_id', $user_data['user_id'])
                ->get()->row_array();

            if (!$class) {
                $this->send_error('Class not found or access denied', 404);
                return;
            }

            // Find the corresponding classroom for this class
            $classroom = $this->db->select('classrooms.*')
                ->from('classrooms')
                ->where('classrooms.subject_id', $class['subject_id'])
                ->where('classrooms.section_id', $class['section_id'])
                ->where('classrooms.teacher_id', $class['teacher_id'])
                ->where('classrooms.is_active', 1)
                ->get()->row_array();

            if (!$classroom) {
                $this->send_error('No active classroom found for this class', 404);
                return;
            }

            // Get students enrolled in this class through classroom_enrollments
            $students = $this->db->select('
                users.user_id,
                users.full_name,
                users.student_num,
                users.email,
                users.section_id
            ')
            ->from('users')
            ->join('classroom_enrollments', 'users.user_id = classroom_enrollments.student_id')
            ->where('classroom_enrollments.classroom_id', $classroom['id'])
            ->where('classroom_enrollments.status', 'active')
            ->where('users.role', 'student')
            ->where('users.status', 'active')
            ->order_by('users.full_name', 'ASC')
            ->get()->result_array();

            // If date is provided, check for excuse letters
            if ($date) {
                foreach ($students as &$student) {
                    $excuse_letter = $this->check_excuse_letter($student['user_id'], $class['class_id'], $date);
                    if ($excuse_letter) {
                        $student['excuse_letter'] = [
                            'letter_id' => $excuse_letter['letter_id'],
                            'reason' => $excuse_letter['reason'],
                            'status' => $excuse_letter['status'],
                            'teacher_notes' => $excuse_letter['teacher_notes']
                        ];
                    } else {
                        $student['excuse_letter'] = null;
                    }
                }
            }

            $this->send_success([
                'class' => $class,
                'students' => $students,
                'date' => $date
            ], 'Students retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve students: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Record attendance via QR code or manual entry
     * Endpoint: POST /api/attendance/record
     */
    /**
     * Check if student has approved excuse letter for a specific date and class
     */
    private function check_excuse_letter($student_id, $class_id, $date)
    {
        $excuse_letter = $this->db->select('excuse_letters.*')
            ->from('excuse_letters')
            ->where('excuse_letters.student_id', $student_id)
            ->where('excuse_letters.class_id', $class_id)
            ->where('excuse_letters.date_absent', $date)
            ->where('excuse_letters.status', 'approved')
            ->get()->row_array();

        return $excuse_letter;
    }

    public function record_post()
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $data = $this->get_json_input();
        if (!$data) return;

        // Validate required fields
        $required_fields = ['student_id', 'class_id', 'date', 'status'];
        if (!$this->validate_required_fields($data, $required_fields)) {
            return;
        }

        try {
            // Verify teacher has access to this class
            $class = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                ->from('classes')
                ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                ->join('sections', 'classes.section_id = sections.section_id', 'left')
                ->where('classes.class_id', $data->class_id)
                ->where('classes.teacher_id', $user_data['user_id'])
                ->get()->row_array();

            if (!$class) {
                $this->send_error('Class not found or access denied', 404);
                return;
            }

            // Find the corresponding classroom for this class
            $classroom = $this->db->select('classrooms.*')
                ->from('classrooms')
                ->where('classrooms.subject_id', $class['subject_id'])
                ->where('classrooms.section_id', $class['section_id'])
                ->where('classrooms.teacher_id', $class['teacher_id'])
                ->where('classrooms.is_active', 1)
                ->get()->row_array();

            if (!$classroom) {
                $this->send_error('No active classroom found for this class', 404);
                return;
            }

            // Check if student is enrolled in the corresponding classroom
            $enrollment = $this->db->select('classroom_enrollments.*')
                ->from('classroom_enrollments')
                ->where('classroom_enrollments.classroom_id', $classroom['id'])
                ->where('classroom_enrollments.student_id', $data->student_id)
                ->where('classroom_enrollments.status', 'active')
                ->get()->row_array();

            if (!$enrollment) {
                $this->send_error('Student not enrolled in this class', 400);
                return;
            }

            // Get student details for the attendance record
            $student = $this->db->select('users.*')
                ->from('users')
                ->where('users.user_id', $data->student_id)
                ->where('users.role', 'student')
                ->where('users.status', 'active')
                ->get()->row_array();

            if (!$student) {
                $this->send_error('Student not found or inactive', 400);
                return;
            }

            // Check for existing attendance record
            $existing = $this->db->where('student_id', $data->student_id)
                ->where('class_id', $class['class_id'])
                ->where('date', $data->date)
                ->get('attendance')->row_array();

            // Check for approved excuse letter
            $excuse_letter = $this->check_excuse_letter($data->student_id, $class['class_id'], $data->date);
            
            // Determine final status
            $final_status = $data->status;
            $excuse_note = null;
            
            if ($excuse_letter && $data->status === 'absent') {
                // If student is marked absent but has approved excuse letter, mark as excused
                $final_status = 'excused';
                $excuse_note = "Auto-excused: " . $excuse_letter['reason'];
            } elseif ($excuse_letter && $data->status === 'present') {
                // If student is present, keep as present (excuse letter doesn't override present status)
                $final_status = 'present';
            }

            $attendance_data = [
                'student_id' => $data->student_id,
                'class_id' => $class['class_id'],
                'subject_id' => $class['subject_id'],
                'section_name' => $class['section_name'],
                'date' => $data->date,
                'time_in' => isset($data->time_in) ? $data->time_in : date('H:i:s'),
                'status' => $final_status,
                'notes' => isset($data->notes) ? $data->notes : $excuse_note,
                'teacher_id' => $user_data['user_id'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($existing) {
                // Update existing record
                $this->db->where('attendance_id', $existing['attendance_id']);
                $this->db->update('attendance', $attendance_data);
                $message = 'Attendance updated successfully';
                $attendance_id = $existing['attendance_id'];
                
                // Log attendance update to audit logs
                log_audit_event(
                    'UPDATED ATTENDANCE',
                    'ATTENDANCE MANAGEMENT',
                    "Teacher updated attendance for student {$student['full_name']} - Class: {$class['subject_name']} ({$class['section_name']}) - Date: {$data->date} - Status: {$final_status}",
                    [
                        'table_name' => 'attendance',
                        'record_id' => $attendance_id
                    ]
                );
                
                // Log to dedicated attendance_logs table
                $attendance_data['attendance_id'] = $attendance_id;
                $attendance_data['class_id'] = $class['class_id'];
                log_attendance_event($attendance_data, 'UPDATED', $user_data, [
                    'excuse_letter' => $excuse_letter,
                    'remarks' => 'Attendance updated by teacher'
                ]);
            } else {
                // Insert new record
                $this->db->insert('attendance', $attendance_data);
                $attendance_id = $this->db->insert_id();
                $message = 'Attendance recorded successfully';
                
                // Log attendance recording to audit logs
                log_audit_event(
                    'RECORDED ATTENDANCE',
                    'ATTENDANCE MANAGEMENT',
                    "Teacher recorded attendance for student {$student['full_name']} - Class: {$class['subject_name']} ({$class['section_name']}) - Date: {$data->date} - Status: {$final_status}",
                    [
                        'table_name' => 'attendance',
                        'record_id' => $attendance_id
                    ]
                );
                
                // Log to dedicated attendance_logs table
                $attendance_data['attendance_id'] = $attendance_id;
                $attendance_data['class_id'] = $class['class_id'];
                log_attendance_event($attendance_data, 'RECORDED', $user_data, [
                    'excuse_letter' => $excuse_letter,
                    'remarks' => 'Attendance recorded by teacher'
                ]);
            }

            // Get detailed attendance record with student info
            $attendance_record = $this->db->select('
                attendance.*,
                users.full_name as student_name,
                users.student_num,
                users.email as student_email,
                subjects.subject_name,
                sections.section_name
            ')
            ->from('attendance')
            ->join('users', 'attendance.student_id = users.user_id', 'left')
            ->join('subjects', 'attendance.subject_id = subjects.id', 'left')
            ->join('sections', 'attendance.section_name = sections.section_name', 'left')
            ->where('attendance.attendance_id', $attendance_id)
            ->get()->row_array();

            // Add excuse letter info if applicable
            if ($excuse_letter) {
                $attendance_record['excuse_letter'] = [
                    'letter_id' => $excuse_letter['letter_id'],
                    'reason' => $excuse_letter['reason'],
                    'teacher_notes' => $excuse_letter['teacher_notes'],
                    'status' => $excuse_letter['status']
                ];
            }

            $this->send_success($attendance_record, $message);
        } catch (Exception $e) {
            $this->send_error('Failed to record attendance: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Record bulk attendance (manual entry)
     * Endpoint: POST /api/attendance/bulk-record
     */
    public function bulk_record_post()
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $data = $this->get_json_input();
        if (!$data) return;

        // Validate required fields
        $required_fields = ['class_id', 'date', 'attendance_records'];
        if (!$this->validate_required_fields($data, $required_fields)) {
            return;
        }

        if (!is_array($data->attendance_records) || empty($data->attendance_records)) {
            $this->send_error('Attendance records array is required', 400);
            return;
        }

        try {
            // Verify teacher has access to this class
            $class = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                ->from('classes')
                ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                ->join('sections', 'classes.section_id = sections.section_id', 'left')
                ->where('classes.class_id', $data->class_id)
                ->where('classes.teacher_id', $user_data['user_id'])
                ->get()->row_array();

            if (!$class) {
                $this->send_error('Class not found or access denied', 404);
                return;
            }

            // Find the corresponding classroom for this class
            $classroom = $this->db->select('classrooms.*')
                ->from('classrooms')
                ->where('classrooms.subject_id', $class['subject_id'])
                ->where('classrooms.section_id', $class['section_id'])
                ->where('classrooms.teacher_id', $class['teacher_id'])
                ->where('classrooms.is_active', 1)
                ->get()->row_array();

            if (!$classroom) {
                $this->send_error('No active classroom found for this class', 404);
                return;
            }

            $this->db->trans_start();

            $success_count = 0;
            $error_count = 0;
            $errors = [];

            foreach ($data->attendance_records as $record) {
                if (!isset($record->student_id) || !isset($record->status)) {
                    $error_count++;
                    $errors[] = 'Invalid record format';
                    continue;
                }

                // Check if student is enrolled in the corresponding classroom
                $enrollment = $this->db->select('classroom_enrollments.*')
                    ->from('classroom_enrollments')
                    ->where('classroom_enrollments.classroom_id', $classroom['id'])
                    ->where('classroom_enrollments.student_id', $record->student_id)
                    ->where('classroom_enrollments.status', 'active')
                    ->get()->row_array();

                if (!$enrollment) {
                    $error_count++;
                    $errors[] = "Student {$record->student_id} not enrolled";
                    continue;
                }

                // Get student details for the attendance record
                $student = $this->db->select('users.*')
                    ->from('users')
                    ->where('users.user_id', $record->student_id)
                    ->where('users.role', 'student')
                    ->where('users.status', 'active')
                    ->get()->row_array();

                if (!$student) {
                    $error_count++;
                    $errors[] = "Student {$record->student_id} not found or inactive";
                    continue;
                }

                // Check for approved excuse letter
                $excuse_letter = $this->check_excuse_letter($record->student_id, $class['class_id'], $data->date);
                
                // Determine final status
                $final_status = $record->status;
                $excuse_note = null;
                
                if ($excuse_letter && $record->status === 'absent') {
                    // If student is marked absent but has approved excuse letter, mark as excused
                    $final_status = 'excused';
                    $excuse_note = "Auto-excused: " . $excuse_letter['reason'];
                } elseif ($excuse_letter && $record->status === 'present') {
                    // If student is present, keep as present (excuse letter doesn't override present status)
                    $final_status = 'present';
                }

                $attendance_data = [
                    'student_id' => $record->student_id,
                    'class_id' => $class['class_id'],
                    'subject_id' => $class['subject_id'],
                    'section_name' => $class['section_name'],
                    'date' => $data->date,
                    'time_in' => isset($record->time_in) ? $record->time_in : date('H:i:s'),
                    'status' => $final_status,
                    'notes' => isset($record->notes) ? $record->notes : $excuse_note,
                    'teacher_id' => $user_data['user_id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];

                // Check for existing record
                $existing = $this->db->where('student_id', $record->student_id)
                    ->where('class_id', $class['class_id'])
                    ->where('date', $data->date)
                    ->get('attendance')->row_array();

                if ($existing) {
                    // Update existing record
                    $this->db->where('attendance_id', $existing['attendance_id']);
                    $this->db->update('attendance', $attendance_data);
                } else {
                    // Insert new record
                    $this->db->insert('attendance', $attendance_data);
                }

                $success_count++;
            }

            $this->db->trans_complete();

            if ($this->db->trans_status() === FALSE) {
                $this->send_error('Database transaction failed', 500);
                return;
            }

            $this->send_success([
                'success_count' => $success_count,
                'error_count' => $error_count,
                'errors' => $errors
            ], "Bulk attendance recorded: {$success_count} successful, {$error_count} failed");
        } catch (Exception $e) {
            $this->send_error('Failed to record bulk attendance: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get attendance records for a specific class and date
     * Endpoint: GET /api/attendance/records/{class_id}/{date}
     */
    public function records_get($class_id = null, $date = null)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        if (!$class_id || !$date) {
            $this->send_error('Class ID and date are required', 400);
            return;
        }

        try {
            // Verify teacher has access to this class
            $class = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                ->from('classes')
                ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                ->join('sections', 'classes.section_id = sections.section_id', 'left')
                ->where('classes.class_id', $class_id)
                ->where('classes.teacher_id', $user_data['user_id'])
                ->get()->row_array();

            if (!$class) {
                $this->send_error('Class not found or access denied', 404);
                return;
            }

            // Find the corresponding classroom for this class
            $classroom = $this->db->select('classrooms.*')
                ->from('classrooms')
                ->where('classrooms.subject_id', $class['subject_id'])
                ->where('classrooms.section_id', $class['section_id'])
                ->where('classrooms.teacher_id', $class['teacher_id'])
                ->where('classrooms.is_active', 1)
                ->get()->row_array();

            if (!$classroom) {
                $this->send_error('No active classroom found for this class', 404);
                return;
            }

            // Get attendance records
            $records = $this->db->select('
                attendance.*,
                users.full_name as student_name,
                users.student_num,
                users.email as student_email
            ')
            ->from('attendance')
            ->join('users', 'attendance.student_id = users.user_id', 'left')
            ->where('attendance.class_id', $class['class_id'])
            ->where('attendance.date', $date)
            ->order_by('users.full_name', 'ASC')
            ->get()->result_array();

            // Get all enrolled students (to show who hasn't been recorded)
            $enrolled_students = $this->db->select('
                users.user_id,
                users.full_name,
                users.student_num,
                users.email
            ')
            ->from('users')
            ->join('classroom_enrollments', 'users.user_id = classroom_enrollments.student_id')
            ->where('classroom_enrollments.classroom_id', $classroom['id'])
            ->where('classroom_enrollments.status', 'active')
            ->where('users.role', 'student')
            ->where('users.status', 'active')
            ->order_by('users.full_name', 'ASC')
            ->get()->result_array();

            // Create a map of recorded students
            $recorded_students = [];
            foreach ($records as $record) {
                $recorded_students[$record['student_id']] = $record;
            }

            // Add missing students with null attendance
            foreach ($enrolled_students as $student) {
                if (!isset($recorded_students[$student['user_id']])) {
                    $records[] = [
                        'student_id' => $student['user_id'],
                        'student_name' => $student['full_name'],
                        'student_num' => $student['student_num'],
                        'student_email' => $student['email'],
                        'status' => null,
                        'time_in' => null,
                        'notes' => null
                    ];
                }
            }

            // Calculate summary
            $summary = [
                'present' => 0,
                'late' => 0,
                'absent' => 0,
                'excused' => 0,
                'total' => count($enrolled_students)
            ];

            foreach ($records as $record) {
                if ($record['status']) {
                    switch (strtolower($record['status'])) {
                        case 'present':
                            $summary['present']++;
                            break;
                        case 'late':
                            $summary['late']++;
                            break;
                        case 'absent':
                            $summary['absent']++;
                            break;
                        case 'excused':
                            $summary['excused']++;
                            break;
                    }
                }
            }

            $this->send_success([
                'class' => $class,
                'date' => $date,
                'records' => $records,
                'summary' => $summary
            ], 'Attendance records retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve attendance records: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update attendance status
     * Endpoint: PUT /api/attendance/update/{attendance_id}
     */
    public function update_put($attendance_id = null)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        if (!$attendance_id) {
            $this->send_error('Attendance ID is required', 400);
            return;
        }

        $data = $this->get_json_input();
        if (!$data) return;

        try {
            // Get attendance record and verify teacher access
            $attendance = $this->db->select('attendance.*, classes.teacher_id')
                ->from('attendance')
                ->join('classes', 'attendance.class_id = classes.class_id', 'left')
                ->where('attendance.attendance_id', $attendance_id)
                ->get()->row_array();

            if (!$attendance) {
                $this->send_error('Attendance record not found', 404);
                return;
            }

            if ($attendance['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Access denied', 403);
                return;
            }

            $update_data = [
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (isset($data->status)) {
                $update_data['status'] = $data->status;
            }
            if (isset($data->time_in)) {
                $update_data['time_in'] = $data->time_in;
            }
            if (isset($data->notes)) {
                $update_data['notes'] = $data->notes;
            }

            $this->db->where('attendance_id', $attendance_id);
            $this->db->update('attendance', $update_data);

            $this->send_success(null, 'Attendance updated successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to update attendance: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get attendance summary for a class and date range
     * Endpoint: GET /api/attendance/summary/{class_id}
     */
    public function summary_get($class_id = null)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        if (!$class_id) {
            $this->send_error('Class ID is required', 400);
            return;
        }

        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');

        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }

        try {
            // Verify teacher has access to this class
            $class = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                ->from('classes')
                ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                ->join('sections', 'classes.section_id = sections.section_id', 'left')
                ->where('classes.class_id', $class_id)
                ->where('classes.teacher_id', $user_data['user_id'])
                ->get()->row_array();

            if (!$class) {
                $this->send_error('Class not found or access denied', 404);
                return;
            }

            // Get attendance summary
            $summary = $this->db->select('
                status,
                COUNT(*) as count,
                DATE(date) as date
            ')
            ->from('attendance')
            ->where('class_id', $class_id)
            ->where('date >=', $date_from)
            ->where('date <=', $date_to)
            ->group_by(['status', 'DATE(date)'])
            ->order_by('date', 'ASC')
            ->get()->result_array();

            // Get total students in class
            $total_students = $this->db->where('role', 'student')
                ->where('section_id', $class['section_id'])
                ->where('status', 'active')
                ->count_all_results('users');

            $this->send_success([
                'class' => $class,
                'date_range' => [
                    'from' => $date_from,
                    'to' => $date_to
                ],
                'summary' => $summary,
                'total_students' => $total_students
            ], 'Attendance summary retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve attendance summary: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Export attendance report
     * Endpoint: GET /api/attendance/export/{class_id}
     */
    public function export_get($class_id = null)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        if (!$class_id) {
            $this->send_error('Class ID is required', 400);
            return;
        }

        $date_from = $this->input->get('date_from');
        $date_to = $this->input->get('date_to');
        $format = $this->input->get('format') ?: 'json';

        if (!$date_from) {
            $date_from = date('Y-m-d', strtotime('-30 days'));
        }
        if (!$date_to) {
            $date_to = date('Y-m-d');
        }

        try {
            // Verify teacher has access to this class
            $class = $this->db->select('classes.*, subjects.subject_name, sections.section_name')
                ->from('classes')
                ->join('subjects', 'classes.subject_id = subjects.id', 'left')
                ->join('sections', 'classes.section_id = sections.section_id', 'left')
                ->where('classes.class_id', $class_id)
                ->where('classes.teacher_id', $user_data['user_id'])
                ->get()->row_array();

            if (!$class) {
                $this->send_error('Class not found or access denied', 404);
                return;
            }

            // Get attendance records for export
            $records = $this->db->select('
                attendance.*,
                users.full_name as student_name,
                users.student_num,
                users.email as student_email
            ')
            ->from('attendance')
            ->join('users', 'attendance.student_id = users.user_id', 'left')
            ->where('attendance.class_id', $class_id)
            ->where('attendance.date >=', $date_from)
            ->where('attendance.date <=', $date_to)
            ->order_by('attendance.date', 'DESC')
            ->order_by('users.full_name', 'ASC')
            ->get()->result_array();

            if ($format === 'csv') {
                // Generate CSV
                $filename = "attendance_report_{$class['subject_code']}_{$class['section_name']}_{$date_from}_to_{$date_to}.csv";
                
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                
                $output = fopen('php://output', 'w');
                
                // CSV headers
                fputcsv($output, [
                    'Date', 'Student Name', 'Student Number', 'Status', 
                    'Time In', 'Notes', 'Subject', 'Section'
                ]);
                
                foreach ($records as $record) {
                    fputcsv($output, [
                        $record['date'],
                        $record['student_name'],
                        $record['student_num'],
                        $record['status'],
                        $record['time_in'],
                        $record['notes'],
                        $class['subject_name'],
                        $class['section_name']
                    ]);
                }
                
                fclose($output);
                exit;
            } else {
                // Return JSON
                $this->send_success([
                    'class' => $class,
                    'date_range' => [
                        'from' => $date_from,
                        'to' => $date_to
                    ],
                    'records' => $records,
                    'total_records' => count($records)
                ], 'Attendance export data retrieved successfully');
            }
        } catch (Exception $e) {
            $this->send_error('Failed to export attendance: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete attendance record
     * Endpoint: DELETE /api/attendance/delete/{attendance_id}
     */
    public function delete_delete($attendance_id = null)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        if (!$attendance_id) {
            $this->send_error('Attendance ID is required', 400);
            return;
        }

        try {
            // Get attendance record and verify teacher access
            $attendance = $this->db->select('attendance.*, classes.teacher_id')
                ->from('attendance')
                ->join('classes', 'attendance.class_id = classes.class_id', 'left')
                ->where('attendance.attendance_id', $attendance_id)
                ->get()->row_array();

            if (!$attendance) {
                $this->send_error('Attendance record not found', 404);
                return;
            }

            if ($attendance['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Access denied', 403);
                return;
            }

            $this->db->where('attendance_id', $attendance_id);
            $this->db->delete('attendance');

            $this->send_success(null, 'Attendance record deleted successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to delete attendance record: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all attendance records for the logged-in teacher with optional filtering
     * Endpoint: GET /api/attendance/all
     * Query Parameters:
     * - class_id (optional): Filter by class ID
     * - date (optional): Filter by date (YYYY-MM-DD)
     * - status (optional): Filter by status (present/late/absent/excused)
     * - limit (optional): Limit results (default: 50)
     * - offset (optional): Offset for pagination (default: 0)
     * - student_id (optional): Filter by student ID
     */
    public function all_get()
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            // Get query parameters
            $class_id = $this->input->get('class_id');
            $date = $this->input->get('date');
            $status = $this->input->get('status');
            $student_id = $this->input->get('student_id');
            $limit = (int)($this->input->get('limit') ?: 50);
            $offset = (int)($this->input->get('offset') ?: 0);

            // Validate limit and offset
            if ($limit > 100) $limit = 100; // Maximum limit
            if ($limit < 1) $limit = 50;
            if ($offset < 0) $offset = 0;

            // Build query
            $this->db->select('
                attendance.*,
                users.full_name as student_name,
                users.student_num,
                users.email as student_email,
                subjects.subject_name,
                sections.section_name
            ')
            ->from('attendance')
            ->join('users', 'attendance.student_id = users.user_id', 'left')
            ->join('classes', 'attendance.class_id = classes.class_id', 'left')
            ->join('subjects', 'attendance.subject_id = subjects.id', 'left')
            ->join('sections', 'attendance.section_name = sections.section_name', 'left')
            ->where('attendance.teacher_id', $user_data['user_id']);

            // Apply filters
            if ($class_id) {
                $this->db->where('attendance.class_id', $class_id);
            }
            if ($date) {
                $this->db->where('attendance.date', $date);
            }
            if ($status) {
                $this->db->where('attendance.status', $status);
            }
            if ($student_id) {
                $this->db->where('attendance.student_id', $student_id);
            }

            // Get total count for pagination
            $total_records = $this->db->count_all_results();

            // Rebuild query for actual data
            $this->db->select('
                attendance.*,
                users.full_name as student_name,
                users.student_num,
                users.email as student_email,
                subjects.subject_name,
                sections.section_name
            ')
            ->from('attendance')
            ->join('users', 'attendance.student_id = users.user_id', 'left')
            ->join('classes', 'attendance.class_id = classes.class_id', 'left')
            ->join('subjects', 'attendance.subject_id = subjects.id', 'left')
            ->join('sections', 'attendance.section_name = sections.section_name', 'left')
            ->where('attendance.teacher_id', $user_data['user_id']);

            // Apply filters again
            if ($class_id) {
                $this->db->where('attendance.class_id', $class_id);
            }
            if ($date) {
                $this->db->where('attendance.date', $date);
            }
            if ($status) {
                $this->db->where('attendance.status', $status);
            }
            if ($student_id) {
                $this->db->where('attendance.student_id', $student_id);
            }

            // Apply pagination
            $this->db->limit($limit, $offset);
            $this->db->order_by('attendance.date', 'DESC');
            $this->db->order_by('attendance.time_in', 'DESC');

            $attendance_records = $this->db->get()->result_array();

            // Prepare response
            $response = [
                'attendance_records' => $attendance_records,
                'pagination' => [
                    'total_records' => $total_records,
                    'limit' => $limit,
                    'offset' => $offset,
                    'total_pages' => ceil($total_records / $limit),
                    'current_page' => floor($offset / $limit) + 1
                ],
                'filters' => [
                    'class_id' => $class_id,
                    'date' => $date,
                    'status' => $status,
                    'student_id' => $student_id
                ]
            ];

            $this->send_success($response, 'All attendance records retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve attendance records: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get teacher's assigned classes for attendance
     * Endpoint: GET /api/attendance/teacher-assignments
     */
    public function teacher_assignments_get()
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            // Get classes assigned to this teacher
            $classes = $this->db->select('
                classes.*, 
                subjects.subject_name, 
                subjects.subject_code,
                sections.section_name,
                users.full_name as teacher_name
            ')
            ->from('classes')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->join('users', 'classes.teacher_id = users.user_id', 'left')
            ->where('classes.teacher_id', $user_data['user_id'])
            ->where('classes.status', 'active')
            ->order_by('subjects.subject_name', 'ASC')
            ->order_by('sections.section_name', 'ASC')
            ->get()->result_array();

            // Add is_active field and students for each class
            foreach ($classes as &$class) {
                $class['is_active'] = ($class['status'] === 'active') ? '1' : '0';
                
                // Get students enrolled in this section
                $students = $this->db->select('
                    users.user_id,
                    users.full_name,
                    users.student_num,
                    users.email,
                    users.section_id
                ')
                ->from('users')
                ->where('users.role', 'student')
                ->where('users.section_id', $class['section_id'])
                ->where('users.status', 'active')
                ->order_by('users.full_name', 'ASC')
                ->get()->result_array();
                
                $class['students'] = $students;
                $class['student_count'] = count($students);
            }

            $this->send_success($classes, 'Classes retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve classes: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get student's attendance records
     * Endpoint: GET /api/attendance/student
     * Query Parameters:
     * - subject_id (optional): Filter by subject ID
     * - date_from (optional): Filter by date range start (YYYY-MM-DD)
     * - date_to (optional): Filter by date range end (YYYY-MM-DD)
     * - limit (optional): Limit results (default: 50)
     * - offset (optional): Offset for pagination (default: 0)
     */
    public function student_get()
    {
        $user_data = require_student($this);
        if (!$user_data) return;

        try {
            // Get query parameters
            $subject_id = $this->input->get('subject_id');
            $date_from = $this->input->get('date_from');
            $date_to = $this->input->get('date_to');
            $limit = (int)($this->input->get('limit') ?: 50);
            $offset = (int)($this->input->get('offset') ?: 0);

            // Validate limit and offset
            if ($limit > 100) $limit = 100; // Maximum limit
            if ($limit < 1) $limit = 50;
            if ($offset < 0) $offset = 0;

            // Build query for attendance records
            $this->db->select('
                attendance.*,
                subjects.subject_name,
                subjects.subject_code,
                sections.section_name,
                users.full_name as teacher_name
            ')
            ->from('attendance')
            ->join('subjects', 'attendance.subject_id = subjects.id', 'left')
            ->join('sections', 'attendance.section_name = sections.section_name', 'left')
            ->join('users', 'attendance.teacher_id = users.user_id', 'left')
            ->where('attendance.student_id', $user_data['user_id']);

            // Apply filters
            if ($subject_id) {
                $this->db->where('attendance.subject_id', $subject_id);
            }
            if ($date_from) {
                $this->db->where('attendance.date >=', $date_from);
            }
            if ($date_to) {
                $this->db->where('attendance.date <=', $date_to);
            }

            // Get total count for pagination
            $total_records = $this->db->count_all_results();

            // Rebuild query for actual data
            $this->db->select('
                attendance.*,
                subjects.subject_name,
                subjects.subject_code,
                sections.section_name,
                users.full_name as teacher_name
            ')
            ->from('attendance')
            ->join('subjects', 'attendance.subject_id = subjects.id', 'left')
            ->join('sections', 'attendance.section_name = sections.section_name', 'left')
            ->join('users', 'attendance.teacher_id = users.user_id', 'left')
            ->where('attendance.student_id', $user_data['user_id']);

            // Apply filters again
            if ($subject_id) {
                $this->db->where('attendance.subject_id', $subject_id);
            }
            if ($date_from) {
                $this->db->where('attendance.date >=', $date_from);
            }
            if ($date_to) {
                $this->db->where('attendance.date <=', $date_to);
            }

            // Apply pagination and ordering
            $this->db->limit($limit, $offset);
            $this->db->order_by('attendance.date', 'DESC');
            $this->db->order_by('attendance.time_in', 'DESC');

            $attendance_records = $this->db->get()->result_array();

            // Calculate summary statistics
            $summary = [
                'present' => 0,
                'late' => 0,
                'absent' => 0,
                'excused' => 0,
                'total' => 0
            ];

            foreach ($attendance_records as $record) {
                $summary['total']++;
                switch (strtolower($record['status'])) {
                    case 'present':
                        $summary['present']++;
                        break;
                    case 'late':
                        $summary['late']++;
                        break;
                    case 'absent':
                        $summary['absent']++;
                        break;
                    case 'excused':
                        $summary['excused']++;
                        break;
                }
            }

            // Get available subjects for filtering
            $subjects = $this->db->select('subjects.id, subjects.subject_name, subjects.subject_code')
                ->from('attendance')
                ->join('subjects', 'attendance.subject_id = subjects.id', 'left')
                ->where('attendance.student_id', $user_data['user_id'])
                ->group_by('subjects.id, subjects.subject_name, subjects.subject_code')
                ->order_by('subjects.subject_name', 'ASC')
                ->get()->result_array();

            // Prepare response
            $response = [
                'attendance_records' => $attendance_records,
                'summary' => $summary,
                'available_subjects' => $subjects,
                'pagination' => [
                    'total_records' => $total_records,
                    'limit' => $limit,
                    'offset' => $offset,
                    'total_pages' => ceil($total_records / $limit),
                    'current_page' => floor($offset / $limit) + 1
                ],
                'filters' => [
                    'subject_id' => $subject_id,
                    'date_from' => $date_from,
                    'date_to' => $date_to
                ]
            ];

            $this->send_success($response, 'Student attendance records retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve student attendance records: ' . $e->getMessage(), 500);
        }
    }
} 