<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

class TaskController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Task_model');
        $this->load->helper(['auth', 'audit', 'notification']);
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
            $data->assignment_type = $this->input->post('assignment_type') ?? 'classroom';
            $data->assigned_students = json_decode($this->input->post('assigned_students'), true);
            $data->allow_comments = $this->input->post('allow_comments') ? 1 : 0;
            $data->is_draft = $this->input->post('is_draft') ? 1 : 0;
            $data->is_scheduled = $this->input->post('is_scheduled') ? 1 : 0;
            $data->scheduled_at = $this->input->post('scheduled_at');
            $data->due_date = $this->input->post('due_date');
            
            // Handle multiple file uploads
            $attachments = [];
            
            // Method 1: Multiple files with same field name (attachment[])
            if (isset($_FILES['attachment']) && is_array($_FILES['attachment']['name'])) {
                $file_count = count($_FILES['attachment']['name']);
                
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['attachment']['error'][$i] === UPLOAD_ERR_OK) {
                        $uploaded_file = $this->upload_task_file($_FILES['attachment']['tmp_name'][$i], $_FILES['attachment']['name'][$i]);
                        if ($uploaded_file) {
                            $attachments[] = $uploaded_file;
                        }
                    }
                }
            }
            // Method 2: Multiple files with different field names (attachment1, attachment2, etc.)
            else {
                foreach ($_FILES as $field_name => $file_data) {
                    if (strpos($field_name, 'attachment') === 0 && $file_data['error'] === UPLOAD_ERR_OK) {
                        $uploaded_file = $this->upload_task_file($file_data['tmp_name'], $file_data['name']);
                        if ($uploaded_file) {
                            $attachments[] = $uploaded_file;
                        }
                    }
                }
            }
            
            // For backward compatibility, set attachment_url and attachment_type if only one file
            if (count($attachments) === 1) {
                $attachment_url = $attachments[0]['file_name'];
                $attachment_type = 'file';
                $original_filename = $attachments[0]['original_name'];
            } else {
                $attachment_url = null;
                $attachment_type = null;
                $original_filename = null;
            }
        } else {
            // Handle JSON request
            $data = $this->get_json_input();
            if (!$data) return;
            
            $attachment_url = $data->attachment_url ?? null;
            $attachment_type = $data->attachment_type ?? null;
            $original_filename = $data->original_filename ?? null;
            $attachments = [];
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
                'original_filename' => $original_filename,
                'allow_comments' => $data->allow_comments,
                'is_draft' => $data->is_draft,
                'is_scheduled' => $data->is_scheduled,
                'scheduled_at' => $data->scheduled_at,
                'due_date' => $data->due_date,
                'teacher_id' => $user_data['user_id']
            ];

            // Use the new method for multiple attachments
            if (!empty($attachments)) {
                $task_id = $this->Task_model->insert_with_attachments($task_data, $attachments);
            } else {
                $task_id = $this->Task_model->insert($task_data);
            }
            
            if ($task_id) {
                // If individual assignment, assign specific students
                if ($task_data['assignment_type'] === 'individual' && !empty($data->assigned_students)) {
                    $this->Task_model->safe_assign_students_to_task($task_id, $data->assigned_students);
                }
                
                $task = $this->Task_model->get_task_with_attachments($task_id);
                
                // Send notifications to students in the affected classes
                $this->send_task_notifications($task_id, $task, $data->class_codes, $user_data);
                
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

            $tasks = $this->Task_model->get_all_with_attachments($user_data['user_id'], $filters);
            
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
            
            // Add submission status and attachments for each task
            foreach ($tasks as &$task) {
                $submission = $this->Task_model->get_student_submission($task['task_id'], $user_data['user_id'], $class_code);
                $task['submission_status'] = $submission ? $submission['status'] : 'not_submitted';
                $task['submission_id'] = $submission ? $submission['submission_id'] : null;
                $task['class_codes'] = json_decode($task['class_codes'], true);
                
                // Add due date status
                $task['is_past_due'] = $this->is_task_past_due($task['due_date']);
                
                // Get task attachments
                $task['attachments'] = $this->Task_model->get_task_attachments($task['task_id']);
                $task['attachment_count'] = count($task['attachments']);
            }

            $this->send_success($tasks, 'Tasks retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get student's individually assigned tasks only (Student only)
     * GET /api/tasks/student/assigned
     */
    public function student_assigned_get()
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
            
            // Add submission status and attachments for each task
            foreach ($tasks as &$task) {
                $submission = $this->Task_model->get_student_submission($task['task_id'], $user_data['user_id'], $class_code);
                $task['submission_status'] = $submission ? $submission['status'] : 'not_submitted';
                $task['submission_id'] = $submission ? $submission['submission_id'] : null;
                $task['class_codes'] = json_decode($task['class_codes'], true);
                
                // Add due date status
                $task['is_past_due'] = $this->is_task_past_due($task['due_date']);
                
                // Get task attachments
                $task['attachments'] = $this->Task_model->get_task_attachments($task['task_id']);
                $task['attachment_count'] = count($task['attachments']);
            }

            $this->send_success($tasks, 'Individually assigned tasks retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve assigned tasks: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get task details (Teacher only)
     * GET /api/tasks/{task_id}
     */
    public function task_get($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_task_with_attachments($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
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
     * Submit task (Student only) - Enhanced with multiple file support
     * POST /api/tasks/{task_id}/submit
     * 
     * Supports three methods for multiple files:
     * 1. Multiple files with same field name (attachment[])
     * 2. Multiple files with different field names (attachment1, attachment2, etc.)
     * 3. JSON array of attachment URLs
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
            
            // Handle multiple file uploads
            $attachments = [];
            
            // Method 1: Multiple files with same field name (attachment[])
            if (isset($_FILES['attachment']) && is_array($_FILES['attachment']['name'])) {
                $file_count = count($_FILES['attachment']['name']);
                
                for ($i = 0; $i < $file_count; $i++) {
                    if ($_FILES['attachment']['error'][$i] === UPLOAD_ERR_OK) {
                        $uploaded_file = $this->upload_single_file($_FILES['attachment']['tmp_name'][$i], $_FILES['attachment']['name'][$i]);
                        if ($uploaded_file) {
                            $attachments[] = $uploaded_file;
                        }
                    }
                }
            }
            // Method 2: Multiple files with different field names (attachment1, attachment2, etc.)
            else {
                $attachment_fields = [];
                foreach ($_FILES as $key => $file) {
                    if (strpos($key, 'attachment') === 0 && $file['error'] === UPLOAD_ERR_OK) {
                        $attachment_fields[] = $key;
                    }
                }
                
                foreach ($attachment_fields as $field_name) {
                    $uploaded_file = $this->upload_single_file($_FILES[$field_name]['tmp_name'], $_FILES[$field_name]['name']);
                    if ($uploaded_file) {
                        $attachments[] = $uploaded_file;
                    }
                }
            }
            
            // Legacy support: Single file upload
            if (empty($attachments) && isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $uploaded_file = $this->upload_single_file($_FILES['attachment']['tmp_name'], $_FILES['attachment']['name']);
                if ($uploaded_file) {
                    $attachments[] = $uploaded_file;
                }
            }
            
        } else {
            $data = $this->get_json_input();
            if (!$data) return;
            
            // Handle JSON attachments
            $attachments = [];
            if (isset($data->attachments) && is_array($data->attachments)) {
                foreach ($data->attachments as $attachment) {
                    $attachments[] = [
                        'file_name' => $attachment->file_name ?? 'external_file',
                        'original_name' => $attachment->original_name ?? $attachment->file_name ?? 'external_file',
                        'file_path' => $attachment->file_path ?? '',
                        'file_size' => $attachment->file_size ?? 0,
                        'mime_type' => $attachment->mime_type ?? 'application/octet-stream',
                        'attachment_type' => $attachment->attachment_type ?? 'link',
                        'attachment_url' => $attachment->attachment_url ?? ''
                    ];
                }
            } else {
                // Legacy support: Single attachment
                if (isset($data->attachment_url) && isset($data->attachment_type)) {
                    $attachments[] = [
                        'file_name' => 'external_file',
                        'original_name' => 'external_file',
                        'file_path' => '',
                        'file_size' => 0,
                        'mime_type' => 'application/octet-stream',
                        'attachment_type' => $data->attachment_type,
                        'attachment_url' => $data->attachment_url
                    ];
                }
            }
        }

        // Debug: Log what we received
        error_log("Task submission debug - Content-Type: " . $this->input->server('CONTENT_TYPE'));
        error_log("Task submission debug - POST data: " . print_r($this->input->post(), true));
        error_log("Task submission debug - FILES data: " . print_r($_FILES, true));
        error_log("Task submission debug - submission_content: " . ($data->submission_content ?? 'NULL'));
        error_log("Task submission debug - attachments count: " . count($attachments));

        if (empty($data->submission_content) && empty($attachments)) {
            $this->send_error('At least one attachment is required', 400);
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

            // Check if task is past due date
            if ($this->is_task_past_due($task['due_date'])) {
                $this->send_error('Cannot submit task after the due date', 400);
                return;
            }

            $submission_data = [
                'task_id' => $task_id,
                'student_id' => $user_data['user_id'],
                'class_code' => $data->class_code,
                'submission_content' => $data->submission_content ?? null,
                'attachment_type' => null, // Will be handled by attachments table
                'attachment_url' => null   // Will be handled by attachments table
            ];

            // Use new method for multiple attachments
            $submission_id = $this->Task_model->submit_task_with_attachments($submission_data, $attachments);
            if ($submission_id) {
                // Send notification to teacher about the submission
                $this->send_submission_notification($task, $user_data, $submission_id, $data->class_code);
                
                $this->send_success([
                    'submission_id' => $submission_id,
                    'attachments_count' => count($attachments)
                ], 'Task submitted successfully', 201);
            } else {
                $this->send_error('Failed to submit task', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to submit task: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Check if a task is past its due date
     */
    private function is_task_past_due($due_date) {
        if (empty($due_date)) {
            return false; // No due date means not past due
        }
        
        $due_datetime = new DateTime($due_date);
        $current_datetime = new DateTime();
        
        return $current_datetime > $due_datetime;
    }

    /**
     * Upload a single file and return attachment data
     */
    private function upload_single_file($tmp_name, $original_name) {
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
        
        // Set the file data for upload
        $_FILES['temp_file'] = [
            'name' => $original_name,
            'type' => $_FILES['attachment']['type'] ?? 'application/octet-stream',
            'tmp_name' => $tmp_name,
            'error' => UPLOAD_ERR_OK,
            'size' => $_FILES['attachment']['size'] ?? 0
        ];

        if ($this->upload->do_upload('temp_file')) {
            $upload_data = $this->upload->data();
            return [
                'file_name' => $upload_data['file_name'],
                'original_name' => $original_name,
                'file_path' => 'uploads/submissions/' . $upload_data['file_name'],
                'file_size' => $upload_data['file_size'],
                'mime_type' => $upload_data['file_type'],
                'attachment_type' => 'file',
                'attachment_url' => 'uploads/submissions/' . $upload_data['file_name']
            ];
        } else {
            $this->send_error('File upload failed: ' . $this->upload->display_errors('', ''), 400);
            return false;
        }
    }

    /**
     * Upload a single file for task attachments
     */
    private function upload_task_file($tmp_name, $original_name) {
        $upload_config = [
            'upload_path' => './uploads/tasks/',
            'allowed_types' => 'gif|jpg|jpeg|png|webp|pdf|doc|docx|ppt|pptx|xls|xlsx|txt|zip|rar|mp4|mp3',
            'max_size' => 10240, // 10MB
            'encrypt_name' => true,
            'overwrite' => false
        ];

        if (!is_dir($upload_config['upload_path'])) {
            mkdir($upload_config['upload_path'], 0755, true);
        }

        $this->load->library('upload', $upload_config);
        
        // Set the file data for upload
        $_FILES['temp_file'] = [
            'name' => $original_name,
            'type' => $_FILES['attachment']['type'] ?? 'application/octet-stream',
            'tmp_name' => $tmp_name,
            'error' => UPLOAD_ERR_OK,
            'size' => $_FILES['attachment']['size'] ?? 0
        ];

        if ($this->upload->do_upload('temp_file')) {
            $upload_data = $this->upload->data();
            return [
                'file_name' => $upload_data['file_name'],
                'original_name' => $original_name,
                'file_path' => 'uploads/tasks/' . $upload_data['file_name'],
                'file_size' => $upload_data['file_size'],
                'mime_type' => $upload_data['file_type'],
                'attachment_type' => 'file',
                'attachment_url' => 'uploads/tasks/' . $upload_data['file_name']
            ];
        } else {
            $this->send_error('File upload failed: ' . $this->upload->display_errors('', ''), 400);
            return false;
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
                // Send notification to student about the grade
                $this->send_grade_notification($submission, $data->grade, $data->feedback ?? null);
                
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
     * GET /api/tasks/attachment/{filename}
     */
    public function serve_task_attachment($filename)
    {
        try {
            // Get task attachment info
            $attachment = $this->Task_model->get_task_attachment_by_filename($filename);
            if (!$attachment) {
                show_404();
                return;
            }

            $file_path = FCPATH . $attachment['file_path'];
            if (!file_exists($file_path)) {
                show_404();
                return;
            }

            // Get file info
            $file_size = filesize($file_path);
            $mime_type = $attachment['mime_type'] ?: $this->get_mime_type(pathinfo($filename, PATHINFO_EXTENSION));

            // Set headers
            header('Content-Type: ' . $mime_type);
            header('Content-Length: ' . $file_size);
            header('Content-Disposition: inline; filename="' . $attachment['original_name'] . '"');
            header('Cache-Control: public, max-age=3600');

            // Output file
            readfile($file_path);
        } catch (Exception $e) {
            log_message('error', 'Error serving task attachment: ' . $e->getMessage());
            show_404();
        }
    }

    /**
     * Serve file (for backward compatibility)
     * GET /api/tasks/file/{filename}
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

            $success = $this->Task_model->safe_assign_students_to_task($task_id, $data->students);
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

    /**
     * Debug endpoint to check table existence and test query
     * GET /api/tasks/debug-assignments
     */
    public function debug_assignments_get()
    {
        try {
            // Check if table exists
            $table_exists = $this->db->table_exists('task_student_assignments');
            
            // Get sample data from the table
            $assignments = [];
            if ($table_exists) {
                $assignments = $this->db->get('task_student_assignments')->result_array();
            }
            
            // Test the query with a sample student and class
            $test_student_id = 'STU685651BF9DDCF988';
            $test_class_code = 'J56NHD';
            
            $sql = "SELECT class_tasks.*, users.full_name as teacher_name
                    FROM class_tasks
                    LEFT JOIN users ON class_tasks.teacher_id = users.user_id COLLATE utf8mb4_general_ci
                    WHERE class_tasks.status = 'active'
                    AND class_tasks.is_draft = 0
                    AND JSON_CONTAINS(class_tasks.class_codes, ?)
                    AND (
                        class_tasks.assignment_type = 'classroom'
                        OR (
                            class_tasks.assignment_type = 'individual'
                            AND EXISTS (
                                SELECT 1 FROM task_student_assignments tsa
                                WHERE tsa.task_id = class_tasks.task_id
                                AND tsa.student_id = ?
                                AND tsa.class_code = ?
                            )
                        )
                    )
                    ORDER BY class_tasks.created_at DESC";
            
            $test_results = $this->db->query($sql, [json_encode($test_class_code), $test_student_id, $test_class_code])->result_array();
            
            $debug_data = [
                'table_exists' => $table_exists,
                'assignments_count' => count($assignments),
                'sample_assignments' => array_slice($assignments, 0, 5),
                'test_query_results_count' => count($test_results),
                'test_student_id' => $test_student_id,
                'test_class_code' => $test_class_code
            ];
            
            $this->send_success($debug_data, 'Debug information retrieved');
        } catch (Exception $e) {
            $this->send_error('Debug failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Debug endpoint to check what data is being received
     * POST /api/tasks/debug-submit
     */
    public function debug_submit_post()
    {
        $user_data = require_student($this);
        if (!$user_data) return;

        $debug_data = [
            'content_type' => $this->input->server('CONTENT_TYPE'),
            'is_multipart' => strpos($this->input->server('CONTENT_TYPE'), 'multipart/form-data') !== false,
            'post_data' => $this->input->post(),
            'files_data' => $_FILES,
            'raw_input' => file_get_contents('php://input'),
            'headers' => getallheaders()
        ];

        // Check if files were uploaded
        if (isset($_FILES['attachment'])) {
            $debug_data['file_info'] = [
                'name' => $_FILES['attachment']['name'],
                'type' => $_FILES['attachment']['type'],
                'size' => $_FILES['attachment']['size'],
                'error' => $_FILES['attachment']['error'],
                'tmp_name' => $_FILES['attachment']['tmp_name']
            ];
        }

        $this->send_success($debug_data, 'Debug data received');
    }

    /**
     * Get task details for student (Student only)
     * GET /api/tasks/student/{task_id}
     */
    public function student_task_get($task_id)
    {
        $user_data = require_student($this);
        if (!$user_data) return;

        try {
            // Get the task details
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task) {
                $this->send_error('Task not found', 404);
                return;
            }

            // Check if student has access to this task
            $class_codes = json_decode($task['class_codes'], true);
            $has_access = false;
            
            // Check if student is enrolled in any of the task's classes
            foreach ($class_codes as $class_code) {
                $enrollment = $this->db->get_where('classroom_enrollments', [
                    'classroom_id' => $class_code,
                    'student_id' => $user_data['user_id'],
                    'status' => 'active'
                ])->row_array();
                
                if ($enrollment) {
                    $has_access = true;
                    break;
                }
            }

            // For individual assignments, check if student is specifically assigned
            if ($task['assignment_type'] === 'individual') {
                $assigned_students = json_decode($task['assigned_students'], true);
                foreach ($assigned_students as $assigned_student) {
                    if ($assigned_student['student_id'] === $user_data['user_id']) {
                        $has_access = true;
                        break;
                    }
                }
            }

            if (!$has_access) {
                $this->send_error('Access denied. You do not have permission to view this task.', 403);
                return;
            }

            // Get teacher name
            $teacher = $this->db->get_where('users', ['user_id' => $task['teacher_id']])->row_array();
            $task['teacher_name'] = $teacher ? $teacher['full_name'] : 'Unknown Teacher';

            // Get student's submission if exists
            $submission = $this->Task_model->get_student_submission($task_id, $user_data['user_id'], $class_codes[0]);
            $task['submission'] = $submission;
            $task['submission_status'] = $submission ? $submission['status'] : 'not_submitted';

            // Get comments if allowed
            if ($task['allow_comments']) {
                $comments = $this->Task_model->get_comments($task_id);
                $task['comments'] = $comments;
            }

            // Get task attachments
            $task['attachments'] = $this->Task_model->get_task_attachments($task_id);
            $task['attachment_count'] = count($task['attachments']);

            // Add due date status
            $task['is_past_due'] = $this->is_task_past_due($task['due_date']);

            $task['class_codes'] = $class_codes;
            $task['assigned_students'] = json_decode($task['assigned_students'], true);

            $this->send_success($task, 'Task details retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve task details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all student submissions with attachments for a specific task (Teacher only)
     * GET /api/tasks/{task_id}/submissions
     */
    public function task_submissions_get($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $result = $this->Task_model->get_task_submissions_with_attachments($task_id, $user_data['user_id']);
            
            if (!$result) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            $this->send_success($result, 'Task submissions retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve task submissions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send notifications to students when a task is created
     */
    private function send_task_notifications($task_id, $task, $class_codes, $teacher_data)
    {
        try {
            $task_title = $task['title'];
            $task_type = ucfirst($task['type']);
            $teacher_name = $teacher_data['full_name'];
            
            // Determine notification message based on assignment type
            if ($task['assignment_type'] === 'individual') {
                $title = "New Individual Task: {$task_title}";
                $message = "{$teacher_name} has assigned you a new {$task_type} task: {$task_title}";
            } else {
                $title = "New Class Task: {$task_title}";
                $message = "{$teacher_name} has created a new {$task_type} task for your class: {$task_title}";
            }
            
            // Send notifications to students in each class
            foreach ($class_codes as $class_code) {
                $students = get_class_students($class_code);
                
                if (!empty($students)) {
                    $student_ids = array_column($students, 'user_id');
                    
                    // Create notifications for all students in the class
                    create_notifications_for_users(
                        $student_ids,
                        'task',
                        $title,
                        $message,
                        $task_id,
                        'task',
                        $class_code,
                        false // Not urgent
                    );
                    
                    // Log notification sending
                    log_message('info', "Task notifications sent to " . count($student_ids) . " students in class {$class_code} for task {$task_id}");
                }
            }
        } catch (Exception $e) {
            // Log error but don't fail the task creation
            log_message('error', "Failed to send task notifications: " . $e->getMessage());
        }
    }

    /**
     * Send notification to teacher when student submits a task
     */
    private function send_submission_notification($task, $student_data, $submission_id, $class_code)
    {
        try {
            $task_title = $task['title'];
            $student_name = $student_data['full_name'];
            $teacher_id = $task['teacher_id'];
            
            // Get class name for better notification
            $class_name = get_class_name($class_code);
            
            $title = "New Task Submission: {$task_title}";
            $message = "{$student_name} has submitted the task '{$task_title}' for class {$class_name}";
            
            // Create notification for the teacher
            create_submission_notification(
                $teacher_id,
                $submission_id,
                $title,
                $message,
                $class_code
            );
            
            // Log notification sending
            log_message('info', "Submission notification sent to teacher {$teacher_id} for submission {$submission_id}");
            
        } catch (Exception $e) {
            // Log error but don't fail the submission
            log_message('error', "Failed to send submission notification: " . $e->getMessage());
        }
    }

    /**
     * Send notification to student when teacher grades their submission
     */
    private function send_grade_notification($submission, $grade, $feedback = null)
    {
        try {
            $student_id = $submission['student_id'];
            $task_id = $submission['task_id'];
            $class_code = $submission['class_code'];
            
            // Get task information
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task) {
                log_message('error', "Task not found for grade notification: {$task_id}");
                return;
            }
            
            $task_title = $task['title'];
            $teacher_id = $task['teacher_id'];
            
            // Get teacher information
            $teacher = $this->db->select('full_name')
                ->from('users')
                ->where('user_id', $teacher_id)
                ->get()->row_array();
            
            $teacher_name = $teacher ? $teacher['full_name'] : 'Teacher';
            
            // Get class name for better notification
            $class_name = get_class_name($class_code);
            
            // Create grade-specific title and message
            $title = "Task Graded: {$task_title}";
            $message = "Your submission for '{$task_title}' has been graded by {$teacher_name}.";
            $message .= " Grade: {$grade}";
            
            if ($class_name) {
                $message .= " (Class: {$class_name})";
            }
            
            if ($feedback) {
                $message .= "\n\nFeedback: {$feedback}";
            }
            
            // Create notification for the student
            create_grade_notification(
                $student_id,
                $submission['submission_id'],
                $title,
                $message,
                $class_code
            );
            
            // Log notification sending
            log_message('info', "Grade notification sent to student {$student_id} for submission {$submission['submission_id']} - Grade: {$grade}");
            
        } catch (Exception $e) {
            // Log error but don't fail the grading
            log_message('error', "Failed to send grade notification: " . $e->getMessage());
        }
    }

    /**
     * Get submission with attachments
     * GET /api/tasks/submissions/{submission_id}
     */
    public function submission_get($submission_id)
    {
        $user_data = require_auth($this);
        if (!$user_data) return;

        try {
            $submission = $this->Task_model->get_submission_with_attachments($submission_id);
            if (!$submission) {
                $this->send_error('Submission not found', 404);
                return;
            }

            // Check access permissions
            if ($user_data['role'] === 'student') {
                if ($submission['student_id'] !== $user_data['user_id']) {
                    $this->send_error('Access denied', 403);
                    return;
                }
            } elseif ($user_data['role'] === 'teacher') {
                $task = $this->Task_model->get_by_id($submission['task_id']);
                if (!$task || $task['teacher_id'] !== $user_data['user_id']) {
                    $this->send_error('Access denied', 403);
                    return;
                }
            }

            $this->send_success($submission, 'Submission retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete attachment
     * DELETE /api/tasks/submissions/{submission_id}/attachments/{attachment_id}
     */
    public function delete_attachment_delete($submission_id, $attachment_id)
    {
        $user_data = require_student($this);
        if (!$user_data) return;

        try {
            // Check if submission belongs to student
            $submission = $this->Task_model->get_student_submission($submission_id, $user_data['user_id']);
            if (!$submission) {
                $this->send_error('Submission not found or access denied', 404);
                return;
            }

            $success = $this->Task_model->delete_attachment($attachment_id, $submission_id);
            if ($success) {
                $this->send_success(null, 'Attachment deleted successfully');
            } else {
                $this->send_error('Failed to delete attachment', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to delete attachment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get student submission with attachments
     * GET /api/tasks/{task_id}/submission
     */
    public function student_submission_get($task_id)
    {
        $user_data = require_student($this);
        if (!$user_data) return;

        $class_code = $this->input->get('class_code');
        if (empty($class_code)) {
            $this->send_error('Class code is required', 400);
            return;
        }

        try {
            $submission = $this->Task_model->get_student_submission_with_attachments($task_id, $user_data['user_id'], $class_code);
            if ($submission) {
                $this->send_success($submission, 'Submission retrieved successfully');
            } else {
                $this->send_success(null, 'No submission found');
            }
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve submission: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get task file information including original filename
     * GET /api/tasks/files/info/{filename}
     */
    public function get_task_file_info($filename)
    {
        $user_data = require_auth($this);
        if (!$user_data) return;

        try {
            // Verify the file exists
            $file_path = './uploads/tasks/' . $filename;
            
            if (!file_exists($file_path)) {
                $this->send_error('File not found: ' . $filename, 404);
                return;
            }

            // Get file info
            $file_info = pathinfo($file_path);
            $extension = strtolower($file_info['extension']);
            $file_size = filesize($file_path);
            $mime_type = $this->get_mime_type($extension);

            // Try to find the task that uses this file
            $task = $this->Task_model->get_task_by_attachment($filename);
            
            $response_data = [
                'filename' => $filename,
                'original_name' => $task ? ($task['original_filename'] ?: $task['title'] . '.' . $extension) : null,
                'file_size' => $file_size,
                'file_size_formatted' => $this->format_file_size($file_size),
                'mime_type' => $mime_type,
                'extension' => $extension,
                'file_path' => 'uploads/tasks/' . $filename,
                'download_url' => base_url("api/tasks/files/" . urlencode($filename)),
                'task_info' => $task ? [
                    'task_id' => $task['task_id'],
                    'title' => $task['title'],
                    'type' => $task['type']
                ] : null
            ];

            $this->send_success($response_data, 'File information retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to get file information: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all task files with information
     * GET /api/tasks/files/list
     */
    public function list_task_files()
    {
        $user_data = require_auth($this);
        if (!$user_data) return;

        try {
            $upload_path = './uploads/tasks/';
            
            if (!is_dir($upload_path)) {
                $this->send_success([], 'No task files found');
                return;
            }

            $files = [];
            $task_files = glob($upload_path . '*');
            
            foreach ($task_files as $file_path) {
                if (is_file($file_path)) {
                    $filename = basename($file_path);
                    $file_info = pathinfo($file_path);
                    $extension = strtolower($file_info['extension']);
                    $file_size = filesize($file_path);
                    
                    // Try to find the task that uses this file
                    $task = $this->Task_model->get_task_by_attachment($filename);
                    
                    $files[] = [
                        'filename' => $filename,
                        'original_name' => $task ? ($task['original_filename'] ?: $task['title'] . '.' . $extension) : null,
                        'file_size' => $file_size,
                        'file_size_formatted' => $this->format_file_size($file_size),
                        'mime_type' => $this->get_mime_type($extension),
                        'extension' => $extension,
                        'file_path' => 'uploads/tasks/' . $filename,
                        'download_url' => base_url("api/tasks/files/" . urlencode($filename)),
                        'task_info' => $task ? [
                            'task_id' => $task['task_id'],
                            'title' => $task['title'],
                            'type' => $task['type']
                        ] : null
                    ];
                }
            }

            $this->send_success($files, 'Task files list retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to list task files: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Format file size in human readable format
     */
    private function format_file_size($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Get task attachments (Teacher only)
     * GET /api/tasks/{task_id}/attachments
     */
    public function task_attachments_get($task_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            $attachments = $this->Task_model->get_task_attachments($task_id);
            $this->send_success($attachments, 'Task attachments retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve task attachments: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get task attachments for student (Student only)
     * GET /api/tasks/student/{task_id}/attachments
     */
    public function student_task_attachments_get($task_id)
    {
        $user_data = require_student($this);
        if (!$user_data) return;

        try {
            // Get the task details
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task) {
                $this->send_error('Task not found', 404);
                return;
            }

            // Check if student has access to this task
            $class_codes = json_decode($task['class_codes'], true);
            $has_access = false;
            
            // Check if student is enrolled in any of the task's classes
            foreach ($class_codes as $class_code) {
                $enrollment = $this->db->get_where('classroom_enrollments', [
                    'classroom_id' => $class_code,
                    'student_id' => $user_data['user_id'],
                    'status' => 'active'
                ])->row_array();
                
                if ($enrollment) {
                    $has_access = true;
                    break;
                }
            }

            // For individual assignments, check if student is specifically assigned
            if ($task['assignment_type'] === 'individual') {
                $assigned_students = json_decode($task['assigned_students'], true);
                foreach ($assigned_students as $assigned_student) {
                    if ($assigned_student['student_id'] === $user_data['user_id']) {
                        $has_access = true;
                        break;
                    }
                }
            }

            if (!$has_access) {
                $this->send_error('Access denied. You do not have permission to view this task.', 403);
                return;
            }

            $attachments = $this->Task_model->get_task_attachments($task_id);
            $this->send_success($attachments, 'Task attachments retrieved successfully');
        } catch (Exception $e) {
            $this->send_error('Failed to retrieve task attachments: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete task attachment (Teacher only)
     * DELETE /api/tasks/{task_id}/attachments/{attachment_id}
     */
    public function delete_task_attachment_delete($task_id, $attachment_id)
    {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $task = $this->Task_model->get_by_id($task_id);
            if (!$task || $task['teacher_id'] != $user_data['user_id']) {
                $this->send_error('Task not found or access denied', 404);
                return;
            }

            $success = $this->Task_model->delete_task_attachment($attachment_id, $task_id);
            if ($success) {
                $this->send_success(null, 'Task attachment deleted successfully');
            } else {
                $this->send_error('Failed to delete task attachment', 500);
            }
        } catch (Exception $e) {
            $this->send_error('Failed to delete task attachment: ' . $e->getMessage(), 500);
        }
    }
} 