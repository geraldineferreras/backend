<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database connection
$host = 'localhost:3308';
$dbname = 'scms_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get the requested role from query parameter
    $role = isset($_GET['role']) ? $_GET['role'] : '';
    
    if (empty($role)) {
        echo json_encode(['status' => false, 'message' => 'Role parameter is required']);
        exit;
    }
    
    // Query users by role
    $stmt = $pdo->prepare('SELECT * FROM users WHERE role = ?');
    $stmt->execute([$role]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => true,
        'data' => $users,
        'message' => 'Users fetched successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
