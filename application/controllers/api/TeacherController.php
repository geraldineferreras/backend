<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

class TeacherController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // Implement the index method
    }

    public function create()
    {
        // Implement the create method
    }

    public function update()
    {
        // Implement the update method
    }

    public function delete()
    {
        // Implement the delete method
    }

    // --- Teacher Classroom Management ---
    public function classrooms_get() {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $this->load->model('Subject_model');
        $this->load->model('Section_model');
        
        // Get only classrooms created by this teacher
        $classrooms = $this->db->select('classrooms.*, users.full_name as teacher_name')
            ->from('classrooms')
            ->join('users', 'classrooms.teacher_id = users.user_id', 'left')
            ->where('classrooms.teacher_id', $user_data['user_id'])
            ->order_by('classrooms.created_at', 'DESC')
            ->get()->result_array();
        
        $result = [];
        foreach ($classrooms as $classroom) {
            // Find corresponding class (subject offering) for this classroom
            $class = $this->db->select('classes.class_id')
                ->from('classes')
                ->where('classes.subject_id', $classroom['subject_id'])
                ->where('classes.section_id', $classroom['section_id'])
                ->where('classes.teacher_id', $classroom['teacher_id'])
                ->where('classes.status', 'active')
                ->get()->row_array();
            
            // Fetch subject name
            $subject = $this->Subject_model->get_by_id($classroom['subject_id']);
            $subject_name = $subject ? $subject['subject_name'] : '';
            // Fetch section name
            $section = $this->Section_model->get_by_id($classroom['section_id']);
            $section_name = $section ? $section['section_name'] : '';
            // Count students in section (users table, role=student)
            $student_count = $this->db->where('section_id', $classroom['section_id'])->where('role', 'student')->count_all_results('users');
            $result[] = [
                'class_id' => $classroom['id'], // Use classroom.id for attendance
                'class_code' => $classroom['class_code'],
                'subject_name' => $subject_name,
                'section_name' => $section_name,
                'semester' => $classroom['semester'],
                'school_year' => $classroom['school_year'],
                'student_count' => $student_count
            ];
        }
        return json_response(true, 'Classrooms retrieved successfully', $result);
    }

    public function classroom_get($id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $classroom = $this->Classroom_model->get_by_id($id);
        if (!$classroom) {
            return json_response(false, 'Classroom not found', null, 404);
        }
        return json_response(true, 'Classroom retrieved successfully', $classroom);
    }

    public function classrooms_post() {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $required = ['subject_id', 'section_id', 'semester', 'school_year'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        
        // Validate that the teacher is assigned to this subject and section
        $assigned_class = $this->db->select('classes.*')
            ->from('classes')
            ->where('classes.teacher_id', $user_data['user_id'])
            ->where('classes.subject_id', $data['subject_id'])
            ->where('classes.section_id', $data['section_id'])
            ->get()->row_array();
        
        if (!$assigned_class) {
            return json_response(false, 'You are not assigned to teach this subject and section combination', null, 403);
        }
        
        // Check if classroom already exists for this teacher, subject, section, semester, and school year
        $existing_classroom = $this->db->select('classrooms.*')
            ->from('classrooms')
            ->where('classrooms.teacher_id', $user_data['user_id'])
            ->where('classrooms.subject_id', $data['subject_id'])
            ->where('classrooms.section_id', $data['section_id'])
            ->where('classrooms.semester', $data['semester'])
            ->where('classrooms.school_year', $data['school_year'])
            ->get()->row_array();
        
        if ($existing_classroom) {
            return json_response(false, 'A classroom already exists for this subject, section, semester, and school year combination', null, 409);
        }
        
        $data['teacher_id'] = $user_data['user_id'];
        if (empty($data['title']) && !empty($data['custom_title'])) {
            $data['title'] = $data['custom_title'];
        }
        unset($data['custom_title']);
        $id = $this->Classroom_model->insert($data);
        if ($id) {
            // Fetch subject name
            $this->load->model('Subject_model');
            $subject = $this->Subject_model->get_by_id($data['subject_id']);
            $subject_name = $subject ? $subject['subject_name'] : '';
            // Fetch section name
            $this->load->model('Section_model');
            $section = $this->Section_model->get_by_id($data['section_id']);
            $section_name = $section ? $section['section_name'] : '';
            // Count students in section (users table, role=student)
            $student_count = $this->db->where('section_id', $data['section_id'])->where('role', 'student')->count_all_results('users');
            // Get class_code
            $classroom = $this->Classroom_model->get_by_id($id);
            $class_code = $classroom['class_code'];
            // Find corresponding class (subject offering) for this classroom
            $class = $this->db->select('classes.class_id')
                ->from('classes')
                ->where('classes.subject_id', $data['subject_id'])
                ->where('classes.section_id', $data['section_id'])
                ->where('classes.teacher_id', $user_data['user_id'])
                ->where('classes.status', 'active')
                ->get()->row_array();
            
            $response = [
                'class_id' => $id, // Use classroom.id for attendance
                'class_code' => $class_code,
                'subject_name' => $subject_name,
                'section_name' => $section_name,
                'semester' => $data['semester'],
                'school_year' => $data['school_year'],
                'student_count' => $student_count
            ];
            return json_response(true, 'Classroom created successfully', $response, 201);
        } else {
            return json_response(false, 'Failed to create classroom', null, 500);
        }
    }

    public function classrooms_put($id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $success = $this->Classroom_model->update($id, $data);
        if ($success) {
            return json_response(true, 'Classroom updated successfully');
        } else {
            return json_response(false, 'Failed to update classroom', null, 500);
        }
    }

    public function classrooms_delete($id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $success = $this->Classroom_model->delete($id);
        if ($success) {
            return json_response(true, 'Classroom deleted successfully');
        } else {
            return json_response(false, 'Failed to delete classroom', null, 500);
        }
    }

    public function classroom_by_code_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $this->load->model('Subject_model');
        $this->load->model('Section_model');
        $classroom = $this->Classroom_model->get_by_code($class_code);
        if (!$classroom) {
            return json_response(false, 'Classroom not found', null, 404);
        }
        $subject = $this->Subject_model->get_by_id($classroom['subject_id']);
        $subject_name = $subject ? $subject['subject_name'] : '';
        $section = $this->Section_model->get_by_id($classroom['section_id']);
        $section_name = $section ? $section['section_name'] : '';
        $student_count = $this->db->where('section_id', $classroom['section_id'])->where('role', 'student')->count_all_results('users');
        
        // Find corresponding class (subject offering) for this classroom
        $class = $this->db->select('classes.class_id')
            ->from('classes')
            ->where('classes.subject_id', $classroom['subject_id'])
            ->where('classes.section_id', $classroom['section_id'])
            ->where('classes.teacher_id', $classroom['teacher_id'])
            ->where('classes.status', 'active')
            ->get()->row_array();
        
        $response = [
            'class_id' => $classroom['id'], // Use classroom.id for attendance
            'class_code' => $classroom['class_code'],
            'subject_name' => $subject_name,
            'section_name' => $section_name,
            'semester' => $classroom['semester'],
            'school_year' => $classroom['school_year'],
            'student_count' => $student_count
        ];
        return json_response(true, 'Classroom retrieved successfully', $response);
    }

    public function classrooms_put_by_code($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $classroom = $this->Classroom_model->get_by_code($class_code);
        if (!$classroom) {
            return json_response(false, 'Classroom not found', null, 404);
        }
        $success = $this->Classroom_model->update($classroom['id'], $data);
        if ($success) {
            return json_response(true, 'Classroom updated successfully');
        } else {
            return json_response(false, 'Failed to update classroom', null, 500);
        }
    }

    public function classrooms_delete_by_code($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('Classroom_model');
        $classroom = $this->Classroom_model->get_by_code($class_code);
        if (!$classroom) {
            return json_response(false, 'Classroom not found', null, 404);
        }
        $success = $this->Classroom_model->delete($classroom['id']);
        if ($success) {
            return json_response(true, 'Classroom deleted successfully');
        } else {
            return json_response(false, 'Failed to delete classroom', null, 500);
        }
    }

    public function classroom_stream_post($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');

        // Check if multipart/form-data with files and JSON
        if (isset($_FILES) && !empty($_FILES) && isset($_POST['data'])) {
            $data = json_decode($_POST['data'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return json_response(false, 'Invalid JSON format in data field', null, 400);
            }
            
            // Handle multiple file uploads
            $uploaded_files = [];
            $upload_path = FCPATH . 'uploads/announcement/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            
            // Process each uploaded file
            foreach ($_FILES as $field_name => $file_data) {
                if ($file_data['error'] === UPLOAD_ERR_OK) {
                    $config['upload_path'] = $upload_path;
                    $config['allowed_types'] = 'gif|jpg|jpeg|png|webp|pdf|doc|docx|ppt|pptx|xls|xlsx|txt|zip|rar|mp4|mp3';
                    $config['max_size'] = 10240; // 10MB
                    $config['encrypt_name'] = false;
                    $config['remove_spaces'] = true;
                    $config['file_ext_tolower'] = true;
                    
                    // Get original filename and sanitize it
                    $original_name = $file_data['name'];
                    $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                    $file_name_without_ext = pathinfo($original_name, PATHINFO_FILENAME);
                    
                    // Sanitize filename: keep Unicode characters, alphanumeric, dots, hyphens, underscores, and spaces
                    // Remove only potentially dangerous characters
                    $sanitized_name = preg_replace('/[^\p{L}\p{N}\s._-]/u', '', $file_name_without_ext);
                    $sanitized_name = trim($sanitized_name, '._-');
                    // Replace multiple spaces with single space
                    $sanitized_name = preg_replace('/\s+/', ' ', $sanitized_name);
                    
                    // If sanitized name is empty, use a default name
                    if (empty($sanitized_name)) {
                        $sanitized_name = 'file';
                    }
                    
                    // Check if file already exists and append number if necessary
                    $final_filename = $sanitized_name . '.' . $file_extension;
                    $counter = 1;
                    while (file_exists($upload_path . $final_filename)) {
                        $final_filename = $sanitized_name . '_' . $counter . '.' . $file_extension;
                        $counter++;
                    }
                    
                    $config['file_name'] = $final_filename;
                    $this->load->library('upload', $config);
                    $this->upload->initialize($config);
                    
                    if ($this->upload->do_upload($field_name)) {
                        $upload_data = $this->upload->data();
                        $file_path = 'uploads/announcement/' . $upload_data['file_name'];
                        $uploaded_files[] = [
                            'field_name' => $field_name,
                            'file_path' => $file_path,
                            'file_name' => $upload_data['file_name'],
                            'file_size' => $upload_data['file_size'],
                            'file_type' => $upload_data['file_type']
                        ];
                    } else {
                        $error = $this->upload->display_errors('', '');
                        return json_response(false, 'Upload failed for ' . $field_name . ': ' . $error, null, 400);
                    }
                }
            }
            
            // Store file information in data
            if (!empty($uploaded_files)) {
                $data['attachment_type'] = 'multiple';
                $data['attachment_url'] = json_encode($uploaded_files);
            }
        } else {
            // Fallback to JSON body (raw)
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return json_response(false, 'Invalid JSON format', null, 400);
            }
        }
        
        $required = ['content'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        
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
        
        if (!empty($data['student_ids'])) {
            $insert_data['student_ids'] = $data['student_ids'];
        }
        
        $id = $this->ClassroomStream_model->insert($insert_data);
        if ($id) {
            $post = $this->ClassroomStream_model->get_by_id($id);
            
            // Create notifications for students if post is published (not draft)
            if (!$data['is_draft']) {
                $this->load->helper('notification');
                
                // Get all students in this class
                $students = get_class_students($class_code);
                
                if ($students) {
                    $user_ids = array_column($students, 'user_id');
                    $title = $data['title'] ?: 'New Announcement';
                    $message = $data['content'] ?? 'A new announcement has been posted.';
                    
                    // Create notifications for all students in the class
                    create_notifications_for_users(
                        $user_ids,
                        'announcement',
                        $title,
                        $message,
                        $id,
                        'announcement',
                        $class_code,
                        false
                    );
                }
            }
            
            return json_response(true, 'Announcement posted successfully', $post, 201);
        } else {
            return json_response(false, 'Failed to post announcement', null, 500);
        }
    }

    public function classroom_stream_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $posts = $this->ClassroomStream_model->get_stream_for_classroom_ui($class_code);
        return json_response(true, 'Stream posts retrieved successfully', $posts);
    }

    // Like a stream post
    public function classroom_stream_like_post($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $likes = json_decode($post['liked_by_user_ids'], true) ?: [];
        if (!in_array($user_data['user_id'], $likes)) {
            $likes[] = $user_data['user_id'];
            $this->db->where('id', $stream_id)->update('classroom_stream', [
                'liked_by_user_ids' => json_encode($likes)
            ]);
        }
        return json_response(true, 'Post liked successfully');
    }

    // Unlike a stream post
    public function classroom_stream_unlike_post($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $likes = json_decode($post['liked_by_user_ids'], true) ?: [];
        $likes = array_diff($likes, [$user_data['user_id']]);
        $this->db->where('id', $stream_id)->update('classroom_stream', [
            'liked_by_user_ids' => json_encode(array_values($likes))
        ]);
        return json_response(true, 'Post unliked successfully');
    }

    // Pin a stream post
    public function classroom_stream_pin_post($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $this->db->where('id', $stream_id)->update('classroom_stream', [
            'is_pinned' => 1
        ]);
        return json_response(true, 'Post pinned successfully');
    }

    // Unpin a stream post
    public function classroom_stream_unpin_post($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $this->db->where('id', $stream_id)->update('classroom_stream', [
            'is_pinned' => 0
        ]);
        return json_response(true, 'Post unpinned successfully');
    }

    public function classroom_stream_scheduled_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $posts = $this->ClassroomStream_model->get_scheduled_for_classroom_ui($class_code);
        return json_response(true, 'Scheduled posts retrieved successfully', $posts);
    }

    public function classroom_stream_drafts_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $posts = $this->ClassroomStream_model->get_drafts_for_classroom_ui($class_code);
        return json_response(true, 'Draft posts retrieved successfully', $posts);
    }

    // Update a draft by ID (can also publish by setting is_draft=0)
    public function classroom_stream_draft_put($class_code, $draft_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $post = $this->ClassroomStream_model->get_by_id($draft_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Draft post not found', null, 404);
        }
        $success = $this->ClassroomStream_model->update($draft_id, $data);
        if ($success) {
            return json_response(true, 'Draft updated successfully');
        } else {
            return json_response(false, 'Failed to update draft', null, 500);
        }
    }

    // Get teacher's assigned subjects and sections from offerings management
    public function assigned_subjects_get() {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        $this->load->model('Class_model');
        $this->load->model('Subject_model');
        $this->load->model('Section_model');
        
        // Get all classes (offerings) assigned to this teacher
        $assigned_classes = $this->db->select('classes.*, subjects.subject_name, subjects.subject_code, sections.section_name')
            ->from('classes')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->where('classes.teacher_id', $user_data['user_id'])
            ->get()->result_array();
        
        // Group by subject and section
        $subjects = [];
        $sections = [];
        
        foreach ($assigned_classes as $class) {
            $subject_key = $class['subject_id'];
            if (!isset($subjects[$subject_key])) {
                $subjects[$subject_key] = [
                    'id' => $class['subject_id'],
                    'name' => $class['subject_name'],
                    'code' => $class['subject_code'],
                    'sections' => []
                ];
            }
            
            $section_key = $class['section_id'];
            if (!isset($sections[$section_key])) {
                $sections[$section_key] = [
                    'id' => $class['section_id'],
                    'name' => $class['section_name']
                ];
            }
            
            // Add section to subject if not already added
            $section_exists = false;
            foreach ($subjects[$subject_key]['sections'] as $existing_section) {
                if ($existing_section['id'] == $class['section_id']) {
                    $section_exists = true;
                    break;
                }
            }
            
            if (!$section_exists) {
                $subjects[$subject_key]['sections'][] = [
                    'id' => $class['section_id'],
                    'name' => $class['section_name']
                ];
            }
        }
        
        $result = [
            'subjects' => array_values($subjects),
            'sections' => array_values($sections)
        ];
        
        return json_response(true, 'Teacher assigned subjects retrieved successfully', $result);
    }

    // Get available subjects for teacher (filtered by assigned subjects)
    public function available_subjects_get() {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        $this->load->model('Subject_model');
        
        // Get subjects assigned to this teacher from classes table
        $assigned_subject_ids = $this->db->select('DISTINCT(subject_id)')
            ->from('classes')
            ->where('teacher_id', $user_data['user_id'])
            ->get()->result_array();
        
        $subject_ids = array_column($assigned_subject_ids, 'subject_id');
        
        if (empty($subject_ids)) {
            return json_response(true, 'No subjects assigned to teacher', []);
        }
        
        // Get subject details
        $subjects = $this->db->select('id, subject_name, subject_code')
            ->from('subjects')
            ->where_in('id', $subject_ids)
            ->order_by('subject_name', 'ASC')
            ->get()->result_array();
        
        return json_response(true, 'Available subjects retrieved successfully', $subjects);
    }

    // Get available sections for a specific subject (filtered by teacher's assignments)
    public function available_sections_get($subject_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        $this->load->model('Section_model');
        
        // Get sections assigned to this teacher for the specific subject
        $assigned_sections = $this->db->select('sections.section_id, sections.section_name')
            ->from('classes')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->where('classes.teacher_id', $user_data['user_id'])
            ->where('classes.subject_id', $subject_id)
            ->get()->result_array();
        
        return json_response(true, 'Available sections retrieved successfully', $assigned_sections);
    }

    // Add a comment to a stream post
    public function classroom_stream_comment_post($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data['comment'])) {
            return json_response(false, 'Comment is required', null, 400);
        }
        $comment_id = $this->ClassroomStream_model->add_comment($stream_id, $user_data['user_id'], $data['comment']);
        if ($comment_id) {
            $comments = $this->ClassroomStream_model->get_comments($stream_id);
            return json_response(true, 'Comment added successfully', $comments);
        } else {
            return json_response(false, 'Failed to add comment', null, 500);
        }
    }

    // Get all comments for a stream post
    public function classroom_stream_comments_get($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $comments = $this->ClassroomStream_model->get_comments($stream_id);
        return json_response(true, 'Comments retrieved successfully', $comments);
    }

    // Edit a comment
    public function classroom_stream_comment_put($class_code, $stream_id, $comment_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($data['comment'])) {
            return json_response(false, 'Comment is required', null, 400);
        }
        $success = $this->ClassroomStream_model->update_comment($comment_id, $user_data['user_id'], $data['comment']);
        if ($success) {
            $comments = $this->ClassroomStream_model->get_comments($stream_id);
            return json_response(true, 'Comment updated successfully', $comments);
        } else {
            return json_response(false, 'Failed to update comment (maybe not your comment)', null, 403);
        }
    }

    // Delete a comment
    public function classroom_stream_comment_delete($class_code, $stream_id, $comment_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $success = $this->ClassroomStream_model->delete_comment($comment_id, $user_data['user_id']);
        if ($success) {
            $comments = $this->ClassroomStream_model->get_comments($stream_id);
            return json_response(true, 'Comment deleted successfully', $comments);
        } else {
            return json_response(false, 'Failed to delete comment (maybe not your comment)', null, 403);
        }
    }

    /**
     * Get list of students enrolled in a specific class
     * GET /api/teacher/classroom/{class_code}/students
     */
    public function classroom_students_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        // Get classroom by code and verify teacher ownership
        $this->load->model('Classroom_model');
        $classroom = $this->Classroom_model->get_by_code($class_code);
        if (!$classroom) {
            return json_response(false, 'Classroom not found', null, 404);
        }
        
        // Verify that this teacher owns the classroom
        if ($classroom['teacher_id'] != $user_data['user_id']) {
            return json_response(false, 'Access denied. You can only view students in your own classes.', null, 403);
        }
        
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
        
        // Format the response
        $students = [];
        foreach ($enrolled_students as $student) {
            // Get section name separately to avoid collation issues
            $section_name = '';
            if (!empty($student['section_id'])) {
                $section = $this->db->get_where('sections', ['section_id' => $student['section_id']])->row_array();
                $section_name = $section ? $section['section_name'] : '';
            }
            
            $students[] = [
                'user_id' => $student['user_id'],
                'full_name' => $student['full_name'],
                'email' => $student['email'],
                'student_num' => $student['student_num'],
                'contact_num' => $student['contact_num'],
                'program' => $student['program'],
                'section_name' => $section_name,
                'enrolled_at' => $student['enrolled_at'],
                'enrollment_status' => $student['enrollment_status'],
                'profile_pic' => $student['profile_pic']
            ];
        }
        
        $response_data = [
            'class_code' => $classroom['class_code'],
            'total_students' => count($students),
            'students' => $students
        ];
        
        return json_response(true, 'Enrolled students retrieved successfully', $response_data);
    }

    /**
     * Get classroom enrollment statistics (Teacher only)
     * GET /api/teacher/classroom/{class_code}/enrollment-stats
     */
    public function classroom_enrollment_stats_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        try {
            // Get classroom by code and verify teacher ownership
            $this->load->model('Classroom_model');
            $classroom = $this->Classroom_model->get_by_code($class_code);
            if (!$classroom || $classroom['teacher_id'] != $user_data['user_id']) {
                return json_response(false, 'Classroom not found or access denied', null, 404);
            }
            
            // Get enrollment statistics
            $total_enrolled = $this->db->where('classroom_id', $classroom['id'])
                ->where('status', 'active')
                ->count_all_results('classroom_enrollments');
            
            $recent_enrollments = $this->db->select('ce.enrolled_at, u.full_name, u.student_num')
                ->from('classroom_enrollments ce')
                ->join('users u', 'ce.student_id = u.user_id')
                ->where('ce.classroom_id', $classroom['id'])
                ->where('ce.status', 'active')
                ->order_by('ce.enrolled_at', 'DESC')
                ->limit(5)
                ->get()->result_array();
            
            $stats = [
                'classroom' => [
                    'class_code' => $classroom['class_code'],
                    'title' => $classroom['title'],
                    'semester' => $classroom['semester'],
                    'school_year' => $classroom['school_year']
                ],
                'enrollment_stats' => [
                    'total_enrolled' => $total_enrolled,
                    'recent_enrollments' => $recent_enrollments
                ]
            ];
            
            return json_response(true, 'Enrollment statistics retrieved successfully', $stats);
            
        } catch (Exception $e) {
            return json_response(false, 'Failed to retrieve enrollment statistics: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get all grades for a specific class (Teacher only)
     * GET /api/teacher/classroom/{class_code}/grades
     */
    public function classroom_grades_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        try {
            // Get classroom by code and verify teacher ownership
            $this->load->model('Classroom_model');
            $classroom = $this->Classroom_model->get_by_code($class_code);
            if (!$classroom || $classroom['teacher_id'] != $user_data['user_id']) {
                return json_response(false, 'Classroom not found or access denied', null, 404);
            }
            
            // Get query parameters
            $task_id = $this->input->get('task_id'); // Optional: filter by specific task
            $student_id = $this->input->get('student_id'); // Optional: filter by specific student
            
            // Get all tasks for this class
            $tasks_query = $this->db->select('ct.*')
                ->from('class_tasks ct')
                ->where("ct.class_codes LIKE '%\"$class_code\"%'")
                ->where('ct.is_draft', 0)
                ->where('ct.is_scheduled', 0)
                ->order_by('ct.due_date', 'ASC');
            
            if ($task_id) {
                $tasks_query->where('ct.task_id', $task_id);
            }
            
            $tasks = $tasks_query->get()->result_array();
            
            // Get all enrolled students - using raw query to handle collation
            $students_sql = "SELECT 
                            ce.student_id, 
                            u.full_name, 
                            u.student_num, 
                            u.email, 
                            u.profile_pic
                        FROM classroom_enrollments ce
                        JOIN users u ON ce.student_id = u.user_id COLLATE utf8mb4_unicode_ci
                        WHERE ce.classroom_id = ?
                        AND ce.status = 'active'";
            
            $students_params = [$classroom['id']];
            
            if ($student_id) {
                $students_sql .= " AND ce.student_id = ?";
                $students_params[] = $student_id;
            }
            
            $students_sql .= " ORDER BY u.full_name ASC";
            
            $students = $this->db->query($students_sql, $students_params)->result_array();
            
            // Get all submissions and grades
            $submissions_query = $this->db->select('ts.*, ct.title as task_title, ct.points as task_points, ct.type as task_type')
                ->from('task_submissions ts')
                ->join('class_tasks ct', 'ts.task_id = ct.task_id')
                ->where("ct.class_codes LIKE '%\"$class_code\"%'")
                ->where('ct.is_draft', 0)
                ->where('ct.is_scheduled', 0);
            
            if ($task_id) {
                $submissions_query->where('ts.task_id', $task_id);
            }
            if ($student_id) {
                $submissions_query->where('ts.student_id', $student_id);
            }
            
            $submissions = $submissions_query->get()->result_array();
            
            // Get attendance records for this class
            $actual_class_id_for_attendance = null;
            
            // Find the correct class_id for attendance records
            $classes_query = $this->db->select('class_id')
                ->from('classes')
                ->where('subject_id', $classroom['subject_id'])
                ->where('section_id', $classroom['section_id'])
                ->where('teacher_id', $classroom['teacher_id'])
                ->get();
            
            $class_ids = $classes_query->result_array();
            
            // Find the class_id that has actual attendance records
            foreach ($class_ids as $class) {
                $attendance_count = $this->db->where('class_id', $class['class_id'])
                    ->from('attendance')
                    ->count_all_results();
                
                if ($attendance_count > 0) {
                    $actual_class_id_for_attendance = $class['class_id'];
                    break;
                }
            }
            
            // If no class_id with attendance found, use the first one
            if (!$actual_class_id_for_attendance && !empty($class_ids)) {
                $actual_class_id_for_attendance = $class_ids[0]['class_id'];
            }
            
            $attendance_records = [];
            if ($actual_class_id_for_attendance) {
                $attendance_query = $this->db->select('a.*, u.full_name as student_name, u.student_num, u.email')
                    ->from('attendance a')
                    ->join('users u', 'a.student_id = u.user_id')
                    ->where('a.class_id', $actual_class_id_for_attendance);
                
                if ($student_id) {
                    $attendance_query->where('a.student_id', $student_id);
                }
                
                $attendance_records = $attendance_query->get()->result_array();
            }
            
            // Organize data for the grades table
            $grades_data = [];
            $task_summary = [];
            
            foreach ($students as $student) {
                $student_grades = [
                    'student_id' => $student['student_id'],
                    'student_name' => $student['full_name'],
                    'student_num' => $student['student_num'],
                    'email' => $student['email'],
                    'profile_pic' => $student['profile_pic'],
                    'attendance' => [
                        'total_sessions' => 0,
                        'present_sessions' => 0,
                        'late_sessions' => 0,
                        'absent_sessions' => 0,
                        'excused_sessions' => 0,
                        'total_earned_score' => 0,
                        'max_possible_score' => 6,
                        'max_possible_total' => 0,
                        'attendance_percentage' => 0
                    ],
                    'assignments' => [],
                    'total_points' => 0,
                    'total_earned' => 0,
                    'average_grade' => 0
                ];
                
                $student_total_points = 0;
                $student_total_earned = 0;
                $graded_count = 0;
                
                // Calculate attendance for this student
                $student_attendance = array_filter($attendance_records, function($record) use ($student) {
                    return $record['student_id'] === $student['student_id'];
                });
                
                $total_sessions = count($student_attendance);
                $present_sessions = count(array_filter($student_attendance, function($record) {
                    return strtolower($record['status']) === 'present';
                }));
                $late_sessions = count(array_filter($student_attendance, function($record) {
                    return strtolower($record['status']) === 'late';
                }));
                $absent_sessions = count(array_filter($student_attendance, function($record) {
                    return strtolower($record['status']) === 'absent';
                }));
                $excused_sessions = count(array_filter($student_attendance, function($record) {
                    return strtolower($record['status']) === 'excused';
                }));
                
                // Calculate attendance score based on 6-point scale
                $max_possible_score = 6;
                $total_earned_score = 0;
                
                foreach ($student_attendance as $record) {
                    $status = strtolower($record['status']);
                    switch ($status) {
                        case 'present':
                            $total_earned_score += 6;
                            break;
                        case 'late':
                            $total_earned_score += 4;
                            break;
                        case 'excused':
                            $total_earned_score += 5;
                            break;
                        case 'absent':
                            $total_earned_score += 0;
                            break;
                        default:
                            $total_earned_score += 0;
                            break;
                    }
                }
                
                $max_possible_total = $total_sessions * $max_possible_score;
                $attendance_percentage = $max_possible_total > 0 ? 
                    ($total_earned_score / $max_possible_total) * 100 : 0;
                
                $student_grades['attendance'] = [
                    'total_sessions' => $total_sessions,
                    'present_sessions' => $present_sessions,
                    'late_sessions' => $late_sessions,
                    'absent_sessions' => $absent_sessions,
                    'excused_sessions' => $excused_sessions,
                    'total_earned_score' => $total_earned_score,
                    'max_possible_score' => $max_possible_score,
                    'max_possible_total' => $max_possible_total,
                    'attendance_percentage' => round($attendance_percentage, 2)
                ];
                
                foreach ($tasks as $task) {
                    $assignment_grade = [
                        'task_id' => $task['task_id'],
                        'task_title' => $task['title'],
                        'task_type' => $task['type'],
                        'points' => $task['points'],
                        'due_date' => $task['due_date'],
                        'submission_id' => null,
                        'grade' => null,
                        'grade_percentage' => null,
                        'feedback' => null,
                        'status' => 'not_submitted',
                        'submitted_at' => null,
                        'attachment_url' => null
                    ];
                    
                    // Find submission for this student and task
                    foreach ($submissions as $submission) {
                        if ($submission['student_id'] == $student['student_id'] && 
                            $submission['task_id'] == $task['task_id']) {
                            
                            $assignment_grade['submission_id'] = $submission['submission_id'];
                            $assignment_grade['grade'] = $submission['grade'];
                            $assignment_grade['feedback'] = $submission['feedback'];
                            $assignment_grade['submitted_at'] = $submission['submitted_at'];
                            $assignment_grade['attachment_url'] = $submission['attachment_url'];
                            
                            if ($submission['grade'] !== null) {
                                $assignment_grade['status'] = 'graded';
                                $assignment_grade['grade_percentage'] = round(($submission['grade'] / $task['points']) * 100, 1);
                                $student_total_earned += $submission['grade'];
                                $graded_count++;
                            } else {
                                $assignment_grade['status'] = 'submitted';
                            }
                            
                            break;
                        }
                    }
                    
                    $student_total_points += $task['points'];
                    $student_grades['assignments'][] = $assignment_grade;
                }
                
                // Calculate student averages
                $student_grades['total_points'] = $student_total_points;
                $student_grades['total_earned'] = $student_total_earned;
                $student_grades['average_grade'] = $graded_count > 0 ? round($student_total_earned / $graded_count, 1) : 0;
                
                $grades_data[] = $student_grades;
            }
            
            // Calculate class statistics
            $class_stats = [
                'total_students' => count($students),
                'total_assignments' => count($tasks),
                'total_submissions' => count($submissions),
                'graded_submissions' => count(array_filter($submissions, function($s) { return $s['grade'] !== null; })),
                'average_class_grade' => 0
            ];
            
            // Calculate class average
            $total_grades = 0;
            $total_graded = 0;
            foreach ($grades_data as $student) {
                if ($student['total_earned'] > 0) {
                    $total_grades += $student['total_earned'];
                    $total_graded += $student['total_points'];
                }
            }
            $class_stats['average_class_grade'] = $total_graded > 0 ? round(($total_grades / $total_graded) * 100, 1) : 0;
            
            $response_data = [
                'classroom' => [
                    'class_code' => $classroom['class_code'],
                    'title' => $classroom['title'],
                    'semester' => $classroom['semester'],
                    'school_year' => $classroom['school_year']
                ],
                'tasks' => array_map(function($task) {
                    return [
                        'task_id' => $task['task_id'],
                        'title' => $task['title'],
                        'type' => $task['type'],
                        'points' => $task['points'],
                        'due_date' => $task['due_date']
                    ];
                }, $tasks),
                'students' => $grades_data,
                'statistics' => $class_stats,
                'filters' => [
                    'current_task_filter' => $task_id ?: 'all',
                    'current_student_filter' => $student_id ?: 'all'
                ]
            ];
            
            return json_response(true, 'Class grades retrieved successfully', $response_data);
            
        } catch (Exception $e) {
            return json_response(false, 'Failed to retrieve class grades: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get comprehensive grades for a specific class with customizable percentages (Teacher only)
     * GET /api/teacher/classroom/{class_code}/comprehensive-grades
     */
    public function classroom_comprehensive_grades_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        try {
            // Get classroom by code and verify teacher ownership
            $this->load->model('Classroom_model');
            $classroom = $this->Classroom_model->get_by_code($class_code);
            if (!$classroom || $classroom['teacher_id'] != $user_data['user_id']) {
                return json_response(false, 'Classroom not found or access denied', null, 404);
            }
            
            // Get query parameters for customizable percentages
            $attendance_weight = (float)($this->input->get('attendance_weight') ?: 10);
            $activity_weight = (float)($this->input->get('activity_weight') ?: 30);
            $assignment_quiz_weight = (float)($this->input->get('assignment_quiz_weight') ?: 30);
            $major_exam_weight = (float)($this->input->get('major_exam_weight') ?: 30);
            
            // Validate weights total 100%
            $total_weight = $attendance_weight + $activity_weight + $assignment_quiz_weight + $major_exam_weight;
            if (abs($total_weight - 100) > 0.01) {
                return json_response(false, 'Weights must total 100%. Current total: ' . $total_weight . '%', null, 400);
            }
            
            // Get all enrolled students
            $students_sql = "SELECT 
                            ce.student_id, 
                            u.full_name, 
                            u.student_num, 
                            u.email, 
                            u.profile_pic
                        FROM classroom_enrollments ce
                        JOIN users u ON ce.student_id = u.user_id COLLATE utf8mb4_unicode_ci
                        WHERE ce.classroom_id = ?
                        AND ce.status = 'active'
                        ORDER BY u.full_name ASC";
            
            $students = $this->db->query($students_sql, [$classroom['id']])->result_array();
            
            // Get all tasks for this class categorized by type
            $tasks_query = $this->db->select('ct.*')
                ->from('class_tasks ct')
                ->where("ct.class_codes LIKE '%\"$class_code\"%'")
                ->where('ct.is_draft', 0)
                ->where('ct.is_scheduled', 0)
                ->order_by('ct.type', 'ASC')
                ->order_by('ct.created_at', 'ASC');
            
            $tasks = $tasks_query->get()->result_array();
            
            // Categorize tasks
            $attendance_tasks = [];
            $activity_tasks = [];
            $assignment_quiz_tasks = [];
            $major_exam_tasks = [];
            
            foreach ($tasks as $task) {
                switch ($task['type']) {
                    case 'activity':
                        $activity_tasks[] = $task;
                        break;
                    case 'quiz':
                        $assignment_quiz_tasks[] = $task;
                        break;
                    case 'assignment':
                        $assignment_quiz_tasks[] = $task;
                        break;
                    case 'exam':
                        $major_exam_tasks[] = $task;
                        break;
                    case 'project':
                        $major_exam_tasks[] = $task;
                        break;
                    default:
                        $assignment_quiz_tasks[] = $task;
                        break;
                }
            }
            
            // Get the corresponding class_id from the 'classes' table
            // This is crucial because attendance.class_id refers to the 'classes' table's class_id (VARCHAR)
            // not the 'classrooms' table's ID (INT)
            $class_offerings = $this->db->select('class_id')
                                      ->from('classes')
                                      ->where('subject_id', $classroom['subject_id'])
                                      ->where('section_id', $classroom['section_id'])
                                      ->where('teacher_id', $classroom['teacher_id'])
                                      ->where('status', 'active')
                                      ->get()->result_array();

            $actual_class_id_for_attendance = null;
            $attendance_records = [];

            // Check each class offering to find the one with attendance records
            foreach ($class_offerings as $class_offering) {
                $class_id = $class_offering['class_id'];
                
                // Check if this class has attendance records
                $attendance_count = $this->db->select('COUNT(*) as count')
                                           ->from('attendance')
                                           ->where('teacher_id', $user_data['user_id'])
                                           ->where('class_id', $class_id)
                                           ->get()->row_array();
                
                if ($attendance_count && $attendance_count['count'] > 0) {
                    $actual_class_id_for_attendance = $class_id;
                    break;
                }
            }

            // If no class with attendance found, use the first class offering (fallback)
            if (!$actual_class_id_for_attendance && !empty($class_offerings)) {
                $actual_class_id_for_attendance = $class_offerings[0]['class_id'];
                log_message('info', 'No attendance records found for any class offering. Using first class_id: ' . $actual_class_id_for_attendance);
            }

            if (!$actual_class_id_for_attendance) {
                // Log a warning or handle the case where no matching class offering is found
                log_message('warning', 'No matching class offering found for classroom ID: ' . $classroom['id'] . ' for attendance retrieval.');
                // If no class offering, there won't be attendance records for it, so return empty attendance
                $attendance_records = [];
            } else {
                // Get attendance records for this class
                $attendance_records = $this->db->select('
                    attendance.*,
                    users.full_name as student_name,
                    users.student_num
                ')
                ->from('attendance')
                ->join('users', 'attendance.student_id = users.user_id', 'left')
                ->where('attendance.teacher_id', $user_data['user_id'])
                ->where('attendance.class_id', $actual_class_id_for_attendance)
                ->get()->result_array();
            }
            
            // Get all submissions and grades
            $submissions_query = $this->db->select('ts.*, ct.title as task_title, ct.points as task_points, ct.type as task_type')
                ->from('task_submissions ts')
                ->join('class_tasks ct', 'ts.task_id = ct.task_id')
                ->where("ct.class_codes LIKE '%\"$class_code\"%'")
                ->where('ct.is_draft', 0)
                ->where('ct.is_scheduled', 0);
            
            $submissions = $submissions_query->get()->result_array();
            
            // Organize data for comprehensive grades
            $comprehensive_grades = [];
            $category_summary = [
                'attendance' => [
                    'total_possible' => 0,
                    'total_earned' => 0,
                    'weight' => $attendance_weight
                ],
                'activity' => [
                    'total_possible' => 0,
                    'total_earned' => 0,
                    'weight' => $activity_weight
                ],
                'assignment_quiz' => [
                    'total_possible' => 0,
                    'total_earned' => 0,
                    'weight' => $assignment_quiz_weight
                ],
                'major_exam' => [
                    'total_possible' => 0,
                    'total_earned' => 0,
                    'weight' => $major_exam_weight
                ]
            ];
            
            foreach ($students as $student) {
                $student_grades = [
                    'student_id' => $student['student_id'],
                    'student_name' => $student['full_name'],
                    'student_num' => $student['student_num'],
                    'email' => $student['email'],
                    'profile_pic' => $student['profile_pic'],
                    'attendance' => [
                        'total_sessions' => 0,
                        'present_sessions' => 0,
                        'late_sessions' => 0,
                        'absent_sessions' => 0,
                        'excused_sessions' => 0,
                        'total_earned_score' => 0,
                        'max_possible_score' => 0,
                        'max_possible_total' => 0,
                        'attendance_percentage' => 0,
                        'weighted_score' => 0
                    ],
                    'activities' => [],
                    'assignments_quizzes' => [],
                    'major_exams' => [],
                    'category_scores' => [
                        'attendance' => 0,
                        'activity' => 0,
                        'assignment_quiz' => 0,
                        'major_exam' => 0
                    ],
                    'final_grade' => 0
                ];
                
                // Calculate attendance
                $student_attendance = array_filter($attendance_records, function($record) use ($student) {
                    return $record['student_id'] === $student['student_id'];
                });
                
                $total_sessions = count($student_attendance);
                $present_sessions = count(array_filter($student_attendance, function($record) {
                    return strtolower($record['status']) === 'present';
                }));
                $late_sessions = count(array_filter($student_attendance, function($record) {
                    return strtolower($record['status']) === 'late';
                }));
                $absent_sessions = count(array_filter($student_attendance, function($record) {
                    return strtolower($record['status']) === 'absent';
                }));
                $excused_sessions = count(array_filter($student_attendance, function($record) {
                    return strtolower($record['status']) === 'excused';
                }));
                
                // Calculate attendance score based on 6-point scale
                $max_possible_score = 6;
                $total_earned_score = 0;
                
                foreach ($student_attendance as $record) {
                    $status = strtolower($record['status']);
                    switch ($status) {
                        case 'present':
                            $total_earned_score += 6;
                            break;
                        case 'late':
                            $total_earned_score += 4;
                            break;
                        case 'excused':
                            $total_earned_score += 5;
                            break;
                        case 'absent':
                            $total_earned_score += 0;
                            break;
                        default:
                            $total_earned_score += 0;
                            break;
                    }
                }
                
                $max_possible_total = $total_sessions * $max_possible_score;
                $attendance_percentage = $max_possible_total > 0 ? 
                    ($total_earned_score / $max_possible_total) * 100 : 0;
                
                $student_grades['attendance'] = [
                    'total_sessions' => $total_sessions,
                    'present_sessions' => $present_sessions,
                    'late_sessions' => $late_sessions,
                    'absent_sessions' => $absent_sessions,
                    'excused_sessions' => $excused_sessions,
                    'total_earned_score' => $total_earned_score,
                    'max_possible_score' => $max_possible_score,
                    'max_possible_total' => $max_possible_total,
                    'attendance_percentage' => round($attendance_percentage, 2),
                    'weighted_score' => round(($attendance_percentage * $attendance_weight) / 100, 2)
                ];
                
                // Process activities
                foreach ($activity_tasks as $task) {
                    $task_grade = [
                        'task_id' => $task['task_id'],
                        'title' => $task['title'],
                        'type' => $task['type'],
                        'points' => $task['points'],
                        'grade' => null,
                        'grade_percentage' => 0,
                        'status' => 'not_submitted',
                        'submitted_at' => null,
                        'feedback' => null
                    ];
                    
                    // Find submission for this task and student
                    foreach ($submissions as $submission) {
                        if ($submission['student_id'] === $student['student_id'] && 
                            $submission['task_id'] === $task['task_id']) {
                            
                            $task_grade['grade'] = $submission['grade'];
                            $task_grade['status'] = $submission['grade'] !== null ? 'graded' : 'submitted';
                            $task_grade['submitted_at'] = $submission['submitted_at'];
                            $task_grade['feedback'] = $submission['feedback'];
                            
                            if ($submission['grade'] !== null) {
                                $task_grade['grade_percentage'] = round(($submission['grade'] / $task['points']) * 100, 2);
                            }
                            break;
                        }
                    }
                    
                    $student_grades['activities'][] = $task_grade;
                }
                
                // Process assignments and quizzes
                foreach ($assignment_quiz_tasks as $task) {
                    $task_grade = [
                        'task_id' => $task['task_id'],
                        'title' => $task['title'],
                        'type' => $task['type'],
                        'points' => $task['points'],
                        'grade' => null,
                        'grade_percentage' => 0,
                        'status' => 'not_submitted',
                        'submitted_at' => null,
                        'feedback' => null
                    ];
                    
                    foreach ($submissions as $submission) {
                        if ($submission['student_id'] === $student['student_id'] && 
                            $submission['task_id'] === $task['task_id']) {
                            
                            $task_grade['grade'] = $submission['grade'];
                            $task_grade['status'] = $submission['grade'] !== null ? 'graded' : 'submitted';
                            $task_grade['submitted_at'] = $submission['submitted_at'];
                            $task_grade['feedback'] = $submission['feedback'];
                            
                            if ($submission['grade'] !== null) {
                                $task_grade['grade_percentage'] = round(($submission['grade'] / $task['points']) * 100, 2);
                            }
                            break;
                        }
                    }
                    
                    $student_grades['assignments_quizzes'][] = $task_grade;
                }
                
                // Process major exams
                foreach ($major_exam_tasks as $task) {
                    $task_grade = [
                        'task_id' => $task['task_id'],
                        'title' => $task['title'],
                        'type' => $task['type'],
                        'points' => $task['points'],
                        'grade' => null,
                        'grade_percentage' => 0,
                        'status' => 'not_submitted',
                        'submitted_at' => null,
                        'feedback' => null
                    ];
                    
                    foreach ($submissions as $submission) {
                        if ($submission['student_id'] === $student['student_id'] && 
                            $submission['task_id'] === $task['task_id']) {
                            
                            $task_grade['grade'] = $submission['grade'];
                            $task_grade['status'] = $submission['grade'] !== null ? 'graded' : 'submitted';
                            $task_grade['submitted_at'] = $submission['submitted_at'];
                            $task_grade['feedback'] = $submission['feedback'];
                            
                            if ($submission['grade'] !== null) {
                                $task_grade['grade_percentage'] = round(($submission['grade'] / $task['points']) * 100, 2);
                            }
                            break;
                        }
                    }
                    
                    $student_grades['major_exams'][] = $task_grade;
                }
                
                // Calculate category averages
                $activity_avg = $this->calculate_category_average($student_grades['activities']);
                $assignment_quiz_avg = $this->calculate_category_average($student_grades['assignments_quizzes']);
                $major_exam_avg = $this->calculate_category_average($student_grades['major_exams']);
                
                $student_grades['category_scores'] = [
                    'attendance' => $student_grades['attendance']['weighted_score'],
                    'activity' => round(($activity_avg * $activity_weight) / 100, 2),
                    'assignment_quiz' => round(($assignment_quiz_avg * $assignment_quiz_weight) / 100, 2),
                    'major_exam' => round(($major_exam_avg * $major_exam_weight) / 100, 2)
                ];
                
                // Calculate final grade
                $final_grade = $student_grades['category_scores']['attendance'] + 
                              $student_grades['category_scores']['activity'] + 
                              $student_grades['category_scores']['assignment_quiz'] + 
                              $student_grades['category_scores']['major_exam'];
                
                $student_grades['final_grade'] = round($final_grade, 2);
                
                $comprehensive_grades[] = $student_grades;
            }
            
            // Calculate class statistics
            $class_stats = [
                'total_students' => count($students),
                'total_activities' => count($activity_tasks),
                'total_assignments_quizzes' => count($assignment_quiz_tasks),
                'total_major_exams' => count($major_exam_tasks),
                'total_submissions' => count($submissions),
                'graded_submissions' => count(array_filter($submissions, function($s) { 
                    return $s['grade'] !== null; 
                })),
                'average_final_grade' => 0,
                'weights' => [
                    'attendance' => $attendance_weight,
                    'activity' => $activity_weight,
                    'assignment_quiz' => $assignment_quiz_weight,
                    'major_exam' => $major_exam_weight
                ]
            ];
            
            // Calculate class average
            $total_final_grade = 0;
            $students_with_grades = 0;
            foreach ($comprehensive_grades as $student) {
                if ($student['final_grade'] > 0) {
                    $total_final_grade += $student['final_grade'];
                    $students_with_grades++;
                }
            }
            $class_stats['average_final_grade'] = $students_with_grades > 0 ? 
                round($total_final_grade / $students_with_grades, 2) : 0;
            
            $response_data = [
                'classroom' => [
                    'class_code' => $classroom['class_code'],
                    'title' => $classroom['title'],
                    'semester' => $classroom['semester'],
                    'school_year' => $classroom['school_year']
                ],
                'tasks_summary' => [
                    'activities' => array_map(function($task) {
                        return [
                            'task_id' => $task['task_id'],
                            'title' => $task['title'],
                            'type' => $task['type'],
                            'points' => $task['points']
                        ];
                    }, $activity_tasks),
                    'assignments_quizzes' => array_map(function($task) {
                        return [
                            'task_id' => $task['task_id'],
                            'title' => $task['title'],
                            'type' => $task['type'],
                            'points' => $task['points']
                        ];
                    }, $assignment_quiz_tasks),
                    'major_exams' => array_map(function($task) {
                        return [
                            'task_id' => $task['task_id'],
                            'title' => $task['title'],
                            'type' => $task['type'],
                            'points' => $task['points']
                        ];
                    }, $major_exam_tasks)
                ],
                'students' => $comprehensive_grades,
                'statistics' => $class_stats
            ];
            
            return json_response(true, 'Comprehensive grades retrieved successfully', $response_data);
            
        } catch (Exception $e) {
            return json_response(false, 'Failed to retrieve comprehensive grades: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Export comprehensive grades to XLSX format with formulas and formatting
     * GET /api/teacher/classroom/{class_code}/export-grades
     */
    public function classroom_export_grades_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        try {
            // Get classroom by code and verify teacher ownership
            $this->load->model('Classroom_model');
            $classroom = $this->Classroom_model->get_by_code($class_code);
            if (!$classroom || $classroom['teacher_id'] != $user_data['user_id']) {
                return json_response(false, 'Classroom not found or access denied', null, 404);
            }
            
            // Get customizable percentages
            $attendance_weight = (float)($this->input->get('attendance_weight') ?: 10);
            $activity_weight = (float)($this->input->get('activity_weight') ?: 30);
            $assignment_quiz_weight = (float)($this->input->get('assignment_quiz_weight') ?: 30);
            $major_exam_weight = (float)($this->input->get('major_exam_weight') ?: 30);
            
            // Validate weights total 100%
            $total_weight = $attendance_weight + $activity_weight + $assignment_quiz_weight + $major_exam_weight;
            if (abs($total_weight - 100) > 0.01) {
                return json_response(false, 'Weights must total 100%. Current total: ' . $total_weight . '%', null, 400);
            }
            
            // Get comprehensive grades data
            $grades_data = $this->get_comprehensive_grades_data($classroom, $user_data, [
                'attendance_weight' => $attendance_weight,
                'activity_weight' => $activity_weight,
                'assignment_quiz_weight' => $assignment_quiz_weight,
                'major_exam_weight' => $major_exam_weight
            ]);
            
            // Generate XLSX file
            $this->generate_grades_xlsx($grades_data, $classroom, [
                'attendance_weight' => $attendance_weight,
                'activity_weight' => $activity_weight,
                'assignment_quiz_weight' => $assignment_quiz_weight,
                'major_exam_weight' => $major_exam_weight
            ]);
            
        } catch (Exception $e) {
            return json_response(false, 'Failed to export grades: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Helper method to calculate category average
     */
    private function calculate_category_average($tasks) {
        $total_grade = 0;
        $graded_count = 0;
        
        foreach ($tasks as $task) {
            if ($task['grade'] !== null) {
                $total_grade += $task['grade_percentage'];
                $graded_count++;
            }
        }
        
        return $graded_count > 0 ? round($total_grade / $graded_count, 2) : 0;
    }
    
    /**
     * Helper method to get comprehensive grades data
     */
    private function get_comprehensive_grades_data($classroom, $user_data, $weights) {
        try {
            $attendance_weight = $weights['attendance_weight'];
            $activity_weight = $weights['activity_weight'];
            $assignment_quiz_weight = $weights['assignment_quiz_weight'];
            $major_exam_weight = $weights['major_exam_weight'];
            $class_code = $classroom['class_code'];
            
            // Get all enrolled students
            $students_sql = "SELECT 
                            ce.student_id, 
                            u.full_name, 
                            u.student_num, 
                            u.email, 
                            u.profile_pic
                        FROM classroom_enrollments ce
                        JOIN users u ON ce.student_id = u.user_id COLLATE utf8mb4_unicode_ci
                        WHERE ce.classroom_id = ?
                        AND ce.status = 'active'
                        ORDER BY u.full_name ASC";
            
            $students = $this->db->query($students_sql, [$classroom['id']])->result_array();
            
            // Get all tasks for this class categorized by type
            $tasks_query = $this->db->select('ct.*')
                ->from('class_tasks ct')
                ->where("ct.class_codes LIKE '%\"$class_code\"%'")
                ->where('ct.is_draft', 0)
                ->where('ct.is_scheduled', 0)
                ->order_by('ct.type', 'ASC')
                ->order_by('ct.created_at', 'ASC');
            
            $tasks = $tasks_query->get()->result_array();
            
            // Categorize tasks
            $attendance_tasks = [];
            $activity_tasks = [];
            $assignment_quiz_tasks = [];
            $major_exam_tasks = [];
            
            foreach ($tasks as $task) {
                switch ($task['type']) {
                    case 'activity':
                        $activity_tasks[] = $task;
                        break;
                    case 'quiz':
                        $assignment_quiz_tasks[] = $task;
                        break;
                    case 'assignment':
                        $assignment_quiz_tasks[] = $task;
                        break;
                    case 'exam':
                        $major_exam_tasks[] = $task;
                        break;
                    case 'project':
                        $major_exam_tasks[] = $task;
                        break;
                    default:
                        $assignment_quiz_tasks[] = $task;
                        break;
                }
            }
            
            // Get the corresponding class_id from the 'classes' table
            // This is crucial because attendance.class_id refers to the 'classes' table's class_id (VARCHAR)
            // not the 'classrooms' table's ID (INT)
            $class_offerings = $this->db->select('class_id')
                                      ->from('classes')
                                      ->where('subject_id', $classroom['subject_id'])
                                      ->where('section_id', $classroom['section_id'])
                                      ->where('teacher_id', $classroom['teacher_id'])
                                      ->where('status', 'active')
                                      ->get()->result_array();

            $actual_class_id_for_attendance = null;
            $attendance_records = [];

            // Check each class offering to find the one with attendance records
            foreach ($class_offerings as $class_offering) {
                $class_id = $class_offering['class_id'];
                
                // Check if this class has attendance records
                $attendance_count = $this->db->select('COUNT(*) as count')
                                           ->from('attendance')
                                           ->where('teacher_id', $user_data['user_id'])
                                           ->where('class_id', $class_id)
                                           ->get()->row_array();
                
                if ($attendance_count && $attendance_count['count'] > 0) {
                    $actual_class_id_for_attendance = $class_id;
                    break;
                }
            }

            // If no class with attendance found, use the first class offering (fallback)
            if (!$actual_class_id_for_attendance && !empty($class_offerings)) {
                $actual_class_id_for_attendance = $class_offerings[0]['class_id'];
                log_message('info', 'No attendance records found for any class offering. Using first class_id: ' . $actual_class_id_for_attendance);
            }

            if (!$actual_class_id_for_attendance) {
                // Log a warning or handle the case where no matching class offering is found
                log_message('warning', 'No matching class offering found for classroom ID: ' . $classroom['id'] . ' for attendance retrieval.');
                // If no class offering, there won't be attendance records for it, so return empty attendance
                $attendance_records = [];
            } else {
                // Get attendance records for this class
                $attendance_records = $this->db->select('
                    attendance.*,
                    users.full_name as student_name,
                    users.student_num
                ')
                ->from('attendance')
                ->join('users', 'attendance.student_id = users.user_id', 'left')
                ->where('attendance.teacher_id', $user_data['user_id'])
                ->where('attendance.class_id', $actual_class_id_for_attendance)
                ->get()->result_array();
            }
            
            // Get all submissions and grades
            $submissions_query = $this->db->select('ts.*, ct.title as task_title, ct.points as task_points, ct.type as task_type')
                ->from('task_submissions ts')
                ->join('class_tasks ct', 'ts.task_id = ct.task_id')
                ->where("ct.class_codes LIKE '%\"$class_code\"%'")
                ->where('ct.is_draft', 0)
                ->where('ct.is_scheduled', 0);
            
            $submissions = $submissions_query->get()->result_array();
            
            // Organize data for comprehensive grades
            $comprehensive_grades = [];
            
            foreach ($students as $student) {
                $student_grades = [
                    'student_id' => $student['student_id'],
                    'student_name' => $student['full_name'],
                    'student_num' => $student['student_num'],
                    'email' => $student['email'],
                    'profile_pic' => $student['profile_pic'],
                    'attendance' => [
                        'total_sessions' => 0,
                        'present_sessions' => 0,
                        'late_sessions' => 0,
                        'absent_sessions' => 0,
                        'excused_sessions' => 0,
                        'total_earned_score' => 0,
                        'max_possible_score' => 0,
                        'max_possible_total' => 0,
                        'attendance_percentage' => 0,
                        'weighted_score' => 0
                    ],
                    'activities' => [],
                    'assignments_quizzes' => [],
                    'major_exams' => [],
                    'category_scores' => [
                        'attendance' => 0,
                        'activity' => 0,
                        'assignment_quiz' => 0,
                        'major_exam' => 0
                    ],
                    'final_grade' => 0
                ];
                
                // Calculate attendance
                $student_attendance = array_filter($attendance_records, function($record) use ($student) {
                    return $record['student_id'] === $student['student_id'];
                });
                
                $total_sessions = count($student_attendance);
                $present_sessions = count(array_filter($student_attendance, function($record) {
                    return strtolower($record['status']) === 'present';
                }));
                $late_sessions = count(array_filter($student_attendance, function($record) {
                    return strtolower($record['status']) === 'late';
                }));
                $absent_sessions = count(array_filter($student_attendance, function($record) {
                    return strtolower($record['status']) === 'absent';
                }));
                $excused_sessions = count(array_filter($student_attendance, function($record) {
                    return strtolower($record['status']) === 'excused';
                }));
                
                // Calculate attendance score based on 6-point scale
                $max_possible_score = 6;
                $total_earned_score = 0;
                
                foreach ($student_attendance as $record) {
                    $status = strtolower($record['status']);
                    switch ($status) {
                        case 'present':
                            $total_earned_score += 6;
                            break;
                        case 'late':
                            $total_earned_score += 4;
                            break;
                        case 'excused':
                            $total_earned_score += 5;
                            break;
                        case 'absent':
                            $total_earned_score += 0;
                            break;
                        default:
                            $total_earned_score += 0;
                            break;
                    }
                }
                
                $max_possible_total = $total_sessions * $max_possible_score;
                $attendance_percentage = $max_possible_total > 0 ? 
                    ($total_earned_score / $max_possible_total) * 100 : 0;
                
                $student_grades['attendance'] = [
                    'total_sessions' => $total_sessions,
                    'present_sessions' => $present_sessions,
                    'late_sessions' => $late_sessions,
                    'absent_sessions' => $absent_sessions,
                    'excused_sessions' => $excused_sessions,
                    'total_earned_score' => $total_earned_score,
                    'max_possible_score' => $max_possible_score,
                    'max_possible_total' => $max_possible_total,
                    'attendance_percentage' => round($attendance_percentage, 2),
                    'weighted_score' => round(($attendance_percentage * $attendance_weight) / 100, 2)
                ];
                
                // Process activities
                foreach ($activity_tasks as $task) {
                    $task_grade = [
                        'task_id' => $task['task_id'],
                        'title' => $task['title'],
                        'type' => $task['type'],
                        'points' => $task['points'],
                        'grade' => null,
                        'grade_percentage' => 0,
                        'status' => 'not_submitted',
                        'submitted_at' => null,
                        'feedback' => null
                    ];
                    
                    // Find submission for this task and student
                    foreach ($submissions as $submission) {
                        if ($submission['student_id'] === $student['student_id'] && 
                            $submission['task_id'] === $task['task_id']) {
                            
                            $task_grade['grade'] = $submission['grade'];
                            $task_grade['status'] = $submission['grade'] !== null ? 'graded' : 'submitted';
                            $task_grade['submitted_at'] = $submission['submitted_at'];
                            $task_grade['feedback'] = $submission['feedback'];
                            
                            if ($submission['grade'] !== null) {
                                $task_grade['grade_percentage'] = round(($submission['grade'] / $task['points']) * 100, 2);
                            }
                            break;
                        }
                    }
                    
                    $student_grades['activities'][] = $task_grade;
                }
                
                // Process assignments and quizzes
                foreach ($assignment_quiz_tasks as $task) {
                    $task_grade = [
                        'task_id' => $task['task_id'],
                        'title' => $task['title'],
                        'type' => $task['type'],
                        'points' => $task['points'],
                        'grade' => null,
                        'grade_percentage' => 0,
                        'status' => 'not_submitted',
                        'submitted_at' => null,
                        'feedback' => null
                    ];
                    
                    foreach ($submissions as $submission) {
                        if ($submission['student_id'] === $student['student_id'] && 
                            $submission['task_id'] === $task['task_id']) {
                            
                            $task_grade['grade'] = $submission['grade'];
                            $task_grade['status'] = $submission['grade'] !== null ? 'graded' : 'submitted';
                            $task_grade['submitted_at'] = $submission['submitted_at'];
                            $task_grade['feedback'] = $submission['feedback'];
                            
                            if ($submission['grade'] !== null) {
                                $task_grade['grade_percentage'] = round(($submission['grade'] / $task['points']) * 100, 2);
                            }
                            break;
                        }
                    }
                    
                    $student_grades['assignments_quizzes'][] = $task_grade;
                }
                
                // Process major exams
                foreach ($major_exam_tasks as $task) {
                    $task_grade = [
                        'task_id' => $task['task_id'],
                        'title' => $task['title'],
                        'type' => $task['type'],
                        'points' => $task['points'],
                        'grade' => null,
                        'grade_percentage' => 0,
                        'status' => 'not_submitted',
                        'submitted_at' => null,
                        'feedback' => null
                    ];
                    
                    foreach ($submissions as $submission) {
                        if ($submission['student_id'] === $student['student_id'] && 
                            $submission['task_id'] === $task['task_id']) {
                            
                            $task_grade['grade'] = $submission['grade'];
                            $task_grade['status'] = $submission['grade'] !== null ? 'graded' : 'submitted';
                            $task_grade['submitted_at'] = $submission['submitted_at'];
                            $task_grade['feedback'] = $submission['feedback'];
                            
                            if ($submission['grade'] !== null) {
                                $task_grade['grade_percentage'] = round(($submission['grade'] / $task['points']) * 100, 2);
                            }
                            break;
                        }
                    }
                    
                    $student_grades['major_exams'][] = $task_grade;
                }
                
                // Calculate category averages
                $activity_avg = $this->calculate_category_average($student_grades['activities']);
                $assignment_quiz_avg = $this->calculate_category_average($student_grades['assignments_quizzes']);
                $major_exam_avg = $this->calculate_category_average($student_grades['major_exams']);
                
                $student_grades['category_scores'] = [
                    'attendance' => $student_grades['attendance']['weighted_score'],
                    'activity' => round(($activity_avg * $activity_weight) / 100, 2),
                    'assignment_quiz' => round(($assignment_quiz_avg * $assignment_quiz_weight) / 100, 2),
                    'major_exam' => round(($major_exam_avg * $major_exam_weight) / 100, 2)
                ];
                
                // Calculate final grade
                $final_grade = $student_grades['category_scores']['attendance'] + 
                              $student_grades['category_scores']['activity'] + 
                              $student_grades['category_scores']['assignment_quiz'] + 
                              $student_grades['category_scores']['major_exam'];
                
                $student_grades['final_grade'] = round($final_grade, 2);
                
                $comprehensive_grades[] = $student_grades;
            }
            
            return [
                'students' => $comprehensive_grades,
                'classroom' => $classroom,
                'weights' => $weights
            ];
            
        } catch (Exception $e) {
            log_message('error', 'Failed to get comprehensive grades data: ' . $e->getMessage());
            return [
                'students' => [],
                'classroom' => $classroom,
                'weights' => $weights
            ];
        }
    }
    
    /**
     * Helper method to generate XLSX file with formulas and formatting
     */
    private function generate_grades_xlsx($grades_data, $classroom, $weights) {
        try {
            // Check if we have valid data
            if (!isset($grades_data['students']) || !is_array($grades_data['students'])) {
                throw new Exception('No student data available for export');
            }
            
            // Create filename
            $filename = preg_replace('/[^a-zA-Z0-9\s]/', '', $classroom['title']) . '_Grades_' . date('Y-m-d') . '.csv';
            $filename = str_replace(' ', '_', $filename);
            
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
            
            $output = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($output, ['Student Name', 'Student Number', 'Attendance %', 'Activity %', 'Assignment/Quiz %', 'Major Exam %', 'Final Grade']);
            
            // Add data rows
            if (empty($grades_data['students'])) {
                // Add a row indicating no data
                fputcsv($output, ['No students found', '', '', '', '', '', '']);
            } else {
                foreach ($grades_data['students'] as $student) {
                    // Ensure all required fields exist
                    $student_name = isset($student['student_name']) ? $student['student_name'] : 'Unknown';
                    $student_num = isset($student['student_num']) ? $student['student_num'] : '';
                    $attendance_percentage = isset($student['attendance']['attendance_percentage']) ? $student['attendance']['attendance_percentage'] : 0;
                    $activity_avg = $this->calculate_category_average($student['activities'] ?? []);
                    $assignment_quiz_avg = $this->calculate_category_average($student['assignments_quizzes'] ?? []);
                    $major_exam_avg = $this->calculate_category_average($student['major_exams'] ?? []);
                    $final_grade = isset($student['final_grade']) ? $student['final_grade'] : 0;
                    
                    fputcsv($output, [
                        $student_name,
                        $student_num,
                        $attendance_percentage,
                        $activity_avg,
                        $assignment_quiz_avg,
                        $major_exam_avg,
                        $final_grade
                    ]);
                }
            }
            
            fclose($output);
            
        } catch (Exception $e) {
            // If there's an error, return a simple error message
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="error.txt"');
            echo "Error generating grades export: " . $e->getMessage();
        }
    }
}
