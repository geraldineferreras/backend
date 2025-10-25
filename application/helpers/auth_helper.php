<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Authentication Helper Functions
 */

/**
 * Check if user is authenticated via JWT token
 * @param CI_Controller $controller
 * @return array|false
 */
function check_auth($controller) {
    $controller->load->library('Token_lib');
    // Accept token from header first; fall back to query/cookie if needed (e.g., SSE/EventSource)
    $token = $controller->token_lib->get_token_from_request();
    
    if (!$token) {
        return false;
    }
    
    $payload = $controller->token_lib->validate_token($token);
    return $payload;
}

/**
 * Require authentication - returns user data or sends error response
 * @param CI_Controller $controller
 * @return array|void
 */
function require_auth($controller) {
    $user_data = check_auth($controller);
    
    if (!$user_data) {
        $controller->output
            ->set_status_header(401)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => false, 
                'message' => 'Authentication required. Please login.'
            ]));
        return;
    }
    
    return $user_data;
}

/**
 * Require specific role authentication
 * @param CI_Controller $controller
 * @param string|array $allowed_roles
 * @return array|void
 */
function require_role($controller, $allowed_roles) {
    $user_data = require_auth($controller);
    
    if (!$user_data) {
        return; // Error response already sent
    }
    
    $user_role = $user_data['role'];
    $allowed_roles = is_array($allowed_roles) ? $allowed_roles : [$allowed_roles];
    
    if (!in_array($user_role, $allowed_roles)) {
        $controller->output
            ->set_status_header(403)
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => false, 
                'message' => 'Access denied. Insufficient permissions.'
            ]));
        return;
    }
    
    return $user_data;
}

/**
 * Require admin role
 * @param CI_Controller $controller
 * @return array|void
 */
function require_admin($controller) {
    return require_role($controller, 'admin');
}

/**
 * Require teacher role
 * @param CI_Controller $controller
 * @return array|void
 */
function require_teacher($controller) {
    return require_role($controller, 'teacher');
}

/**
 * Require student role
 * @param CI_Controller $controller
 * @return array|void
 */
function require_student($controller) {
    return require_role($controller, 'student');
}

/**
 * Require admin or teacher role
 * @param CI_Controller $controller
 * @return array|void
 */
function require_admin_or_teacher($controller) {
    return require_role($controller, ['admin', 'teacher']);
}

/**
 * Require admin or student role
 * @param CI_Controller $controller
 * @return array|void
 */
function require_admin_or_student($controller) {
    return require_role($controller, ['admin', 'student']);
}

/**
 * Get current user ID from token
 * @param CI_Controller $controller
 * @return string|false
 */
function get_current_user_id($controller) {
    $user_data = check_auth($controller);
    return $user_data ? $user_data['user_id'] : false;
}

/**
 * Get current user role from token
 * @param CI_Controller $controller
 * @return string|false
 */
function get_current_user_role($controller) {
    $user_data = check_auth($controller);
    return $user_data ? $user_data['role'] : false;
}

/**
 * Check if current user can access specific user data
 * @param CI_Controller $controller
 * @param string $target_user_id
 * @param string $target_role
 * @return bool
 */
function can_access_user_data($controller, $target_user_id, $target_role) {
    $user_data = check_auth($controller);
    
    if (!$user_data) {
        return false;
    }
    
    $current_role = $user_data['role'];
    $current_user_id = $user_data['user_id'];
    
    // Admin can access all user data
    if ($current_role === 'admin') {
        return true;
    }
    
    // Users can access their own data
    if ($current_user_id === $target_user_id && $current_role === $target_role) {
        return true;
    }
    
    // Teachers can access student data (for their classes)
    if ($current_role === 'teacher' && $target_role === 'student') {
        // You can add additional logic here to check if teacher teaches this student
        return true;
    }
    
    return false;
}

/**
 * Generate a unique user ID with a prefix (e.g., ADM, TCH, STD)
 * @param string $prefix
 * @return string
 */
function generate_user_id($prefix) {
    return $prefix . strtoupper(uniqid()) . rand(100, 999);
}

