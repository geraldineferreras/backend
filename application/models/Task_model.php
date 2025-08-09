<?php
class Task_model extends CI_Model {
    
    public function get_all($teacher_id = null, $filters = []) {
        // Build WHERE conditions
        $where_conditions = ["class_tasks.status != 'deleted'"];
        $params = [];
        
        if ($teacher_id) {
            $where_conditions[] = "class_tasks.teacher_id = ?";
            $params[] = $teacher_id;
        }
        
        // Apply filters
        if (isset($filters['type'])) {
            $where_conditions[] = "class_tasks.type = ?";
            $params[] = $filters['type'];
        }
        if (isset($filters['is_draft'])) {
            $where_conditions[] = "class_tasks.is_draft = ?";
            $params[] = $filters['is_draft'];
        }
        if (isset($filters['is_scheduled'])) {
            $where_conditions[] = "class_tasks.is_scheduled = ?";
            $params[] = $filters['is_scheduled'];
        }
        if (isset($filters['class_code'])) {
            $where_conditions[] = "JSON_CONTAINS(class_tasks.class_codes, ?)";
            $params[] = json_encode($filters['class_code']);
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Use raw query to handle collation issue
        $sql = "SELECT class_tasks.*, users.full_name as teacher_name
                FROM class_tasks
                LEFT JOIN users ON class_tasks.teacher_id = users.user_id COLLATE utf8mb4_general_ci
                WHERE {$where_clause}
                ORDER BY class_tasks.created_at DESC";
        
        return $this->db->query($sql, $params)->result_array();
    }
    
    public function get_by_id($task_id) {
        // Use raw query to handle collation issue
        $sql = "SELECT class_tasks.*, users.full_name as teacher_name
                FROM class_tasks
                LEFT JOIN users ON class_tasks.teacher_id = users.user_id COLLATE utf8mb4_general_ci
                WHERE class_tasks.task_id = ?
                AND class_tasks.status != 'deleted'";
        
        return $this->db->query($sql, [$task_id])->row_array();
    }
    
    public function get_drafts($teacher_id) {
        return $this->get_all($teacher_id, ['is_draft' => 1]);
    }
    
    public function get_scheduled($teacher_id) {
        return $this->get_all($teacher_id, ['is_scheduled' => 1]);
    }
    
    public function get_published($teacher_id) {
        return $this->get_all($teacher_id, ['is_draft' => 0, 'is_scheduled' => 0]);
    }
    
    public function insert($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Convert class_codes array to JSON if it's an array
        if (isset($data['class_codes']) && is_array($data['class_codes'])) {
            $data['class_codes'] = json_encode($data['class_codes']);
        }
        
        // Convert assigned_students array to JSON if it's an array
        if (isset($data['assigned_students']) && is_array($data['assigned_students'])) {
            $data['assigned_students'] = json_encode($data['assigned_students']);
        }
        
        $this->db->insert('class_tasks', $data);
        return $this->db->insert_id();
    }
    
    public function update($task_id, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Convert class_codes array to JSON if it's an array
        if (isset($data['class_codes']) && is_array($data['class_codes'])) {
            $data['class_codes'] = json_encode($data['class_codes']);
        }
        
        // Convert assigned_students array to JSON if it's an array
        if (isset($data['assigned_students']) && is_array($data['assigned_students'])) {
            $data['assigned_students'] = json_encode($data['assigned_students']);
        }
        
        $this->db->where('task_id', $task_id);
        return $this->db->update('class_tasks', $data);
    }
    
    public function delete($task_id) {
        // Soft delete - just mark as deleted
        $this->db->where('task_id', $task_id);
        return $this->db->update('class_tasks', ['status' => 'deleted', 'updated_at' => date('Y-m-d H:i:s')]);
    }
    
    public function hard_delete($task_id) {
        // Hard delete - actually remove from database
        // First check if task has submissions
        $submission_count = $this->get_submission_count($task_id);
        if ($submission_count > 0) {
            return false; // Cannot hard delete if submissions exist
        }
        
        // Delete related comments first
        $this->db->where('task_id', $task_id)->delete('task_comments');
        
        // Delete the task
        $this->db->where('task_id', $task_id);
        return $this->db->delete('class_tasks');
    }
    
    public function publish_draft($task_id) {
        return $this->update($task_id, [
            'is_draft' => 0,
            'is_scheduled' => 0,
            'scheduled_at' => null
        ]);
    }
    
    public function schedule_task($task_id, $scheduled_at) {
        return $this->update($task_id, [
            'is_draft' => 0,
            'is_scheduled' => 1,
            'scheduled_at' => $scheduled_at
        ]);
    }
    
    public function get_tasks_for_student($student_id, $class_code) {
        // Use raw query to handle collation issue
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
        
        return $this->db->query($sql, [json_encode($class_code), $student_id, $class_code])->result_array();
    }
    
    public function get_task_with_submissions($task_id, $teacher_id) {
        $task = $this->get_by_id($task_id);
        if (!$task || $task['teacher_id'] != $teacher_id) {
            return null;
        }
        
        // Get submissions for this task
        $sql = "SELECT task_submissions.*, users.full_name as student_name, users.student_num
                FROM task_submissions
                LEFT JOIN users ON task_submissions.student_id = users.user_id COLLATE utf8mb4_general_ci
                WHERE task_submissions.task_id = ?
                ORDER BY task_submissions.submitted_at ASC";
        
        $submissions = $this->db->query($sql, [$task_id])->result_array();
        
        $task['submissions'] = $submissions;
        return $task;
    }
    
    public function get_submission_count($task_id) {
        return $this->db->where('task_id', $task_id)->count_all_results('task_submissions');
    }
    
    public function get_student_submission($task_id, $student_id, $class_code) {
        return $this->db->where('task_id', $task_id)
            ->where('student_id', $student_id)
            ->where('class_code', $class_code)
            ->get('task_submissions')->row_array();
    }
    
    public function submit_task($data) {
        $data['submitted_at'] = date('Y-m-d H:i:s');
        $this->db->insert('task_submissions', $data);
        return $this->db->insert_id();
    }
    
    /**
     * Submit task with multiple attachments
     */
    public function submit_task_with_attachments($submission_data, $attachments = []) {
        $this->db->trans_start();
        
        // Insert the main submission
        $submission_data['submitted_at'] = date('Y-m-d H:i:s');
        $this->db->insert('task_submissions', $submission_data);
        $submission_id = $this->db->insert_id();
        
        // Insert attachments if provided
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $attachment['submission_id'] = $submission_id;
                $attachment['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('task_submission_attachments', $attachment);
            }
        }
        
        $this->db->trans_complete();
        
        if ($this->db->trans_status() === FALSE) {
            return false;
        }
        
        return $submission_id;
    }
    
    /**
     * Get attachments for a submission
     */
    public function get_submission_attachments($submission_id) {
        return $this->db->where('submission_id', $submission_id)
            ->order_by('created_at', 'ASC')
            ->get('task_submission_attachments')->result_array();
    }
    
    /**
     * Get submission with attachments
     */
    public function get_submission_with_attachments($submission_id) {
        $submission = $this->db->where('submission_id', $submission_id)
            ->get('task_submissions')->row_array();
            
        if ($submission) {
            $submission['attachments'] = $this->get_submission_attachments($submission_id);
        }
        
        return $submission;
    }
    
    /**
     * Get student submission with attachments
     */
    public function get_student_submission_with_attachments($task_id, $student_id, $class_code) {
        $submission = $this->get_student_submission($task_id, $student_id, $class_code);
        
        if ($submission) {
            $submission['attachments'] = $this->get_submission_attachments($submission['submission_id']);
        }
        
        return $submission;
    }
    
    /**
     * Delete attachment
     */
    public function delete_attachment($attachment_id, $submission_id) {
        return $this->db->where('attachment_id', $attachment_id)
            ->where('submission_id', $submission_id)
            ->delete('task_submission_attachments');
    }
    
    /**
     * Get attachment count for submission
     */
    public function get_attachment_count($submission_id) {
        return $this->db->where('submission_id', $submission_id)
            ->count_all_results('task_submission_attachments');
    }
    
    public function update_submission($submission_id, $data) {
        $this->db->where('submission_id', $submission_id);
        return $this->db->update('task_submissions', $data);
    }
    
    public function grade_submission($submission_id, $grade, $feedback = null) {
        return $this->update_submission($submission_id, [
            'grade' => $grade,
            'feedback' => $feedback,
            'status' => 'graded'
        ]);
    }
    
    public function add_comment($task_id, $user_id, $comment) {
        $data = [
            'task_id' => $task_id,
            'user_id' => $user_id,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert('task_comments', $data);
        return $this->db->insert_id();
    }
    
    public function get_comments($task_id) {
        // Use raw query to handle collation issue
        $sql = "SELECT task_comments.*, users.full_name as user_name, users.profile_pic, users.email, users.role, users.student_num
                FROM task_comments
                LEFT JOIN users ON task_comments.user_id = users.user_id COLLATE utf8mb4_general_ci
                WHERE task_comments.task_id = ?
                ORDER BY task_comments.created_at ASC";
        
        return $this->db->query($sql, [$task_id])->result_array();
    }
    
    public function update_comment($comment_id, $user_id, $comment) {
        $this->db->where('comment_id', $comment_id);
        $this->db->where('user_id', $user_id);
        return $this->db->update('task_comments', [
            'comment' => $comment,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function delete_comment($comment_id, $user_id) {
        $this->db->where('comment_id', $comment_id);
        $this->db->where('user_id', $user_id);
        return $this->db->delete('task_comments');
    }
    
    /**
     * Get task statistics
     */
    public function get_task_statistics($task_id)
    {
        // Get total submissions
        $total_submissions = $this->db->where('task_id', $task_id)->count_all_results('task_submissions');
        
        // Get graded submissions
        $graded_submissions = $this->db->where('task_id', $task_id)
            ->where('grade IS NOT NULL')
            ->count_all_results('task_submissions');
        
        // Get pending grading
        $pending_grading = $this->db->where('task_id', $task_id)
            ->where('grade IS NULL')
            ->where('status', 'submitted')
            ->count_all_results('task_submissions');
        
        // Get average grade
        $avg_grade = $this->db->select('AVG(grade) as avg_grade')
            ->where('task_id', $task_id)
            ->where('grade IS NOT NULL')
            ->get('task_submissions')->row()->avg_grade;
        
        // Get highest and lowest grades
        $grade_stats = $this->db->select('MAX(grade) as highest_grade, MIN(grade) as lowest_grade')
            ->where('task_id', $task_id)
            ->where('grade IS NOT NULL')
            ->get('task_submissions')->row();
        
        // Get total assigned students (this would need to be calculated based on class enrollment)
        // For now, we'll use total submissions as a proxy
        $total_assigned = $total_submissions + $pending_grading;
        
        return [
            'total_assigned' => $total_assigned,
            'submitted' => $total_submissions,
            'graded' => $graded_submissions,
            'pending_grading' => $pending_grading,
            'not_submitted' => $total_assigned - $total_submissions,
            'average_grade' => $avg_grade ? round($avg_grade, 1) : 0,
            'highest_grade' => $grade_stats->highest_grade ?? 0,
            'lowest_grade' => $grade_stats->lowest_grade ?? 0
        ];
    }

    /**
     * Assign students to a task
     */
    public function assign_students_to_task($task_id, $students_data)
    {
        // First, remove existing assignments for this task
        $this->db->where('task_id', $task_id)->delete('task_student_assignments');
        
        // Convert stdClass to array if needed
        if (is_object($students_data)) {
            $students_data = json_decode(json_encode($students_data), true);
        }
        
        // Insert new assignments with duplicate checking
        $assignments = [];
        $unique_keys = []; // Track unique combinations to prevent duplicates
        
        foreach ($students_data as $student) {
            // Convert individual student object to array if needed
            if (is_object($student)) {
                $student = json_decode(json_encode($student), true);
            }
            
            // Create unique key to prevent duplicates
            $unique_key = $task_id . '-' . $student['student_id'] . '-' . $student['class_code'];
            
            // Skip if this combination already exists
            if (in_array($unique_key, $unique_keys)) {
                continue;
            }
            
            $unique_keys[] = $unique_key;
            
            $assignments[] = [
                'task_id' => $task_id,
                'student_id' => $student['student_id'],
                'class_code' => $student['class_code'],
                'assigned_at' => date('Y-m-d H:i:s'),
                'status' => 'assigned'
            ];
        }
        
        if (!empty($assignments)) {
            try {
                $result = $this->db->insert_batch('task_student_assignments', $assignments);
                
                // Log the assignment operation for debugging
                log_message('info', 'Task assignment: Task ID ' . $task_id . ', Students assigned: ' . count($assignments));
                
                return $result;
            } catch (Exception $e) {
                log_message('error', 'Task assignment failed: ' . $e->getMessage());
                throw $e;
            }
        }
        
        return true;
    }

    /**
     * Get students assigned to a task
     */
    public function get_assigned_students($task_id)
    {
        $sql = "SELECT tsa.*, u.full_name, u.student_num, u.email, u.profile_pic
                FROM task_student_assignments tsa
                LEFT JOIN users u ON tsa.student_id = u.user_id COLLATE utf8mb4_general_ci
                WHERE tsa.task_id = ?
                ORDER BY u.full_name ASC";
        
        return $this->db->query($sql, [$task_id])->result_array();
    }

    /**
     * Check if a student is already assigned to a task
     */
    public function is_student_assigned($task_id, $student_id, $class_code)
    {
        return $this->db->where('task_id', $task_id)
            ->where('student_id', $student_id)
            ->where('class_code', $class_code)
            ->count_all_results('task_student_assignments') > 0;
    }

    /**
     * Safely assign students to a task (with duplicate checking)
     */
    public function safe_assign_students_to_task($task_id, $students_data)
    {
        // Convert stdClass to array if needed
        if (is_object($students_data)) {
            $students_data = json_decode(json_encode($students_data), true);
        }
        
        $assignments = [];
        $unique_keys = [];
        
        foreach ($students_data as $student) {
            // Convert individual student object to array if needed
            if (is_object($student)) {
                $student = json_decode(json_encode($student), true);
            }
            
            // Create unique key
            $unique_key = $task_id . '-' . $student['student_id'] . '-' . $student['class_code'];
            
            // Skip if this combination already exists in our batch
            if (in_array($unique_key, $unique_keys)) {
                continue;
            }
            
            // Check if already assigned in database
            if ($this->is_student_assigned($task_id, $student['student_id'], $student['class_code'])) {
                continue;
            }
            
            $unique_keys[] = $unique_key;
            
            $assignments[] = [
                'task_id' => $task_id,
                'student_id' => $student['student_id'],
                'class_code' => $student['class_code'],
                'assigned_at' => date('Y-m-d H:i:s'),
                'status' => 'assigned'
            ];
        }
        
        if (!empty($assignments)) {
            try {
                $result = $this->db->insert_batch('task_student_assignments', $assignments);
                log_message('info', 'Safe task assignment: Task ID ' . $task_id . ', Students assigned: ' . count($assignments));
                return $result;
            } catch (Exception $e) {
                log_message('error', 'Safe task assignment failed: ' . $e->getMessage());
                throw $e;
            }
        }
        
        return true;
    }

    /**
     * Get students available for assignment (from specified classrooms)
     */
    public function get_available_students($class_codes)
    {
        if (empty($class_codes)) {
            return [];
        }
        
        $sql = "SELECT u.user_id as student_id, u.full_name, u.student_num, u.email, u.profile_pic, ce.class_code
                FROM users u
                JOIN classroom_enrollments ce ON u.user_id = ce.student_id COLLATE utf8mb4_general_ci
                WHERE u.role = 'student'
                AND u.status = 'active'
                AND ce.enrollment_status = 'enrolled'
                AND ce.class_code IN (" . str_repeat('?,', count($class_codes) - 1) . "?)
                ORDER BY u.full_name ASC";
        
        return $this->db->query($sql, $class_codes)->result_array();
    }

    /**
     * Get task assignment statistics
     */
    public function get_task_assignment_stats($task_id)
    {
        $task = $this->get_by_id($task_id);
        if (!$task) {
            return null;
        }
        
        if ($task['assignment_type'] === 'classroom') {
            // For classroom assignments, count students in the classrooms
            $class_codes = json_decode($task['class_codes'], true);
            $total_assigned = $this->db->where_in('class_code', $class_codes)
                ->where('enrollment_status', 'enrolled')
                ->count_all_results('classroom_enrollments');
        } else {
            // For individual assignments, count assigned students
            $total_assigned = $this->db->where('task_id', $task_id)
                ->count_all_results('task_student_assignments');
        }
        
        $submitted = $this->db->where('task_id', $task_id)
            ->count_all_results('task_submissions');
        
        return [
            'total_assigned' => $total_assigned,
            'submitted' => $submitted,
            'not_submitted' => $total_assigned - $submitted,
            'assignment_type' => $task['assignment_type']
        ];
    }

    /**
     * Get only individually assigned tasks for a student
     */
    public function get_individually_assigned_tasks_for_student($student_id, $class_code) {
        // Use raw query to handle collation issue
        $sql = "SELECT class_tasks.*, users.full_name as teacher_name
                FROM class_tasks
                LEFT JOIN users ON class_tasks.teacher_id = users.user_id COLLATE utf8mb4_general_ci
                WHERE class_tasks.status = 'active'
                AND class_tasks.is_draft = 0
                AND JSON_CONTAINS(class_tasks.class_codes, ?)
                AND class_tasks.assignment_type = 'individual'
                AND EXISTS (
                    SELECT 1 FROM task_student_assignments tsa
                    WHERE tsa.task_id = class_tasks.task_id
                    AND tsa.student_id = ?
                    AND tsa.class_code = ?
                )
                ORDER BY class_tasks.created_at DESC";
        
        return $this->db->query($sql, [json_encode($class_code), $student_id, $class_code])->result_array();
    }

    /**
     * Get all student submissions with attachments for a specific task (Teacher only)
     */
    public function get_task_submissions_with_attachments($task_id, $teacher_id) {
        // First verify the task belongs to the teacher
        $task = $this->get_by_id($task_id);
        if (!$task || $task['teacher_id'] != $teacher_id) {
            return null;
        }
        
        // Get all submissions for this task with student information
        $sql = "SELECT 
                    ts.*,
                    u.full_name as student_name,
                    u.student_num,
                    u.email,
                    u.profile_pic
                FROM task_submissions ts
                LEFT JOIN users u ON ts.student_id = u.user_id COLLATE utf8mb4_general_ci
                WHERE ts.task_id = ?
                ORDER BY ts.submitted_at ASC";
        
        $submissions = $this->db->query($sql, [$task_id])->result_array();
        
        // Add attachments to each submission
        foreach ($submissions as &$submission) {
            $submission['attachments'] = $this->get_submission_attachments($submission['submission_id']);
            $submission['attachment_count'] = count($submission['attachments']);
        }
        
        return [
            'task' => $task,
            'submissions' => $submissions,
            'total_submissions' => count($submissions),
            'submitted_count' => count(array_filter($submissions, function($s) { return $s['submission_id'] !== null; })),
            'graded_count' => count(array_filter($submissions, function($s) { return $s['grade'] !== null; }))
        ];
    }

    /**
     * Get task by attachment filename
     */
    public function get_task_by_attachment($filename) {
        $this->db->select('task_id, title, type, attachment_url, attachment_type, original_filename');
        $this->db->from('class_tasks');
        $this->db->where('attachment_url', $filename);
        $this->db->where('attachment_type', 'file');
        $this->db->limit(1);
        
        $query = $this->db->get();
        return $query->num_rows() > 0 ? $query->row_array() : null;
    }
} 