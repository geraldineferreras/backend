<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications_api extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Load necessary models and libraries
        $this->load->model('user_model');
        $this->load->library('Token_lib');
        $this->load->model('Notification_model');
        
        // Set CORS headers
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Content-Type: application/json');
        
        // Handle preflight requests
        if ($this->input->method() === 'options') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * GET /api/notifications - Get notifications for a user
     */
    public function index() {
        try {
            // Get user ID from query parameter
            $userId = $this->input->get('userId');
            if (!$userId) {
                $this->sendError('userId parameter required', 400);
                return;
            }
            
            // Validate JWT token
            $token = $this->token_lib->get_token_from_header();
            if (!$token) {
                $this->sendError('Authorization token required', 401);
                return;
            }
            
            try {
                $payload = $this->token_lib->validate_token($token);
                if (!$payload || $payload['user_id'] !== $userId) {
                    $this->sendError('Token mismatch', 401);
                    return;
                }
            } catch (Exception $e) {
                $this->sendError('Invalid token', 401);
                return;
            }
            
            // Get notifications from database
            $notifications = $this->getNotificationsFromDB($userId);
            
            $this->sendSuccess($notifications);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch notifications: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/notifications/recent - Get recent notifications for a user
     */
    public function recent() {
        try {
            $userId = $this->input->get('userId');
            $limit = $this->input->get('limit') ?: 10;
            
            if (!$userId) {
                $this->sendError('userId parameter required', 400);
                return;
            }
            
            // Validate JWT token
            $token = $this->token_lib->get_token_from_header();
            if (!$token) {
                $this->sendError('Authorization token required', 401);
                return;
            }
            
            try {
                $payload = $this->token_lib->validate_token($token);
                if (!$payload || $payload['user_id'] !== $userId) {
                    $this->sendError('Token mismatch', 401);
                    return;
                }
            } catch (Exception $e) {
                $this->sendError('Invalid token', 401);
                return;
            }
            
            // Get recent notifications from database
            $notifications = $this->getRecentNotificationsFromDB($userId, $limit);
            
            $this->sendSuccess($notifications);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch recent notifications: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/notifications/unread-count - Get unread notification count
     */
    public function unread_count() {
        try {
            $userId = $this->input->get('userId');
            
            if (!$userId) {
                $this->sendError('userId parameter required', 400);
                return;
            }
            
            // Validate JWT token
            $token = $this->token_lib->get_token_from_header();
            if (!$token) {
                $this->sendError('Authorization token required', 401);
                return;
            }
            
            try {
                $payload = $this->token_lib->validate_token($token);
                if (!$payload || $payload['user_id'] !== $userId) {
                    $this->sendError('Token mismatch', 401);
                    return;
                }
            } catch (Exception $e) {
                $this->sendError('Invalid token', 401);
                return;
            }
            
            // Get unread count from database
            $count = $this->getUnreadCountFromDB($userId);
            
            $this->sendSuccess(['count' => $count]);
            
        } catch (Exception $e) {
            $this->sendError('Failed to fetch unread count: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * POST /api/notifications - Create a new notification
     */
    public function create() {
        try {
            // Get JSON input
            $input = json_decode($this->input->raw_input_stream, true);
            
            if (!$input) {
                $this->sendError('Invalid JSON input', 400);
                return;
            }
            
            // Validate required fields
            $requiredFields = ['recipient_id', 'message'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    $this->sendError("Field '{$field}' is required", 400);
                    return;
                }
            }
            
            // Validate JWT token
            $token = $this->token_lib->get_token_from_header();
            if (!$token) {
                $this->sendError('Authorization token required', 401);
                return;
            }
            
            try {
                $payload = $this->token_lib->validate_token($token);
                if (!$payload) {
                    $this->sendError('Invalid token', 401);
                    return;
                }
            } catch (Exception $e) {
                $this->sendError('Invalid token', 401);
                return;
            }
            
            // Create notification in database
            $notificationId = $this->createNotificationInDB($input);
            
            if ($notificationId) {
                $this->sendSuccess([
                    'id' => $notificationId,
                    'message' => 'Notification created successfully'
                ]);
            } else {
                $this->sendError('Failed to create notification', 500);
            }
            
        } catch (Exception $e) {
            $this->sendError('Failed to create notification: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Helper method to get notifications from database
     */
    private function getNotificationsFromDB($userId) {
        // Use the existing Notification_model
        $notifications = $this->Notification_model->get_user_notifications($userId, 50, 0, false);
        
        // Transform the data to match expected format
        $transformed = [];
        foreach ($notifications as $notification) {
            $transformed[] = [
                'id' => $notification['id'],
                'title' => $notification['title'], // Include title field directly
                'message' => $notification['message'],
                'type' => $notification['type'],
                'data' => [
                    'related_id' => $notification['related_id'],
                    'related_type' => $notification['related_type'],
                    'class_code' => $notification['class_code'],
                    'is_urgent' => $notification['is_urgent']
                ],
                'created_at' => $notification['created_at'],
                'is_read' => $notification['is_read']
            ];
        }
        
        return $transformed;
    }
    
    /**
     * Helper method to get recent notifications from database
     */
    private function getRecentNotificationsFromDB($userId, $limit) {
        // Use the existing Notification_model
        $notifications = $this->Notification_model->get_recent_notifications($userId, $limit);
        
        // Transform the data to match expected format
        $transformed = [];
        foreach ($notifications as $notification) {
            $transformed[] = [
                'id' => $notification['id'],
                'title' => $notification['title'], // Include title field directly
                'message' => $notification['message'],
                'type' => $notification['type'],
                'data' => [
                    'related_id' => $notification['related_id'],
                    'related_type' => $notification['related_type'],
                    'class_code' => $notification['class_code'],
                    'is_urgent' => $notification['is_urgent']
                ],
                'created_at' => $notification['created_at'],
                'is_read' => $notification['is_read']
            ];
        }
        
        return $transformed;
    }
    
    /**
     * Helper method to get unread count from database
     */
    private function getUnreadCountFromDB($userId) {
        // Use the existing Notification_model
        return $this->Notification_model->get_unread_count($userId);
    }
    
    /**
     * Helper method to create notification in database
     */
    private function createNotificationInDB($data) {
        // Transform the data to match the existing notification structure
        $notificationData = [
            'user_id' => $data['recipient_id'],
            'type' => $data['type'] ?? 'system',
            'title' => $data['title'] ?? 'Notification',
            'message' => $data['message'],
            'related_id' => $data['related_id'] ?? null,
            'related_type' => $data['related_type'] ?? null,
            'class_code' => $data['class_code'] ?? null,
            'is_urgent' => isset($data['is_urgent']) ? ($data['is_urgent'] ? 1 : 0) : 0
        ];
        
        // Use the existing Notification_model
        return $this->Notification_model->create_notification($notificationData);
    }
    
    /**
     * Helper method to send success response
     */
    private function sendSuccess($data) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    }
    
    /**
     * Helper method to send error response
     */
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message
        ]);
    }
    
    /**
     * POST /api/notifications/create-test - Create a test notification for SSE testing
     */
    public function create_test() {
        try {
            // Get JSON input
            $input = json_decode($this->input->raw_input_stream, true);
            
            if (!$input) {
                $input = $this->input->post();
            }
            
            // Default values for testing
            $userId = $input['user_id'] ?? 'STU001';
            $message = $input['message'] ?? 'Test notification from SSE';
            $type = $input['type'] ?? 'test';
            $title = $input['title'] ?? 'SSE Test Notification';
            
            // Create notification data
            $notificationData = [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'related_id' => $input['related_id'] ?? null,
                'related_type' => $input['related_type'] ?? 'test',
                'class_code' => $input['class_code'] ?? null,
                'is_urgent' => isset($input['is_urgent']) ? ($input['is_urgent'] ? 1 : 0) : 0
            ];
            
            // Create notification in database
            $notificationId = $this->Notification_model->create_notification($notificationData);
            
            if ($notificationId) {
                $this->sendSuccess([
                    'notification_id' => $notificationId,
                    'message' => 'Test notification created successfully',
                    'data' => $notificationData
                ]);
            } else {
                $this->sendError('Failed to create test notification', 500);
            }
            
        } catch (Exception $e) {
            $this->sendError('Error creating test notification: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * GET /api/notifications/simple-test - Simple test endpoint without complex validation
     */
    public function simple_test() {
        try {
            // Simple test data
            $notificationData = [
                'user_id' => 'STU001',
                'type' => 'test',
                'title' => 'Simple Test Notification',
                'message' => 'This is a simple test notification created at ' . date('Y-m-d H:i:s'),
                'related_id' => null,
                'related_type' => 'test',
                'class_code' => null,
                'is_urgent' => 0
            ];
            
            // Create notification in database
            $notificationId = $this->Notification_model->create_notification($notificationData);
            
            if ($notificationId) {
                $this->sendSuccess([
                    'notification_id' => $notificationId,
                    'message' => 'Simple test notification created successfully',
                    'data' => $notificationData
                ]);
            } else {
                $this->sendError('Failed to create simple test notification', 500);
            }
            
        } catch (Exception $e) {
            $this->sendError('Error creating simple test notification: ' . $e->getMessage(), 500);
        }
    }
}