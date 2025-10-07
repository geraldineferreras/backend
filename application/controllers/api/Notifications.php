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
        
        try {
            // Load authentication helpers
            $this->load->library('Token_lib');
            
            // Validate token from URL path
            if (!$token) {
                error_log("SSE Stream: No token provided");
                $this->sendError('Token required', 401);
                return;
            }
            
            // Validate token and get user data
            $payload = $this->token_lib->validate_token($token);
            if (!$payload) {
                error_log("SSE Stream: Invalid or expired token");
                $this->sendError('Invalid or expired token', 401);
                return;
            }
            
            $userId = $payload['user_id'];
            $role = $payload['role'];
            
            error_log("SSE Stream: userId={$userId}, role={$role}");
            
            // Send initial connection success
            $this->sendEvent('connected', [
                'message' => 'SSE connection established',
                'userId' => $userId,
                'role' => $role,
                'timestamp' => date('c')
            ]);
            
            // Keep connection alive and send notifications
            $this->streamNotifications($userId, $role);
            
        } catch (Exception $e) {
            error_log("SSE Stream: Exception in stream method: " . $e->getMessage());
            $this->sendError('Internal server error: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * SSE Stream endpoint for real-time notifications (token from Authorization header)
     * URL: /api/notifications/stream
     */
    public function stream_header() {
        // Load authentication helpers
        $this->load->library('Token_lib');
        
        // Get token from Authorization header
        $token = $this->token_lib->get_token_from_header();
        
        // Debug logging
        error_log("SSE Stream Header: Method called with token from header: " . ($token ? 'provided' : 'missing'));
        
        // Validate token
        if (!$token) {
            error_log("SSE Stream Header: No token provided in Authorization header");
            $this->sendError('Token required', 401);
            return;
        }
        
        // Validate token and get user data
        $payload = $this->token_lib->validate_token($token);
        if (!$payload) {
            error_log("SSE Stream Header: Invalid or expired token");
            $this->sendError('Invalid or expired token', 401);
            return;
        }
        
        $userId = $payload['user_id'];
        $role = $payload['role'];
        
        error_log("SSE Stream Header: userId={$userId}, role={$role}");
        
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
        try {
            // Send initial connection event
            $this->sendEvent('connected', [
                'message' => 'SSE connection established',
                'timestamp' => date('c'),
                'user_id' => $userId,
                'role' => $role
            ]);
            
            // Debug logging
            error_log("SSE Stream: User {$userId} connected with role {$role}");
            
            // Test database connection first
            try {
                $this->load->database();
                error_log("SSE Stream: Database connection established");
            } catch (Exception $e) {
                error_log("SSE Stream: Database connection failed: " . $e->getMessage());
                $this->sendEvent('error', [
                    'message' => 'Database connection failed',
                    'error' => $e->getMessage()
                ]);
                return;
            }
            
            // Send any existing unread notifications immediately
            try {
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
            } catch (Exception $e) {
                error_log("SSE Stream: Error getting notifications: " . $e->getMessage());
                $this->sendEvent('error', [
                    'message' => 'Error getting notifications',
                    'error' => $e->getMessage()
                ]);
            }
            
            $lastCheck = time();
            
            while (true) {
                try {
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
                } catch (Exception $e) {
                    error_log("SSE Stream: Error in main loop: " . $e->getMessage());
                    $this->sendEvent('error', [
                        'message' => 'Error in SSE stream',
                        'error' => $e->getMessage()
                    ]);
                    break;
                }
            }
        } catch (Exception $e) {
            error_log("SSE Stream: Fatal error: " . $e->getMessage());
            $this->sendEvent('error', [
                'message' => 'Fatal error in SSE stream',
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get new notifications for the user
     */
    private function getNewNotifications($userId, $role) {
        try {
            // Check if this is the first connection for this user
            $isFirstConnection = !isset($this->lastSentAtByUser[$userId]);
            
            if ($isFirstConnection) {
                error_log("SSE Debug: First connection for user {$userId} - sending all unread notifications");
                $this->lastSentAtByUser[$userId] = time(); // Set current time as baseline
            }
            
            $since = $this->lastSentAtByUser[$userId];
            
            // Debug logging
            error_log("SSE Debug: Getting notifications for user {$userId} since " . date('Y-m-d H:i:s', $since));
            
            // Ensure database connection is established
            if (!$this->db->conn_id) {
                error_log("SSE Debug: Database connection not established, attempting to reconnect");
                $this->load->database();
            }
            
            // Get unread notifications for this user
            $this->db->select('*');
            $this->db->from('notifications');
            $this->db->where('user_id', $userId);
            $this->db->where('is_read', 0);
            $this->db->order_by('created_at', 'DESC');
            $this->db->limit(10);
            
            // Debug: Log the exact SQL query being executed
            $sql = $this->db->get_compiled_select();
            error_log("SSE Debug: SQL Query: " . $sql);
            
            $query = $this->db->get();
            $rows = $query->result_array();
            
        } catch (Exception $e) {
            error_log("SSE Debug: Database error in getNewNotifications: " . $e->getMessage());
            return []; // Return empty array on database error
        }
        
        // Debug: Log the actual query results
        error_log("SSE Debug: Query executed, returned " . count($rows) . " rows");
        if (count($rows) > 0) {
            error_log("SSE Debug: First row: " . json_encode($rows[0]));
        }
        
        $newNotifications = [];
        $maxCreated = $since;
        
        foreach ($rows as $row) {
            $createdTs = isset($row['created_at']) ? strtotime($row['created_at']) : 0;
            error_log("SSE Debug: Processing notification ID {$row['id']}, created: {$row['created_at']}, timestamp: {$createdTs}, since: {$since}");
            
            // For first connection, send all unread notifications
            // For subsequent checks, only send notifications newer than last check
            if ($isFirstConnection || $createdTs > $since) {
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
        // If this is an SSE connection, send error event
        if ($this->input->server('HTTP_ACCEPT') === 'text/event-stream' || 
            strpos($this->input->server('REQUEST_URI'), '/stream') !== false) {
            $this->sendEvent('error', [
                'message' => $message,
                'code' => $code,
                'timestamp' => date('c')
            ]);
        } else {
            // For non-SSE requests, send JSON error response
            header('Content-Type: application/json');
            http_response_code($code);
            echo json_encode([
                'error' => true,
                'message' => $message,
                'code' => $code,
                'timestamp' => date('c')
            ]);
            exit;
        }
    }
    
    /**
     * Simple SSE test endpoint
     * GET /api/notifications/sse-test
     */
    public function sse_test() {
        // Set SSE headers
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Methods: GET');
        
        try {
            // Send initial connection event
            echo "event: connected\n";
            echo "data: " . json_encode([
                'message' => 'SSE test connection established',
                'timestamp' => date('c')
            ]) . "\n\n";
            flush();
            
            // Send a test notification after 2 seconds
            sleep(2);
            echo "event: notification\n";
            echo "data: " . json_encode([
                'id' => 'test-123',
                'type' => 'test',
                'title' => 'Test Notification',
                'message' => 'This is a test SSE notification',
                'timestamp' => date('c'),
                'is_urgent' => false
            ]) . "\n\n";
            flush();
            
            // Send heartbeat after 5 seconds
            sleep(3);
            echo "event: heartbeat\n";
            echo "data: " . json_encode([
                'timestamp' => date('c')
            ]) . "\n\n";
            flush();
            
            // Close connection
            echo "event: close\n";
            echo "data: " . json_encode([
                'message' => 'SSE test completed',
                'timestamp' => date('c')
            ]) . "\n\n";
            flush();
            
        } catch (Exception $e) {
            echo "event: error\n";
            echo "data: " . json_encode([
                'message' => 'SSE test error: ' . $e->getMessage(),
                'timestamp' => date('c')
            ]) . "\n\n";
            flush();
        }
    }

    /**
     * Test SSE connection endpoint
     * GET /api/notifications/test-sse
     */
    public function test_sse() {
        // Override SSE headers for this method
        header('Content-Type: application/json');
        header('Connection: close');
        
        try {
            // Load authentication helpers
            $this->load->library('Token_lib');
            
            // Get token from Authorization header
            $token = $this->token_lib->get_token_from_header();
            
            if (!$token) {
                $this->sendError('Token required', 401);
                return;
            }
            
            // Validate token and get user data
            $payload = $this->token_lib->validate_token($token);
            if (!$payload) {
                $this->sendError('Invalid or expired token', 401);
                return;
            }
            
            $userId = $payload['user_id'];
            $role = $payload['role'];
            
            // Check if user has unread notifications
            $this->load->database();
            $this->db->select('COUNT(*) as count');
            $this->db->from('notifications');
            $this->db->where('user_id', $userId);
            $this->db->where('is_read', 0);
            $query = $this->db->get();
            $result = $query->row_array();
            $unreadCount = $result['count'] ?? 0;
            
            // Get recent notifications
            $this->db->select('*');
            $this->db->from('notifications');
            $this->db->where('user_id', $userId);
            $this->db->order_by('created_at', 'DESC');
            $this->db->limit(5);
            $query = $this->db->get();
            $recentNotifications = $query->result_array();
            
            $response = [
                'status' => true,
                'message' => 'SSE test successful',
                'user_id' => $userId,
                'role' => $role,
                'unread_count' => $unreadCount,
                'recent_notifications' => $recentNotifications,
                'sse_endpoint' => base_url('api/notifications/stream'),
                'timestamp' => date('c')
            ];
            
            echo json_encode($response, JSON_PRETTY_PRINT);
            
        } catch (Exception $e) {
            $this->sendError('SSE test error: ' . $e->getMessage(), 500);
        }
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
    
    /**
     * Debug endpoint to check notifications for a user
     * GET /api/notifications/debug/{userId}
     */
    public function debug($userId = null) {
        // Override SSE headers for this method
        header('Content-Type: application/json');
        header('Connection: close');
        
        if (!$userId) {
            echo json_encode(['error' => 'User ID required']);
            exit;
        }
        
        try {
            // Get all notifications for this user
            $this->db->select('*');
            $this->db->from('notifications');
            $this->db->where('user_id', $userId);
            $this->db->order_by('created_at', 'DESC');
            $this->db->limit(20);
            
            $query = $this->db->get();
            $allNotifications = $query->result_array();
            
            // Get unread notifications
            $this->db->select('*');
            $this->db->from('notifications');
            $this->db->where('user_id', $userId);
            $this->db->where('is_read', 0);
            $this->db->order_by('created_at', 'DESC');
            
            $query = $this->db->get();
            $unreadNotifications = $query->result_array();
            
            $response = [
                'success' => true,
                'user_id' => $userId,
                'total_notifications' => count($allNotifications),
                'unread_notifications' => count($unreadNotifications),
                'all_notifications' => $allNotifications,
                'unread_notifications_data' => $unreadNotifications
            ];
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'error' => 'Error checking notifications: ' . $e->getMessage()
            ];
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Test endpoint to simulate getNewNotifications method
     * GET /api/notifications/test-sse/{userId}
     */
    public function test_sse($userId = null) {
        // Override SSE headers for this method
        header('Content-Type: application/json');
        header('Connection: close');
        
        if (!$userId) {
            echo json_encode(['error' => 'User ID required']);
            exit;
        }
        
        try {
            // Test database connection first
            $this->load->database();
            
            // Simple test query
            $testQuery = $this->db->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?", [$userId]);
            $testResult = $testQuery->row_array();
            
            // Test the getNewNotifications method
            $notifications = $this->getNewNotifications($userId, 'teacher');
            
            $response = [
                'success' => true,
                'user_id' => $userId,
                'database_test' => [
                    'connection' => 'ok',
                    'total_notifications' => $testResult['count']
                ],
                'notifications_found' => count($notifications),
                'notifications' => $notifications,
                'last_sent_at_by_user' => $this->lastSentAtByUser
            ];
            
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'error' => 'Error testing SSE method: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
        
        echo json_encode($response);
        exit;
    }
}


