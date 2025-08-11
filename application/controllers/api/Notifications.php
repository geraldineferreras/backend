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
        $rows = $this->Notification_model->get_user_notifications($userId, 50, 0, false);

        $newNotifications = [];
        $maxCreated = $since;
        foreach ($rows as $row) {
            $createdTs = isset($row->created_at) ? strtotime($row->created_at) : 0;
            if ($createdTs > $since) {
                $id = isset($row->id) ? $row->id : (isset($row->notification_id) ? $row->notification_id : uniqid());
                $newNotifications[] = [
                    'id' => $id,
                    'type' => isset($row->type) ? $row->type : 'info',
                    'title' => isset($row->title) ? $row->title : 'Notification',
                    'message' => isset($row->message) ? $row->message : '',
                    'timestamp' => $createdTs ? date('c', $createdTs) : date('c'),
                    'is_urgent' => isset($row->is_urgent) ? (bool)$row->is_urgent : false,
                    'data' => [
                        'related_id' => isset($row->related_id) ? $row->related_id : null,
                        'related_type' => isset($row->related_type) ? $row->related_type : null,
                        'class_code' => isset($row->class_code) ? $row->class_code : null,
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
}


