<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class NotificationStreamController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Notification_model');
        $this->load->library('Token_lib');
        
        // Disable output buffering for SSE
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering
    }
    
    /**
     * SSE endpoint for real-time notifications
     * GET /api/notifications/stream
     */
    public function stream($pathToken = null) {
        // Verify JWT token and get user info
        $user_id = $this->verify_token($pathToken);
        if (!$user_id) {
            $this->send_sse_error('Unauthorized', 401);
            return;
        }
        
        // Get user role
        $user_role = $this->get_user_role($user_id);
        if (!$user_role) {
            $this->send_sse_error('User role not found', 400);
            return;
        }
        
        // Send connection confirmation
        $this->send_sse_message('connected', [
            'user_id' => $user_id,
            'role' => $user_role,
            'message' => 'SSE connection established',
            'timestamp' => date('c')
        ]);
        
        // Keep connection alive and check for new notifications
        $this->monitor_notifications($user_id, $user_role);
    }
    
    /**
     * Monitor for new notifications and send them in real-time
     */
    private function monitor_notifications($user_id, $user_role) {
        $counter = 0;
        $max_messages = 10; // Limit for testing
        
        while ($counter < $max_messages) {
            // Check if client disconnected
            if (connection_aborted()) {
                break;
            }
            
            // Send a test message every 2 seconds
            sleep(2);
            
            $counter++;
            $this->send_sse_message('test_message', [
                'message_number' => $counter,
                'user_id' => $user_id,
                'role' => $user_role,
                'timestamp' => date('c'),
                'message' => "Test message {$counter} for {$user_role}"
            ]);
            
            // Also check for real notifications
            $this->check_and_send_notifications($user_id, $user_role);
        }
        
        // Send completion message
        $this->send_sse_message('complete', [
            'message' => 'Test completed successfully',
            'total_messages' => $counter,
            'timestamp' => date('c')
        ]);
        
        // Close connection
        exit;
    }
    
    /**
     * Check for new notifications and send to client
     */
    private function check_and_send_notifications($user_id, $user_role) {
        try {
            // Get unread notifications for this specific user
            $notifications = $this->Notification_model->get_user_notifications($user_id, 5, 0, true);
            
            if (!empty($notifications)) {
                foreach ($notifications as $notification) {
                    $this->send_sse_message('notification', [
                        'id' => $notification['id'], // Use 'id' instead of 'notification_id'
                        'type' => $notification['type'],
                        'title' => $notification['title'],
                        'message' => $notification['message'],
                        'is_urgent' => (bool)$notification['is_urgent'],
                        'created_at' => $notification['created_at'],
                        'user_role' => $user_role,
                        'data' => [
                            'related_id' => $notification['related_id'] ?? null,
                            'related_type' => $notification['related_type'] ?? null,
                            'class_code' => $notification['class_code'] ?? null
                        ]
                    ]);
                }
            }
        } catch (Exception $e) {
            // Log error but don't break the connection
            $this->send_sse_message('error', [
                'message' => 'Error checking notifications: ' . $e->getMessage(),
                'timestamp' => date('c')
            ]);
        }
    }
    
    /**
     * Send SSE message
     */
    private function send_sse_message($event, $data) {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        
        // Flush output immediately
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
    
    /**
     * Send SSE error
     */
    private function send_sse_error($message, $code = 400) {
        $this->send_sse_message('error', [
            'message' => $message,
            'code' => $code,
            'timestamp' => date('c')
        ]);
        exit;
    }
    
    /**
     * Verify JWT token and return user_id
     */
    private function verify_token($pathToken = null) {
        // Accept token from URI segment (if provided), otherwise use header/query/cookie
        $token = $pathToken ?: $this->token_lib->get_token_from_request();
        
        if (!$token) {
            return false;
        }
        
        try {
            $payload = $this->token_lib->validate_token($token);
            if (!$payload) {
                return false;
            }

            // Optional cross-checks if client provides userId/role in query
            $requestUserId = isset($_GET['userId']) ? $_GET['userId'] : null;
            if ($requestUserId && isset($payload['user_id']) && $payload['user_id'] !== $requestUserId) {
                $this->send_sse_error('Token mismatch', 401);
                return false;
            }
            $requestRole = isset($_GET['role']) ? $_GET['role'] : null;
            if ($requestRole && isset($payload['role']) && $payload['role'] !== $requestRole) {
                $this->send_sse_error('Role mismatch', 401);
                return false;
            }

            return isset($payload['user_id']) ? $payload['user_id'] : false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get user role
     */
    private function get_user_role($user_id) {
        $this->load->model('User_model');
        $user = $this->User_model->get_by_id($user_id);
        return $user ? $user['role'] : false;
    }
    
    /**
     * Get connection status (for debugging)
     */
    public function status() {
        // Override SSE headers for this method
        header('Content-Type: application/json');
        header('Connection: close');
        
        $status = [
            'status' => 'SSE Controller is working',
            'timestamp' => date('c'),
            'message' => 'Controller loaded successfully'
        ];
        
        echo json_encode($status);
        exit; // Ensure clean exit
    }
}
