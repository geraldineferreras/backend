<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends CI_Controller {
    private $lastSentAtByUser = []; // Stores last check timestamp per user
    
    public function __construct() {
        parent::__construct();
        
        // Disable output buffering for SSE
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Methods: GET');
        header('X-Accel-Buffering: no'); // Disable nginx buffering

        $this->load->database();
        $this->load->model('Notification_model');
        
        // Debug logging
        error_log("SSE Notifications Controller: Constructor called");
        error_log("SSE Notifications Controller: Database config - " . json_encode([
            'hostname' => $this->db->hostname,
            'database' => $this->db->database,
            'username' => $this->db->username
        ]));
    }
    
    /**
     * SSE Stream endpoint for real-time notifications
     * URL: /api/notifications/stream/{token}
     */
    public function stream($token = null) {
        // Debug logging
        error_log("SSE Stream: Method called with token: " . ($token ? 'provided' : 'missing'));
        
        // Validate token from URL path
        if (!$token) {
            error_log("SSE Stream: No token provided");
            $this->sendError('Token required', 401);
            return;
        }
        
        // Get query parameters
        $userId = $this->input->get('userId');
        $role = $this->input->get('role');
        
        error_log("SSE Stream: userId={$userId}, role={$role}");
        
        // Validate required parameters
        if (!$userId || !$role) {
            error_log("SSE Stream: Missing required parameters - userId: {$userId}, role: {$role}");
            $this->sendError('userId and role required', 400);
            return;
        }
        
        // Send initial connection success
        $this->sendEvent('connected', [
            'message' => 'SSE connection established',
            'userId' => $userId,
            'role' => $role,
            'timestamp' => date('c')
        ]);
        
        // Keep connection alive and send notifications
        $this->streamNotifications($userId, $role);
    }
    
    /**
     * Stream notifications to the client
     */
    private function streamNotifications($userId, $role) {
        // Send initial connection event
        $this->sendEvent('connected', [
            'message' => 'SSE connection established',
            'timestamp' => date('c'),
            'user_id' => $userId,
            'role' => $role
        ]);
        
        // Debug logging
        error_log("SSE Stream: User {$userId} connected with role {$role}");
        
        // Send any existing unread notifications immediately
        $notifications = $this->getNewNotifications($userId, $role);
        error_log("SSE Stream: Found " . count($notifications) . " notifications for user {$userId}");
        
        if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                error_log("SSE Stream: Sending notification ID " . $notification['id'] . " to user {$userId}");
                $this->sendEvent('notification', $notification);
            }
        } else {
            error_log("SSE Stream: No notifications found for user {$userId} - this might be the issue!");
        }
        
        $lastCheck = time();
        
        while (true) {
            // Check for new notifications every 5 seconds
            if (time() - $lastCheck >= 5) {
                $notifications = $this->getNewNotifications($userId, $role);
                
                if (!empty($notifications)) {
                    error_log("SSE Stream: Found " . count($notifications) . " new notifications for user {$userId}");
                    foreach ($notifications as $notification) {
                        error_log("SSE Stream: Sending new notification ID " . $notification['id'] . " to user {$userId}");
                        $this->sendEvent('notification', $notification);
                    }
                } else {
                    error_log("SSE Stream: No new notifications found for user {$userId} in this check");
                }
                
                $lastCheck = time();
            }
            
            // Send heartbeat every 30 seconds
            if (time() % 30 === 0) {
                $this->sendEvent('heartbeat', [
                    'timestamp' => date('c')
                ]);
            }
            
            // Flush output buffer
            if (ob_get_level()) {
                ob_end_flush();
            }
            flush();
            
            // Sleep for 1 second
            sleep(1);
            
            // Check if client is still connected
            if (connection_aborted()) {
                error_log("SSE Stream: Client disconnected for user {$userId}");
                break;
            }
        }
    }
    
    /**
     * Get new notifications for the user
     */
    private function getNewNotifications($userId, $role) {
        // Initialize last check time for this user
        if (!isset($this->lastSentAtByUser[$userId])) {
            $this->lastSentAtByUser[$userId] = time() - 3600; // Start from 1 hour ago to catch recent notifications
        }
        
        $since = $this->lastSentAtByUser[$userId];
        
        // On first connection, send all unread notifications
        if ($since === (time() - 3600)) {
            error_log("SSE Debug: First connection for user {$userId} - sending all unread notifications");
            $since = 0; // Get all notifications
        }
        
        // Debug logging
        error_log("SSE Debug: Getting notifications for user {$userId} since " . date('Y-m-d H:i:s', $since));
        
        // Get unread notifications created after the last check time
        $this->db->select('*');
        $this->db->from('notifications');
        $this->db->where('user_id', $userId);
        $this->db->where('is_read', 0);
        
        // Only add time filter if not getting all notifications
        if ($since > 0) {
            $this->db->where('created_at >', date('Y-m-d H:i:s', $since));
        }
        
        $this->db->order_by('created_at', 'ASC');
        $this->db->limit(10);
        
        // Debug: Log the exact SQL query being executed
        $sql = $this->db->get_compiled_select();
        error_log("SSE Debug: SQL Query: " . $sql);
        
        $query = $this->db->get();
        $rows = $query->result_array();
        
        // Debug: Log the actual query results
        error_log("SSE Debug: Query executed, returned " . count($rows) . " rows");
        if (count($rows) > 0) {
            error_log("SSE Debug: First row: " . json_encode($rows[0]));
        }
        
        // Debug: Also check for ALL notifications for this user (without time filter)
        $this->db->select('*');
        $this->db->from('notifications');
        $this->db->where('user_id', $userId);
        $this->db->where('is_read', 0);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit(5);
        
        $allQuery = $this->db->get();
        $allRows = $allQuery->result_array();
        error_log("SSE Debug: ALL unread notifications for user {$userId}: " . count($allRows));
        
        if (count($allRows) > 0) {
            foreach ($allRows as $row) {
                error_log("SSE Debug: Found notification ID {$row['id']}, created: {$row['created_at']}, title: {$row['title']}");
            }
        }
        
        // Debug logging
        error_log("SSE Debug: Query returned " . count($rows) . " notifications for user {$userId}");

        $newNotifications = [];
        $maxCreated = $since;
        
        foreach ($rows as $row) {
            $createdTs = isset($row['created_at']) ? strtotime($row['created_at']) : 0;
            error_log("SSE Debug: Processing notification ID {$row['id']}, created: {$row['created_at']}, timestamp: {$createdTs}, since: {$since}");
            
            if ($createdTs > $since) {
                error_log("SSE Debug: Adding notification ID {$row['id']} to new notifications");
                $newNotifications[] = [
                    'id' => $row['id'],
                    'type' => $row['type'] ?? 'info',
                    'title' => $row['title'] ?? 'Notification',
                    'message' => $row['message'] ?? '',
                    'timestamp' => $createdTs ? date('c', $createdTs) : date('c'),
                    'is_urgent' => (bool)($row['is_urgent'] ?? false),
                    'data' => [
                        'related_id' => $row['related_id'] ?? null,
                        'related_type' => $row['related_type'] ?? null,
                        'class_code' => $row['class_code'] ?? null,
                        'role' => $role
                    ]
                ];
                if ($createdTs > $maxCreated) {
                    $maxCreated = $createdTs;
                }
            } else {
                error_log("SSE Debug: Skipping notification ID {$row['id']} - too old");
            }
        }

        // Update the last check time to the most recent notification or current time
        $this->lastSentAtByUser[$userId] = $maxCreated ?: time();
        error_log("SSE Debug: Updated last check time to " . date('Y-m-d H:i:s', $this->lastSentAtByUser[$userId]));
        
        error_log("SSE Debug: Returning " . count($newNotifications) . " new notifications for user {$userId}");
        return $newNotifications;
    }
    
    /**
     * Send SSE event to client
     */
    private function sendEvent($event, $data) {
        // Debug logging
        error_log("SSE sendEvent: Sending event '{$event}' with data: " . json_encode($data));
        
        echo "event: {$event}\n";
        echo "data: " . json_encode($data) . "\n\n";
        
        if (ob_get_level()) {
            ob_end_flush();
        }
        flush();
    }
    
    /**
     * Send error event
     */
    private function sendError($message, $code = 400) {
        $this->sendEvent('error', [
            'message' => $message,
            'code' => $code,
            'timestamp' => date('c')
        ]);
    }
    
    /**
     * Create a test notification for testing SSE
     * POST /api/notifications/create-test
     */
    public function create_test() {
        // Override SSE headers for this method
        header('Content-Type: application/json');
        header('Connection: close');
        
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
                $response = [
                    'success' => true,
                    'message' => 'Test notification created successfully',
                    'notification_id' => $notificationId,
                    'data' => $notificationData
                ];
            } else {
                $response = [
                    'success' => false,
                    'error' => 'Failed to create test notification'
                ];
            }
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'error' => 'Error creating test notification: ' . $e->getMessage()
            ];
        }
        
        echo json_encode($response);
        exit;
    }
}


