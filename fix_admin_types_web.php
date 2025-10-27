<?php
/**
 * Web-based migration script to update existing admin users with blank admin_type
 * Access this file through your web browser: http://your-domain.com/fix_admin_types_web.php
 */

// Simple database connection
$host = '127.0.0.1';
$dbname = 'scms_db';
$username = 'root';
$password = '';

echo "<h2>Admin Type Migration Tool</h2>";
echo "<pre>";

try {
    // Connect to database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully!\n\n";
    
    // First, check and fix the admin_type column size if needed
    echo "🔧 Checking admin_type column size...\n";
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'admin_type'");
    $stmt->execute();
    $column_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column_info) {
        echo "   Current admin_type column: " . $column_info['Type'] . "\n";
        
        // Check if column is too small (less than 50 characters)
        if (strpos($column_info['Type'], 'varchar') !== false) {
            preg_match('/varchar\((\d+)\)/', $column_info['Type'], $matches);
            $current_size = isset($matches[1]) ? (int)$matches[1] : 0;
            
            if ($current_size < 50) {
                echo "   ⚠️  Column size is too small ($current_size). Expanding to 50 characters...\n";
                $pdo->exec("ALTER TABLE users MODIFY COLUMN admin_type VARCHAR(50)");
                echo "   ✅ Column size updated successfully!\n";
            } else {
                echo "   ✅ Column size is adequate ($current_size characters)\n";
            }
        }
    }
    echo "\n";
    
    // Get all admin users with blank or null admin_type
    $stmt = $pdo->prepare("
        SELECT user_id, program, admin_type, full_name
        FROM users 
        WHERE role = 'admin' 
        AND (admin_type IS NULL OR admin_type = '')
    ");
    $stmt->execute();
    $admins_without_type = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Found " . count($admins_without_type) . " admin users without admin_type:\n";
    foreach ($admins_without_type as $admin) {
        echo "   - " . $admin['full_name'] . " (Program: " . ($admin['program'] ?: 'NULL') . ")\n";
    }
    echo "\n";
    
    if (empty($admins_without_type)) {
        echo "✅ No admin users need updating.\n";
        exit;
    }
    
    // Check if there's already a main_admin
    $stmt = $pdo->prepare("
        SELECT user_id, full_name
        FROM users 
        WHERE role = 'admin' 
        AND admin_type = 'main_admin' 
        AND status = 'active'
    ");
    $stmt->execute();
    $existing_main_admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $main_admin_exists = !empty($existing_main_admin);
    echo "🔍 Main admin already exists: " . ($main_admin_exists ? "YES (" . $existing_main_admin['full_name'] . ")" : "NO") . "\n\n";
    
    $updated_count = 0;
    $main_admin_assigned = false;
    
    echo "🔄 Processing admin users...\n\n";
    
    foreach ($admins_without_type as $admin) {
        $user_id = $admin['user_id'];
        $program = $admin['program'];
        $full_name = $admin['full_name'];
        
        echo "👤 Processing: $full_name (Program: $program)\n";
        
        // Determine admin_type
        $admin_type = 'program_chairperson'; // Default
        
        // If no main_admin exists and program is BSIT, make this user main_admin
        if (!$main_admin_exists && !$main_admin_assigned && $program === 'BSIT') {
            $admin_type = 'main_admin';
            $main_admin_assigned = true;
            echo "   🎯 Assigning as main_admin (first BSIT admin)\n";
        } else {
            echo "   📋 Assigning as program_chairperson\n";
        }
        
        // Update the user
        $update_stmt = $pdo->prepare("UPDATE users SET admin_type = ? WHERE user_id = ?");
        $success = $update_stmt->execute([$admin_type, $user_id]);
        
        if ($success) {
            $updated_count++;
            echo "   ✅ Updated successfully\n";
        } else {
            echo "   ❌ Update failed\n";
        }
        
        echo "\n";
    }
    
    echo "🎉 Migration completed!\n";
    echo "📈 Updated $updated_count admin users\n\n";
    
    // Show final admin distribution
    echo "📊 Final admin distribution:\n";
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
        echo "   - $type: " . $dist['count'] . " users\n";
    }
    
    echo "\n✅ All done! You can now refresh your database view.\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error during migration: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
