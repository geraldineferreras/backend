<?php
/**
 * MINIMAL Railway Database Migration for Role-Based Admin Hierarchy
 * 
 * This script only adds the fields that are actually missing from your users table.
 * Since you already have the 'program' field, we only need to add:
 * - admin_type field (to distinguish Main Admin from Chairpersons)
 * - department field (optional, for better organization)
 * - Update role enum to include 'chairperson'
 * - Create programs table (optional, for data integrity)
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

echo "🚀 MINIMAL Railway Database Migration for Role-Based Admin Hierarchy\n";
echo "===================================================================\n\n";

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
    
    echo "🔧 Starting MINIMAL modifications...\n\n";
    
    // 1. Add admin_type field (if it doesn't exist)
    if (!in_array('admin_type', $columns)) {
        echo "1. Adding admin_type field...\n";
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `admin_type` ENUM('main_admin', 'chairperson') NULL AFTER `role`");
        echo "✅ Added admin_type field\n";
    } else {
        echo "1. ⚠️  admin_type field already exists, skipping...\n";
    }
    
    // 2. Add department field (if it doesn't exist)
    if (!in_array('department', $columns)) {
        echo "2. Adding department field...\n";
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `department` VARCHAR(100) NULL AFTER `program`");
        echo "✅ Added department field\n";
    } else {
        echo "2. ⚠️  department field already exists, skipping...\n";
    }
    
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
    
    // 4. Create programs table (optional, for data integrity)
    echo "4. Checking programs table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'programs'");
    if (!$stmt->fetch()) {
        echo "   Creating programs table for better data integrity...\n";
        $createProgramsTable = "
        CREATE TABLE `programs` (
          `program_id` int(11) NOT NULL AUTO_INCREMENT,
          `program_code` varchar(10) NOT NULL,
          `program_name` varchar(100) NOT NULL,
          `department` varchar(100) NOT NULL,
          `status` enum('active','inactive') NOT NULL DEFAULT 'active',
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`program_id`),
          UNIQUE KEY `unique_program_code` (`program_code`),
          KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($createProgramsTable);
        
        // Insert program data based on what you already have
        $programs = [
            ['BSCS', 'Bachelor of Science in Computer Science', 'College of Computing Studies'],
            ['BSIS', 'Bachelor of Science in Information Systems', 'College of Computing Studies'],
            ['BSIT', 'Bachelor of Science in Information Technology', 'College of Computing Studies'],
            ['ACT', 'Associate in Computer Technology', 'College of Computing Studies'],
            ['ADMIN', 'Administration', 'Administration']
        ];
        
        foreach ($programs as $program) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO programs (program_code, program_name, department) VALUES (?, ?, ?)");
            $stmt->execute($program);
        }
        echo "✅ Programs table created with your existing programs\n";
    } else {
        echo "✅ Programs table already exists\n";
    }
    
    // 5. Update existing admin users to be main_admin
    echo "5. Updating existing admin users...\n";
    $stmt = $pdo->prepare("UPDATE users SET admin_type = 'main_admin', department = 'Information Technology' WHERE role = 'admin' AND (admin_type IS NULL OR admin_type = '')");
    $stmt->execute();
    $adminCount = $stmt->rowCount();
    echo "✅ Updated $adminCount existing admin users to main_admin\n";
    
    // 6. Add indexes for better performance
    echo "6. Adding indexes...\n";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS `idx_admin_type` ON `users` (`admin_type`)",
        "CREATE INDEX IF NOT EXISTS `idx_program` ON `users` (`program`)",
        "CREATE INDEX IF NOT EXISTS `idx_department` ON `users` (`department`)"
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
    
    echo "\n🎉 MINIMAL Migration completed successfully!\n\n";
    
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
    echo "2. Update your backend code to use the new admin_type field\n";
    echo "3. Create Chairperson users for each program (CS, IS, IT, CT)\n";
    echo "4. Update frontend to respect new access controls\n";
    echo "5. Test the role-based access control\n";
    
    echo "\n💡 Key Points:\n";
    echo "- Main Admin: role='admin' AND admin_type='main_admin'\n";
    echo "- Chairperson: role='chairperson' AND admin_type='chairperson'\n";
    echo "- Chairpersons can only manage students in their program\n";
    echo "- Your existing 'program' field is perfect for this system!\n";
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
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
