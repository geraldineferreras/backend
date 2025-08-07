<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification_model extends CI_Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create a new notification
     */
    public function create_notification($data) {
        $notification_data = array(
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'related_id' => isset($data['related_id']) ? $data['related_id'] : null,
            'related_type' => isset($data['related_type']) ? $data['related_type'] : null,
            'class_code' => isset($data['class_code']) ? $data['class_code'] : null,
            'is_urgent' => isset($data['is_urgent']) ? $data['is_urgent'] : 0
        );
        
        $this->db->insert('notifications', $notification_data);
        return $this->db->insert_id();
    }
    
    /**
     * Get notifications for a user
     */
    public function get_user_notifications($user_id, $limit = 50, $offset = 0, $unread_only = false) {
        $this->db->select('*');
        $this->db->from('notifications');
        $this->db->where('user_id', $user_id);
        
        if ($unread_only) {
            $this->db->where('is_read', 0);
        }
        
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit, $offset);
        
        $query = $this->db->get();
        return $query->result();
    }
    
    /**
     * Get unread notification count for a user
     */
    public function get_unread_count($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        return $this->db->count_all_results('notifications');
    }
    
    /**
     * Mark notification as read
     */
    public function mark_as_read($notification_id, $user_id) {
        $this->db->where('id', $notification_id);
        $this->db->where('user_id', $user_id);
        return $this->db->update('notifications', array('is_read' => 1));
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function mark_all_as_read($user_id) {
        $this->db->where('user_id', $user_id);
        $this->db->where('is_read', 0);
        return $this->db->update('notifications', array('is_read' => 1));
    }
    
    /**
     * Delete a notification
     */
    public function delete_notification($notification_id, $user_id) {
        $this->db->where('id', $notification_id);
        $this->db->where('user_id', $user_id);
        return $this->db->delete('notifications');
    }
    
    /**
     * Get notification settings for a user
     */
    public function get_notification_settings($user_id) {
        $this->db->where('user_id', $user_id);
        $query = $this->db->get('notification_settings');
        return $query->row();
    }
    
    /**
     * Create or update notification settings for a user
     */
    public function update_notification_settings($user_id, $settings) {
        // Check if settings exist
        $existing = $this->get_notification_settings($user_id);
        
        if ($existing) {
            // Update existing settings
            $this->db->where('user_id', $user_id);
            return $this->db->update('notification_settings', $settings);
        } else {
            // Create new settings
            $settings['user_id'] = $user_id;
            return $this->db->insert('notification_settings', $settings);
        }
    }
    
    /**
     * Check if user has email notifications enabled for a specific type
     */
    public function is_email_enabled($user_id, $type) {
        $settings = $this->get_notification_settings($user_id);
        
        if (!$settings) {
            return true; // Default to enabled if no settings exist
        }
        
        if (!$settings->email_notifications) {
            return false;
        }
        
        // Check specific type setting
        $type_field = $type . '_notifications';
        return isset($settings->$type_field) ? $settings->$type_field : true;
    }
    
    /**
     * Get notifications by type for a user
     */
    public function get_notifications_by_type($user_id, $type, $limit = 20) {
        $this->db->select('*');
        $this->db->from('notifications');
        $this->db->where('user_id', $user_id);
        $this->db->where('type', $type);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        
        $query = $this->db->get();
        return $query->result();
    }
    
    /**
     * Get recent notifications for dashboard
     */
    public function get_recent_notifications($user_id, $limit = 10) {
        $this->db->select('*');
        $this->db->from('notifications');
        $this->db->where('user_id', $user_id);
        $this->db->order_by('created_at', 'DESC');
        $this->db->limit($limit);
        
        $query = $this->db->get();
        return $query->result();
    }
    
    /**
     * Get urgent notifications for a user
     */
    public function get_urgent_notifications($user_id) {
        $this->db->select('*');
        $this->db->from('notifications');
        $this->db->where('user_id', $user_id);
        $this->db->where('is_urgent', 1);
        $this->db->where('is_read', 0);
        $this->db->order_by('created_at', 'DESC');
        
        $query = $this->db->get();
        return $query->result();
    }
}
