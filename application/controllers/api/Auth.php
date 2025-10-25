<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends BaseController {

    public function __construct() {
        parent::__construct();
        error_reporting(0);
        $this->load->model('User_model');
        $this->load->helper(['response', 'auth', 'audit', 'notification']);
        $this->load->library(['Token_lib', 'session']);
    }

    public function login() {
        $data = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
            return;
        }

        log_message('debug', 'Incoming login data: ' . json_encode($data));

        $email = isset($data->email) ? $data->email : null;
        $password = isset($data->password) ? $data->password : null;

        if (empty($email) || empty($password)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Email and Password are required']));
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid email format']));
            return;
        }

        $user = $this->User_model->get_by_email($email);
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $this->output
                    ->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Account is inactive. Please contact administrator.']));
                return;
            }

            // Update last_login
            $this->User_model->update($user['user_id'], [
                'last_login' => date('Y-m-d H:i:s')
            ]);

            // Log successful login to audit
            $user_data = [
                'user_id' => $user['user_id'],
                'name' => $user['full_name'],
                'username' => $user['email'],
                'role' => $user['role']
            ];
            log_user_login($user_data);

            // Generate JWT token
            $token_payload = [
                'user_id' => $user['user_id'],
                'role' => $user['role'],
                'email' => $user['email'],
                'full_name' => $user['full_name']
            ];
            $token = $this->token_lib->generate_token($token_payload);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'role' => $user['role'],
                        'user_id' => $user['user_id'],
                        'full_name' => $user['full_name'],
                        'email' => $user['email'],
                        'status' => $user['status'],
                        'last_login' => date('Y-m-d H:i:s'),
                        'token' => $token,
                        'token_type' => 'Bearer',
                        'expires_in' => $this->token_lib->get_expiration_time()
                    ]
                ]));
            return;
        }

        // Log failed login attempt
        log_audit_event(
            'FAILED LOGIN',
            'AUTHENTICATION',
            "Failed login attempt for email: {$email}",
            [
                'ip_address' => $this->input->ip_address(),
                'user_agent' => $this->input->user_agent()
            ]
        );

        $this->output
            ->set_status_header(401)
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => false, 'message' => 'Invalid email or password']));
    }

    public function register() {
        // Check if request is multipart/form-data or JSON
        $content_type = $this->input->server('CONTENT_TYPE');
        $is_multipart = strpos($content_type, 'multipart/form-data') !== false;
        
        if ($is_multipart) {
            // Handle multipart/form-data (with images)
            $this->register_with_images();
        } else {
            // Handle JSON request (existing code)
            $this->register_json();
        }
    }

    private function register_with_images() {
        try {
            // Get form data
            $role = $this->input->post('role');
            $full_name = $this->input->post('full_name');
            $email = $this->input->post('email');
            $password = $this->input->post('password');
            $contact_num = $this->input->post('contact_num');
            $address = $this->input->post('address');
            $program = $this->input->post('program');
            $student_num = $this->input->post('student_num');
            $section_id = $this->input->post('section_id');
            $qr_code = $this->input->post('qr_code');

            // Debug logging
            log_message('debug', '=== REGISTER WITH IMAGES DEBUG ===');
            log_message('debug', 'Role: ' . $role);
            log_message('debug', 'Full Name: ' . $full_name);
            log_message('debug', 'Email: ' . $email);
            log_message('debug', 'Contact: ' . $contact_num);
            log_message('debug', 'Address: ' . $address);
            log_message('debug', 'Program: ' . $program);
            log_message('debug', 'Student Num: ' . $student_num);
            log_message('debug', 'Section ID: ' . $section_id);
            log_message('debug', 'QR Code: ' . $qr_code);
            log_message('debug', '================================');

            // Validate required fields
            if (empty($role) || empty($full_name) || empty($email) || empty($password)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Required fields are missing']));
                return;
            }
            
            // Validate program based on role
            if ($role === 'student' && empty($program)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Program is required for student accounts']));
                return;
            }

            // Block chairperson creation
            if (strtolower($role) === 'chairperson') {
                $this->output
                    ->set_status_header(403)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Chairperson creation is not allowed']));
                return;
            }

            // Check if email already exists
            $existing_user = $this->User_model->get_by_email($email);
            if ($existing_user) {
                $this->output
                    ->set_status_header(409)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'User with this email already exists!']));
                return;
            }

            // Handle profile image upload
            $profile_pic_path = null;
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                $profile_pic_path = $this->upload_image($_FILES['profile_pic'], 'profile');
            }

            // Handle cover image upload
            $cover_pic_path = null;
            if (isset($_FILES['cover_pic']) && $_FILES['cover_pic']['error'] == 0) {
                $cover_pic_path = $this->upload_image($_FILES['cover_pic'], 'cover');
            }

            // Validate program if provided
            if (!empty($program)) {
                log_message('debug', 'Original program received: "' . $program . '"');
                $program_shortcut = $this->standardize_program_name($program);
                log_message('debug', 'Standardized program: "' . ($program_shortcut ?: 'NULL') . '"');
                
                if (!$program_shortcut) {
                    log_message('error', 'Invalid program provided: "' . $program . '"');
                    $this->output
                        ->set_status_header(400)
                        ->set_content_type('application/json')
                        ->set_output(json_encode(['status' => false, 'message' => 'Invalid program. Must be BSIT, BSIS, BSCS, or ACT']));
                    return;
                }
                $program = $program_shortcut; // Use standardized program name
            }

            // Prepare user data
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $user_id = generate_user_id(strtoupper(substr($role, 0, 3)));
            
            $user_data = [
                'user_id' => $user_id,
                'role' => $role,
                'full_name' => $full_name,
                'email' => $email,
                'password' => $hashed_password,
                'contact_num' => $contact_num,
                'address' => $address,
                'profile_pic' => $profile_pic_path,
                'cover_pic' => $cover_pic_path,
                'status' => 'active',
                'last_login' => null
            ];

            // Add role-specific fields
            if ($role === 'student') {
                if (empty($student_num) || empty($qr_code)) {
                    $this->output
                        ->set_status_header(400)
                        ->set_content_type('application/json')
                        ->set_output(json_encode(['status' => false, 'message' => 'Student number and qr_code are required for student accounts.']));
                    return;
                }
                $user_data['student_num'] = $student_num;
                $user_data['qr_code'] = $qr_code;
                
                // Add section_id only if provided
                if (!empty($section_id)) {
                    $user_data['section_id'] = $section_id;
                }
                
                // Students must have a program - it should already be validated above
                $user_data['program'] = $program;
            } elseif ($role === 'admin') {
                // Set admin_type for admin users
                $user_data['admin_type'] = 'main_admin';
                // Admin users can have a program if specified and valid
                if (!empty($program)) {
                    $user_data['program'] = $program;
                } else {
                    $user_data['program'] = null;
                }
            } elseif ($role === 'teacher') {
                // Teachers can have a program if specified and valid
                if (!empty($program)) {
                    $user_data['program'] = $program;
                } else {
                    $user_data['program'] = null;
                }
            }

            // Debug final data
            log_message('debug', '=== FINAL USER DATA ===');
            log_message('debug', 'Role: ' . $role);
            log_message('debug', 'Program after validation: ' . ($program ?: 'NULL'));
            log_message('debug', 'Final program in user_data: ' . ($user_data['program'] ?: 'NULL'));
            log_message('debug', print_r($user_data, true));
            log_message('debug', '=====================');

            // Insert user into database
            if ($this->User_model->insert($user_data)) {
                // Send welcome system notification
                $this->send_welcome_notification($user_id, $full_name, $role, $email);
                
                $this->output
                    ->set_status_header(201)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => true,
                        'message' => ucfirst($role) . ' registered successfully!',
                        'data' => [
                            'user_id' => $user_id,
                            'role' => $role,
                            'full_name' => $full_name,
                            'email' => $email,
                            'profile_pic' => $profile_pic_path,
                            'cover_pic' => $cover_pic_path
                        ]
                    ]));
            } else {
                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => ucfirst($role) . ' registration failed!']));
            }

        } catch (Exception $e) {
            log_message('error', 'Registration error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Registration failed: ' . $e->getMessage()]));
        }
    }

    private function upload_image($file, $type) {
        $upload_path = FCPATH . 'uploads/' . $type . '/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $type . '_' . uniqid() . '.' . $file_extension;
        $full_path = $upload_path . $filename;

        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array(strtolower($file_extension), $allowed_types)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
        }

        // Validate file size (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum 5MB allowed.');
        }

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $full_path)) {
            return 'uploads/' . $type . '/' . $filename;
        } else {
            throw new Exception('Failed to upload file');
        }
    }

    private function register_json() {
        $data = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
            return;
        }

        log_message('debug', 'Incoming register data: ' . json_encode($data));
        
        // Debug: Log profile and cover pic data
        if (isset($data->profile_pic)) {
            log_message('debug', 'Profile pic received: ' . $data->profile_pic);
        }
        if (isset($data->cover_pic)) {
            log_message('debug', 'Cover pic received: ' . $data->cover_pic);
        }

        $role = isset($data->role) ? strtolower($data->role) : null;
        $full_name = isset($data->full_name) ? $data->full_name : null;
        $email = isset($data->email) ? $data->email : null;
        $password = isset($data->password) ? $data->password : null;
        $program = isset($data->program) ? $data->program : null;
        $contact_num = isset($data->contact_num) ? $data->contact_num : null;
        $address = isset($data->address) ? $data->address : null;
        $errors = [];

        if (empty($role)) {
            $errors[] = 'Role is required.';
        } elseif (strtolower($role) === 'chairperson') {
            $errors[] = 'Chairperson creation is not allowed.';
        }
        if (empty($full_name)) {
            $errors[] = 'Full name is required.';
        }
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if (empty($contact_num)) {
            $errors[] = 'Contact number is required.';
        }
        if (empty($address)) {
            $errors[] = 'Address is required.';
        }
        
        // Validate program based on role
        if ($role === 'student' && empty($program)) {
            $errors[] = 'Program is required for student accounts.';
        }

        if (!empty($errors)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => implode(' ', $errors)]));
            return;
        }

        // Check if user already exists
        $existing_user = $this->User_model->get_by_email($email);
        if ($existing_user) {
            $this->output
                ->set_status_header(409)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'User with this email already exists!']));
            return;
        }

        // Validate program if provided
        if (!empty($program)) {
            log_message('debug', 'JSON - Original program received: "' . $program . '"');
            $program_shortcut = $this->standardize_program_name($program);
            log_message('debug', 'JSON - Standardized program: "' . ($program_shortcut ?: 'NULL') . '"');
            
            if (!$program_shortcut) {
                log_message('error', 'JSON - Invalid program provided: "' . $program . '"');
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Invalid program. Must be BSIT, BSIS, BSCS, or ACT']));
                return;
            }
            $program = $program_shortcut; // Use standardized program name
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $user_id = generate_user_id(strtoupper(substr($role, 0, 3)));
        $dataToInsert = [
            'user_id' => $user_id,
            'role' => $role,
            'full_name' => $full_name,
            'email' => $email,
            'password' => $hashed_password,
            'contact_num' => $contact_num,
            'address' => $address,
            'status' => 'active',
            'last_login' => null,
            'profile_pic' => isset($data->profile_pic) ? $data->profile_pic : null,
            'cover_pic' => isset($data->cover_pic) ? $data->cover_pic : null
        ];

        // Role-specific fields
        if ($role === 'student') {
            if (empty($data->student_num) || empty($data->qr_code)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Student number and qr_code are required for student accounts.']));
                return;
            }
            $dataToInsert['student_num'] = $data->student_num;
            $dataToInsert['qr_code'] = $data->qr_code;
            
            // Add section_id only if provided
            if (!empty($data->section_id)) {
                $dataToInsert['section_id'] = $data->section_id;
            }
            
            // Students must have a program - it should already be validated above
            $dataToInsert['program'] = $program;
        } elseif ($role === 'admin') {
            // Set admin_type for admin users
            $dataToInsert['admin_type'] = 'main_admin';
            // Admin users can have a program if specified and valid
            if (!empty($program)) {
                $dataToInsert['program'] = $program;
            } else {
                $dataToInsert['program'] = null;
            }
        } elseif ($role === 'teacher') {
            // Teachers can have a program if specified and valid
            if (!empty($program)) {
                $dataToInsert['program'] = $program;
            } else {
                $dataToInsert['program'] = null;
            }
        }

        // Debug: Log the final data being inserted
        log_message('debug', '=== JSON REGISTRATION DEBUG ===');
        log_message('debug', 'Role: ' . $role);
        log_message('debug', 'Program after validation: ' . ($program ?: 'NULL'));
        log_message('debug', 'Final program in dataToInsert: ' . ($dataToInsert['program'] ?: 'NULL'));
        log_message('debug', 'Data to insert: ' . json_encode($dataToInsert));
        log_message('debug', '================================');
        
        if ($this->User_model->insert($dataToInsert)) {
            $this->output
                ->set_status_header(201)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => ucfirst($role) . ' registered successfully!',
                    'data' => ['user_id' => $user_id]
                ]));
        } else {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => ucfirst($role) . ' registration failed!']));
        }
    }

    /**
     * Standardize program name to shortcut format
     * 
     * @param string $program_name The program name (can be full name or shortcut)
     * @return string|false The standardized shortcut or false if invalid
     */
    private function standardize_program_name($program_name) {
        $program_name = trim($program_name);
        log_message('debug', 'Standardizing program: "' . $program_name . '"');
        
        // Direct shortcuts
        $shortcuts = ['BSIT', 'BSIS', 'BSCS', 'ACT'];
        if (in_array(strtoupper($program_name), $shortcuts)) {
            log_message('debug', 'Found direct shortcut match: ' . strtoupper($program_name));
            return strtoupper($program_name);
        }
        
        // Map full names to shortcuts
        $full_to_short = [
            'Bachelor of Science in Information Technology' => 'BSIT',
            'Bachelor of Science in Information Systems' => 'BSIS',
            'Bachelor of Science in Information System' => 'BSIS', // Handle singular "System"
            'Bachelor of Science in Computer Science' => 'BSCS',
            'Associate in Computer Technology' => 'ACT'
        ];
        
        // Check exact matches
        if (isset($full_to_short[$program_name])) {
            log_message('debug', 'Found exact full name match: ' . $full_to_short[$program_name]);
            return $full_to_short[$program_name];
        }
        
        // Check case-insensitive matches
        foreach ($full_to_short as $full_name => $shortcut) {
            if (strcasecmp($program_name, $full_name) === 0) {
                log_message('debug', 'Found case-insensitive match: ' . $shortcut);
                return $shortcut;
            }
        }
        
        // Check partial matches (for flexibility)
        $program_lower = strtolower($program_name);
        if (strpos($program_lower, 'information technology') !== false) {
            log_message('debug', 'Found partial match for IT: BSIT');
            return 'BSIT';
        } elseif (strpos($program_lower, 'information system') !== false) {
            log_message('debug', 'Found partial match for IS: BSIS');
            return 'BSIS'; // Handles both "System" and "Systems"
        } elseif (strpos($program_lower, 'computer science') !== false) {
            log_message('debug', 'Found partial match for CS: BSCS');
            return 'BSCS';
        } elseif (strpos($program_lower, 'computer technology') !== false) {
            log_message('debug', 'Found partial match for CT: ACT');
            return 'ACT';
        }
        
        log_message('debug', 'No match found for program: "' . $program_name . '"');
        return false; // Invalid program name
    }

    // Get all users by role
    public function get_users() {
        // Require authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }
        
        $role = $this->input->get('role'); // admin, teacher, student
        
        if (empty($role)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Role parameter is required']));
            return;
        }

        $role = strtolower($role);
        $users = $this->User_model->get_all($role);

        // Add formatted year information for students
        if ($role === 'student' && is_array($users)) {
            foreach ($users as &$user) {
                if (isset($user['year_level'])) {
                    $user['year'] = $this->format_year_level($user['year_level']);
                }
            }
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Users retrieved successfully',
                'data' => $users
            ]));
    }

    // Get user by ID
    public function get_user() {
        // Require authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }
        
        $role = $this->input->get('role'); // admin, teacher, student
        $user_id = $this->input->get('user_id');
        
        if (empty($role) || empty($user_id)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Role and user_id parameters are required']));
            return;
        }

        $role = strtolower($role);
        $user = $this->User_model->get_by_id($user_id);
        if (!$user || $user['role'] !== $role) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
            return;
        }

        // Add formatted year information for students
        if ($user && $user['role'] === 'student' && isset($user['year_level'])) {
            $user['year'] = $this->format_year_level($user['year_level']);
        }

        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'User retrieved successfully',
                'data' => $user
            ]));
    }

    // Update user
    public function update_user() {
        // Require authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }
        
        // Check if this is a multipart form request (for file uploads)
        if ($this->input->method() === 'post' && !empty($_FILES)) {
            $this->update_user_with_images();
            return;
        }
        
        // Handle JSON request
        $this->update_user_json();
    }
    
    /**
     * Format year level to readable format
     * @param string $year_level
     * @return string
     */
    private function format_year_level($year_level) {
        if (empty($year_level)) {
            return null;
        }
        
        // Handle different year level formats
        $year_level = trim($year_level);
        
        // If it's already in ordinal format, return as is
        if (preg_match('/^\d+(st|nd|rd|th)\s+year$/i', $year_level)) {
            return $year_level;
        }
        
        // If it's just a number, convert to ordinal
        if (is_numeric($year_level)) {
            $number = (int)$year_level;
            switch ($number) {
                case 1:
                    return '1st year';
                case 2:
                    return '2nd year';
                case 3:
                    return '3rd year';
                case 4:
                    return '4th year';
                case 5:
                    return '5th year';
                default:
                    return $number . 'th year';
            }
        }
        
        // If it contains a number, extract and format it
        if (preg_match('/(\d+)/', $year_level, $matches)) {
            $number = (int)$matches[1];
            switch ($number) {
                case 1:
                    return '1st year';
                case 2:
                    return '2nd year';
                case 3:
                    return '3rd year';
                case 4:
                    return '4th year';
                case 5:
                    return '5th year';
                default:
                    return $number . 'th year';
            }
        }
        
        // Return original if no pattern matches
        return $year_level;
    }
    
    private function update_user_with_images() {
        try {
            // Get form data
            $role = $this->input->post('role');
            $user_id = $this->input->post('user_id');
            
            if (empty($role) || empty($user_id)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Role and user_id are required']));
                return;
            }
            
            // Check if user exists
            $user = $this->User_model->get_by_id($user_id);
            if (!$user || $user['role'] !== $role) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
                return;
            }
            
            $update_data = [];
            
            // Handle profile image upload
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
                try {
                    $profile_pic_path = $this->upload_image($_FILES['profile_pic'], 'profile');
                    $update_data['profile_pic'] = $profile_pic_path;
                } catch (Exception $e) {
                    $this->output
                        ->set_status_header(400)
                        ->set_content_type('application/json')
                        ->set_output(json_encode(['status' => false, 'message' => 'Profile image upload failed: ' . $e->getMessage()]));
                    return;
                }
            }
            
            // Handle cover image upload
            if (isset($_FILES['cover_pic']) && $_FILES['cover_pic']['error'] == 0) {
                try {
                    $cover_pic_path = $this->upload_image($_FILES['cover_pic'], 'cover');
                    $update_data['cover_pic'] = $cover_pic_path;
                } catch (Exception $e) {
                    $this->output
                        ->set_status_header(400)
                        ->set_content_type('application/json')
                        ->set_output(json_encode(['status' => false, 'message' => 'Cover image upload failed: ' . $e->getMessage()]));
                    return;
                }
            }
            
            // Handle other form fields
            if ($this->input->post('full_name')) $update_data['full_name'] = $this->input->post('full_name');
            if ($this->input->post('email')) $update_data['email'] = $this->input->post('email');
            if ($this->input->post('password')) $update_data['password'] = password_hash($this->input->post('password'), PASSWORD_BCRYPT);
            if ($this->input->post('program')) $update_data['program'] = $this->input->post('program');
            if ($this->input->post('contact_num')) $update_data['contact_num'] = $this->input->post('contact_num');
            if ($this->input->post('address')) $update_data['address'] = $this->input->post('address');
            
            // Status field with validation
            if ($this->input->post('status')) {
                $new_status = strtolower($this->input->post('status'));
                if ($new_status !== 'active' && $new_status !== 'inactive') {
                    $this->output
                        ->set_status_header(400)
                        ->set_content_type('application/json')
                        ->set_output(json_encode(['status' => false, 'message' => 'Status must be either "active" or "inactive"']));
                    return;
                }
                $update_data['status'] = $new_status;
            }
            
            // Student-specific fields
            if ($role === 'student') {
                if ($this->input->post('student_num')) $update_data['student_num'] = $this->input->post('student_num');
                if ($this->input->post('section_id')) $update_data['section_id'] = $this->input->post('section_id');
                if ($this->input->post('qr_code')) $update_data['qr_code'] = $this->input->post('qr_code');
            }
            
            if (empty($update_data)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'No data provided for update']));
                return;
            }
            
            $success = $this->User_model->update($user_id, $update_data);
            if ($success) {
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => true, 'message' => 'User updated successfully']));
            } else {
                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Failed to update user']));
            }
            
        } catch (Exception $e) {
            log_message('error', 'Update user error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Update failed: ' . $e->getMessage()]));
        }
    }
    
    private function update_user_json() {
        $data = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
            return;
        }

        $role = isset($data->role) ? strtolower($data->role) : null;
        $user_id = isset($data->user_id) ? $data->user_id : null;
        
        if (empty($role) || empty($user_id)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Role and user_id are required']));
            return;
        }

        $update_data = [];
        // Common fields
        if (isset($data->full_name)) $update_data['full_name'] = $data->full_name;
        if (isset($data->email)) $update_data['email'] = $data->email;
        if (isset($data->password)) $update_data['password'] = password_hash($data->password, PASSWORD_BCRYPT);
        if (isset($data->program)) $update_data['program'] = $data->program;
        if (isset($data->contact_num)) $update_data['contact_num'] = $data->contact_num;
        if (isset($data->address)) $update_data['address'] = $data->address;
        if (isset($data->profile_pic)) $update_data['profile_pic'] = $data->profile_pic;
        if (isset($data->cover_pic)) $update_data['cover_pic'] = $data->cover_pic;
        
        // Status field with validation
        if (isset($data->status)) {
            $new_status = strtolower($data->status);
            if ($new_status !== 'active' && $new_status !== 'inactive') {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Status must be either "active" or "inactive"']));
                return;
            }
            $update_data['status'] = $new_status;
        }
        
        // Student-specific fields
        if ($role === 'student') {
            if (isset($data->student_num)) $update_data['student_num'] = $data->student_num;
            if (isset($data->section_id)) $update_data['section_id'] = $data->section_id;
            if (isset($data->qr_code)) $update_data['qr_code'] = $data->qr_code;
        }

        if (empty($update_data)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'No data provided for update']));
            return;
        }

        $user = $this->User_model->get_by_id($user_id);
        if (!$user || $user['role'] !== $role) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
            return;
        }

        $success = $this->User_model->update($user_id, $update_data);
        if ($success) {
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => true, 'message' => 'User updated successfully']));
        } else {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to update user']));
        }
    }

    // Delete user
    public function delete_user() {
        // Require authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }
        
        $data = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
            return;
        }

        $role = isset($data->role) ? strtolower($data->role) : null;
        $user_id = isset($data->user_id) ? $data->user_id : null;
        
        if (empty($role) || empty($user_id)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Role and user_id are required']));
            return;
        }

        $user = $this->User_model->get_by_id($user_id);
        if (!$user || $user['role'] !== $role) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
            return;
        }
        
        // Optional force flag: delete dependent audit logs before deleting user
        $force_param = $this->input->get('force');
        $force_body = isset($data->force) ? $data->force : null;
        $force = ($force_param === 'true' || $force_param === '1' || $force_body === true || $force_body === 1);

        // Temporarily disable CI DB debug to prevent HTML error page on FK violation
        $original_db_debug = $this->db->db_debug;
        $this->db->db_debug = FALSE;

        // Use a transaction for safety
        $this->db->trans_begin();

        // When forcing, remove audit log references first to satisfy FK constraints
        if ($force) {
            $this->db->where('user_id', $user_id)->delete('audit_logs');
        }

        // Attempt deletion
        $this->User_model->delete($user_id);
        $db_error = $this->db->error();

        if ((int)$db_error['code'] === 0 && $this->db->trans_status() !== FALSE) {
            $this->db->trans_commit();
            // Restore original db_debug setting
            $this->db->db_debug = $original_db_debug;
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => true, 'message' => 'User deleted successfully']))
                ;
            return;
        }

        // Deletion failed: rollback so we can decide next step
        $this->db->trans_rollback();
        // Restore original db_debug setting before further operations
        $this->db->db_debug = $original_db_debug;

        if (!empty($db_error) && isset($db_error['code']) && (int)$db_error['code'] !== 0) {
            // Handle foreign key constraint - fall back to soft delete to preserve logs
            if ((int)$db_error['code'] === 1451) {
                // Soft delete: deactivate account instead of removing the row
                $this->User_model->update($user_id, ['status' => 'inactive']);
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => true,
                        'message' => 'User has linked records. Account deactivated instead of deleted.',
                        'data' => [ 'soft_deleted' => true ]
                    ]));
                return;
            }
            
            // Unknown DB error
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to delete user: ' . $db_error['message']]));
            return;
        }
        
        // If we reach here, treat as generic failure
        $this->output
            ->set_status_header(500)
            ->set_content_type('application/json')
            ->set_output(json_encode(['status' => false, 'message' => 'Failed to delete user']));
    }

    // Admin method to change user status
    public function change_user_status() {
        // Require admin authentication
        $user_data = require_admin($this);
        if (!$user_data) {
            return; // Error response already sent
        }
        
        $data = json_decode(file_get_contents('php://input'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
            return;
        }

        $target_role = isset($data->target_role) ? strtolower($data->target_role) : null;
        $user_id = isset($data->user_id) ? $data->user_id : null;
        $new_status = isset($data->status) ? strtolower($data->status) : null;
        
        if (empty($target_role) || empty($user_id) || empty($new_status)) {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Target role, user_id, and status are required']));
            return;
        }

        if ($new_status !== 'active' && $new_status !== 'inactive') {
            $this->output
                ->set_status_header(400)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Status must be either "active" or "inactive"']));
            return;
        }

        $user = $this->User_model->get_by_id($user_id);
        if (!$user || $user['role'] !== $target_role) {
            $this->output
                ->set_status_header(404)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
            return;
        }

        $success = $this->User_model->update($user_id, ['status' => $new_status]);
        if ($success) {
            // Send system notification about account status change
            $this->send_account_status_notification($user_id, $new_status, $user['full_name'], $target_role);
            
            $status_text = $new_status === 'active' ? 'activated' : 'deactivated';
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => true, 'message' => ucfirst($target_role) . ' ' . $status_text . ' successfully']));
        } else {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to change user status']));
        }
    }

    // Token refresh method
    public function refresh_token() {
        $token = $this->token_lib->get_token_from_header();
        
        if (!$token) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Token is required']));
            return;
        }
        
        $new_token = $this->token_lib->refresh_token($token);
        
        if (!$new_token) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid or expired token']));
            return;
        }
        
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $new_token,
                    'token_type' => 'Bearer',
                    'expires_in' => $this->token_lib->get_expiration_time()
                ]
            ]));
    }

    // Validate token method
    public function validate_token() {
        $token = $this->token_lib->get_token_from_header();
        
        if (!$token) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Token is required']));
            return;
        }
        
        $payload = $this->token_lib->validate_token($token);
        
        if (!$payload) {
            $this->output
                ->set_status_header(401)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Invalid or expired token']));
            return;
        }
        
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Token is valid',
                'data' => [
                    'user_id' => $payload['user_id'],
                    'role' => $payload['role'],
                    'email' => $payload['email'],
                    'full_name' => $payload['full_name']
                ]
            ]));
    }

    // Logout method
    public function logout() {
        // Get user data from token for logging
        $token = $this->token_lib->get_token_from_header();
        $user_data = null;
        
        if ($token) {
            $payload = $this->token_lib->validate_token($token);
            if ($payload) {
                $user_data = [
                    'user_id' => $payload['user_id'],
                    'name' => $payload['full_name'],
                    'username' => $payload['email'],
                    'role' => $payload['role']
                ];
                
                // Log logout event
                log_user_logout($user_data);
            }
        }
        
        // With JWT, logout is typically handled client-side by removing the token
        // However, we can implement a token blacklist if needed for additional security
        // For now, we'll just return a success message
        
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true, 
                'message' => 'Logout successful. Please remove the token from client storage.'
            ]));
    }

    /**
     * Get current logged-in user's profile (including profile picture)
     * GET /api/user/me
     */
    public function get_current_user() {
        // Require authentication
        $user_data = require_auth($this);
        if (!$user_data) {
            return; // Error response already sent
        }
        
        try {
            // Get complete user data from database
            $user = $this->User_model->get_by_id($user_data['user_id']);
            
            if (!$user) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
                return;
            }
            
            // Remove sensitive information
            unset($user['password']);
            
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Current user profile retrieved successfully',
                    'data' => $user
                ]));
        } catch (Exception $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Failed to retrieve user profile: ' . $e->getMessage()]));
        }
    }

    // Handle OPTIONS preflight requests (CORS)
    public function options() {
        // The BaseController constructor handles CORS and exits for OPTIONS requests.
    }

    /**
     * Send welcome system notification to new users
     */
    private function send_welcome_notification($user_id, $full_name, $role, $email) {
        try {
            $title = "Welcome to SCMS!";
            $message = "Hello {$full_name}! Welcome to the Student Class Management System. ";
            $message .= "Your {$role} account has been successfully created. ";
            $message .= "You can now log in and start using the system.";
            
            create_system_notification($user_id, $title, $message, false);
            
            log_message('info', "Welcome notification sent to user {$user_id} ({$email})");
            
        } catch (Exception $e) {
            log_message('error', "Failed to send welcome notification: " . $e->getMessage());
        }
    }

    /**
     * Send account status change notification
     */
    private function send_account_status_notification($user_id, $status, $full_name, $role) {
        try {
            $status_text = $status === 'active' ? 'activated' : 'deactivated';
            $title = "Account Status Updated";
            $message = "Hello {$full_name}, your {$role} account has been {$status_text}. ";
            
            if ($status === 'active') {
                $message .= "You can now log in and access the system.";
            } else {
                $message .= "Your account access has been temporarily suspended. Please contact the administrator for assistance.";
            }
            
            create_system_notification($user_id, $title, $message, $status === 'inactive');
            
            log_message('info', "Account status notification sent to user {$user_id} - Status: {$status}");
            
        } catch (Exception $e) {
            log_message('error', "Failed to send account status notification: " . $e->getMessage());
        }
    }

    /**
     * Send security alert notification for suspicious login activity
     */
    private function send_security_alert_notification($user_id, $login_details) {
        try {
            $title = "Security Alert - New Login";
            $message = "A new login was detected for your account. ";
            $message .= "If this wasn't you, please change your password immediately. ";
            $message .= "Login details: " . $login_details;
            
            create_system_notification($user_id, $title, $message, true); // Mark as urgent
            
            log_message('info', "Security alert notification sent to user {$user_id}");
            
        } catch (Exception $e) {
            log_message('error', "Failed to send security alert notification: " . $e->getMessage());
        }
    }

    /**
     * Handle Google OAuth authentication
     */
    public function google_oauth() {
        try {
            // Get the request data
            $data = json_decode(file_get_contents('php://input'));
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
                return;
            }

            // Validate required fields
            if (empty($data->email) || empty($data->full_name) || empty($data->google_id)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Missing required OAuth data (email, full_name, google_id)']));
                return;
            }

            // Google OAuth data
            $google_user_data = [
                'email' => $data->email,
                'name' => $data->full_name,
                'google_id' => $data->google_id
            ];
            
            // Check if user exists by email
            $user = $this->User_model->get_by_email($google_user_data['email']);
            
            if (!$user) {
                // Create new user from Google OAuth
                $user_id = generate_user_id('STD'); // Generate unique user ID for student
                $user_data = [
                    'user_id' => $user_id,
                    'email' => $google_user_data['email'],
                    'full_name' => $google_user_data['name'],
                    'role' => 'student', // Default role, can be changed later
                    'status' => 'active',
                    'password' => password_hash('google_oauth_' . uniqid(), PASSWORD_DEFAULT), // Placeholder password for OAuth users
                    'google_id' => $google_user_data['google_id'],
                    'account_type' => 'google',
                    'google_email_verified' => true,
                    'last_oauth_login' => date('Y-m-d H:i:s'),
                    'oauth_provider' => 'google',
                    'created_at' => date('Y-m-d H:i:s'),
                    'last_login' => date('Y-m-d H:i:s')
                ];
                
                $insert_result = $this->User_model->insert($user_data);
                
                if (!$insert_result) {
                    $this->output
                        ->set_status_header(500)
                        ->set_content_type('application/json')
                        ->set_output(json_encode(['status' => false, 'message' => 'Failed to create user account']));
                    return;
                }
                
                // Use the generated user_id instead of insert_id()
                $user = $this->User_model->get_by_id($user_id);
                
                // Send welcome notification
                $this->send_welcome_notification($user_id, $user_data['full_name'], $user_data['role'], $user_data['email']);
                
            } else {
                // User exists - check if they have a local account
                if ($user['account_type'] === 'local') {
                    // Link Google account to existing local account
                    $update_data = [
                        'google_id' => $google_user_data['google_id'],
                        'account_type' => 'unified',
                        'google_email_verified' => true,
                        'last_oauth_login' => date('Y-m-d H:i:s'),
                        'oauth_provider' => 'google'
                    ];
                    
                    $this->User_model->update($user['user_id'], $update_data);
                    $user = array_merge($user, $update_data);
                    
                } elseif ($user['account_type'] === 'google' || $user['account_type'] === 'unified') {
                    // Update Google OAuth info
                    $update_data = [
                        'google_id' => $google_user_data['google_id'],
                        'last_oauth_login' => date('Y-m-d H:i:s'),
                        'oauth_provider' => 'google'
                    ];
                    
                    $this->User_model->update($user['user_id'], $update_data);
                    $user = array_merge($user, $update_data);
                }
                
                // Update last login
                $this->User_model->update($user['user_id'], [
                    'last_login' => date('Y-m-d H:i:s')
                ]);
            }

            // Generate JWT token
            $token_payload = [
                'user_id' => $user['user_id'],
                'role' => $user['role'],
                'email' => $user['email'],
                'full_name' => $user['full_name']
            ];
            $token = $this->token_lib->generate_token($token_payload);

            // Log successful login to audit
            $user_data = [
                'user_id' => $user['user_id'],
                'name' => $user['full_name'],
                'username' => $user['email'],
                'role' => $user['role']
            ];
            log_user_login($user_data);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Google OAuth authentication successful',
                    'data' => [
                        'role' => $user['role'],
                        'user_id' => $user['user_id'],
                        'full_name' => $user['full_name'],
                        'email' => $user['email'],
                        'status' => $user['status'],
                        'account_type' => $user['account_type'],
                        'google_id' => $user['google_id'],
                        'google_email_verified' => $user['google_email_verified'],
                        'last_oauth_login' => $user['last_oauth_login'],
                        'oauth_provider' => $user['oauth_provider'],
                        'last_login' => date('Y-m-d H:i:s'),
                        'token' => $token,
                        'token_type' => 'Bearer',
                        'expires_in' => $this->token_lib->get_expiration_time()
                    ]
                ]));

        } catch (Exception $e) {
            log_message('error', 'Google OAuth error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Internal server error during OAuth authentication']));
        }
    }

    /**
     * Link or unlink Google account
     */
    public function link_google_account() {
        try {
            $data = json_decode(file_get_contents('php://input'));
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
                return;
            }

            // Validate required fields
            if (empty($data->email) || empty($data->google_id)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Email and Google ID are required']));
                return;
            }

            // Check if user exists
            $user = $this->User_model->get_by_email($data->email);
            if (!$user) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
                return;
            }

            // Check if Google account is already linked to another user
            $existing_google_user = $this->User_model->get_by_google_id($data->google_id);
            if ($existing_google_user && $existing_google_user['user_id'] !== $user['user_id']) {
                $this->output
                    ->set_status_header(409)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Google account is already linked to another user']));
                return;
            }

            // Link Google account
            $update_data = [
                'google_id' => $data->google_id,
                'account_type' => 'unified',
                'google_email_verified' => true,
                'last_oauth_login' => date('Y-m-d H:i:s'),
                'oauth_provider' => 'google'
            ];

            $this->User_model->update($user['user_id'], $update_data);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Google account linked successfully',
                    'data' => [
                        'user_id' => $user['user_id'],
                        'email' => $user['email'],
                        'account_type' => 'unified',
                        'google_id' => $data->google_id
                    ]
                ]));

        } catch (Exception $e) {
            log_message('error', 'Link Google account error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Internal server error']));
        }
    }

    /**
     * Unlink Google account
     */
    public function unlink_google_account() {
        try {
            $data = json_decode(file_get_contents('php://input'));
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
                return;
            }

            // Validate required fields
            if (empty($data->email)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Email is required']));
                return;
            }

            // Check if user exists
            $user = $this->User_model->get_by_email($data->email);
            if (!$user) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
                return;
            }

            // Check if user has a local password
            if (empty($user['password'])) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Cannot unlink Google account. Please set a local password first.']));
                return;
            }

            // Unlink Google account
            $update_data = [
                'google_id' => null,
                'account_type' => 'local',
                'google_email_verified' => false,
                'last_oauth_login' => null,
                'oauth_provider' => null
            ];

            $this->User_model->update($user['user_id'], $update_data);

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Google account unlinked successfully',
                    'data' => [
                        'user_id' => $user['user_id'],
                        'email' => $user['email'],
                        'account_type' => 'local'
                    ]
                ]));

        } catch (Exception $e) {
            log_message('error', 'Unlink Google account error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Internal server error']));
        }
    }

    /**
     * Get account status and linked providers
     */
    public function get_account_status() {
        try {
            $data = json_decode(file_get_contents('php://input'));
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
                return;
            }

            // Validate required fields
            if (empty($data->email)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Email is required']));
                return;
            }

            // Check if user exists
            $user = $this->User_model->get_by_email($data->email);
            if (!$user) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
                return;
            }

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Account status retrieved successfully',
                    'data' => [
                        'user_id' => $user['user_id'],
                        'email' => $user['email'],
                        'account_type' => $user['account_type'],
                        'has_local_password' => !empty($user['password']),
                        'has_google_account' => !empty($user['google_id']),
                        'google_email_verified' => $user['google_email_verified'],
                        'last_oauth_login' => $user['last_oauth_login'],
                        'oauth_provider' => $user['oauth_provider']
                    ]
                ]));

        } catch (Exception $e) {
            log_message('error', 'Get account status error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Internal server error']));
        }
    }

    /**
     * Verify Google OAuth token
     */
    private function verify_google_token($credential) {
        try {
            // Load Google OAuth config
            $this->config->load('google_oauth');
            $google_config = $this->config->item('google_oauth');
            
            // Get Google's public keys
            $keys_url = 'https://www.googleapis.com/oauth2/v1/certs';
            $keys_response = file_get_contents($keys_url);
            $keys = json_decode($keys_response, true);
            
            if (!$keys) {
                log_message('error', 'Failed to fetch Google public keys');
                return false;
            }
            
            // Decode the JWT token header to get the key ID
            $token_parts = explode('.', $credential);
            if (count($token_parts) !== 3) {
                return false;
            }
            
            $header = json_decode(base64_decode(strtr($token_parts[0], '-_', '+/')), true);
            if (!$header || !isset($header['kid'])) {
                return false;
            }
            
            // Find the correct public key
            $public_key = null;
            foreach ($keys['keys'] as $key) {
                if ($key['kid'] === $header['kid']) {
                    $public_key = $key;
                    break;
                }
            }
            
            if (!$public_key) {
                log_message('error', 'Public key not found for kid: ' . $header['kid']);
                return false;
            }
            
            // Verify the token
            $verified = $this->verify_jwt_token($credential, $public_key, $google_config['client_id']);
            
            if ($verified) {
                // Decode the payload
                $payload = json_decode(base64_decode(strtr($token_parts[1], '-_', '+/')), true);
                return $payload;
            }
            
            return false;
            
        } catch (Exception $e) {
            log_message('error', 'Token verification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify JWT token with public key
     */
    private function verify_jwt_token($token, $public_key, $audience) {
        try {
            // This is a simplified verification - in production, use a proper JWT library
            $token_parts = explode('.', $token);
            if (count($token_parts) !== 3) {
                return false;
            }
            
            $payload = json_decode(base64_decode(strtr($token_parts[1], '-_', '+/')), true);
            
            // Check expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                return false;
            }
            
            // Check audience (client ID)
            if (isset($payload['aud']) && $payload['aud'] !== $audience) {
                return false;
            }
            
            // Check issuer
            if (isset($payload['iss']) && $payload['iss'] !== 'https://accounts.google.com') {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            log_message('error', 'JWT verification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Test email sending directly - Enhanced version
     */
    public function test_email_sending() {
        try {
            $data = json_decode(file_get_contents('php://input'));
            $test_email = isset($data->email) ? trim($data->email) : 'geferreras@gmail.com';
            
            $this->load->helper('email_notification');
            
            $subject = 'SCMS Email Test - ' . date('H:i:s');
            $body = '<h1>Email Test</h1><p>This is a test email from SCMS system.</p>';
            
            $result = send_email($test_email, $subject, $body);
            
            if ($result) {
                log_message('info', "Test email sent successfully to: {$test_email}");
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => true, 'message' => 'Test email sent successfully']));
            } else {
                log_message('error', "Failed to send test email to: {$test_email}");
                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Failed to send test email']));
            }
        } catch (Exception $e) {
            log_message('error', 'Test email error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Email test error: ' . $e->getMessage()]));
        }
    }

    /**
     * Check database status for forgot password functionality
     */
    public function check_forgot_password_status() {
        try {
            $status = [
                'phpmailer_available' => class_exists('PHPMailer\PHPMailer\PHPMailer'),
                'email_config_loaded' => false,
                'password_reset_table_exists' => false,
                'email_config' => []
            ];
            
            // Check email configuration
            $this->config->load('email');
            $status['email_config_loaded'] = true;
            $status['email_config'] = [
                'smtp_host' => $this->config->item('smtp_host'),
                'smtp_port' => $this->config->item('smtp_port'),
                'smtp_user' => $this->config->item('smtp_user'),
                'smtp_crypto' => $this->config->item('smtp_crypto')
            ];
            
            // Check if password reset table exists
            $query = $this->db->query("SHOW TABLES LIKE 'password_reset_tokens'");
            $status['password_reset_table_exists'] = ($query->num_rows() > 0);
            
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => true, 'data' => $status]));
                
        } catch (Exception $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => $e->getMessage()]));
        }
    }

    /**
     * Forgot Password - Send reset email
     */
    public function forgot_password() {
        try {
            $data = json_decode(file_get_contents('php://input'));

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
                return;
            }

            $email = isset($data->email) ? trim($data->email) : null;

            if (empty($email)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Email is required']));
                return;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Invalid email format']));
                return;
            }

            // Check if user exists
            $user = $this->User_model->get_by_email($email);
            if (!$user) {
                // Don't reveal if user exists or not for security
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => true, 'message' => 'If the email exists, a password reset link has been sent']));
                return;
            }

            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in database
            $this->db->trans_start();
            
            try {
                // Delete any existing tokens for this email
                $this->db->where('email', $email)->delete('password_reset_tokens');
                
                // Insert new token
                $token_data = [
                    'email' => $email,
                    'token' => $token,
                    'expires_at' => $expires_at,
                    'used' => 0
                ];
                $this->db->insert('password_reset_tokens', $token_data);
                
                $this->db->trans_complete();

                if ($this->db->trans_status() === FALSE) {
                    log_message('error', 'Failed to store password reset token for email: ' . $email . ' - Transaction failed');
                    $this->output
                        ->set_status_header(500)
                        ->set_content_type('application/json')
                        ->set_output(json_encode(['status' => false, 'message' => 'Failed to process request. Please try again.']));
                    return;
                }
            } catch (Exception $e) {
                $this->db->trans_rollback();
                log_message('error', 'Database error in forgot password: ' . $e->getMessage());
                
                // Check if it's a table not found error
                if (strpos($e->getMessage(), 'password_reset_tokens') !== false) {
                    log_message('error', 'Password reset tokens table does not exist. Please run the database setup script.');
                }
                
                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Database error. Please contact support.']));
                return;
            }

            // Send email
            // Prefer environment variable, then config, then safe default
            $frontend_url = getenv('FRONTEND_BASE_URL') ?: ($this->config->item('frontend_url') ?: 'https://scmsupdatedbackup.vercel.app');
            $reset_link = rtrim($frontend_url, '/') . '/auth/reset-password?token=' . urlencode($token);
            
            log_message('info', "Attempting to send password reset email to: {$email} with token: " . substr($token, 0, 8) . "...");
            
            $email_sent = $this->send_password_reset_email($email, $user['full_name'], $reset_link);

            if ($email_sent) {
                // Log the request
                log_message('info', "Password reset email sent successfully to: {$email}");
                
                // Try to log audit event if function exists
                if (function_exists('log_audit_event')) {
                    log_audit_event(
                        'PASSWORD_RESET_REQUESTED',
                        'AUTHENTICATION',
                        "Password reset requested for email: {$email}",
                        [
                            'ip_address' => $this->input->ip_address(),
                            'user_agent' => $this->input->user_agent()
                        ]
                    );
                }

                $this->output
                    ->set_status_header(200)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => true, 'message' => 'Password reset link has been sent to your email']));
            } else {
                log_message('error', "Failed to send password reset email to: {$email}");
                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Failed to send email. Please try again.']));
            }

        } catch (Exception $e) {
            log_message('error', 'Forgot password error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Internal server error']));
        }
    }

    /**
     * Reset Password - Change password using token
     */
    public function reset_password() {
        try {
            $data = json_decode(file_get_contents('php://input'));

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
                return;
            }

            $token = isset($data->token) ? trim($data->token) : null;
            $new_password = isset($data->new_password) ? $data->new_password : null;

            if (empty($token) || empty($new_password)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Token and new password are required']));
                return;
            }

            if (strlen($new_password) < 6) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Password must be at least 6 characters long']));
                return;
            }

            // Find valid token
            $token_record = $this->db->where('token', $token)
                                   ->where('expires_at >', date('Y-m-d H:i:s'))
                                   ->where('used', 0)
                                   ->get('password_reset_tokens')
                                   ->row_array();

            if (!$token_record) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Invalid or expired token']));
                return;
            }

            // Get user
            $user = $this->User_model->get_by_email($token_record['email']);
            if (!$user) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
                return;
            }

            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $update_success = $this->User_model->update($user['user_id'], ['password' => $hashed_password]);

            if (!$update_success) {
                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Failed to update password. Please try again.']));
                return;
            }

            // Mark token as used
            $this->db->where('id', $token_record['id'])->update('password_reset_tokens', ['used' => 1]);

            // Log the password reset
            log_audit_event(
                'PASSWORD_RESET_COMPLETED',
                'AUTHENTICATION',
                "Password reset completed for user: {$user['user_id']}",
                [
                    'ip_address' => $this->input->ip_address(),
                    'user_agent' => $this->input->user_agent()
                ]
            );

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => true, 'message' => 'Password has been reset successfully']));

        } catch (Exception $e) {
            log_message('error', 'Reset password error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Internal server error']));
        }
    }

    /**
     * Send password reset email using PHPMailer
     */
    private function send_password_reset_email($email, $full_name, $reset_link) {
        try {
            $this->load->helper('email_notification');

            $html_message = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px;'>Password Reset Request</h2>
                    <p>Hello {$full_name},</p>
                    <p>You have requested to reset your password for your SCMS account.</p>
                    <p>Click the button below to reset your password:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$reset_link}' style='background-color: #007bff; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Reset Password</a>
                    </div>
                    <p>Or copy and paste this link in your browser:</p>
                    <p style='background-color: #f8f9fa; padding: 10px; border-radius: 5px; word-break: break-all;'>{$reset_link}</p>
                    <p><strong style='color: #dc3545;'>This link will expire in 1 hour.</strong></p>
                    <p>If you didn't request this password reset, please ignore this email.</p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                    <p style='color: #666; font-size: 14px;'>Best regards,<br>SCMS Team</p>
                </div>
            </body>
            </html>";

            $subject = 'Password Reset Request - SCMS';
            $result = send_email($email, $subject, $html_message, $full_name);

            if ($result) {
                log_message('info', "Password reset email sent successfully to: {$email} using PHPMailer helper");
                return true;
            }

            log_message('error', "Failed to send password reset email to: {$email} using PHPMailer helper");
            return false;
        } catch (Exception $e) {
            log_message('error', 'Email sending error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send password reset email using PHPMailer
     */
    private function send_password_reset_email_phpmailer($email, $full_name, $reset_link) { return $this->send_password_reset_email($email, $full_name, $reset_link); }

    /**
     * Send password reset email using CodeIgniter email library (fallback)
     */
    private function send_password_reset_email_ci($email, $full_name, $reset_link) { return $this->send_password_reset_email($email, $full_name, $reset_link); }

    /**
     * Change Password - Change password for authenticated users
     * Supports teachers, students, and admins
     */
    public function change_password() {
        try {
            // Check if user is authenticated via token
            $headers = getallheaders();
            $token = null;
            
            // Check Authorization header for Bearer token
            if (isset($headers['Authorization'])) {
                $auth_header = $headers['Authorization'];
                if (strpos($auth_header, 'Bearer ') === 0) {
                    $token = substr($auth_header, 7);
                }
            }
            
            // If no Authorization header, check for token in request body or query
            if (!$token) {
                $data = json_decode(file_get_contents('php://input'));
                if (isset($data->token)) {
                    $token = $data->token;
                }
            }
            
            if (!$token) {
                $this->output
                    ->set_status_header(401)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Authentication token required']));
                return;
            }
            
            // Verify token and get user data
            $token_data = $this->token_lib->validate_token($token);
            if (!$token_data) {
                $this->output
                    ->set_status_header(401)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Invalid or expired token']));
                return;
            }
            $user_id = $token_data['user_id'];

            $data = json_decode(file_get_contents('php://input'));

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Invalid JSON format']));
                return;
            }

            $current_password = isset($data->current_password) ? trim($data->current_password) : null;
            $new_password = isset($data->new_password) ? trim($data->new_password) : null;
            $confirm_password = isset($data->confirm_password) ? trim($data->confirm_password) : null;

            // Validate required fields
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Current password, new password, and confirm password are required']));
                return;
            }

            // Validate password confirmation
            if ($new_password !== $confirm_password) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'New password and confirm password do not match']));
                return;
            }

            // Validate new password length
            if (strlen($new_password) < 8) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Password must be at least 8 characters long']));
                return;
            }

            // Validate password complexity
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)/', $new_password)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Password must contain both letters and numbers']));
                return;
            }

            // Get current user data
            $user = $this->User_model->get_by_id($user_id);
            if (!$user) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'User not found']));
                return;
            }

            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Current password is incorrect']));
                return;
            }

            // Check if new password is different from current
            if (password_verify($new_password, $user['password'])) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'New password must be different from current password']));
                return;
            }

            // Hash new password
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Update password
            $update_success = $this->User_model->update($user_id, ['password' => $hashed_password]);

            if (!$update_success) {
                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode(['status' => false, 'message' => 'Failed to update password. Please try again.']));
                return;
            }

            // Log the password change
            log_audit_event(
                'PASSWORD_CHANGED',
                'AUTHENTICATION',
                "Password changed for user: {$user_id} ({$user['role']})",
                [
                    'ip_address' => $this->input->ip_address(),
                    'user_agent' => $this->input->user_agent(),
                    'user_role' => $user['role']
                ]
            );

            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true, 
                    'message' => 'Password changed successfully',
                    'user_role' => $user['role']
                ]));

        } catch (Exception $e) {
            log_message('error', 'Change password error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Internal server error']));
        }
    }
    
    /**
     * Debug routing and environment
     * GET /api/auth/debug-routing
     */
    public function debug_routing() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        $response = [
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => getenv('RAILWAY_ENVIRONMENT') ?: 'local',
            'base_url' => base_url(),
            'index_page' => $this->config->item('index_page'),
            'uri_protocol' => $this->config->item('uri_protocol'),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'not set',
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
            'path_info' => $_SERVER['PATH_INFO'] ?? 'not set',
            'query_string' => $_SERVER['QUERY_STRING'] ?? 'not set',
            'twofactor_controller_exists' => file_exists(APPPATH . 'controllers/api/TwoFactor.php'),
            'routes_loaded' => true
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Debug email configuration and test sending
     * GET /api/auth/debug-email
     */
    public function debug_email() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        
        $response = [
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => getenv('RAILWAY_ENVIRONMENT') ?: 'local',
            'tests' => []
        ];
        
        // Test 1: Environment Variables
        $response['tests']['environment'] = [
            'SMTP_HOST' => getenv('SMTP_HOST') ?: 'NOT SET',
            'SMTP_PORT' => getenv('SMTP_PORT') ?: 'NOT SET', 
            'SMTP_USER' => getenv('SMTP_USER') ?: 'NOT SET',
            'SMTP_PASS' => getenv('SMTP_PASS') ? 'SET' : 'NOT SET',
            'SMTP_CRYPTO' => getenv('SMTP_CRYPTO') ?: 'NOT SET'
        ];
        
        // Test 2: Email Configuration
        $this->config->load('email');
        $response['tests']['config'] = [
            'protocol' => $this->config->item('protocol'),
            'smtp_host' => $this->config->item('smtp_host'),
            'smtp_port' => $this->config->item('smtp_port'),
            'smtp_user' => $this->config->item('smtp_user'),
            'smtp_crypto' => $this->config->item('smtp_crypto'),
            'smtp_timeout' => $this->config->item('smtp_timeout')
        ];
        
        // Test 3: SMTP Connection
        try {
            $smtp_host = $this->config->item('smtp_host');
            $smtp_port = $this->config->item('smtp_port');
            
            $connection = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
            if ($connection) {
                $response['tests']['smtp_connection'] = [
                    'status' => 'success',
                    'message' => "Connected to {$smtp_host}:{$smtp_port}"
                ];
                fclose($connection);
            } else {
                $response['tests']['smtp_connection'] = [
                    'status' => 'error',
                    'error' => "Cannot connect: {$errstr} ({$errno})"
                ];
            }
        } catch (Exception $e) {
            $response['tests']['smtp_connection'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
        
        // Test 4: Direct Email Test (using unified PHPMailer helper)
        try {
            $this->load->helper('email_notification');
            $test_email = 'geferreras@gmail.com';
            $subject = 'SCMS Email Debug - ' . date('H:i:s');
            $body = '<h2>Email Test</h2><p>This is a debug test email.</p>';
            $result = send_email($test_email, $subject, $body);

            $response['tests']['email_send'] = [
                'status' => $result ? 'success' : 'error',
                'to' => $test_email,
                'debug_info' => $result ? 'Email sent' : 'Failed to send using PHPMailer helper'
            ];
        } catch (Exception $e) {
            $response['tests']['email_send'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
}