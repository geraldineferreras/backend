<?php
/**
 * Simplified Forgot Password Endpoint for Railway
 * This version focuses on core functionality without email dependencies
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }
    
    // Validate email
    $email = isset($data['email']) ? trim($data['email']) : '';
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Generate reset token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // For now, just log the reset link instead of sending email
    $frontend_url = 'https://scmsupdatedbackup.vercel.app';
    $reset_link = $frontend_url . "/auth/reset-password?token=" . $token;
    
    // Log the reset link (Railway will show this in logs)
    error_log("Password Reset Link for {$email}: {$reset_link}");
    error_log("Token: {$token} (expires: {$expires_at})");
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'If an account with that email exists, a password reset link has been sent.',
        'data' => [
            'email' => $email,
            'reset_link' => $reset_link,
            'expires_at' => $expires_at,
            'note' => 'Check Railway logs for the reset link (temporary for testing)'
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Forgot password error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'debug' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'error' => $e->getMessage()
        ]
    ]);
}
?>

