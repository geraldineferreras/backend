<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Audit Helper Functions
 * Provides easy-to-use functions for logging audit events
 */

if (!function_exists('log_audit_event')) {
    /**
     * Log an audit event
     * 
     * @param string $action_type The type of action (e.g., 'CREATED', 'UPDATED', 'DELETED', 'LOGGED IN')
     * @param string $module The module where the action occurred (e.g., 'USER MANAGEMENT', 'SECTION MANAGEMENT')
     * @param string $details Description of the action
     * @param array $additional_data Additional data to include (table_name, record_id, etc.)
     * @return bool Success status
     */
    function log_audit_event($action_type, $module, $details, $additional_data = []) {
        $CI =& get_instance();
        
        // Get current user data from JWT token (for API controllers)
        $user_data = null;
        
        // Try to get from token first (API approach)
        $token = $CI->input->get_request_header('Authorization');
        if ($token) {
            $token = str_replace('Bearer ', '', $token);
            
            try {
                // Load Token_lib if not already loaded
                if (!isset($CI->token_lib)) {
                    $CI->load->library('Token_lib');
                }
                
                $payload = $CI->token_lib->validate_token($token);
                if ($payload) {
                    $user_data = [
                        'user_id' => $payload['user_id'],
                        'name' => $payload['full_name'] ?? $payload['email'],
                        'username' => $payload['email'],
                        'role' => $payload['role']
                    ];
                }
            } catch (Exception $e) {
                // If token validation fails, continue without user data
                // This prevents the audit logging from breaking the main functionality
                log_message('error', 'Audit helper: Token validation failed: ' . $e->getMessage());
            }
        }
        
        // Fallback to session if token not available (for web controllers)
        if (!$user_data && isset($CI->session)) {
            $user_data = $CI->session->userdata('user_data');
        }
        
        if (!$user_data) {
            return false; // Can't log without user data
        }
        
        // Prepare audit data
        $audit_data = [
            'user_id' => $user_data['user_id'],
            'user_name' => $user_data['name'] ?? $user_data['username'] ?? 'Unknown User',
            'user_role' => $user_data['role'] ?? 'unknown',
            'action_type' => strtoupper($action_type),
            'module' => strtoupper($module),
            'details' => $details,
            'ip_address' => $CI->input->ip_address(),
            'table_name' => $additional_data['table_name'] ?? null,
            'record_id' => $additional_data['record_id'] ?? null
        ];
        
        // Load and use Audit model
        $CI->load->model('Audit_model');
        return $CI->Audit_model->create_log($audit_data);
    }
}

if (!function_exists('log_user_login')) {
    /**
     * Log user login event
     * 
     * @param array $user_data User data
     * @param string $ip_address IP address
     * @return bool Success status
     */
    function log_user_login($user_data, $ip_address = null) {
        $CI =& get_instance();
        
        if (!$ip_address) {
            $ip_address = $CI->input->ip_address();
        }
        
        $audit_data = [
            'user_id' => $user_data['user_id'],
            'user_name' => $user_data['name'] ?? $user_data['username'],
            'user_role' => $user_data['role'],
            'action_type' => 'LOGGED IN',
            'module' => 'AUTHENTICATION',
            'details' => "User logged in from IP {$ip_address}",
            'ip_address' => $ip_address
        ];
        
        $CI->load->model('Audit_model');
        return $CI->Audit_model->create_log($audit_data);
    }
}

if (!function_exists('log_user_logout')) {
    /**
     * Log user logout event
     * 
     * @param array $user_data User data
     * @param string $ip_address IP address
     * @return bool Success status
     */
    function log_user_logout($user_data, $ip_address = null) {
        $CI =& get_instance();
        
        if (!$ip_address) {
            $ip_address = $CI->input->ip_address();
        }
        
        $audit_data = [
            'user_id' => $user_data['user_id'],
            'user_name' => $user_data['name'] ?? $user_data['username'],
            'user_role' => $user_data['role'],
            'action_type' => 'LOGGED OUT',
            'module' => 'AUTHENTICATION',
            'details' => "User logged out from IP {$ip_address}",
            'ip_address' => $ip_address
        ];
        
        $CI->load->model('Audit_model');
        return $CI->Audit_model->create_log($audit_data);
    }
}

if (!function_exists('log_data_creation')) {
    /**
     * Log data creation event
     * 
     * @param string $table_name Table name
     * @param int $record_id Record ID
     * @param string $module Module name
     * @param string $details Additional details
     * @return bool Success status
     */
    function log_data_creation($table_name, $record_id, $module, $details = '') {
        return log_audit_event(
            'CREATED',
            $module,
            $details ?: "Created new record in {$table_name}",
            [
                'table_name' => $table_name,
                'record_id' => $record_id
            ]
        );
    }
}

if (!function_exists('log_data_update')) {
    /**
     * Log data update event
     * 
     * @param string $table_name Table name
     * @param int $record_id Record ID
     * @param string $module Module name
     * @param string $details Additional details
     * @return bool Success status
     */
    function log_data_update($table_name, $record_id, $module, $details = '') {
        return log_audit_event(
            'UPDATED',
            $module,
            $details ?: "Updated record in {$table_name}",
            [
                'table_name' => $table_name,
                'record_id' => $record_id
            ]
        );
    }
}

if (!function_exists('log_data_deletion')) {
    /**
     * Log data deletion event
     * 
     * @param string $table_name Table name
     * @param int $record_id Record ID
     * @param string $module Module name
     * @param string $details Additional details
     * @return bool Success status
     */
    function log_data_deletion($table_name, $record_id, $module, $details = '') {
        return log_audit_event(
            'DELETED',
            $module,
            $details ?: "Deleted record from {$table_name}",
            [
                'table_name' => $table_name,
                'record_id' => $record_id
            ]
        );
    }
}

if (!function_exists('log_report_export')) {
    /**
     * Log report export event
     * 
     * @param string $module Module name
     * @param string $report_type Type of report
     * @param string $format Export format (CSV, PDF, etc.)
     * @return bool Success status
     */
    function log_report_export($module, $report_type, $format = 'CSV') {
        return log_audit_event(
            'EXPORTED REPORT',
            $module,
            "Exported {$report_type} report in {$format} format"
        );
    }
} 