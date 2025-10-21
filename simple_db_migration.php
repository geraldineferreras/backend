<?php
/**
 * Simple Database Migration for Role-Based Admin Hierarchy
 * 
 * This script adds only the missing fields to your existing users table.
 * Since you already have the 'program' field, we only need to add:
 * - admin_type field (to distinguish Main Admin from Chairpersons)
 * - department field (optional, for better organization)
 * - Update role enum to include 'chairperson'
 */

// Resolve DB settings from environment
$get = function($k) {
    if (getenv($k) !== false) return getenv($k);
    if (isset($_SERVER[$k])) return $_SERVER[$k];
    if (isset($_ENV[$k])) return $_ENV[$k];
    return null;
};

$envHost = $get('DB_HOST'); if (!$envHost) $envHost = $get('MYSQLHOST');
$envUser = $get('DB_USER'); if (!$envUser) $envUser = $get('MYSQLUSER');
$envPass = $get('DB_PASS'); if (!$envPass) $envPass = $get('MYSQLPASSWORD');
$envName = $get('DB_NAME'); if (!$envName) $envName = $get('MYSQLDATABASE');
$envPort = $get('DB_PORT'); if (!$envPort) $envPort = $get('MYSQLPORT');

// Fallback to URL forms
$urlCandidates = [$get('DATABASE_URL'), $get('MYSQL_URL'), $get('MYSQL_PUBLIC_URL')];
foreach ($urlCandidates as $candidate) {
    if ($candidate) {
        $parts = parse_url($candidate);
        if ($parts !== false) {
            if (isset($parts['host'])) { $envHost = $parts['host']; }
            if (isset($parts['port'])) { $envPort = $parts['port']; }
            if (isset($parts['user'])) { $envUser = $parts['user']; }
            if (isset($parts['pass'])) { $envPass = $parts['pass']; }
            if (isset($parts['path'])) { $envName = ltrim($parts['path'], '/'); }
            break;
        }
    }
}

$host = $envHost ? $envHost : '127.0.0.1';
$username = $envUser ? $envUser : 'root';
$password = $envPass ? $envPass : '';
$database = $envName ? $envName : 'scms_db';
$port = $envPort ? (int)$envPort : 3306;

echo "🚀 Simple Database Migration for Role-Based Admin Hierarchy\n";
echo "===========================================================\n\n";

try {
    echo "📡 Connecting to Railway Database...\n";
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected successfully!\n\n";
    
    // Check current structure
    echo "🔍 Checking current table structure...\n";
    $stmt = $pdo->query('DESCRIBE users');
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    echo "✅ Found " . count($columns) . " columns in users table\n";
    echo "✅ Program field already exists (as you mentioned)\n\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    echo "🔧 Starting modifications...\n\n";
    
    // 1. Add admin_type field (if it doesn't exist)
    if (!in_array('admin_type', $columns)) {
        echo "1. Adding admin_type field...\n";
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `admin_type` ENUM('main_admin', 'chairperson') NULL AFTER `role`");
        echo "✅ Added admin_type field\n";
    } else {
        echo "1. ⚠️  admin_type field already exists, skipping...\n";
    }
    
    // 2. Skip department field - not needed for single department system
    echo "2. Skipping department field (not needed for College of Computing Studies only)\n";
    
    // 3. Update role enum to include chairperson (if needed)
    echo "3. Checking role enum...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    $roleColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (strpos($roleColumn['Type'], 'chairperson') === false) {
        echo "   Updating role enum to include 'chairperson'...\n";
        try {
            $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin', 'teacher', 'student', 'chairperson') NOT NULL");
            echo "✅ Role enum updated to include chairperson\n";
        } catch (Exception $e) {
            echo "⚠️  Role enum update failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✅ Role enum already includes 'chairperson'\n";
    }
    
    // 4. Update existing admin users to be main_admin
    echo "4. Updating existing admin users...\n";
    $stmt = $pdo->prepare("UPDATE users SET admin_type = 'main_admin' WHERE role = 'admin' AND (admin_type IS NULL OR admin_type = '')");
    $stmt->execute();
    $adminCount = $stmt->rowCount();
    echo "✅ Updated $adminCount existing admin users to main_admin\n";
    
    // 5. Add indexes for better performance
    echo "5. Adding indexes...\n";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS `idx_admin_type` ON `users` (`admin_type`)",
        "CREATE INDEX IF NOT EXISTS `idx_program` ON `users` (`program`)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
        } catch (Exception $e) {
            // Index might already exist, continue
        }
    }
    echo "✅ Indexes added\n";
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n🎉 Migration completed successfully!\n\n";
    
    // Show summary
    echo "📊 Migration Summary:\n";
    echo "====================\n";
    
    // Count users by role and admin_type
    $stmt = $pdo->query("SELECT role, admin_type, COUNT(*) as count FROM users GROUP BY role, admin_type ORDER BY role, admin_type");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $display = $row['role'];
        if ($row['admin_type']) {
            $display .= " (" . $row['admin_type'] . ")";
        }
        echo sprintf("%-25s: %d users\n", $display, $row['count']);
    }
    
    echo "\n📋 Your existing programs:\n";
    $stmt = $pdo->query('SELECT program, COUNT(*) as count FROM users WHERE program IS NOT NULL AND program != "" GROUP BY program ORDER BY count DESC');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-50s: %d users\n", $row['program'], $row['count']);
    }
    
    echo "\n✅ Next Steps:\n";
    echo "==============\n";
    echo "1. Your database is now ready for role-based admin hierarchy!\n";
    echo "2. Your backend code has been updated with the new methods\n";
    echo "3. Test the new API endpoints:\n";
    echo "   - GET /api/admin/get_students\n";
    echo "   - POST /api/admin/create_user\n";
    echo "   - GET /api/admin/get_chairpersons\n";
    echo "   - GET /api/admin/get_available_programs\n";
    echo "   - GET /api/admin/get_user_permissions\n";
    echo "   - PUT /api/admin/update_user/{user_id}\n";
    echo "4. Create Chairperson users for each program (CS, IS, IT, CT)\n";
    echo "5. Update frontend to respect new access controls\n";
    
    echo "\n💡 Key Points:\n";
    echo "- Main Admin: role='admin' AND admin_type='main_admin'\n";
    echo "- Chairperson: role='chairperson' AND admin_type='chairperson'\n";
    echo "- Chairpersons can only manage students in their program\n";
    echo "- Your existing 'program' field is perfect for this system!\n";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "\n💡 Troubleshooting:\n";
    echo "- Check your Railway environment variables\n";
    echo "- Ensure DATABASE_URL or DB_* variables are set correctly\n";
    echo "- Verify the database service is running on Railway\n";
    echo "- Check Railway logs for any database connection issues\n";
}
?>
