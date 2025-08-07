<?php
// Test script to check if user exists and test notification system
// Bypass CodeIgniter's direct access restriction
define('BASEPATH', '');

// Database connection
$hostname = 'localhost';
$username = 'root';
$password = '';
$database = 'scms_db';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connected successfully.\n\n";
    
    // Check if user 'J56NHD' exists
    $stmt = $pdo->prepare("SELECT user_id, full_name, email, role FROM users WHERE user_id = ?");
    $stmt->execute(['J56NHD']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "User 'J56NHD' found:\n";
        echo "Full Name: " . $user['full_name'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Role: " . $user['role'] . "\n\n";
    } else {
        echo "User 'J56NHD' NOT found in users table.\n\n";
        
        // Show some existing users
        echo "Some existing users:\n";
        $stmt = $pdo->query("SELECT user_id, full_name, email, role FROM users LIMIT 5");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $u) {
            echo "- " . $u['user_id'] . " (" . $u['full_name'] . ", " . $u['role'] . ")\n";
        }
        echo "\n";
    }
    
    // Check notifications table structure
    echo "Checking notifications table structure:\n";
    $stmt = $pdo->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    echo "\n";
    
    // Check if there are any existing notifications
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total notifications in database: " . $result['count'] . "\n\n";
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?> 