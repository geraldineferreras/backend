<?php
/**
 * Migration script to update existing admin users with blank admin_type
 * This script assigns admin_type based on the hierarchical admin system
 */

// Include CodeIgniter bootstrap
require_once('index.php');

// Get CodeIgniter instance
$CI =& get_instance();
$CI->load->database();

echo "Starting admin_type migration...\n";

try {
    // Get all admin users with blank or null admin_type
    $admins_without_type = $CI->db->select('user_id, program, admin_type')
        ->from('users')
        ->where('role', 'admin')
        ->where('(admin_type IS NULL OR admin_type = "")', null, false)
        ->get()
        ->result_array();
    
    echo "Found " . count($admins_without_type) . " admin users without admin_type\n";
    
    if (empty($admins_without_type)) {
        echo "No admin users need updating.\n";
        exit;
    }
    
    // Check if there's already a main_admin
    $existing_main_admin = $CI->db->select('user_id')
        ->from('users')
        ->where('role', 'admin')
        ->where('admin_type', 'main_admin')
        ->where('status', 'active')
        ->get()
        ->row_array();
    
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
        $update_data = ['admin_type' => $admin_type];
        $success = $CI->db->where('user_id', $user_id)->update('users', $update_data);
        
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
    $admin_distribution = $CI->db->select('admin_type, COUNT(*) as count')
        ->from('users')
        ->where('role', 'admin')
        ->group_by('admin_type')
        ->get()
        ->result_array();
    
    foreach ($admin_distribution as $dist) {
        $type = $dist['admin_type'] ?: 'NULL/BLANK';
        echo "- $type: " . $dist['count'] . " users\n";
    }
    
} catch (Exception $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
