<?php
/**
 * Simple test to check profile picture handling in the database
 */

// Database connection
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== DATABASE CONNECTION SUCCESSFUL ===\n\n";
    
    // Check users table structure
    echo "=== USERS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (in_array($row['Field'], ['profile_pic', 'cover_pic'])) {
            echo "Field: {$row['Field']}\n";
            echo "Type: {$row['Type']}\n";
            echo "Null: {$row['Null']}\n";
            echo "Default: {$row['Default']}\n";
            echo "---\n";
        }
    }
    
    // Check recent users and their profile pictures
    echo "\n=== RECENT USERS WITH PROFILE PICTURES ===\n";
    $stmt = $pdo->query("SELECT user_id, full_name, role, profile_pic, cover_pic, created_at 
                         FROM users 
                         ORDER BY created_at DESC 
                         LIMIT 10");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "User ID: {$row['user_id']}\n";
        echo "Name: {$row['full_name']}\n";
        echo "Role: {$row['role']}\n";
        echo "Profile Pic: " . ($row['profile_pic'] ?: 'NULL') . "\n";
        echo "Cover Pic: " . ($row['cover_pic'] ?: 'NULL') . "\n";
        echo "Created: {$row['created_at']}\n";
        echo "---\n";
    }
    
    // Check for users with empty profile_pic fields
    echo "\n=== USERS WITH EMPTY PROFILE PICTURE FIELDS ===\n";
    $stmt = $pdo->query("SELECT user_id, full_name, role, profile_pic, cover_pic 
                         FROM users 
                         WHERE profile_pic = '' OR profile_pic IS NULL 
                         ORDER BY created_at DESC 
                         LIMIT 5");
    
    $empty_profile_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($empty_profile_users)) {
        echo "No users found with empty profile picture fields.\n";
    } else {
        foreach ($empty_profile_users as $user) {
            echo "User ID: {$user['user_id']}\n";
            echo "Name: {$user['full_name']}\n";
            echo "Role: {$user['role']}\n";
            echo "Profile Pic: " . ($user['profile_pic'] ?: 'NULL') . "\n";
            echo "Cover Pic: " . ($user['cover_pic'] ?: 'NULL') . "\n";
            echo "---\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
}
?>
