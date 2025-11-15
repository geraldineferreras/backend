<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Notification Broadcast Helper
 * 
 * This helper provides functions to broadcast notifications to connected SSE clients
 * and manage real-time notification delivery.
 */

if (!function_exists('broadcast_notification')) {
    /**
     * Broadcast a notification to all connected clients or specific users
     * 
     * @param array $notification_data Notification data to broadcast
     * @param array|int|null $target_users User ID(s) to send to (null = all users)
     * @param string $event_type Type of event (default: 'notification')
     * @return bool Success status
     */
    function broadcast_notification($notification_data, $target_users = null, $event_type = 'notification') {
        $CI =& get_instance();
        
        // Load the stream controller to access connections
        if (!class_exists('NotificationStreamController')) {
            $controllerPath = APPPATH . 'controllers/api/NotificationStreamController.php';
            if (file_exists($controllerPath) && is_readable($controllerPath)) {
                @require_once($controllerPath);
            } else {
                // NotificationStreamController doesn't exist, return silently
                return false;
            }
        }
        
        // Check if class exists after require
        if (!class_exists('NotificationStreamController')) {
            return false;
        }
        
        // Create instance to access connections
        $stream_controller = new NotificationStreamController();
        
        // Get connections using reflection (since connections are private)
        $reflection = new ReflectionClass($stream_controller);
        $connections_property = $reflection->getProperty('connections');
        $connections_property->setAccessible(true);
        $connections = $connections_property->getValue($stream_controller);
        
        if (empty($connections)) {
            return false; // No active connections
        }
        
        $sent_count = 0;
        
        foreach ($connections as $connection_id => $connection) {
            // Check if we should send to this user
            if ($target_users !== null) {
                if (is_array($target_users)) {
                    if (!in_array($connection['user_id'], $target_users)) {
                        continue;
                    }
                } else {
                    if ($connection['user_id'] != $target_users) {
                        continue;
                    }
                }
            }
            
            // Add user-specific data
            $message_data = array_merge($notification_data, [
                'user_id' => $connection['user_id'],
                'role' => $connection['role'],
                'timestamp' => date('c')
            ]);
            
            // Send the notification using the stream controller's method
            $send_method = $reflection->getMethod('send_sse_message');
            $send_method->setAccessible(true);
            $send_method->invoke($stream_controller, $event_type, $message_data);
            
            $sent_count++;
        }
        
        return $sent_count > 0;
    }
}

if (!function_exists('broadcast_to_role')) {
    /**
     * Broadcast notification to users with specific role
     * 
     * @param array $notification_data Notification data
     * @param string $role Role to target (teacher, student, admin)
     * @param string $event_type Type of event
     * @return bool Success status
     */
    function broadcast_to_role($notification_data, $role, $event_type = 'notification') {
        $CI =& get_instance();
        
        if (!class_exists('NotificationStreamController')) {
            $controllerPath = APPPATH . 'controllers/api/NotificationStreamController.php';
            if (file_exists($controllerPath) && is_readable($controllerPath)) {
                @require_once($controllerPath);
            } else {
                // NotificationStreamController doesn't exist, return silently
                return false;
            }
        }
        
        // Check if class exists after require
        if (!class_exists('NotificationStreamController')) {
            return false;
        }
        
        $stream_controller = new NotificationStreamController();
        $reflection = new ReflectionClass($stream_controller);
        $connections_property = $reflection->getProperty('connections');
        $connections_property->setAccessible(true);
        $connections = $connections_property->getValue($stream_controller);
        
        if (empty($connections)) {
            return false;
        }
        
        $sent_count = 0;
        
        foreach ($connections as $connection_id => $connection) {
            if ($connection['role'] === $role) {
                $message_data = array_merge($notification_data, [
                    'user_id' => $connection['user_id'],
                    'role' => $connection['role'],
                    'timestamp' => date('c')
                ]);
                
                $send_method = $reflection->getMethod('send_sse_message');
                $send_method->setAccessible(true);
                $send_method->invoke($stream_controller, $event_type, $message_data);
                
                $sent_count++;
            }
        }
        
        return $sent_count > 0;
    }
}

if (!function_exists('broadcast_urgent_notification')) {
    /**
     * Broadcast urgent notification to all connected users
     * 
     * @param string $title Notification title
     * @param string $message Notification message
     * @param string $type Notification type
     * @return bool Success status
     */
    function broadcast_urgent_notification($title, $message, $type = 'urgent') {
        $notification_data = [
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_urgent' => true,
            'priority' => 'critical',
            'icon' => '🚨'
        ];
        
        return broadcast_notification($notification_data, null, 'urgent_notification');
    }
}

if (!function_exists('get_connected_users_count')) {
    /**
     * Get count of currently connected users
     * 
     * @return int Number of connected users
     */
    function get_connected_users_count() {
        if (!class_exists('NotificationStreamController')) {
            $controllerPath = APPPATH . 'controllers/api/NotificationStreamController.php';
            if (file_exists($controllerPath) && is_readable($controllerPath)) {
                @require_once($controllerPath);
            } else {
                // NotificationStreamController doesn't exist, return silently
                return false;
            }
        }
        
        // Check if class exists after require
        if (!class_exists('NotificationStreamController')) {
            return false;
        }
        
        $stream_controller = new NotificationStreamController();
        $reflection = new ReflectionClass($stream_controller);
        $connections_property = $reflection->getProperty('connections');
        $connections_property->setAccessible(true);
        $connections = $connections_property->getValue($stream_controller);
        
        return count($connections);
    }
}

if (!function_exists('get_connected_users_by_role')) {
    /**
     * Get count of connected users by role
     * 
     * @return array Count by role
     */
    function get_connected_users_by_role() {
        if (!class_exists('NotificationStreamController')) {
            $controllerPath = APPPATH . 'controllers/api/NotificationStreamController.php';
            if (file_exists($controllerPath) && is_readable($controllerPath)) {
                @require_once($controllerPath);
            } else {
                // NotificationStreamController doesn't exist, return silently
                return false;
            }
        }
        
        // Check if class exists after require
        if (!class_exists('NotificationStreamController')) {
            return false;
        }
        
        $stream_controller = new NotificationStreamController();
        $reflection = new ReflectionClass($stream_controller);
        $connections_property = $reflection->getProperty('connections');
        $connections_property->setAccessible(true);
        $connections = $connections_property->getValue($stream_controller);
        
        $role_counts = [
            'teacher' => 0,
            'student' => 0,
            'admin' => 0,
            'total' => 0
        ];
        
        foreach ($connections as $connection) {
            $role = $connection['role'];
            if (isset($role_counts[$role])) {
                $role_counts[$role]++;
            }
            $role_counts['total']++;
        }
        
        return $role_counts;
    }
}
