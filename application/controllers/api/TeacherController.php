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
        $this->load->model('StreamAttachment_model');

        // Support both raw JSON bodies and multipart form-data (with or without a 'data' field)
        $contentType = $this->input->server('CONTENT_TYPE') ?? '';
        $isMultipart = stripos($contentType, 'multipart/form-data') !== false;

        if ($isMultipart) {
            // Prefer a JSON blob in 'data' when provided; otherwise read individual form fields
            if (isset($_POST['data'])) {
                $data = json_decode($_POST['data'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return json_response(false, 'Invalid JSON format in data field', null, 400);
                }
            } else {
                // Build data from standard form fields
                $data = [
                    'title' => $this->input->post('title'),
                    'content' => $this->input->post('content'),
                    'is_draft' => (int)($this->input->post('is_draft') ?? 0),
                    'is_scheduled' => (int)($this->input->post('is_scheduled') ?? 0),
                    'scheduled_at' => $this->input->post('scheduled_at') ?: null,
                    'allow_comments' => (int)($this->input->post('allow_comments') ?? 1),
                ];

                // Optional student_ids can come as JSON or comma-separated
                $studentIds = $this->input->post('student_ids');
                if (!empty($studentIds)) {
                    if (is_string($studentIds)) {
                        $decoded = json_decode($studentIds, true);
                        $data['student_ids'] = json_last_error() === JSON_ERROR_NONE ? $decoded : array_filter(array_map('trim', explode(',', $studentIds)));
                    } elseif (is_array($studentIds)) {
                        $data['student_ids'] = $studentIds;
                    }
                }
            }

            // Handle one or many file uploads
            $uploaded_files = [];
            $link_attachments = [];
            $upload_path = FCPATH . 'uploads/announcement/';
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            
            // Process file uploads (support single fields and array-style fields like attachments[])
            foreach ($_FILES as $field_name => $file_data) {
                // Array-style upload
                if (is_array($file_data['name'])) {
                    $count = count($file_data['name']);
                    for ($i = 0; $i < $count; $i++) {
                        if ($file_data['error'][$i] !== UPLOAD_ERR_OK) continue;
                        $original_name = $file_data['name'][$i];
                        $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                        $file_name_without_ext = pathinfo($original_name, PATHINFO_FILENAME);

                        // Sanitize filename
                        $sanitized_name = preg_replace('/[^\p{L}\p{N}\s._-]/u', '', $file_name_without_ext);
                        $sanitized_name = trim($sanitized_name, '._-');
                        $sanitized_name = preg_replace('/\s+/', ' ', $sanitized_name);
                        if (empty($sanitized_name)) { $sanitized_name = 'file'; }

                        $final_filename = $sanitized_name . '.' . $file_extension;
                        $counter = 1;
                        while (file_exists($upload_path . $final_filename)) {
                            $final_filename = $sanitized_name . '_' . $counter . '.' . $file_extension;
                            $counter++;
                        }

                        $disk_path = $upload_path . $final_filename;
                        if (move_uploaded_file($file_data['tmp_name'][$i], $disk_path)) {
                            $rel_path = 'uploads/announcement/' . $final_filename;
                            $uploaded_files[] = [
                                'field_name' => $field_name,
                                'file_path' => $rel_path,
                                'file_name' => $final_filename,
                                'original_name' => $original_name,
                                'file_size' => $file_data['size'][$i],
                                'mime_type' => $file_data['type'][$i],
                                'attachment_type' => 'file',
                                'attachment_url' => $rel_path
                            ];
                        }
                    }
                    continue;
                }

                // Single-file upload
                if ($file_data['error'] === UPLOAD_ERR_OK) {
                    $config['upload_path'] = $upload_path;
                    $config['allowed_types'] = 'gif|jpg|jpeg|png|webp|pdf|doc|docx|ppt|pptx|xls|xlsx|txt|zip|rar|mp4|mp3';
                    $config['max_size'] = 102400; // 100MB
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
                            'original_name' => $original_name,
                            'file_size' => $upload_data['file_size'],
                            'mime_type' => $upload_data['file_type'],
                            'attachment_type' => 'file',
                            'attachment_url' => $file_path
                        ];
                    } else {
                        $error = $this->upload->display_errors('', '');
                        return json_response(false, 'Upload failed for ' . $field_name . ': ' . $error, null, 400);
                    }
                }
            }

            // Handle link attachments from form data
            $this->_process_link_attachments_from_form($link_attachments);
            
            // Combine all attachments
            $all_attachments = array_merge($uploaded_files, $link_attachments);
            
            // Store attachment information in data
            if (!empty($all_attachments)) {
                if (count($all_attachments) === 1) {
                    $data['attachment_type'] = $all_attachments[0]['attachment_type'];
                    $data['attachment_url'] = $all_attachments[0]['attachment_url'];
                } else {
                    $data['attachment_type'] = 'multiple';
                    $data['attachment_url'] = null;
                }
            }
        } else {
            // Fallback to JSON body (raw)
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return json_response(false, 'Invalid JSON format', null, 400);
            }
            
            // Handle link attachments from JSON
            $link_attachments = [];
            $this->_process_link_attachments_from_json($data, $link_attachments);
            
            // Combine with any file attachments (if any were provided in JSON)
            $all_attachments = array_merge($uploaded_files ?? [], $link_attachments);
            
            // Update attachment type if we have attachments
            if (!empty($all_attachments)) {
                if (count($all_attachments) === 1) {
                    $data['attachment_type'] = $all_attachments[0]['attachment_type'];
                    $data['attachment_url'] = $all_attachments[0]['attachment_url'];
                } else {
                    $data['attachment_type'] = 'multiple';
                    $data['attachment_url'] = null;
                }
            }
        }
        
        $required = ['content'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        
        // Resolve classroom by class_code to ensure classroom_id is stored (aligns with student flow)
        $this->load->model('Classroom_model');
        $classroom = $this->Classroom_model->get_by_code($class_code);
        if (!$classroom) {
            return json_response(false, 'Classroom not found', null, 404);
        }

        // Ensure attachment fields are set at insert time if files are present
        $initial_attachment_type = $data['attachment_type'] ?? null;
        $initial_attachment_url = $data['attachment_url'] ?? null;
        if (!empty($all_attachments)) {
            if (count($all_attachments) === 1) {
                $initial_attachment_type = 'file';
                $initial_attachment_url = $all_attachments[0]['attachment_url'] ?? $all_attachments[0]['file_path'] ?? null;
            } else {
                $initial_attachment_type = 'multiple';
                $initial_attachment_url = null;
            }
        }

        $insert_data = [
            'class_code' => $class_code,
            'classroom_id' => $classroom['id'],
            'user_id' => $user_data['user_id'],
            'title' => $data['title'] ?? null,
            'content' => $data['content'],
            'is_draft' => $data['is_draft'] ?? 0,
            'is_scheduled' => $data['is_scheduled'] ?? 0,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'allow_comments' => $data['allow_comments'] ?? 1,
            'attachment_type' => $initial_attachment_type,
            'attachment_url' => $initial_attachment_url
        ];
        
        if (!empty($data['student_ids'])) {
            $insert_data['student_ids'] = $data['student_ids'];
        }
        
        $id = $this->ClassroomStream_model->insert($insert_data);
        if ($id) {
            // Handle attachments - save ALL attachments to the attachments table for consistency
            if (!empty($all_attachments)) {
                $this->StreamAttachment_model->insert_multiple($id, $all_attachments);
                
                // Update the main post with attachment info for backward compatibility
                if (count($all_attachments) === 1) {
                    // Single file - set main table fields for backward compatibility
                    $this->db->where('id', $id)->update('classroom_stream', [
                        'attachment_type' => 'file',
                        'attachment_url' => $all_attachments[0]['attachment_url']
                    ]);
                } else {
                    // Multiple files - set type to multiple
                    $this->db->where('id', $id)->update('classroom_stream', [
                        'attachment_type' => 'multiple',
                        'attachment_url' => null
                    ]);
                }
            }
            
            $post = $this->ClassroomStream_model->get_by_id($id);
            
            // Create notifications when:
            // - Not a draft and not scheduled (immediate publish)
            // Scheduled posts will be dispatched by a cron endpoint when due
            if (!$data['is_draft'] && (empty($data['is_scheduled']) || empty($data['scheduled_at']) || strtotime($data['scheduled_at']) <= time())) {
                $this->load->helper('notification');
                
                // Debug logging
                error_log("Creating notifications for announcement ID: " . $id);
                error_log("Class code: " . $class_code);
                
                // Determine recipients: targeted students if visible_to_student_ids set, otherwise all students in class
                $user_ids = [];
                if (!empty($post['visible_to_student_ids'])) {
                    $target_ids = json_decode($post['visible_to_student_ids'], true) ?: [];
                    $user_ids = array_values($target_ids);
                    error_log("Using targeted students: " . json_encode($user_ids));
                } else {
                    $students = get_class_students($class_code);
                    error_log("Found " . count($students) . " students in class");
                    if ($students) {
                        $user_ids = array_column($students, 'user_id');
                        error_log("Student IDs: " . json_encode($user_ids));
                    }
                }
                
                if (!empty($user_ids)) {
                    $title = $data['title'] ?: 'New Announcement';
                    $message = $data['content'] ?? 'A new announcement has been posted.';
                    
                    error_log("Creating notifications for " . count($user_ids) . " users");
                    error_log("Title: " . $title);
                    error_log("Message: " . $message);
                    
                    // Create notifications for all students in the class
                    $notification_ids = create_notifications_for_users(
                        $user_ids,
                        'announcement',
                        $title,
                        $message,
                        $id,
                        'announcement',
                        $class_code,
                        false
                    );
                    
                    error_log("Created " . count($notification_ids) . " notifications");
                } else {
                    error_log("No students found for notifications");
                }
            } else {
                error_log("Skipping notification creation - is_draft: " . ($data['is_draft'] ? 'true' : 'false'));
            }
            
            return json_response(true, 'Announcement posted successfully', $post, 201);
        } else {
            return json_response(false, 'Failed to post announcement', null, 500);
        }
    }

    // Edit a stream post by ID (supports both drafts and published posts)
    public function classroom_stream_put($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        
        $this->load->model('ClassroomStream_model');
        $this->load->model('StreamAttachment_model');
        
        // Check if the stream post exists and belongs to this teacher
        $existing_post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$existing_post || $existing_post['class_code'] !== $class_code || $existing_post['user_id'] !== $user_data['user_id']) {
            return json_response(false, 'Stream post not found or access denied', null, 404);
        }
        
        // Support both raw JSON bodies and multipart form-data
        $data = [];
        $all_attachments = [];
        
        // Check if this is a multipart request by examining Content-Type header
        $is_multipart = false;
        $content_type = $this->input->get_request_header('Content-Type');
        if ($content_type && strpos($content_type, 'multipart/form-data') !== false) {
            $is_multipart = true;
        }
        
        if ($is_multipart) {
            // Handle multipart form-data for file uploads
            $this->load->helper('file');
            
            // For PUT requests with multipart, we need to parse the raw input manually
            // because $_POST is not populated the same way as with POST requests
            $raw_input = file_get_contents('php://input');
            $boundary = null;
            
            // Extract boundary from Content-Type header
            if (preg_match('/boundary=(.*)$/', $content_type, $matches)) {
                $boundary = $matches[1];
            }
            
            if ($boundary) {
                // Parse multipart data manually
                $parts = explode('--' . $boundary, $raw_input);
                $data = [];
                
                foreach ($parts as $part) {
                    if (empty($part) || $part === '--') continue;
                    
                    // Parse each part
                    if (preg_match('/name="([^"]+)"/', $part, $name_matches)) {
                        $field_name = $name_matches[1];
                        
                        // Check if this is a file upload
                        if (preg_match('/filename="([^"]+)"/', $part)) {
                            // This is a file - we'll handle it separately
                            continue;
                        }
                        
                        // Extract the value (text field)
                        $value_start = strpos($part, "\r\n\r\n") + 4;
                        $value_end = strrpos($part, "\r\n");
                        if ($value_start !== false && $value_end !== false && $value_end > $value_start) {
                            $value = substr($part, $value_start, $value_end - $value_start);
                            $data[$field_name] = trim($value);
                        }
                    }
                }
            } else {
                // Fallback: try to get data from $_POST (may work in some cases)
                $data = [
                    'title' => $this->input->post('title'),
                    'content' => $this->input->post('content'),
                    'is_draft' => $this->input->post('is_draft'),
                    'is_scheduled' => $this->input->post('is_scheduled'),
                    'scheduled_at' => $this->input->post('scheduled_at'),
                    'allow_comments' => $this->input->post('allow_comments'),
                    'student_ids' => $this->input->post('student_ids')
                ];
            }
            
            // Handle multiple file uploads - check both $_FILES and $_POST for file data
            $uploaded_files = [];
            $file_inputs = ['attachment_0', 'attachment_1', 'attachment_2', 'attachment_3', 'attachment_4'];
            
            foreach ($file_inputs as $input_name) {
                // Check if file was uploaded via $_FILES
                if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES[$input_name];
                    $original_name = $file['name'];
                    $file_size = $file['size'];
                    $file_type = $file['type'];
                    
                    // Generate unique filename
                    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
                    $file_name = uniqid('stream_') . '_' . time() . '.' . $extension;
                    $upload_path = 'uploads/announcement/';
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($upload_path)) {
                        mkdir($upload_path, 0755, true);
                    }
                    
                    $file_path = $upload_path . $file_name;
                    
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        $uploaded_files[] = [
                            'file_path' => $file_path,
                            'file_name' => $file_name,
                            'original_name' => $original_name,
                            'file_size' => $file_size,
                            'mime_type' => $file_type,
                            'attachment_type' => 'file',
                            'attachment_url' => $file_path
                        ];
                    }
                }
                // Check if file data was sent via $_POST (for PUT requests)
                elseif (isset($_POST[$input_name]) && !empty($_POST[$input_name])) {
                    // This handles cases where files might be sent differently in PUT requests
                    $file_data = $_POST[$input_name];
                    if (is_array($file_data) && isset($file_data['tmp_name']) && $file_data['error'] === UPLOAD_ERR_OK) {
                        $original_name = $file_data['name'];
                        $file_size = $file_data['size'];
                        $file_type = $file_data['type'];
                        
                        // Generate unique filename
                        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
                        $file_name = uniqid('stream_') . '_' . time() . '.' . $extension;
                        $upload_path = 'uploads/announcement/';
                        
                        // Create directory if it doesn't exist
                        if (!is_dir($upload_path)) {
                            mkdir($upload_path, 0755, true);
                        }
                        
                        $file_path = $upload_path . $file_name;
                        
                        if (move_uploaded_file($file_data['tmp_name'], $file_path)) {
                            $uploaded_files[] = [
                                'file_path' => $file_path,
                                'file_name' => $file_name,
                                'original_name' => $original_name,
                                'file_size' => $file_size,
                                'mime_type' => $file_type,
                                'attachment_type' => 'file',
                                'attachment_url' => $file_path
                            ];
                        }
                    }
                }
            }
            
            // Handle link attachments if provided
            $link_attachments = [];
            $link_fields = ['link_0', 'link_1', 'link_2', 'link_3', 'link_4'];
            foreach ($link_fields as $link_field) {
                $link_url = $data[$link_field] ?? $this->input->post($link_field);
                if (!empty($link_url) && filter_var($link_url, FILTER_VALIDATE_URL)) {
                    $link_attachments[] = [
                        'file_path' => $link_url,
                        'file_name' => 'link_' . uniqid(),
                        'original_name' => $link_url,
                        'file_size' => 0,
                        'mime_type' => 'text/plain',
                        'attachment_type' => 'link',
                        'attachment_url' => $link_url
                    ];
                }
            }
            
            $all_attachments = array_merge($uploaded_files, $link_attachments);
            
        } else {
            // Handle JSON request
            $data = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return json_response(false, 'Invalid JSON format', null, 400);
            }
        }
        
        // Validate required fields
        $required = ['content'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return json_response(false, "$field is required", null, 400);
            }
        }
        
        // Prepare update data
        $update_data = [
            'title' => $data['title'] ?? $existing_post['title'],
            'content' => $data['content'],
            'is_draft' => $data['is_draft'] ?? $existing_post['is_draft'],
            'is_scheduled' => $data['is_scheduled'] ?? $existing_post['is_scheduled'],
            'scheduled_at' => $data['scheduled_at'] ?? $existing_post['scheduled_at'],
            'allow_comments' => $data['allow_comments'] ?? $existing_post['allow_comments'],
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (!empty($data['student_ids'])) {
            $update_data['visible_to_student_ids'] = json_encode($data['student_ids']);
        }
        
        // Handle attachments if provided
        if (!empty($all_attachments)) {
            // Delete existing attachments
            $this->StreamAttachment_model->delete_by_stream_id($stream_id);
            
            // Insert new attachments
            $this->StreamAttachment_model->insert_multiple($stream_id, $all_attachments);
            
            // Update the main post with attachment info for backward compatibility
            if (count($all_attachments) === 1) {
                // Single file - set main table fields for backward compatibility
                $update_data['attachment_type'] = 'file';
                $update_data['attachment_url'] = $all_attachments[0]['attachment_url'];
            } else {
                // Multiple files - set type to multiple
                $update_data['attachment_type'] = 'multiple';
                $update_data['attachment_url'] = null;
            }
        } else {
            // If no new attachments provided, keep existing ones
            // But update attachment_type if needed
            if ($existing_post['attachment_type'] === 'multiple') {
                $existing_attachments = $this->StreamAttachment_model->get_by_stream_id($stream_id);
                if (count($existing_attachments) === 1) {
                    // Update to single file type
                    $update_data['attachment_type'] = 'file';
                    $update_data['attachment_url'] = $existing_attachments[0]['attachment_url'];
                }
            }
        }
        
        // Update the stream post
        $success = $this->ClassroomStream_model->update($stream_id, $update_data);
        if ($success) {
            // If publishing now and either not scheduled or scheduled time already reached, send notifications now
            if (isset($data['is_draft']) && !$data['is_draft'] && (empty($data['is_scheduled']) || empty($data['scheduled_at']) || strtotime($data['scheduled_at']) <= time())) {
                $this->load->helper('notification');
                $updated = $this->ClassroomStream_model->get_by_id($stream_id);
                $user_ids = [];
                if (!empty($updated['visible_to_student_ids'])) {
                    $user_ids = json_decode($updated['visible_to_student_ids'], true) ?: [];
                } else {
                    $students = get_class_students($class_code);
                    if ($students) {
                        $user_ids = array_column($students, 'user_id');
                    }
                }
                if (!empty($user_ids)) {
                    $title = $updated['title'] ?: 'Updated Announcement';
                    $message = $updated['content'] ?? 'An announcement has been updated.';
                    create_notifications_for_users(
                        $user_ids,
                        'announcement',
                        $title,
                        $message,
                        $stream_id,
                        'announcement',
                        $class_code,
                        false
                    );
                }
            }
            
            $updated_post = $this->ClassroomStream_model->get_by_id($stream_id);
            return json_response(true, 'Stream post updated successfully', $updated_post);
        } else {
            return json_response(false, 'Failed to update stream post', null, 500);
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

    // Delete a stream post (and cascade delete comments/attachments)
    public function classroom_stream_delete($class_code, $stream_id) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $post = $this->ClassroomStream_model->get_by_id($stream_id);
        if (!$post || $post['class_code'] !== $class_code) {
            return json_response(false, 'Stream post not found', null, 404);
        }
        $this->db->where('id', $stream_id)->delete('classroom_stream');
        return json_response(true, 'Stream post deleted successfully');
    }

    public function classroom_stream_scheduled_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $this->load->model('ClassroomStream_model');
        $posts = $this->ClassroomStream_model->get_scheduled_for_classroom_ui($class_code);
        return json_response(true, 'Scheduled posts retrieved successfully', $posts);
    }

    // Dispatch notifications for due scheduled posts (to be run via cron)
    public function classroom_stream_dispatch_scheduled_notifications_post($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;
        $now = date('Y-m-d H:i:s');
        // Find scheduled posts that are due and have not been dispatched
        $due_posts = $this->db->select('*')
            ->from('classroom_stream')
            ->where('class_code', $class_code)
            ->where('is_draft', 0)
            ->where('is_scheduled', 1)
            ->where('scheduled_at <=', $now)
            ->group_start()
                ->where('notification_dispatched_at IS NULL', null, false)
                ->or_where('notification_dispatched_at =', null)
            ->group_end()
            ->get()->result_array();

        if (empty($due_posts)) {
            return json_response(true, 'No due scheduled posts to dispatch');
        }

        $this->load->helper('notification');
        $dispatched = 0;
        foreach ($due_posts as $post) {
            $user_ids = [];
            if (!empty($post['visible_to_student_ids'])) {
                $user_ids = json_decode($post['visible_to_student_ids'], true) ?: [];
            } else {
                $students = get_class_students($class_code);
                if ($students) {
                    $user_ids = array_column($students, 'user_id');
                }
            }
            if (!empty($user_ids)) {
                $title = $post['title'] ?: 'New Announcement';
                $message = $post['content'] ?? 'A new announcement has been posted.';
                create_notifications_for_users(
                    $user_ids,
                    'announcement',
                    $title,
                    $message,
                    $post['id'],
                    'announcement',
                    $class_code,
                    false
                );
                $dispatched++;
            }
            // Mark as dispatched to avoid duplicates
            $this->db->where('id', $post['id'])->update('classroom_stream', [
                'notification_dispatched_at' => $now
            ]);
        }

        return json_response(true, 'Dispatched scheduled post notifications', [
            'count' => $dispatched
        ]);
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
            // If publishing now and either not scheduled or scheduled time already reached, send notifications now
            if (isset($data['is_draft']) && !$data['is_draft'] && (empty($data['is_scheduled']) || empty($data['scheduled_at']) || strtotime($data['scheduled_at']) <= time())) {
                $this->load->helper('notification');
                $updated = $this->ClassroomStream_model->get_by_id($draft_id);
                $user_ids = [];
                if (!empty($updated['visible_to_student_ids'])) {
                    $user_ids = json_decode($updated['visible_to_student_ids'], true) ?: [];
                } else {
                    $students = get_class_students($class_code);
                    if ($students) {
                        $user_ids = array_column($students, 'user_id');
                    }
                }
                if (!empty($user_ids)) {
                    $title = $updated['title'] ?: 'New Announcement';
                    $message = $updated['content'] ?? 'A new announcement has been posted.';
                    create_notifications_for_users(
                        $user_ids,
                        'announcement',
                        $title,
                        $message,
                        $draft_id,
                        'announcement',
                        $class_code,
                        false
                    );
                }
            }
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
            // Create notifications for post author and students targeted by the post (or all students)
            $this->load->helper('notification');

            $notification_user_ids = [];

            // Notify post author if different from commenter
            if (!empty($post['user_id']) && $post['user_id'] !== $user_data['user_id']) {
                $notification_user_ids[] = $post['user_id'];
            }

            // Notify students in this class except the commenter if commenter is a student author
            $students = get_class_students($class_code);
            if (!empty($students)) {
                foreach ($students as $student) {
                    if ($student['user_id'] !== $user_data['user_id']) {
                        $notification_user_ids[] = $student['user_id'];
                    }
                }
            }

            // De-duplicate
            $notification_user_ids = array_values(array_unique($notification_user_ids));

            if (!empty($notification_user_ids)) {
                // Ensure User_model is loaded for commenter details
                $this->load->model('User_model');
                $commenter = $this->User_model->get_by_id($user_data['user_id']);
                $commenter_name = $commenter && !empty($commenter['full_name']) ? $commenter['full_name'] : $user_data['user_id'];
                $title = 'New comment from ' . $commenter_name;
                $snippet = mb_substr(trim($data['comment']), 0, 120);
                if (mb_strlen(trim($data['comment'])) > 120) {
                    $snippet .= '…';
                }
                $message = $snippet;

                create_notifications_for_users(
                    $notification_user_ids,
                    'announcement',
                    $title,
                    $message,
                    $stream_id,
                    'announcement',
                    $class_code,
                    false
                );
            }
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
                    u.profile_pic,
                    u.student_type
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
            $section_year = '';
            $section_program = '';
            if (!empty($student['section_id'])) {
                $section = $this->db->select('section_name, year_level, program')
                    ->from('sections')
                    ->where('section_id', $student['section_id'])
                    ->get()->row_array();
                if ($section) {
                    $section_name = $section['section_name'] ?? '';
                    $section_year = $section['year_level'] ?? '';
                    $section_program = $section['program'] ?? '';
                }
            }
            
            $students[] = [
                'user_id' => $student['user_id'],
                'full_name' => $student['full_name'],
                'email' => $student['email'],
                'student_num' => $student['student_num'],
                'contact_num' => $student['contact_num'],
                'program' => $student['program'],
                'section_name' => $section_name,
                'section_year_level' => $section_year,
                'section_program' => $section_program,
                'enrolled_at' => $student['enrolled_at'],
                'enrollment_status' => $student['enrollment_status'],
                'profile_pic' => $student['profile_pic'],
                'student_type' => $student['student_type'] ?? 'regular'
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
     * Remove/Delete a participant from classroom (Teacher only)
     * DELETE /api/teacher/classroom/{class_code}/students/{student_id}
     */
    public function classroom_students_delete($class_code, $student_id) {
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
            return json_response(false, 'Access denied. You can only remove students from your own classes.', null, 403);
        }
        
        // Verify student_id is provided
        if (empty($student_id)) {
            return json_response(false, 'Student ID is required', null, 400);
        }
        
        // Check if enrollment exists
        $enrollment = $this->db->where('classroom_id', $classroom['id'])
            ->where('student_id', $student_id)
            ->get('classroom_enrollments')->row_array();
        
        if (!$enrollment) {
            return json_response(false, 'Student is not enrolled in this classroom', null, 404);
        }
        
        // Check if already removed/dropped
        if (strtolower($enrollment['status']) === 'dropped') {
            return json_response(false, 'Student has already been removed from this classroom', null, 400);
        }
        
        // Get student info for logging
        $student = $this->db->where('user_id', $student_id)
            ->get('users')->row_array();
        
        // Update enrollment status to 'dropped'
        $this->db->where('id', $enrollment['id'])
            ->update('classroom_enrollments', ['status' => 'dropped']);
        
        // Log the action
        $this->load->model('Audit_model');
        if (class_exists('Audit_model')) {
            $this->Audit_model->create_log([
                'user_id' => $user_data['user_id'],
                'user_name' => $user_data['full_name'] ?? 'Unknown Teacher',
                'user_role' => 'teacher',
                'action_type' => 'student_removed_from_class',
                'module' => 'classroom',
                'table_name' => 'classroom_enrollments',
                'record_id' => $enrollment['id'],
                'details' => json_encode([
                    'class_code' => $classroom['class_code'],
                    'classroom_id' => $classroom['id'],
                    'student_id' => $student_id,
                    'student_name' => $student['full_name'] ?? 'Unknown Student',
                    'removed_at' => date('Y-m-d H:i:s')
                ])
            ]);
        }
        
        return json_response(true, 'Participant removed from classroom successfully', [
            'class_code' => $classroom['class_code'],
            'student_id' => $student_id,
            'student_name' => $student['full_name'] ?? null,
            'removed_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get pending classroom join requests (Teacher only)
     * GET /api/teacher/classroom/{class_code}/join-requests
     */
    public function classroom_join_requests_get($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $this->load->model('Classroom_model');
        $classroom = $this->Classroom_model->get_by_code($class_code);
        if (!$classroom || $classroom['teacher_id'] != $user_data['user_id']) {
            return json_response(false, 'Classroom not found or access denied', null, 404);
        }

        $subject = $this->db->select('subject_name')
            ->from('subjects')
            ->where('id', $classroom['subject_id'])
            ->get()->row_array();
        $section = $this->db->select('section_name')
            ->from('sections')
            ->where('section_id', $classroom['section_id'])
            ->get()->row_array();

        $classroom['subject_name'] = $subject['subject_name'] ?? '';
        $classroom['section_name'] = $section['section_name'] ?? '';

        $requests = $this->db->select('
                ce.id as enrollment_id,
                ce.student_id,
                ce.enrolled_at as requested_at,
                u.full_name,
                u.email,
                u.student_num,
                u.student_type,
                stu_sec.section_name as student_section,
                stu_sec.program as student_program,
                stu_sec.year_level as student_year_level
            ')
            ->from('classroom_enrollments ce')
            ->join('users u', 'ce.student_id = u.user_id COLLATE utf8mb4_unicode_ci', 'inner', false)
            ->join('sections stu_sec', 'u.section_id = stu_sec.section_id', 'left')
            ->where('ce.classroom_id', $classroom['id'])
            ->where('ce.status', 'pending')
            ->order_by('ce.enrolled_at', 'ASC')
            ->get()->result_array();

        return json_response(true, 'Pending join requests retrieved successfully', [
            'class_code' => $classroom['class_code'],
            'subject_name' => $classroom['subject_name'],
            'section_name' => $classroom['section_name'],
            'total_requests' => count($requests),
            'requests' => $requests
        ]);
    }

    /**
     * Approve or reject a classroom join request
     * POST /api/teacher/classroom/{class_code}/join-requests
     * Body: { "student_id": "...", "decision": "approve|reject" }
     */
    public function classroom_join_requests_post($class_code) {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $payload = json_decode(file_get_contents('php://input'), true);
        $student_id = $payload['student_id'] ?? null;
        $decision = strtolower($payload['decision'] ?? '');

        if (empty($student_id) || !in_array($decision, ['approve', 'reject'])) {
            return json_response(false, 'student_id and valid decision (approve/reject) are required', null, 400);
        }

        $this->load->model('Classroom_model');
        $classroom = $this->Classroom_model->get_by_code($class_code);
        if (!$classroom || $classroom['teacher_id'] != $user_data['user_id']) {
            return json_response(false, 'Classroom not found or access denied', null, 404);
        }

        $subject = $this->db->select('subject_name')
            ->from('subjects')
            ->where('id', $classroom['subject_id'])
            ->get()->row_array();
        $section = $this->db->select('section_name')
            ->from('sections')
            ->where('section_id', $classroom['section_id'])
            ->get()->row_array();

        $classroom['subject_name'] = $subject['subject_name'] ?? '';
        $classroom['section_name'] = $section['section_name'] ?? '';

        $enrollment = $this->db->where('classroom_id', $classroom['id'])
            ->where('student_id', $student_id)
            ->get('classroom_enrollments')->row_array();

        if (!$enrollment || strtolower($enrollment['status']) !== 'pending') {
            return json_response(false, 'Pending join request not found for this student', null, 404);
        }

        $student_row = $this->db->select('student_type')
            ->from('users')
            ->where('user_id', $student_id)
            ->get()->row_array();
        $student_type = $student_row['student_type'] ?? 'regular';

        $now = date('Y-m-d H:i:s');
        $new_status = $decision === 'approve' ? 'active' : 'rejected';
        $update_data = ['status' => $new_status];
        if ($decision === 'approve') {
            $update_data['enrolled_at'] = $now;
        }

        $this->db->where('id', $enrollment['id'])->update('classroom_enrollments', $update_data);

        // Log decision
        $this->load->model('Audit_model');
        if (class_exists('Audit_model')) {
            $this->Audit_model->create_log([
                'user_id' => $user_data['user_id'],
                'user_name' => $user_data['full_name'] ?? 'Unknown Teacher',
                'user_role' => 'teacher',
                'action_type' => $decision === 'approve' ? 'class_join_approved' : 'class_join_rejected',
                'module' => 'class',
                'table_name' => 'classroom_enrollments',
                'record_id' => $classroom['id'],
                'details' => json_encode([
                    'class_code' => $classroom['class_code'],
                    'student_id' => $student_id,
                    'decision' => $decision
                ])
            ]);
        }

        // Notify student about the decision
        $this->notify_student_join_request_decision($student_id, $classroom, $decision);

        $message = $decision === 'approve'
            ? 'Join request approved successfully'
            : 'Join request rejected successfully';

        return json_response(true, $message, [
            'student_id' => $student_id,
            'decision' => $decision,
            'status' => $new_status,
            'student_type' => $student_type
        ]);
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
     * Notify students when their join request is approved or rejected
     */
    private function notify_student_join_request_decision($student_id, $classroom, $decision) {
        try {
            $this->load->helper('notification');

            $student = $this->db->select('full_name')
                ->from('users')
                ->where('user_id', $student_id)
                ->get()->row_array();

            if (!$student) {
                log_message('error', "Student not found for join request decision notification: {$student_id}");
                return;
            }

            $class_identifier = $classroom['subject_name'] && $classroom['section_name']
                ? "{$classroom['subject_name']} ({$classroom['section_name']})"
                : ($classroom['title'] ?? $classroom['class_code']);

            if ($decision === 'approve') {
                $title = 'Join Request Approved';
                $message = "Your request to join {$class_identifier} has been approved.";
            } else {
                $title = 'Join Request Rejected';
                $message = "Your request to join {$class_identifier} was rejected.";
            }

            create_system_notification($student_id, $title, $message, false);
        } catch (Exception $e) {
            log_message('error', 'Failed to send join request decision notification: ' . $e->getMessage());
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

    /**
     * Process link attachments from form data
     */
    private function _process_link_attachments_from_form(&$link_attachments) {
        // Check for link attachments in form data
        $link_fields = ['link_0', 'link_1', 'link_2', 'link_3', 'link_4'];
        $youtube_fields = ['youtube_0', 'youtube_1', 'youtube_2', 'youtube_3', 'youtube_4'];
        $gdrive_fields = ['gdrive_0', 'gdrive_1', 'gdrive_2', 'gdrive_3', 'gdrive_4'];
        
        // Process regular links
        foreach ($link_fields as $field) {
            $url = $this->input->post($field);
            if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                $link_attachments[] = [
                    'file_path' => $url,
                    'file_name' => 'link_' . count($link_attachments),
                    'original_name' => 'External Link',
                    'file_size' => null,
                    'mime_type' => 'text/plain',
                    'attachment_type' => 'link',
                    'attachment_url' => $url
                ];
            }
        }
        
        // Process YouTube links
        foreach ($youtube_fields as $field) {
            $url = $this->input->post($field);
            if (!empty($url) && $this->_is_valid_youtube_url($url)) {
                $link_attachments[] = [
                    'file_path' => $url,
                    'file_name' => 'youtube_' . count($link_attachments),
                    'original_name' => 'YouTube Video',
                    'file_size' => null,
                    'mime_type' => 'video/youtube',
                    'attachment_type' => 'youtube',
                    'attachment_url' => $url
                ];
            }
        }
        
        // Process Google Drive links
        foreach ($gdrive_fields as $field) {
            $url = $this->input->post($field);
            if (!empty($url) && $this->_is_valid_google_drive_url($url)) {
                $link_attachments[] = [
                    'file_path' => $url,
                    'file_name' => 'gdrive_' . count($link_attachments),
                    'original_name' => 'Google Drive File',
                    'file_size' => null,
                    'mime_type' => 'application/gdrive',
                    'attachment_type' => 'google_drive',
                    'attachment_url' => $url
                ];
            }
        }
    }

    /**
     * Process link attachments from JSON data
     */
    private function _process_link_attachments_from_json($data, &$link_attachments) {
        // Check for attachments array in JSON
        if (isset($data['attachments']) && is_array($data['attachments'])) {
            foreach ($data['attachments'] as $attachment) {
                if (isset($attachment['type']) && isset($attachment['url'])) {
                    $type = $attachment['type'];
                    $url = $attachment['url'];
                    
                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        $link_attachments[] = [
                            'file_path' => $url,
                            'file_name' => $type . '_' . count($link_attachments),
                            'original_name' => $attachment['title'] ?? ucfirst($type),
                            'file_size' => null,
                            'mime_type' => $this->_get_mime_type_for_attachment_type($type),
                            'attachment_type' => $type,
                            'attachment_url' => $url
                        ];
                    }
                }
            }
        }
    }

    /**
     * Validate YouTube URL
     */
    private function _is_valid_youtube_url($url) {
        $patterns = [
            '/^https?:\/\/(www\.)?youtube\.com\/watch\?v=[a-zA-Z0-9_-]+/',
            '/^https?:\/\/youtu\.be\/[a-zA-Z0-9_-]+/',
            '/^https?:\/\/(www\.)?youtube\.com\/embed\/[a-zA-Z0-9_-]+/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Validate Google Drive URL
     */
    private function _is_valid_google_drive_url($url) {
        $patterns = [
            '/^https?:\/\/drive\.google\.com\/file\/d\/[a-zA-Z0-9_-]+\/view/',
            '/^https?:\/\/drive\.google\.com\/open\?id=[a-zA-Z0-9_-]+/',
            '/^https?:\/\/docs\.google\.com\/[a-zA-Z]+\/d\/[a-zA-Z0-9_-]+/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get MIME type for attachment type
     */
    private function _get_mime_type_for_attachment_type($type) {
        $mime_types = [
            'link' => 'text/plain',
            'youtube' => 'video/youtube',
            'google_drive' => 'application/gdrive'
        ];
        
        return $mime_types[$type] ?? 'text/plain';
    }

    /**
     * Resolve a teacher's program either from the JWT payload or the database.
     */
    private function resolve_teacher_program($user_id, $program_from_token = null) {
        if (!empty($program_from_token)) {
            return trim($program_from_token);
        }

        $teacher = $this->db->select('program')
            ->from('users')
            ->where('user_id', $user_id)
            ->get()
            ->row_array();

        return $teacher['program'] ?? null;
    }

    // ==================== BULK CLASSROOM CREATION ENDPOINTS ====================

    /**
     * Check for duplicate classroom name
     * POST /api/teacher/classrooms/check-duplicate
     */
    public function classrooms_check_duplicate_post() {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }

        if (empty($input['class_name'])) {
            return json_response(false, 'class_name is required', null, 400);
        }

        $class_name = trim($input['class_name']);
        $teacher_id = $user_data['user_id'];

        // Check for active classroom with same name
        $this->db->select('classrooms.*')
            ->from('classrooms')
            ->where('classrooms.teacher_id', $teacher_id)
            ->where('classrooms.title', $class_name);
        
        if ($this->db->field_exists('status', 'classrooms')) {
            $this->db->where("(classrooms.status = 'active' OR classrooms.status IS NULL)", null, false);
        }
        
        $active_classroom = $this->db->get()->row_array();

        if ($active_classroom && ($active_classroom['status'] === 'active' || empty($active_classroom['status']))) {
            return json_response(true, 'Duplicate found', [
                'exists' => true,
                'is_active' => true,
                'classroom' => [
                    'id' => $active_classroom['id'],
                    'class_code' => $active_classroom['class_code'] ?? null,
                    'title' => $active_classroom['title'] ?? null,
                    'status' => $active_classroom['status'] ?? 'active'
                ]
            ], 200);
        }

        // Check for archived/inactive classroom with same name
        $this->db->select('classrooms.*')
            ->from('classrooms')
            ->where('classrooms.teacher_id', $teacher_id)
            ->where('classrooms.title', $class_name);
        
        if ($this->db->field_exists('status', 'classrooms')) {
            $this->db->where("(classrooms.status = 'archived' OR classrooms.status = 'inactive')", null, false);
        }
        
        $archived_classroom = $this->db->get()->row_array();

        if ($archived_classroom) {
            return json_response(true, 'Archived classroom found', [
                'exists' => true,
                'is_active' => false,
                'classroom' => [
                    'id' => $archived_classroom['id'],
                    'class_code' => $archived_classroom['class_code'] ?? null,
                    'title' => $archived_classroom['title'] ?? null,
                    'status' => $archived_classroom['status'] ?? 'archived'
                ]
            ], 200);
        }

        return json_response(true, 'No duplicate found', [
            'exists' => false,
            'is_active' => false,
            'classroom' => null
        ], 200);
    }

    /**
     * Get teacher's assigned programs
     * GET /api/teacher/assigned-programs
     */
    public function assigned_programs_get() {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $teacher_program = $this->resolve_teacher_program($user_data['user_id'], $user_data['program'] ?? null);
        
        if (empty($teacher_program)) {
            return json_response(true, 'No assigned programs', [], 200);
        }

        // Get program details from programs table if it exists
        $this->load->model('Program_model');
        $program = $this->Program_model->get_by_code($teacher_program);
        
        if ($program) {
            $result = [[
                'program' => $program['name'],
                'program_code' => $program['code']
            ]];
        } else {
            // Fallback: use program from user table
            $result = [[
                'program' => $teacher_program,
                'program_code' => $teacher_program
            ]];
        }

        return json_response(true, 'Assigned programs retrieved successfully', $result, 200);
    }

    /**
     * Get all active programs
     * GET /api/teacher/programs
     */
    public function programs_get() {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        try {
            $this->load->model('Program_model');
            $programs = $this->Program_model->get_active();
            
            // Format response for frontend compatibility
            $formatted_programs = array_map(function($program) {
                return [
                    'program_id' => $program['program_id'],
                    'code' => $program['code'],
                    'name' => $program['name'],
                    'description' => $program['description'] ?? null,
                    'program' => $program['code'] // Alias for backward compatibility
                ];
            }, $programs);
            
            return json_response(true, 'Active programs retrieved successfully', $formatted_programs);
        } catch (Exception $e) {
            log_message('error', 'Teacher programs_get error: ' . $e->getMessage());
            return json_response(false, 'Failed to retrieve programs', null, 500);
        }
    }

    /**
     * Get sections by criteria (filtered by teacher's assigned program)
     * GET /api/teacher/sections-by-criteria
     */
    public function sections_by_criteria_get() {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $program = $this->input->get('program');
        $year_levels = $this->input->get('year_levels'); // Can be comma-separated or array
        $academic_year_id = $this->input->get('academic_year_id');

        $teacher_program = $this->resolve_teacher_program($user_data['user_id'], $user_data['program'] ?? null);
        
        if (empty($teacher_program)) {
            return json_response(false, 'Teacher has no assigned program', null, 403);
        }

        $this->load->model('Section_model');

        // Build query
        $this->db->select('sections.section_id, sections.section_name, sections.program, sections.year_level, sections.academic_year, sections.semester,
                          (SELECT COUNT(*) FROM users WHERE users.section_id = sections.section_id AND users.role = "student" AND users.status = "active") as student_count')
            ->from('sections')
            ->where('sections.program', $teacher_program);

        // Filter by requested program (must match teacher's program)
        if (!empty($program) && $program !== $teacher_program) {
            return json_response(false, 'You can only create classes for your assigned program(s)', null, 403);
        }

        // Filter by year levels
        if (!empty($year_levels)) {
            if (is_string($year_levels)) {
                $year_levels = array_map('trim', explode(',', $year_levels));
            }
            if (is_array($year_levels) && !empty($year_levels)) {
                $this->db->where_in('sections.year_level', $year_levels);
            }
        }

        // Filter by academic year
        if (!empty($academic_year_id)) {
            if (is_numeric($academic_year_id) && $this->db->field_exists('academic_year_id', 'sections')) {
                $this->db->where('sections.academic_year_id', (int)$academic_year_id);
            } else {
                // Allow requesting by actual academic year value (e.g., "AY 2025-2026")
                $this->db->group_start()
                    ->where('sections.academic_year', $academic_year_id);
                if ($this->db->field_exists('academic_year_id', 'sections')) {
                    $this->db->or_where('sections.academic_year_id', $academic_year_id);
                }
                $this->db->group_end();
            }
        }

        // Only active sections
        if ($this->db->field_exists('is_archived', 'sections')) {
            $this->db->where('sections.is_archived', 0);
        }

        $sections = $this->db->order_by('sections.year_level', 'ASC')
            ->order_by('sections.section_name', 'ASC')
            ->get()->result_array();

        $result = [];
        foreach ($sections as $section) {
            $result[] = [
                'id' => $section['section_id'],
                'name' => $section['section_name'],
                'program' => $section['program'],
                'year_level' => $section['year_level'],
                'student_count' => (int)$section['student_count']
            ];
        }

        return json_response(true, 'Sections retrieved successfully', $result, 200);
    }

    /**
     * Bulk create classroom with multiple sections
     * POST /api/teacher/classrooms/bulk-create
     */
    public function classrooms_bulk_create_post() {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $this->load->helper('notification');
        $this->load->model('Classroom_model');
        $this->load->model('Section_model');

        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }

        // Validate required fields
        $required = ['class_name', 'program', 'section_ids', 'semester', 'school_year'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                return json_response(false, "$field is required", null, 400);
            }
        }

        $class_name = trim($input['class_name']);
        $program = trim($input['program']);
        $section_ids = is_array($input['section_ids']) ? $input['section_ids'] : explode(',', $input['section_ids']);
        $semester = $input['semester'];
        $school_year = $input['school_year'];
        $academic_year_id = $input['academic_year_id'] ?? null;
        $teacher_id = $user_data['user_id'];
        $teacher_program = $this->resolve_teacher_program($teacher_id, $user_data['program'] ?? null);

        // Check teacher's assigned program
        if (empty($teacher_program) || $program !== $teacher_program) {
            return json_response(false, 'You can only create classes for your assigned program(s)', null, 403);
        }

        // Check for duplicate active classroom
        $this->db->select('classrooms.*')
            ->from('classrooms')
            ->where('classrooms.teacher_id', $teacher_id)
            ->where('classrooms.title', $class_name);
        
        // Handle different status values (active/inactive or active/archived)
        if ($this->db->field_exists('status', 'classrooms')) {
            $this->db->where("(classrooms.status = 'active' OR classrooms.status IS NULL)", null, false);
        }
        
        $existing_active = $this->db->get()->row_array();

        if ($existing_active && ($existing_active['status'] === 'active' || empty($existing_active['status']))) {
            return json_response(false, "A classroom named {$class_name} already exists. Please archive it first before creating a new one with the same name.", null, 409);
        }

        // Check for archived/inactive classroom (re-tagging)
        $this->db->select('classrooms.*')
            ->from('classrooms')
            ->where('classrooms.teacher_id', $teacher_id)
            ->where('classrooms.title', $class_name);
        
        if ($this->db->field_exists('status', 'classrooms')) {
            $this->db->where("(classrooms.status = 'archived' OR classrooms.status = 'inactive')", null, false);
        }
        
        $existing_archived = $this->db->get()->row_array();

        $classroom_id = null;
        $is_retagging = false;

        if ($existing_archived) {
            // Re-tagging: reactivate and update
            $classroom_id = $existing_archived['id'];
            $is_retagging = true;
            
            $update_data = [
                'semester' => $semester,
                'school_year' => $school_year
            ];
            
            // Update status if field exists
            if ($this->db->field_exists('status', 'classrooms')) {
                $update_data['status'] = 'active';
            }
            
            // Update academic_year_id if field exists
            if ($academic_year_id && $this->db->field_exists('academic_year_id', 'classrooms')) {
                $update_data['academic_year_id'] = $academic_year_id;
            }
            
            if ($this->db->field_exists('updated_at', 'classrooms')) {
                $update_data['updated_at'] = date('Y-m-d H:i:s');
            }
            
            $this->Classroom_model->update($classroom_id, $update_data);
        } else {
            // Create new classroom
            $classroom_data = [
                'teacher_id' => $teacher_id,
                'title' => $class_name,
                'semester' => $semester,
                'school_year' => $school_year,
                'subject_id' => $input['subject_id'] ?? null, // Optional
                'section_id' => $section_ids[0] ?? null // Primary section (for compatibility)
            ];
            
            // Add optional fields if they exist in the table
            if ($this->db->field_exists('status', 'classrooms')) {
                $classroom_data['status'] = 'active';
            }
            if ($this->db->field_exists('program', 'classrooms')) {
                $classroom_data['program'] = $program;
            }
            if ($academic_year_id && $this->db->field_exists('academic_year_id', 'classrooms')) {
                $classroom_data['academic_year_id'] = $academic_year_id;
            }
            
            $classroom_id = $this->Classroom_model->insert($classroom_data);
        }

        if (!$classroom_id) {
            return json_response(false, 'Failed to create or update classroom', null, 500);
        }

        // Get classroom for class_code
        $classroom = $this->Classroom_model->get_by_id($classroom_id);
        $class_code = $classroom['class_code'] ?? null;

        // Process each section
        $summary = [
            'students_added' => 0,
            'students_skipped' => 0,
            'sections_processed' => 0
        ];
        $details = [];

        foreach ($section_ids as $section_id) {
            $section = $this->Section_model->get_by_id($section_id);
            if (!$section) {
                continue;
            }

            // Verify section belongs to teacher's program
            if ($section['program'] !== $teacher_program) {
                continue; // Skip unauthorized sections
            }

            // Get active students in this section
            $students = $this->db->select('user_id, full_name, email, program')
                ->from('users')
                ->where('section_id', $section_id)
                ->where('role', 'student')
                ->where('status', 'active')
                ->get()->result_array();

            $section_added = 0;
            $section_skipped = 0;

            foreach ($students as $student) {
                // Check program match
                if ($student['program'] !== $teacher_program) {
                    continue; // Skip students from other programs
                }

                // Check if already enrolled
                $existing_enrollment = $this->db->select('id')
                    ->from('classroom_enrollments')
                    ->where('classroom_id', $classroom_id)
                    ->where('student_id', $student['user_id'])
                    ->where('status', 'active')
                    ->get()->row_array();

                if ($existing_enrollment) {
                    $section_skipped++;
                    continue;
                }

                // Enroll student
                $enrollment_data = [
                    'classroom_id' => $classroom_id,
                    'student_id' => $student['user_id'],
                    'enrolled_at' => date('Y-m-d H:i:s'),
                    'status' => 'active'
                ];

                $this->db->insert('classroom_enrollments', $enrollment_data);

                if ($this->db->affected_rows() > 0) {
                    $section_added++;
                    
                    // Send notification
                    create_notification(
                        $student['user_id'],
                        'classroom',
                        'Added to Class',
                        "You have been added to the class {$class_name}.",
                        $classroom_id,
                        'classroom',
                        $class_code,
                        false
                    );
                }
            }

            $summary['students_added'] += $section_added;
            $summary['students_skipped'] += $section_skipped;
            $summary['sections_processed']++;

            $details["section_{$section_id}"] = [
                'name' => $section['section_name'],
                'students_added' => $section_added,
                'students_skipped' => $section_skipped
            ];
        }

        return json_response(true, $is_retagging ? 'Classroom re-tagged and students enrolled successfully' : 'Classroom created and students enrolled successfully', [
            'classroom_id' => $classroom_id,
            'class_code' => $class_code,
            'summary' => $summary,
            'details' => $details
        ], 201);
    }

    /**
     * Bulk add students to existing classroom (re-tagging)
     * POST /api/teacher/classrooms/bulk-add-students
     */
    public function classrooms_bulk_add_students_post() {
        $user_data = require_teacher($this);
        if (!$user_data) return;

        $this->load->helper('notification');
        $this->load->model('Classroom_model');
        $this->load->model('Section_model');

        $input = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json_response(false, 'Invalid JSON format', null, 400);
        }

        if (empty($input['classroom_id']) || empty($input['section_ids'])) {
            return json_response(false, 'classroom_id and section_ids are required', null, 400);
        }

        $classroom_id = $input['classroom_id'];
        $section_ids = is_array($input['section_ids']) ? $input['section_ids'] : explode(',', $input['section_ids']);
        $teacher_id = $user_data['user_id'];
        $teacher_program = $this->resolve_teacher_program($teacher_id, $user_data['program'] ?? null);

        // Verify classroom exists and belongs to teacher
        $classroom = $this->Classroom_model->get_by_id($classroom_id);
        if (!$classroom || $classroom['teacher_id'] !== $teacher_id) {
            return json_response(false, 'Classroom not found or access denied', null, 404);
        }

        $class_name = $classroom['title'] ?? 'Unknown Class';
        $class_code = $classroom['class_code'] ?? null;

        // Process each section
        $summary = [
            'students_added' => 0,
            'students_skipped' => 0,
            'sections_processed' => 0
        ];
        $details = [];

        foreach ($section_ids as $section_id) {
            $section = $this->Section_model->get_by_id($section_id);
            if (!$section) {
                continue;
            }

            // Verify section belongs to teacher's program
            if ($section['program'] !== $teacher_program) {
                continue; // Skip unauthorized sections
            }

            // Get active students in this section
            $students = $this->db->select('user_id, full_name, email, program')
                ->from('users')
                ->where('section_id', $section_id)
                ->where('role', 'student')
                ->where('status', 'active')
                ->get()->result_array();

            $section_added = 0;
            $section_skipped = 0;

            foreach ($students as $student) {
                // Check program match
                if ($student['program'] !== $teacher_program) {
                    continue; // Skip students from other programs
                }

                // Check if already enrolled
                $existing_enrollment = $this->db->select('id')
                    ->from('classroom_enrollments')
                    ->where('classroom_id', $classroom_id)
                    ->where('student_id', $student['user_id'])
                    ->where('status', 'active')
                    ->get()->row_array();

                if ($existing_enrollment) {
                    $section_skipped++;
                    continue;
                }

                // Enroll student
                $enrollment_data = [
                    'classroom_id' => $classroom_id,
                    'student_id' => $student['user_id'],
                    'enrolled_at' => date('Y-m-d H:i:s'),
                    'status' => 'active'
                ];

                $this->db->insert('classroom_enrollments', $enrollment_data);

                if ($this->db->affected_rows() > 0) {
                    $section_added++;
                    
                    // Send notification
                    create_notification(
                        $student['user_id'],
                        'classroom',
                        'Added to Class',
                        "You have been added to the class {$class_name}.",
                        $classroom_id,
                        'classroom',
                        $class_code,
                        false
                    );
                }
            }

            $summary['students_added'] += $section_added;
            $summary['students_skipped'] += $section_skipped;
            $summary['sections_processed']++;

            $details["section_{$section_id}"] = [
                'name' => $section['section_name'],
                'students_added' => $section_added,
                'students_skipped' => $section_skipped
            ];
        }

        return json_response(true, 'Students added to classroom successfully', [
            'classroom_id' => $classroom_id,
            'class_code' => $class_code,
            'summary' => $summary,
            'details' => $details
        ], 200);
    }
}
