<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class PermissionsController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('User_model');
        $this->load->helper(['response', 'auth']);
        $this->load->library('Token_lib');
        // CORS headers are already handled by BaseController
    }

    /**
     * Get permissions for a specific user
     * GET /api/admin/permissions/{userId}
     */
    public function get_user_permissions($userId) {
        // Validate admin access
        $user_data = require_admin($this);
        if (!$user_data) return;

        try {
            // Check if user_permissions table exists, create if needed
            $this->ensure_permissions_table_exists();
            
            $query = $this->db->where('user_id', $userId)->get('user_permissions');
            $result = $query->row_array();
            
            if ($result) {
                $permissions = json_decode($result['permissions'], true);
                
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => true,
                        'message' => 'User permissions retrieved successfully',
                        'data' => [
                            'user_id' => $userId,
                            'permissions' => $permissions
                        ]
                    ]));
            } else {
                // No custom permissions found, return empty (will use defaults)
                $this->output
                    ->set_status_header(200)  // Changed to 200 for consistency
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'No custom permissions found for user',
                        'data' => null
                    ]));
            }
        } catch (Exception $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Error retrieving user permissions: ' . $e->getMessage(),
                    'data' => null
                ]));
        }
    }

    /**
     * Save permissions for a specific user
     * POST /api/admin/permissions/{userId}
     */
    public function save_user_permissions($userId) {
        // Validate admin access
        $user_data = require_admin($this);
        if (!$user_data) return;

        try {
            // Check if user_permissions table exists, create if needed
            $this->ensure_permissions_table_exists();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['permissions']) || !is_array($input['permissions'])) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Invalid permissions data',
                        'data' => null
                    ]));
                return;
            }
            
            $permissions = json_encode($input['permissions']);
            
            // Check if user permissions already exist
            $query = $this->db->where('user_id', $userId)->get('user_permissions');
            $exists = $query->row();
            
            if ($exists) {
                // Update existing permissions
                $this->db->where('user_id', $userId);
                $this->db->update('user_permissions', [
                    'permissions' => $permissions,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            } else {
                // Insert new permissions
                $this->db->insert('user_permissions', [
                    'user_id' => $userId,
                    'permissions' => $permissions,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'User permissions saved successfully',
                    'data' => [
                        'user_id' => $userId,
                        'permissions' => $input['permissions']
                    ]
                ]));

        } catch (Exception $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Error saving user permissions: ' . $e->getMessage(),
                    'data' => null
                ]));
        }
    }

    /**
     * Get all user permissions (for admin overview)
     * GET /api/admin/permissions
     */
    public function get_all_permissions() {
        // Validate admin access
        $user_data = require_admin($this);
        if (!$user_data) return;

        try {
            // Check if user_permissions table exists, create if needed
            $this->ensure_permissions_table_exists();
            
            $this->db->select('up.user_id, up.permissions, u.full_name, u.email, u.role, u.admin_type');
            $this->db->from('user_permissions up');
            $this->db->join('users u', 'up.user_id = u.user_id', 'left');
            $this->db->order_by('u.full_name', 'ASC');
            $query = $this->db->get();
            $results = $query->result_array();
            
            $userPermissions = [];
            foreach ($results as $row) {
                $userPermissions[] = [
                    'user_id' => $row['user_id'],
                    'full_name' => $row['full_name'],
                    'email' => $row['email'],
                    'role' => $row['role'],
                    'admin_type' => $row['admin_type'],
                    'permissions' => json_decode($row['permissions'], true)
                ];
            }
            
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'All user permissions retrieved successfully',
                    'data' => $userPermissions
                ]));

        } catch (Exception $e) {
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Error retrieving all user permissions: ' . $e->getMessage(),
                    'data' => null
                ]));
        }
    }

    /**
     * Ensure the user_permissions table exists
     */
    private function ensure_permissions_table_exists() {
        // Check if table exists
        if (!$this->db->table_exists('user_permissions')) {
            // Create the table
            $sql = "
            CREATE TABLE IF NOT EXISTS user_permissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                permissions JSON NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_id (user_id),
                INDEX idx_user_id (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ";
            
            $this->db->query($sql);
        }
    }

    /**
     * Test endpoint to verify permissions controller is accessible
     * GET /api/admin/permissions/test
     */
    public function test() {
        $this->output
            ->set_status_header(200)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Permissions API is working',
                'timestamp' => date('Y-m-d H:i:s'),
                'environment' => getenv('RAILWAY_ENVIRONMENT') ? 'railway' : 'local'
            ]));
    }
}
