<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (\['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database connection
\System.Management.Automation.Internal.Host.InternalHost = 'localhost:3308';
\ = 'scms_db';
\ = 'root';
\ = '';

try {
    \ = new PDO(\"mysql:host=\System.Management.Automation.Internal.Host.InternalHost;dbname=\\", \, \);
    \->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the requested role from query parameter
    \ = isset(\['role']) ? \['role'] : '';
    
    if (empty(\)) {
        echo json_encode(['status' => false, 'message' => 'Role parameter is required']);
        exit;
    }
    
    // Query users by role
    \ = \->prepare('SELECT * FROM users WHERE role = ?');
    \->execute([\]);
    \ = \->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => true,
        'data' => \,
        'message' => 'Users fetched successfully'
    ]);
    
} catch (PDOException \) {
    echo json_encode([
        'status' => false,
        'message' => 'Database error: ' . \->getMessage()
    ]);
}
?>
