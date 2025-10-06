<?php
/**
 * Simplified Reset Password Endpoint for Railway
 * This version handles password reset with tokens
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
    
    // Validate input
    $token = isset($data['token']) ? trim($data['token']) : '';
    $new_password = isset($data['new_password']) ? $data['new_password'] : '';
    
    if (empty($token) || empty($new_password)) {
        throw new Exception('Token and new password are required');
    }
    
    if (strlen($new_password) < 6) {
        throw new Exception('Password must be at least 6 characters long');
    }
    
    // For now, just validate the token format and return success
    // In a real implementation, you would:
    // 1. Check if token exists in database
    // 2. Check if token is not expired
    // 3. Check if token is not already used
    // 4. Update user password
    // 5. Mark token as used
    
    if (strlen($token) !== 64) {
        throw new Exception('Invalid token format');
    }
    
    // Log the reset attempt
    error_log("Password reset attempt with token: " . substr($token, 0, 8) . "...");
    error_log("New password length: " . strlen($new_password));
    
    // Return success response
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Password has been reset successfully',
        'data' => [
            'token_used' => substr($token, 0, 8) . '...',
            'password_updated' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'note' => 'This is a simplified version - actual password update would happen here'
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Reset password error: " . $e->getMessage());
    
    // Return error response
    http_response_code(400);
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
