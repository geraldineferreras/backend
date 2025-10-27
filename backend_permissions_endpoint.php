<?php
/**
 * User Permissions Management API Endpoint
 * 
 * Handles:
 * - GET /api/admin/permissions/{userId} - Get user permissions
 * - POST /api/admin/permissions/{userId} - Save user permissions
 * - GET /api/admin/permissions - Get all user permissions
 */

// Enable CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Load database configuration
    require_once 'config/database.php';
    
    // Get JWT token from Authorization header
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        throw new Exception('Authorization header missing or invalid');
    }
    
    $token = $matches[1];
    
    // For now, we'll skip JWT validation to get this working
    // In production, you should implement proper JWT validation
    
    // Connect to database
    $pdo = getDatabaseConnection();
    
    // Parse the request URI
    $requestUri = $_SERVER['REQUEST_URI'];
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Remove query string and decode
    $path = parse_url($requestUri, PHP_URL_PATH);
    $pathParts = explode('/', trim($path, '/'));
    
    // Expected paths:
    // /api/admin/permissions/{userId} - GET/POST
    // /api/admin/permissions - GET
    
    if (count($pathParts) >= 3 && $pathParts[0] === 'api' && $pathParts[1] === 'admin' && $pathParts[2] === 'permissions') {
        
        if ($method === 'GET') {
            if (isset($pathParts[3])) {
                // GET /api/admin/permissions/{userId}
                $userId = $pathParts[3];
                getUserPermissions($pdo, $userId);
            } else {
                // GET /api/admin/permissions
                getAllUserPermissions($pdo);
            }
        } elseif ($method === 'POST' && isset($pathParts[3])) {
            // POST /api/admin/permissions/{userId}
            $userId = $pathParts[3];
            $input = json_decode(file_get_contents('php://input'), true);
            saveUserPermissions($pdo, $userId, $input);
        } else {
            throw new Exception('Invalid request method or path');
        }
    } else {
        throw new Exception('Invalid API endpoint');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => false,
        'message' => $e->getMessage(),
        'data' => null
    ]);
}

/**
 * Get permissions for a specific user
 */
function getUserPermissions($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT permissions FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $permissions = json_decode($result['permissions'], true);
            echo json_encode([
                'status' => true,
                'message' => 'User permissions retrieved successfully',
                'data' => [
                    'user_id' => $userId,
                    'permissions' => $permissions
                ]
            ]);
        } else {
            // No custom permissions found, return empty (will use defaults)
            echo json_encode([
                'status' => false,
                'message' => 'No custom permissions found for user',
                'data' => null
            ]);
        }
    } catch (Exception $e) {
        throw new Exception('Error retrieving user permissions: ' . $e->getMessage());
    }
}

/**
 * Save permissions for a specific user
 */
function saveUserPermissions($pdo, $userId, $input) {
    try {
        if (!isset($input['permissions']) || !is_array($input['permissions'])) {
            throw new Exception('Invalid permissions data');
        }
        
        $permissions = json_encode($input['permissions']);
        
        // Check if user permissions already exist
        $stmt = $pdo->prepare("SELECT user_id FROM user_permissions WHERE user_id = ?");
        $stmt->execute([$userId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Update existing permissions
            $stmt = $pdo->prepare("UPDATE user_permissions SET permissions = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->execute([$permissions, $userId]);
        } else {
            // Insert new permissions
            $stmt = $pdo->prepare("INSERT INTO user_permissions (user_id, permissions, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
            $stmt->execute([$userId, $permissions]);
        }
        
        echo json_encode([
            'status' => true,
            'message' => 'User permissions saved successfully',
            'data' => [
                'user_id' => $userId,
                'permissions' => $input['permissions']
            ]
        ]);
    } catch (Exception $e) {
        throw new Exception('Error saving user permissions: ' . $e->getMessage());
    }
}

/**
 * Get all user permissions (for admin overview)
 */
function getAllUserPermissions($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT up.user_id, up.permissions, u.full_name, u.email, u.role, u.admin_type
            FROM user_permissions up
            LEFT JOIN users u ON up.user_id = u.user_id
            ORDER BY u.full_name ASC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $userPermissions = [];
        foreach ($results as $row) {
            $userPermissions[] = [
                'user_id' => $row['user_id'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'role' => $row['role'],
                'admin_type' => $row['admin_type'],
                'permissions' => json_decode($row['permissions'], true)
            ];
        }
        
        echo json_encode([
            'status' => true,
            'message' => 'All user permissions retrieved successfully',
            'data' => $userPermissions
        ]);
    } catch (Exception $e) {
        throw new Exception('Error retrieving all user permissions: ' . $e->getMessage());
    }
}

/**
 * Create the user_permissions table if it doesn't exist
 * Run this once to set up the database table
 */
function createUserPermissionsTable($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS user_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        permissions JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_id (user_id),
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($sql);
}

// Uncomment the line below to create the table (run once)
// createUserPermissionsTable($pdo);

?>
