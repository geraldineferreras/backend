<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends CI_Controller {
    private $lastSentAtByUser = [];
    
    public function __construct() {
        parent::__construct();
        
        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Content-Type');
        header('Access-Control-Allow-Methods: GET');

        $this->load->database();
        $this->load->model('Notification_model');
    }
    
    /**
     * SSE Stream endpoint for real-time notifications
     * URL: /api/notifications/stream/{token}
     */
    public function stream($token = null) {
        // Validate token from URL path
        if (!$token) {
            $this->sendError('Token required', 401);
            return;
        }
        
        // Get query parameters
        $userId = $this->input->get('userId');
        $role = $this->input->get('role');
        
        // Validate required parameters
        if (!$userId || !$role) {
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
        $lastCheck = time();
        
        while (true) {
            // Check for new notifications every 5 seconds
            if (time() - $lastCheck >= 5) {
                $notifications = $this->getNewNotifications($userId, $role);
                
                foreach ($notifications as $notification) {
                    $this->sendEvent('notification', $notification);
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
                break;
            }
        }
    }
    
    /**
     * Get new notifications for the user
     */
    private function getNewNotifications($userId, $role) {
        if (!isset($this->lastSentAtByUser[$userId])) {
            $this->lastSentAtByUser[$userId] = time();
            return [];
        }

        $since = $this->lastSentAtByUser[$userId];
        
        // Get notifications created after the last check time
        $this->db->select('*');
        $this->db->from('notifications');
        $this->db->where('user_id', $userId);
        $this->db->where('created_at >', date('Y-m-d H:i:s', $since));
        $this->db->order_by('created_at', 'ASC');
        $this->db->limit(10);
        
        $query = $this->db->get();
        $rows = $query->result_array();

        $newNotifications = [];
        $maxCreated = $since;
        
        foreach ($rows as $row) {
            $createdTs = isset($row['created_at']) ? strtotime($row['created_at']) : 0;
            if ($createdTs > $since) {
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
            }
        }

        $this->lastSentAtByUser[$userId] = $maxCreated ?: time();
        return $newNotifications;
    }
    
    /**
     * Send SSE event to client
     */
    private function sendEvent($event, $data) {
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


