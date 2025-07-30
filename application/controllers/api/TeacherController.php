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
            // Fetch subject name
            $subject = $this->Subject_model->get_by_id($classroom['subject_id']);
            $subject_name = $subject ? $subject['subject_name'] : '';
            // Fetch section name
            $section = $this->Section_model->get_by_id($classroom['section_id']);
            $section_name = $section ? $section['section_name'] : '';
            // Count students in section (users table, role=student)
            $student_count = $this->db->where('section_id', $classroom['section_id'])->where('role', 'student')->count_all_results('users');
            $result[] = [
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
            $response = [
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
        $response = [
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
        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }
        $required = ['content'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        // student_ids is optional: if provided, only those students can see the post
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
}
