<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

class TaskController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Task_model');
        $this->load->helper(['auth', 'audit']);
        $this->load->library('Token_lib');
    }

    /**
     * Create a new class task (Teacher only)
     * POST /api/tasks/create
     */
    public function create_post()
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        // Check if multipart form data with files
        $content_type = $this->input->server('CONTENT_TYPE');
        $is_multipart = strpos($content_type, 'multipart/form-data') !== false;

        if ($is_multipart) {
            // Handle multipart form data with file upload
            $data = new stdClass();
            $data->title = $this->input->post('title');
            $data->type = $this->input->post('type');
            $data->points = $this->input->post('points');
            $data->instructions = $this->input->post('instructions');
            $data->class_codes = json_decode($this->input->post('class_codes'), true);
            $data->allow_comments = $this->input->post('allow_comments') ? 1 : 0;
            $data->is_draft = $this->input->post('is_draft') ? 1 : 0;
            $data->is_scheduled = $this->input->post('is_scheduled') ? 1 : 0;
            $data->scheduled_at = $this->input->post('scheduled_at');
            $data->due_date = $this->input->post('due_date');
            
            // Handle file upload
            $attachment_url = null;
            $attachment_type = null;
            
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_path = './uploads/tasks/';
                
                // Create upload directory if it doesn't exist
                if (!is_dir($upload_path)) {
                    if (!mkdir($upload_path, 0755, true)) {
                        $this->send_error('Failed to create upload directory', 500);
                        return;
                    }
                }
                
                // Check directory permissions
                if (!is_writable($upload_path)) {
                    $this->send_error('Upload directory is not writable', 500);
                    return;
                }
                
                $upload_config = [
                    'upload_path' => $upload_path,
                    'allowed_types' => 'gif|jpg|jpeg|png|webp|pdf|doc|docx|ppt|pptx|xls|xlsx|txt|zip|rar|mp4|mp3',
                    'max_size' => 10240, // 10MB
                    'encrypt_name' => true,
                    'overwrite' => false
                ];

                $this->load->library('upload', $upload_config);

                if ($this->upload->do_upload('attachment')) {
                    $upload_data = $this->upload->data();
                    $attachment_url = $upload_data['file_name']; // Just the filename
                    $attachment_type = 'file';
                    
                    // Verify file was actually saved
                    $file_path = $upload_path . $upload_data['file_name'];
                    if (!file_exists($file_path)) {
                        $this->send_error('File upload succeeded but file not found on disk', 500);
                        return;
                    }
                } else {
                    $error_msg = $this->upload->display_errors('', '');
                    $this->send_error('File upload failed: ' . $error_msg, 400);
                    return;
                }
            }
        } else {
            // Handle JSON request
            $data = $this->get_json_input();
            if (!$data) return;
            
            $attachment_url = $data->attachment_url ?? null;
            $attachment_type = $data->attachment_type ?? null;
        }

        // Validate required fields
        $required_fields = ['title', 'type', 'points', 'instructions', 'class_codes'];
        foreach ($required_fields as $field) {
            if (empty($data->$field)) {
                $this->send_error("$field is required", 400);
                return;
            }
        }

        // Validate type
        $valid_types = ['assignment', 'quiz', 'activity', 'project', 'exam'];
        if (!in_array($data->type, $valid_types)) {
            $this->send_error('Invalid task type', 400);
            return;
        }

        // Validate points
        if (!is_numeric($data->points) || $data->points < 0) {
            $this->send_error('Points must be a positive number', 400);
            return;
        }

        // Validate class codes
        if (!is_array($data->class_codes) || empty($data->class_codes)) {
            $this->send_error('At least one class must be selected', 400);
            return;
        }

        try {
            $task_data = [
                'title' => $data->title,
                'type' => $data->type,
                'points' => $data->points,
                'instructions' => $data->instructions,
                'class_codes' => $data->class_codes,
                'assignment_type' => $data->assignment_type ?? 'classroom',
                'assigned_students' => $data->assigned_students ?? null,
                'attachment_type' => $attachment_type,
                'attachment_url' => $attachment_url,
                'allow_comments' => $data->allow_comments,
                'is_draft' => $data->is_draft,
                'is_scheduled' => $data->is_scheduled,
                'scheduled_at' => $data->scheduled_at,
                'due_date' => $data->due_date,
                'teacher_id' => $user_data['user_id']
            ];

            $task_id = $this->Task_model->insert($task_data);
            
            if ($task_id) {
                // If individual assignment, assign specific students
                if ($task_data['assignment_type'] === 'individual' && !empty($data->assigned_students)) {
                    $this->Task_model->assign_students_to_task($task_id, $data->assigned_students);
                }
                
                $task = $this->Task_model->get_by_id($task_id);
                
                // Log task creation
                log_audit_event(
                    'CREATED CLASS TASK',
                    'TASK MANAGEMENT',
                    "Teacher created {$data->type} task: {$data->title}",
                    [
                        'table_name' => 'class_tasks',
                        'record_id' => $task_id
                    ]
                );
                
                $this->send_success($task, 'Task created successfully', 201);
            } else {
                $this->send_error('Failed to create task', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to create task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get teacher's tasks (Teacher only)
     * GET /api/tasks/teacher
     */
    public function teacher_get()
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $type = $this->input->get('type');
            $is_draft = $this->input->get('is_draft');
            $is_scheduled = $this->input->get('is_scheduled');
            $class_code = $this->input->get('class_code');

            $filters = [];
            if ($type) $filters['type'] = $type;
            if ($is_draft !== null) $filters['is_draft'] = $is_draft;
            if ($is_scheduled !== null) $filters['is_scheduled'] = $is_scheduled;
            if ($class_code) $filters['class_code'] = $class_code;

            $tasks = $this->Task_model->get_all($user_data['user_id'], $filters);
            
            // Add submission counts to each task
            foreach ($tasks as &$task) {
                $task['submission_count'] = $this->Task_model->get_submission_count($task['task_id']);
                $task['class_codes'] = json_decode($task['class_codes'], true);
            }

            $this->send_success($tasks, 'Tasks retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get student's tasks (Student only)
     * GET /api/tasks/student
     */
    public function student_get()
    {
        $user_data = require_student($this);
        if (!$user_data) return;

        try {
            $class_code = $this->input->get('class_code');
            if (!$class_code) {
                $this->send_error('Class code is required', 400);
                return;
            }

            $tasks = $this->Task_model->get_tasks_for_student($user_data['user_id'], $class_code);
            
            // Add submission status for each task
            foreach ($tasks as &$task) {
                $submission = $this->Task_model->get_student_submission($task['task_id'], $user_data['user_id'], $class_code);
                $task['submission_status'] = $submission ? $submission['status'] : 'not_submitted';
                $task['submission_id'] = $submission ? $submission['submission_id'] : null;
                $task['class_codes'] = json_decode($task['class_codes'], true);
            }

            $this->send_success($tasks, 'Tasks retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get task details with submissions (Teacher only)
     * GET /api/tasks/{task_id}
     */
    public function task_get($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_task_with_submissions($task_id, $user_data['user_id']);
            if (!$task) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            $task['class_codes'] = json_decode($task['class_codes'], true);
            $this->send_success($task, 'Task details retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve task details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update task (Teacher only)
     * PUT /api/tasks/{task_id}
     */
    public function task_put($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $data = $this->get_json_input();
        if (!$data) return;

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            // Validate required fields if updating
            if (isset($data->title) && empty($data->title)) {
                $this->send_error('Title is required', 400);
                return;
            }

            if (isset($data->type)) {
                $valid_types = ['assignment', 'quiz', 'activity', 'project', 'exam'];
                if (!in_array($data->type, $valid_types)) {
                    $this->send_error('Invalid task type', 400);
                    return;
                }
            }

            if (isset($data->points) && (!is_numeric($data->points) || $data->points < 0)) {
                $this->send_error('Points must be a positive number', 400);
                return;
            }

            $update_data = [];
            if (isset($data->title)) $update_data['title'] = $data->title;
            if (isset($data->type)) $update_data['type'] = $data->type;
            if (isset($data->points)) $update_data['points'] = $data->points;
            if (isset($data->instructions)) $update_data['instructions'] = $data->instructions;
            if (isset($data->class_codes)) $update_data['class_codes'] = $data->class_codes;
            if (isset($data->allow_comments)) $update_data['allow_comments'] = $data->allow_comments;
            if (isset($data->is_draft)) $update_data['is_draft'] = $data->is_draft;
            if (isset($data->is_scheduled)) $update_data['is_scheduled'] = $data->is_scheduled;
            if (isset($data->scheduled_at)) $update_data['scheduled_at'] = $data->scheduled_at;
            if (isset($data->due_date)) $update_data['due_date'] = $data->due_date;

            $success = $this->Task_model->update($task_id, $update_data);
            if ($success) {
                $updated_task = $this->Task_model->get_by_id($task_id);
                
                // Log task update
                log_audit_event(
                    'UPDATED CLASS TASK',
                    'TASK MANAGEMENT',
                    "Teacher updated task: {$updated_task['title']}",
                    [
                        'table_name' => 'class_tasks',
                        'record_id' => $task_id
                    ]
                );
                
                $this->send_success($updated_task, 'Task updated successfully');
            } else {
                $this->send_error('Failed to update task', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to update task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete task (Teacher only)
     * DELETE /api/tasks/{task_id}
     */
    public function task_delete($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            // Check if task has submissions
            $submission_count = $this->Task_model->get_submission_count($task_id);
            if ($submission_count > 0) {
                $this->send_error('Cannot delete task that has student submissions. Please archive it instead.', 400);
                return;
            }

            $success = $this->Task_model->delete($task_id);
            if ($success) {
                // Log task deletion
                log_audit_event(
                    'DELETED CLASS TASK',
                    'TASK MANAGEMENT',
                    "Teacher deleted task: {$task['title']}",
                    [
                        'table_name' => 'class_tasks',
                        'record_id' => $task_id
                    ]
                );
                
                $this->send_success(null, 'Task deleted successfully (soft delete)');
            } else {
                $this->send_error('Failed to delete task', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to delete task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Hard delete task (Teacher only) - Permanently removes from database
     * DELETE /api/tasks/{task_id}/hard-delete
     */
    public function hard_delete_task($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            // Check if task has submissions
            $submission_count = $this->Task_model->get_submission_count($task_id);
            if ($submission_count > 0) {
                $this->send_error('Cannot permanently delete task that has student submissions. Please archive it instead.', 400);
                return;
            }

            $success = $this->Task_model->hard_delete($task_id);
            if ($success) {
                // Log task hard deletion
                log_audit_event(
                    'HARD DELETED CLASS TASK',
                    'TASK MANAGEMENT',
                    "Teacher permanently deleted task: {$task['title']}",
                    [
                        'table_name' => 'class_tasks',
                        'record_id' => $task_id
                    ]
                );
                
                $this->send_success(null, 'Task permanently deleted from database');
            } else {
                $this->send_error('Failed to permanently delete task', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to permanently delete task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Publish draft task (Teacher only)
     * POST /api/tasks/{task_id}/publish
     */
    public function publish_post($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            $success = $this->Task_model->publish_draft($task_id);
            if ($success) {
                $this->send_success(null, 'Task published successfully');
            } else {
                $this->send_error('Failed to publish task', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to publish task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Archive task (Teacher only)
     * POST /api/tasks/{task_id}/archive
     */
    public function archive_post($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            $success = $this->Task_model->update($task_id, ['status' => 'inactive']);
            if ($success) {
                // Log task archival
                log_audit_event(
                    'ARCHIVED CLASS TASK',
                    'TASK MANAGEMENT',
                    "Teacher archived task: {$task['title']}",
                    [
                        'table_name' => 'class_tasks',
                        'record_id' => $task_id
                    ]
                );
                
                $this->send_success(null, 'Task archived successfully');
            } else {
                $this->send_error('Failed to archive task', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to archive task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Schedule task (Teacher only)
     * POST /api/tasks/{task_id}/schedule
     */
    public function schedule_post($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $data = $this->get_json_input();
        if (!$data || !isset($data->scheduled_at)) {
            $this->send_error('Scheduled date is required', 400);
            return;
        }

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            $success = $this->Task_model->schedule_task($task_id, $data->scheduled_at);
            if ($success) {
                $this->send_success(null, 'Task scheduled successfully');
            } else {
                $this->send_error('Failed to schedule task', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to schedule task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Submit task (Student only)
     * POST /api/tasks/{task_id}/submit
     */
    public function submit_post($task_id)
    {
        $user_data = require_student($this);
        if (!$user_data) return;

        // Check if multipart form data with files
        $content_type = $this->input->server('CONTENT_TYPE');
        $is_multipart = strpos($content_type, 'multipart/form-data') !== false;

        if ($is_multipart) {
            $data = new stdClass();
            $data->submission_content = $this->input->post('submission_content');
            $data->class_code = $this->input->post('class_code');
            
            // Handle file upload
            $attachment_url = null;
            $attachment_type = null;
            
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_config = [
                    'upload_path' => './uploads/submissions/',
                    'allowed_types' => 'gif|jpg|jpeg|png|webp|pdf|doc|docx|ppt|pptx|xls|xlsx|txt|zip|rar|mp4|mp3',
                    'max_size' => 10240, // 10MB
                    'encrypt_name' => true,
                    'overwrite' => false
                ];

                if (!is_dir($upload_config['upload_path'])) {
                    mkdir($upload_config['upload_path'], 0755, true);
                }

                $this->load->library('upload', $upload_config);

                if ($this->upload->do_upload('attachment')) {
                    $upload_data = $this->upload->data();
                    $attachment_url = 'uploads/submissions/' . $upload_data['file_name'];
                    $attachment_type = 'file';
                } else {
                    $this->send_error('File upload failed: ' . $this->upload->display_errors('', ''), 400);
                    return;
                }
            }
        } else {
            $data = $this->get_json_input();
            if (!$data) return;
            
            $attachment_url = $data->attachment_url ?? null;
            $attachment_type = $data->attachment_type ?? null;
        }

        if (empty($data->submission_content) && empty($attachment_url)) {
            $this->send_error('Submission content or attachment is required', 400);
            return;
        }

        if (empty($data->class_code)) {
            $this->send_error('Class code is required', 400);
            return;
        }

        try {
            // Check if task exists and student is enrolled
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task) {
                $this->send_error('Task not found', 404);
                return;
            }

            $class_codes = json_decode($task['class_codes'], true);
            if (!in_array($data->class_code, $class_codes)) {
                $this->send_error('You are not enrolled in this class', 403);
                return;
            }

            // Check if already submitted
            $existing_submission = $this->Task_model->get_student_submission($task_id, $user_data['user_id'], $data->class_code);
            if ($existing_submission) {
                $this->send_error('You have already submitted this task', 409);
                return;
            }

            $submission_data = [
                'task_id' => $task_id,
                'student_id' => $user_data['user_id'],
                'class_code' => $data->class_code,
                'submission_content' => $data->submission_content,
                'attachment_type' => $attachment_type,
                'attachment_url' => $attachment_url
            ];

            $submission_id = $this->Task_model->submit_task($submission_data);
            if ($submission_id) {
                $this->send_success(['submission_id' => $submission_id], 'Task submitted successfully', 201);
            } else {
                $this->send_error('Failed to submit task', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to submit task: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Grade submission (Teacher only)
     * POST /api/tasks/submissions/{submission_id}/grade
     */
    public function grade_submission_post($submission_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $data = $this->get_json_input();
        if (!$data || !isset($data->grade)) {
            $this->send_error('Grade is required', 400);
            return;
        }

        try {
            // Get submission and verify teacher access
            $submission = $this->db->select('task_submissions.*, class_tasks.teacher_id')
                ->from('task_submissions')
                ->join('class_tasks', 'task_submissions.task_id = class_tasks.task_id')
                ->where('task_submissions.submission_id', $submission_id)
                ->get()->row_array();

            if (!$submission || $submission['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Submission not found or access denied', 404);
                return;
            }

            $success = $this->Task_model->grade_submission($submission_id, $data->grade, $data->feedback ?? null);
            if ($success) {
                $this->send_success(null, 'Submission graded successfully');
            } else {
                $this->send_error('Failed to grade submission', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to grade submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add comment to task (Teacher/Student)
     * POST /api/tasks/{task_id}/comments
     */
    public function comment_post($task_id)
    {
        $user_data = require_auth($this);
        if (!$user_data) return;

        $data = $this->get_json_input();
        if (!$data || empty($data->comment)) {
            $this->send_error('Comment is required', 400);
            return;
        }

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task) {
                $this->send_error('Task not found', 404);
                return;
            }

            // Check if comments are allowed
            if (!$task['allow_comments']) {
                $this->send_error('Comments are not allowed for this task', 403);
                return;
            }

            $comment_id = $this->Task_model->add_comment($task_id, $user_data['user_id'], $data->comment);
            if ($comment_id) {
                $comments = $this->Task_model->get_comments($task_id);
                
                // Process comments to add user role and additional info
                foreach ($comments as &$comment) {
                    $comment['user_role'] = $comment['role'];
                    $comment['user_info'] = [
                        'full_name' => $comment['user_name'],
                        'email' => $comment['email'],
                        'profile_pic' => $comment['profile_pic'],
                        'role' => $comment['role']
                    ];
                    
                    // Add role-specific information
                    if ($comment['role'] === 'student') {
                        $comment['user_info']['student_num'] = $comment['student_num'];
                        $comment['user_info']['display_name'] = $comment['user_name'] . ' (Student)';
                    } elseif ($comment['role'] === 'teacher') {
                        $comment['user_info']['teacher_id'] = $comment['teacher_id'];
                        $comment['user_info']['display_name'] = $comment['user_name'] . ' (Teacher)';
                    }
                    
                    // Remove redundant fields
                    unset($comment['user_name'], $comment['email'], $comment['role'], $comment['student_num'], $comment['teacher_id']);
                }
                
                $this->send_success($comments, 'Comment added successfully');
            } else {
                $this->send_error('Failed to add comment', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to add comment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get task comments
     * GET /api/tasks/{task_id}/comments
     */
    public function comments_get($task_id)
    {
        $user_data = require_auth($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task) {
                $this->send_error('Task not found', 404);
                return;
            }

            $comments = $this->Task_model->get_comments($task_id);
            
            // Process comments to add user role and additional info
            foreach ($comments as &$comment) {
                $comment['user_role'] = $comment['role'];
                $comment['user_info'] = [
                    'full_name' => $comment['user_name'],
                    'email' => $comment['email'],
                    'profile_pic' => $comment['profile_pic'],
                    'role' => $comment['role']
                ];
                
                // Add role-specific information
                if ($comment['role'] === 'student') {
                    $comment['user_info']['student_num'] = $comment['student_num'];
                    $comment['user_info']['display_name'] = $comment['user_name'] . ' (Student)';
                } elseif ($comment['role'] === 'teacher') {
                    $comment['user_info']['teacher_id'] = $comment['teacher_id'];
                    $comment['user_info']['display_name'] = $comment['user_name'] . ' (Teacher)';
                }
                
                // Remove redundant fields
                unset($comment['user_name'], $comment['email'], $comment['role'], $comment['student_num'], $comment['teacher_id']);
            }
            
            $this->send_success($comments, 'Comments retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve comments: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Serve task attachment file
     * GET /api/tasks/files/{filename}
     */
    public function serve_file($filename)
    {
        $user_data = require_auth($this);
        if (!$user_data) return;

        try {
            // Check if this is a Google Drive link
            if (strpos($filename, 'drive.google.com') !== false) {
                $this->redirect_to_google_drive($filename);
                return;
            }

            // Verify the file exists and is a task attachment
            $file_path = './uploads/tasks/' . $filename;
            
            if (!file_exists($file_path)) {
                $this->send_error('File not found: ' . $filename, 404);
                return;
            }

            // Get file info
            $file_info = pathinfo($file_path);
            $extension = strtolower($file_info['extension']);

            // Set appropriate headers
            $this->output->set_content_type($this->get_mime_type($extension));
            $this->output->set_header('Content-Disposition: inline; filename="' . $filename . '"');
            $this->output->set_header('Cache-Control: public, max-age=3600');

            // Output file content
            readfile($file_path);
        } catch (Exception $e) {
            $this->send_error('Failed to serve file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Serve submission file
     * GET /api/tasks/submissions/files/{filename}
     */
    public function serve_submission_file($filename)
    {
        $user_data = require_auth($this);
        if (!$user_data) return;

        try {
            // Check if this is a Google Drive link
            if (strpos($filename, 'drive.google.com') !== false) {
                $this->redirect_to_google_drive($filename);
                return;
            }

            // Verify the file exists and is a submission file
            $file_path = './uploads/submissions/' . $filename;
            
            if (!file_exists($file_path)) {
                $this->send_error('File not found: ' . $filename, 404);
                return;
            }

            // Get file info
            $file_info = pathinfo($file_path);
            $extension = strtolower($file_info['extension']);

            // Set appropriate headers
            $this->output->set_content_type($this->get_mime_type($extension));
            $this->output->set_header('Content-Disposition: inline; filename="' . $filename . '"');
            $this->output->set_header('Cache-Control: public, max-age=3600');

            // Output file content
            readfile($file_path);
        } catch (Exception $e) {
            $this->send_error('Failed to serve file: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Redirect to Google Drive file
     */
    private function redirect_to_google_drive($url)
    {
        // Convert folder link to file link if needed
        $file_url = $this->convert_google_drive_url($url);
        
        // Redirect to Google Drive
        redirect($file_url);
    }

    /**
     * Convert Google Drive folder link to file link
     */
    private function convert_google_drive_url($url)
    {
        // If it's already a file link, return as is
        if (strpos($url, '/file/d/') !== false) {
            return $url;
        }
        
        // If it's a folder link, we need to handle it differently
        // For now, just return the original URL
        // In a real implementation, you'd need Google Drive API to list folder contents
        return $url;
    }

    /**
     * Get file preview URL
     * GET /api/tasks/files/{task_id}/preview
     */
    public function preview_file($task_id)
    {
        $user_data = require_auth($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task) {
                $this->send_error('Task not found', 404);
                return;
            }

            if (!$task['attachment_url']) {
                $this->send_error('No attachment found for this task', 404);
                return;
            }

            $attachment_url = $task['attachment_url'];
            $attachment_type = $task['attachment_type'];

            if ($attachment_type === 'link') {
                // For external links, return the URL directly
                $this->send_success([
                    'url' => $attachment_url,
                    'type' => 'external',
                    'preview_url' => $attachment_url
                ], 'External file URL retrieved');
            } else {
                // For local files, return the API endpoint URL
                $preview_url = base_url("api/tasks/files/" . urlencode($attachment_url));
                $this->send_success([
                    'url' => $attachment_url,
                    'type' => 'local',
                    'preview_url' => $preview_url
                ], 'Local file URL retrieved');
            }
        } catch (Exception $e) {
            $this->send_error('Failed to get file preview: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get task statistics (Teacher only)
     * GET /api/tasks/{task_id}/stats
     */
    public function stats_get($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            // Get submission statistics
            $stats = $this->Task_model->get_task_statistics($task_id);
            $this->send_success($stats, 'Task statistics retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve task statistics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Bulk grade submissions (Teacher only)
     * POST /api/tasks/{task_id}/bulk-grade
     */
    public function bulk_grade_post($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $data = $this->get_json_input();
        if (!$data || !isset($data->grades) || !is_array($data->grades)) {
            $this->send_error('Grades array is required', 400);
            return;
        }

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            $success_count = 0;
            $errors = [];

            foreach ($data->grades as $grade_data) {
                if (!isset($grade_data->submission_id) || !isset($grade_data->grade)) {
                    $errors[] = 'Missing submission_id or grade for one or more submissions';
                    continue;
                }

                $success = $this->Task_model->grade_submission(
                    $grade_data->submission_id, 
                    $grade_data->grade, 
                    $grade_data->feedback ?? null
                );

                if ($success) {
                    $success_count++;
                } else {
                    $errors[] = "Failed to grade submission {$grade_data->submission_id}";
                }
            }

            if ($success_count > 0) {
                $this->send_success([
                    'graded_count' => $success_count,
                    'errors' => $errors
                ], "Successfully graded {$success_count} submissions");
            } else {
                $this->send_error('Failed to grade any submissions: ' . implode(', ', $errors), 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to bulk grade submissions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Test upload directory status (for debugging)
     * GET /api/tasks/test-upload
     */
    public function test_upload_get()
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $upload_path = './uploads/tasks/';
        $submissions_path = './uploads/submissions/';
        
        $status = [
            'tasks_directory' => [
                'path' => $upload_path,
                'exists' => is_dir($upload_path),
                'writable' => is_writable($upload_path),
                'permissions' => substr(sprintf('%o', fileperms($upload_path)), -4),
                'files' => is_dir($upload_path) ? count(scandir($upload_path)) - 2 : 0
            ],
            'submissions_directory' => [
                'path' => $submissions_path,
                'exists' => is_dir($submissions_path),
                'writable' => is_writable($submissions_path),
                'permissions' => substr(sprintf('%o', fileperms($submissions_path)), -4),
                'files' => is_dir($submissions_path) ? count(scandir($submissions_path)) - 2 : 0
            ],
            'php_upload_max_filesize' => ini_get('upload_max_filesize'),
            'php_post_max_size' => ini_get('post_max_size'),
            'php_max_file_uploads' => ini_get('max_file_uploads')
        ];
        
        $this->send_success($status, 'Upload directory status retrieved');
    }

    /**
     * Helper method to get MIME type for file extension
     */
    private function get_mime_type($extension)
    {
        $mime_types = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];

        return $mime_types[$extension] ?? 'application/octet-stream';
    }

    /**
     * Get available students for assignment (Teacher only)
     * GET /api/tasks/available-students
     */
    public function available_students_get()
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $class_codes = $this->input->get('class_codes');
            if (!$class_codes) {
                $this->send_error('Class codes are required', 400);
                return;
            }

            $class_codes_array = explode(',', $class_codes);
            $students = $this->Task_model->get_available_students($class_codes_array);
            
            $this->send_success($students, 'Available students retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve available students: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get students assigned to a task (Teacher only)
     * GET /api/tasks/{task_id}/assigned-students
     */
    public function assigned_students_get($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            $students = $this->Task_model->get_assigned_students($task_id);
            $this->send_success($students, 'Assigned students retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve assigned students: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Assign students to a task (Teacher only)
     * POST /api/tasks/{task_id}/assign-students
     */
    public function assign_students_post($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $data = $this->get_json_input();
        if (!$data || !isset($data->students) || !is_array($data->students)) {
            $this->send_error('Students array is required', 400);
            return;
        }

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            $success = $this->Task_model->assign_students_to_task($task_id, $data->students);
            if ($success) {
                // Update task assignment type
                $this->Task_model->update($task_id, [
                    'assignment_type' => 'individual',
                    'assigned_students' => json_encode($data->students)
                ]);
                
                $this->send_success(null, 'Students assigned successfully');
            } else {
                $this->send_error('Failed to assign students', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to assign students: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get task assignment statistics (Teacher only)
     * GET /api/tasks/{task_id}/assignment-stats
     */
    public function assignment_stats_get($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            $stats = $this->Task_model->get_task_assignment_stats($task_id);
            $this->send_success($stats, 'Assignment statistics retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve assignment statistics: ' . $e->getMessage(), 500);
        }
    }
} 