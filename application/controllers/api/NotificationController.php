<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class NotificationController extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Notification_model');
        $this->load->helper('notification');
        $this->load->helper('email_notification');
    }
    
    /**
     * Get user notifications
     * GET /api/notifications
     */
    public function get_notifications() {
        // Verify JWT token
        $user_id = $this->verify_token();
        if (!$user_id) {
            $this->output->set_status_header(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $limit = $this->input->get('limit') ?: 50;
        $offset = $this->input->get('offset') ?: 0;
        $unread_only = $this->input->get('unread_only') ?: false;
        
        $notifications = $this->Notification_model->get_user_notifications($user_id, $limit, $offset, $unread_only);
        $unread_count = $this->Notification_model->get_unread_count($user_id);
        
        $response = [
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unread_count
            ]
        ];
        
        $this->output->set_content_type('application/json');
        echo json_encode($response);
    }
    
    /**
     * Mark notification as read
     * PUT /api/notifications/{id}/read
     */
    public function mark_as_read($notification_id) {
        // Verify JWT token
        $user_id = $this->verify_token();
        if (!$user_id) {
            $this->output->set_status_header(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $result = $this->Notification_model->mark_as_read($notification_id, $user_id);
        
        if ($result) {
            $response = ['success' => true, 'message' => 'Notification marked as read'];
        } else {
            $this->output->set_status_header(404);
            $response = ['error' => 'Notification not found'];
        }
        
        $this->output->set_content_type('application/json');
        echo json_encode($response);
    }
    
    /**
     * Mark all notifications as read
     * PUT /api/notifications/mark-all-read
     */
    public function mark_all_as_read() {
        // Verify JWT token
        $user_id = $this->verify_token();
        if (!$user_id) {
            $this->output->set_status_header(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $result = $this->Notification_model->mark_all_as_read($user_id);
        
        $response = [
            'success' => true,
            'message' => 'All notifications marked as read'
        ];
        
        $this->output->set_content_type('application/json');
        echo json_encode($response);
    }
    
    /**
     * Delete notification
     * DELETE /api/notifications/{id}
     */
    public function delete_notification($notification_id) {
        // Verify JWT token
        $user_id = $this->verify_token();
        if (!$user_id) {
            $this->output->set_status_header(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $result = $this->Notification_model->delete_notification($notification_id, $user_id);
        
        if ($result) {
            $response = ['success' => true, 'message' => 'Notification deleted'];
        } else {
            $this->output->set_status_header(404);
            $response = ['error' => 'Notification not found'];
        }
        
        $this->output->set_content_type('application/json');
        echo json_encode($response);
    }
    
    /**
     * Get notification settings
     * GET /api/notifications/settings
     */
    public function get_settings() {
        // Verify JWT token
        $user_id = $this->verify_token();
        if (!$user_id) {
            $this->output->set_status_header(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $settings = $this->Notification_model->get_notification_settings($user_id);
        
        $response = [
            'success' => true,
            'data' => $settings
        ];
        
        $this->output->set_content_type('application/json');
        echo json_encode($response);
    }
    
    /**
     * Update notification settings
     * PUT /api/notifications/settings
     */
    public function update_settings() {
        // Verify JWT token
        $user_id = $this->verify_token();
        if (!$user_id) {
            $this->output->set_status_header(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $settings = [
            'email_notifications' => isset($input['email_notifications']) ? $input['email_notifications'] : 1,
            'push_notifications' => isset($input['push_notifications']) ? $input['push_notifications'] : 1,
            'announcement_notifications' => isset($input['announcement_notifications']) ? $input['announcement_notifications'] : 1,
            'task_notifications' => isset($input['task_notifications']) ? $input['task_notifications'] : 1,
            'submission_notifications' => isset($input['submission_notifications']) ? $input['submission_notifications'] : 1,
            'grade_notifications' => isset($input['grade_notifications']) ? $input['grade_notifications'] : 1,
            'enrollment_notifications' => isset($input['enrollment_notifications']) ? $input['enrollment_notifications'] : 1
        ];
        
        $result = $this->Notification_model->update_notification_settings($user_id, $settings);
        
        if ($result) {
            $response = ['success' => true, 'message' => 'Settings updated successfully'];
        } else {
            $this->output->set_status_header(500);
            $response = ['error' => 'Failed to update settings'];
        }
        
        $this->output->set_content_type('application/json');
        echo json_encode($response);
    }
    
    /**
     * Get unread count
     * GET /api/notifications/unread-count
     */
    public function get_unread_count() {
        // Verify JWT token
        $user_id = $this->verify_token();
        if (!$user_id) {
            $this->output->set_status_header(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $count = $this->Notification_model->get_unread_count($user_id);
        
        $response = [
            'success' => true,
            'data' => ['unread_count' => $count]
        ];
        
        $this->output->set_content_type('application/json');
        echo json_encode($response);
    }
    
    /**
     * Get recent notifications for dashboard
     * GET /api/notifications/recent
     */
    public function get_recent() {
        // Verify JWT token
        $user_id = $this->verify_token();
        if (!$user_id) {
            $this->output->set_status_header(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $limit = $this->input->get('limit') ?: 10;
        $notifications = $this->Notification_model->get_recent_notifications($user_id, $limit);
        
        $response = [
            'success' => true,
            'data' => $notifications
        ];
        
        $this->output->set_content_type('application/json');
        echo json_encode($response);
    }
    
    /**
     * Get urgent notifications
     * GET /api/notifications/urgent
     */
    public function get_urgent() {
        // Verify JWT token
        $user_id = $this->verify_token();
        if (!$user_id) {
            $this->output->set_status_header(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }
        
        $notifications = $this->Notification_model->get_urgent_notifications($user_id);
        
        $response = [
            'success' => true,
            'data' => $notifications
        ];
        
        $this->output->set_content_type('application/json');
        echo json_encode($response);
    }
    
    /**
     * Verify JWT token and return user_id
     */
    private function verify_token() {
        $headers = getallheaders();
        $token = null;
        
        if (isset($headers['Authorization'])) {
            $token = str_replace('Bearer ', '', $headers['Authorization']);
        }
        
        if (!$token) {
            return false;
        }
        
        // Load JWT library
        $this->load->library('jwt');
        
        try {
            $decoded = $this->jwt->decode($token, $this->config->item('jwt_key'));
            return $decoded->user_id;
        } catch (Exception $e) {
            return false;
        }
    }
} 