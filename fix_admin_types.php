<?php
/**
 * Simple migration script to update existing admin users with blank admin_type
 */

// Database configuration
$host = '127.0.0.1';
$dbname = 'scms_db';
$username = 'root';
$password = '';

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully!\n";
    
    // Get all admin users with blank or null admin_type
    $stmt = $pdo->prepare("
        SELECT user_id, program, admin_type 
        FROM users 
        WHERE role = 'admin' 
        AND (admin_type IS NULL OR admin_type = '')
    ");
    $stmt->execute();
    $admins_without_type = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($admins_without_type) . " admin users without admin_type\n";
    
    if (empty($admins_without_type)) {
        echo "No admin users need updating.\n";
        exit;
    }
    
    // Check if there's already a main_admin
    $stmt = $pdo->prepare("
        SELECT user_id 
        FROM users 
        WHERE role = 'admin' 
        AND admin_type = 'main_admin' 
        AND status = 'active'
    ");
    $stmt->execute();
    $existing_main_admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $main_admin_exists = !empty($existing_main_admin);
    echo "Main admin already exists: " . ($main_admin_exists ? "YES" : "NO") . "\n";
    
    $updated_count = 0;
    $main_admin_assigned = false;
    
    foreach ($admins_without_type as $admin) {
        $user_id = $admin['user_id'];
        $program = $admin['program'];
        
        echo "Processing admin: $user_id, Program: $program\n";
        
        // Determine admin_type
        $admin_type = 'program_chairperson'; // Default
        
        // If no main_admin exists and program is BSIT, make this user main_admin
        if (!$main_admin_exists && !$main_admin_assigned && $program === 'BSIT') {
            $admin_type = 'main_admin';
            $main_admin_assigned = true;
            echo "  -> Assigning as main_admin (first BSIT admin)\n";
        } else {
            echo "  -> Assigning as program_chairperson\n";
        }
        
        // Update the user
        $update_stmt = $pdo->prepare("UPDATE users SET admin_type = ? WHERE user_id = ?");
        $success = $update_stmt->execute([$admin_type, $user_id]);
        
        if ($success) {
            $updated_count++;
            echo "  -> Updated successfully\n";
        } else {
            echo "  -> Update failed\n";
        }
        
        echo "\n";
    }
    
    echo "Migration completed!\n";
    echo "Updated $updated_count admin users\n";
    
    // Show final admin distribution
    echo "\nFinal admin distribution:\n";
    $stmt = $pdo->prepare("
        SELECT admin_type, COUNT(*) as count 
        FROM users 
        WHERE role = 'admin' 
        GROUP BY admin_type
    ");
    $stmt->execute();
    $admin_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($admin_distribution as $dist) {
        $type = $dist['admin_type'] ?: 'NULL/BLANK';
        echo "- $type: " . $dist['count'] . " users\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
