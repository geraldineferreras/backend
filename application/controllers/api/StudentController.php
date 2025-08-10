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
            // Create notification for teacher
            $this->load->helper('notification');
            
            // Get the enrollment ID that was just created
            $enrollment_id = $this->db->insert_id();
            
            // Create notification for the teacher
            create_enrollment_notification(
                $classroom['teacher_id'],  // teacher's user_id
                $enrollment_id,           // enrollment_id
                'New Student Enrollment', // title
                $complete_user_data['full_name'] . ' has joined your class ' . $classroom['class_code'], // message
                $classroom['class_code']  // class_code
            );
            
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
        $available_classes = $this->db->select('classrooms.*, users.full_name as teacher_name, subjects.subject_code, subjects.subject_name, sections.section_name')
            ->from('classrooms')
            ->join('users', 'classrooms.teacher_id = users.user_id')
            ->join('subjects', 'classrooms.subject_id = subjects.id')
            ->join('sections', 'classrooms.section_id = sections.section_id')
            ->where('classrooms.section_id', $complete_user_data['section_id'])
            ->where('classrooms.is_active', 1)
            ->order_by('classrooms.created_at', 'DESC')
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
            // Find corresponding class in classes table
            $corresponding_class = $this->db->select('classes.class_id')
                ->from('classes')
                ->where('classes.subject_id', $class['subject_id'])
                ->where('classes.section_id', $class['section_id'])
                ->where('classes.teacher_id', $class['teacher_id'])
                ->get()->row_array();

            $result[] = [
                'class_id' => $corresponding_class ? $corresponding_class['class_id'] : $class['id'],
                'subject_id' => $class['subject_id'],
                'teacher_id' => $class['teacher_id'],
                'section_id' => $class['section_id'],
                'semester' => $class['semester'],
                'school_year' => $class['school_year'],
                'status' => $class['is_active'] ? 'active' : 'inactive',
                'date_created' => $class['created_at'],
                'is_active' => $class['is_active'],
                'subject_code' => $class['subject_code'],
                'subject_name' => $class['subject_name'],
                'teacher_name' => $class['teacher_name'],
                'section_name' => $class['section_name'],
                'class_code' => $class['class_code'],
                'title' => $class['title'],
                'is_enrolled' => in_array($class['id'], $enrolled_ids)
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

    /**
     * Debug endpoint to check classes and classrooms relationship
     * GET /api/student/debug-classes
     */
    public function debug_classes() {
        // Get all classes
        $classes = $this->db->get('classes')->result_array();
        
        // Get all classrooms
        $classrooms = $this->db->get('classrooms')->result_array();
        
        $debug_data = [
            'classes' => $classes,
            'classrooms' => $classrooms
        ];
        
        return json_response(true, 'Debug data retrieved', $debug_data);
    }

    /**
     * Get all people in a specific class (Student only)
     * GET /api/student/classroom/{class_code}/people
     */
    public function classroom_people_get($class_code) {
        // Require student authentication
        $user_data = require_student($this);
        if (!$user_data) return;
        
        try {
            // Get classroom by code
            $classroom = $this->Classroom_model->get_by_code($class_code);
            if (!$classroom) {
                return json_response(false, 'Classroom not found', null, 404);
            }
            
            // Check if student is enrolled in this class
            $enrollment = $this->db->get_where('classroom_enrollments', [
                'classroom_id' => $classroom['id'],
                'student_id' => $user_data['user_id'],
                'status' => 'active'
            ])->row_array();
            
            if (!$enrollment) {
                return json_response(false, 'Access denied. You must be enrolled in this class to view its members.', null, 403);
            }
            
            // Get teacher information
            $teacher = $this->db->select('user_id, full_name, email, profile_pic')
                ->from('users')
                ->where('user_id', $classroom['teacher_id'])
                ->where('role', 'teacher')
                ->get()->row_array();
            
            // Get enrolled students with their details - using raw query to handle collation
            $query = "SELECT 
                        ce.enrolled_at,
                        ce.status as enrollment_status,
                        u.user_id,
                        u.full_name,
                        u.email,
                        u.student_num,
                        u.contact_num,
                        u.program,
                        u.section_id,
                        u.profile_pic
                    FROM classroom_enrollments ce
                    JOIN users u ON ce.student_id = u.user_id COLLATE utf8mb4_unicode_ci
                    WHERE ce.classroom_id = ?
                    AND ce.status = 'active'
                    ORDER BY u.full_name ASC";
            
            $enrolled_students = $this->db->query($query, [$classroom['id']])->result_array();
            
            // Get section information
            $this->load->model('Section_model');
            $section = $this->Section_model->get_by_id($classroom['section_id']);
            
            // Format the response
            $response_data = [
                'classroom' => [
                    'id' => $classroom['id'],
                    'class_code' => $classroom['class_code'],
                    'title' => $classroom['title'],
                    'semester' => $classroom['semester'],
                    'school_year' => $classroom['school_year'],
                    'section_name' => $section ? $section['section_name'] : 'Unknown Section'
                ],
                'teacher' => $teacher ? [
                    'user_id' => $teacher['user_id'],
                    'full_name' => $teacher['full_name'],
                    'email' => $teacher['email'],
                    'profile_pic' => $teacher['profile_pic'],
                    'role' => 'Primary Instructor',
                    'status' => 'Active'
                ] : null,
                'students' => array_map(function($student) {
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
                }, $enrolled_students),
                'statistics' => [
                    'total_members' => count($enrolled_students) + ($teacher ? 1 : 0),
                    'total_teachers' => $teacher ? 1 : 0,
                    'total_students' => count($enrolled_students)
                ]
            ];
            
            return json_response(true, 'Classroom members retrieved successfully', $response_data);
            
        } catch (Exception $e) {
            return json_response(false, 'Failed to retrieve classroom members: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get student grades per task (Student only)
     * GET /api/student/grades
     */
    public function grades_get() {
        // Require student authentication
        $user_data = require_student($this);
        if (!$user_data) return;
        
        try {
            // Get query parameters
            $class_code = $this->input->get('class_code');
            $status_filter = $this->input->get('status'); // 'all', 'graded', 'submitted', 'not_submitted'
            
            // Get student's enrolled classes
            $enrolled_classes = $this->db->select('ce.classroom_id, c.class_code, c.title, c.semester, c.school_year')
                ->from('classroom_enrollments ce')
                ->join('classrooms c', 'ce.classroom_id = c.id')
                ->where('ce.student_id', $user_data['user_id'])
                ->where('ce.status', 'active')
                ->get()->result_array();
            
            if (empty($enrolled_classes)) {
                return json_response(true, 'No enrolled classes found', [
                    'academic_performance' => [
                        'student_name' => $user_data['full_name'],
                        'average_grade' => 0,
                        'total_assignments' => 0
                    ],
                    'grades' => [],
                    'filters' => [
                        'available_statuses' => ['all', 'graded', 'submitted', 'not_submitted'],
                        'available_classes' => []
                    ]
                ]);
            }
            
            // Filter by class if specified
            $classes_to_use = $enrolled_classes;
            if ($class_code) {
                $filtered_classes = array_filter($enrolled_classes, function($class) use ($class_code) {
                    return $class['class_code'] === $class_code;
                });
                if (empty($filtered_classes)) {
                    return json_response(false, 'Class not found or you are not enrolled', null, 404);
                }
                $classes_to_use = array_values($filtered_classes);
            }
            
            // Get tasks for enrolled classes
            $this->load->model('Task_model');
            
            // Build the class codes array for the WHERE IN clause
            $class_codes_array = array_map(function($class) {
                return json_encode([$class['class_code']]);
            }, $classes_to_use);
            
            // Use raw query to properly handle the JOIN with student_id parameter
            $sql = "SELECT ct.*, ts.submission_id, ts.grade, ts.feedback, ts.status as submission_status, ts.submitted_at, ts.attachment_url, ts.attachment_type
                    FROM class_tasks ct
                    LEFT JOIN task_submissions ts ON ct.task_id = ts.task_id AND ts.student_id = ?
                    WHERE ct.class_codes IN (" . str_repeat('?,', count($class_codes_array) - 1) . "?)
                    AND ct.is_draft = 0
                    AND ct.is_scheduled = 0
                    ORDER BY ct.due_date DESC";
            
            // Prepare parameters array
            $params = array_merge([$user_data['user_id']], $class_codes_array);
            
            $tasks = $this->db->query($sql, $params)->result_array();
            
            // Process tasks and calculate statistics
            $grades = [];
            $total_grade = 0;
            $graded_count = 0;
            $total_assignments = 0;
            
            foreach ($tasks as $task) {
                $total_assignments++;
                
                // Calculate grade percentage
                $grade_percentage = null;
                if ($task['grade'] !== null && $task['points'] > 0) {
                    $grade_percentage = round(($task['grade'] / $task['points']) * 100, 1);
                    $total_grade += $grade_percentage;
                    $graded_count++;
                }
                
                // Determine status
                $status = 'not_submitted';
                if ($task['submission_id']) {
                    if ($task['grade'] !== null) {
                        $status = 'graded';
                    } else {
                        $status = 'submitted';
                    }
                }
                
                // Apply status filter
                if ($status_filter && $status_filter !== 'all' && $status !== $status_filter) {
                    continue;
                }
                
                // Count attachments
                $attachment_count = 0;
                if ($task['attachment_url']) {
                    $attachment_count = 1; // For now, count as 1. Could be enhanced to count multiple files
                }
                
                $grades[] = [
                    'task_id' => $task['task_id'],
                    'title' => $task['title'],
                    'type' => $task['type'],
                    'points' => $task['points'],
                    'due_date' => $task['due_date'],
                    'submission_id' => $task['submission_id'],
                    'grade' => $task['grade'],
                    'grade_percentage' => $grade_percentage,
                    'feedback' => $task['feedback'],
                    'status' => $status,
                    'submitted_at' => $task['submitted_at'],
                    'attachment_count' => $attachment_count,
                    'attachment_url' => $task['attachment_url'],
                    'attachment_type' => $task['attachment_type'],
                    'class_code' => json_decode($task['class_codes'], true)[0] ?? null
                ];
            }
            
            // Calculate average grade
            $average_grade = $graded_count > 0 ? round($total_grade / $graded_count, 1) : 0;
            
            // Get available classes for filters
            $available_classes = array_map(function($class) {
                return [
                    'class_code' => $class['class_code'],
                    'title' => $class['title'],
                    'semester' => $class['semester'],
                    'school_year' => $class['school_year']
                ];
            }, $classes_to_use);
            
            $response_data = [
                'academic_performance' => [
                    'student_name' => $user_data['full_name'],
                    'average_grade' => $average_grade,
                    'total_assignments' => $total_assignments,
                    'graded_assignments' => $graded_count
                ],
                'grades' => $grades,
                'filters' => [
                    'available_statuses' => ['all', 'graded', 'pending', 'submitted'],
                    'available_classes' => $available_classes,
                    'current_status_filter' => $status_filter ?: 'all',
                    'current_class_filter' => $class_code ?: 'all'
                ]
            ];
            
            return json_response(true, 'Student grades retrieved successfully', $response_data);
            
        } catch (Exception $e) {
            return json_response(false, 'Failed to retrieve student grades: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get classroom stream posts (Student only)
     * GET /api/student/classroom/{class_code}/stream
     */
    public function classroom_stream_get($class_code) {
        // Require student authentication
        $user_data = require_student($this);
        if (!$user_data) return;
        
        try {
            // Get classroom by code
            $classroom = $this->Classroom_model->get_by_code($class_code);
            if (!$classroom) {
                return json_response(false, 'Classroom not found', null, 404);
            }
            
            // Check if student is enrolled in this class
            $enrollment = $this->db->get_where('classroom_enrollments', [
                'classroom_id' => $classroom['id'],
                'student_id' => $user_data['user_id'],
                'status' => 'active'
            ])->row_array();
            
            if (!$enrollment) {
                return json_response(false, 'Access denied. You must be enrolled in this class to view its stream.', null, 403);
            }
            
            // Load the ClassroomStream model
            $this->load->model('ClassroomStream_model');
            
            // Get stream posts filtered for this student
            $posts = $this->ClassroomStream_model->get_by_class_code($class_code, [
                'is_draft' => 0, // Exclude drafts
                'is_scheduled' => 0 // Only show published posts
            ], $user_data['user_id']);
            
            // Format posts for UI (similar to teacher endpoint)
            $formatted_posts = [];
            foreach ($posts as $post) {
                // Get user information for the post author
                $user = $this->db->select('full_name, profile_pic')
                    ->from('users')
                    ->where('user_id', $post['user_id'])
                    ->get()->row_array();
                
                $formatted_post = [
                    'id' => $post['id'],
                    'user_name' => $user ? $user['full_name'] : 'Unknown User',
                    'user_avatar' => $user ? $user['profile_pic'] : null,
                    'created_at' => $post['created_at'],
                    'is_pinned' => $post['is_pinned'],
                    'title' => $post['title'],
                    'content' => $post['content'],
                    'like_count' => 0, // Students can't see who liked, just count
                    'attachment_url' => $post['attachment_url'],
                    'attachment_type' => $post['attachment_type']
                ];
                
                // Count likes
                if (!empty($post['liked_by_user_ids'])) {
                    $likes = json_decode($post['liked_by_user_ids'], true);
                    $formatted_post['like_count'] = is_array($likes) ? count($likes) : 0;
                }
                
                // Handle multiple attachments
                if ($post['attachment_type'] === 'multiple' && isset($post['attachments'])) {
                    $formatted_post['attachments'] = $post['attachments'];
                    // Keep backward compatibility
                    $formatted_post['attachment_serving_url'] = $post['attachment_serving_url'] ?? null;
                    $formatted_post['attachment_file_type'] = $post['attachment_file_type'] ?? null;
                } else {
                    // Single attachment (backward compatibility)
                    if (!empty($post['attachment_url'])) {
                        $formatted_post['attachment_serving_url'] = get_file_url($post['attachment_url']);
                        $formatted_post['attachment_file_type'] = get_file_type($post['attachment_url']);
                    }
                }
                
                $formatted_posts[] = $formatted_post;
            }
            
            // Sort by pinned first, then by creation date
            usort($formatted_posts, function($a, $b) {
                if ($a['is_pinned'] != $b['is_pinned']) {
                    return $b['is_pinned'] - $a['is_pinned'];
                }
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            return json_response(true, 'Stream posts retrieved successfully', $formatted_posts);
            
        } catch (Exception $e) {
            return json_response(false, 'Error retrieving stream posts: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Create a stream post (Student only)
     * POST /api/student/classroom/{class_code}/stream
     */
    public function classroom_stream_post($class_code) {
        // Require student authentication
        $user_data = require_student($this);
        if (!$user_data) return;
        
        try {
            // Get classroom by code
            $classroom = $this->Classroom_model->get_by_code($class_code);
            if (!$classroom) {
                return json_response(false, 'Classroom not found', null, 404);
            }
            
            // Check if student is enrolled in this class
            $enrollment = $this->db->get_where('classroom_enrollments', [
                'classroom_id' => $classroom['id'],
                'student_id' => $user_data['user_id'],
                'status' => 'active'
            ])->row_array();
            
            if (!$enrollment) {
                return json_response(false, 'Access denied. You must be enrolled in this class to post to its stream.', null, 403);
            }
            
            // Load the ClassroomStream model
            $this->load->model('ClassroomStream_model');
            
            // Get JSON input
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return json_response(false, 'Invalid JSON format', null, 400);
            }
            
            // Validate required fields
            if (empty($data['content'])) {
                return json_response(false, 'Content is required', null, 400);
            }
            
            // Prepare post data
            $insert_data = [
                'class_code' => $class_code,
                'user_id' => $user_data['user_id'],
                'title' => $data['title'] ?? null,
                'content' => $data['content'],
                'is_draft' => $data['is_draft'] ?? 0,
                'is_scheduled' => $data['is_scheduled'] ?? 0,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'allow_comments' => $data['allow_comments'] ?? 1,
                'attachment_type' => $data['attachment_type'] ?? null,
                'attachment_url' => $data['attachment_url'] ?? null
            ];
            
            // Add student_ids if provided
            if (!empty($data['student_ids'])) {
                $insert_data['student_ids'] = $data['student_ids'];
            }
            
            // Insert the post
            $post_id = $this->ClassroomStream_model->insert($insert_data);
            
            if ($post_id) {
                // Get the created post
                $post = $this->ClassroomStream_model->get_by_id($post_id);
                
                // Create notifications for teacher and targeted students if post is published (not draft)
                if (!$data['is_draft']) {
                    $this->load->helper('notification');
                    
                    // Always notify the teacher
                    $teacher_id = $classroom['teacher_id'];
                    $notification_user_ids = [$teacher_id];
                    
                    // If student_ids are provided, notify only those students
                    if (!empty($data['student_ids'])) {
                        // Validate that the provided student_ids are actually enrolled in this class
                        $valid_student_ids = $this->db->select('student_id')
                            ->from('classroom_enrollments')
                            ->where('classroom_id', $classroom['id'])
                            ->where_in('student_id', $data['student_ids'])
                            ->where('status', 'active')
                            ->get()->result_array();
                        
                        $valid_ids = array_column($valid_student_ids, 'student_id');
                        $notification_user_ids = array_merge($notification_user_ids, $valid_ids);
                    } else {
                        // If no student_ids provided, notify all other students in the class
                        $other_students = $this->db->select('student_id')
                            ->from('classroom_enrollments')
                            ->where('classroom_id', $classroom['id'])
                            ->where('student_id !=', $user_data['user_id'])
                            ->where('status', 'active')
                            ->get()->result_array();
                        
                        foreach ($other_students as $student) {
                            $notification_user_ids[] = $student['student_id'];
                        }
                    }
                    
                    if (!empty($notification_user_ids)) {
                        $title = $data['title'] ?: 'New Student Post';
                        $message = $data['content'] ?? 'A student has posted to the class stream.';
                        
                        // Create notifications for teacher and targeted students
                        create_notifications_for_users(
                            $notification_user_ids,
                            'announcement',
                            $title,
                            $message,
                            $post_id,
                            'announcement',
                            $class_code,
                            false
                        );
                    }
                }
                
                return json_response(true, 'Post created successfully', $post, 201);
            } else {
                return json_response(false, 'Failed to create post', null, 500);
            }
            
        } catch (Exception $e) {
            return json_response(false, 'Error creating post: ' . $e->getMessage(), null, 500);
        }
    }
}
