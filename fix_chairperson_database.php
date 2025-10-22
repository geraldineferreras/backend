<?php
/**
 * Quick Database Fix for Chairperson Login
 * Run this script to fix the database structure issues
 */

echo "🔧 QUICK DATABASE FIX FOR CHAIRPERSON LOGIN\n";
echo "===========================================\n\n";

// Database connection parameters (update these with your Railway credentials)
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'scms_db';
$port = getenv('DB_PORT') ?: 3306;

echo "📡 Database Connection Info:\n";
echo "Host: $host\n";
echo "Database: $database\n";
echo "Port: $port\n\n";

try {
    echo "🔌 Connecting to database...\n";
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected successfully!\n\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    echo "🔍 Checking current database structure...\n";
    
    // Check if admin_type column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'admin_type'");
    $adminTypeExists = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminTypeExists) {
        echo "❌ admin_type column does not exist. Adding it...\n";
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `admin_type` ENUM('main_admin', 'chairperson') NULL AFTER `role`");
        echo "✅ admin_type column added!\n";
    } else {
        echo "✅ admin_type column already exists\n";
    }
    
    // Check role enum
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    $roleColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (strpos($roleColumn['Type'], 'chairperson') === false) {
        echo "❌ Role enum does not include 'chairperson'. Updating it...\n";
        $pdo->exec("ALTER TABLE `users` MODIFY COLUMN `role` ENUM('admin', 'teacher', 'student', 'chairperson') NOT NULL");
        echo "✅ Role enum updated to include 'chairperson'!\n";
    } else {
        echo "✅ Role enum already includes 'chairperson'\n";
    }
    
    // Update existing admin users to be main_admin
    echo "🔧 Updating existing admin users...\n";
    $stmt = $pdo->prepare("UPDATE users SET admin_type = 'main_admin' WHERE role = 'admin' AND (admin_type IS NULL OR admin_type = '')");
    $stmt->execute();
    $adminCount = $stmt->rowCount();
    echo "✅ Updated $adminCount existing admin users to main_admin\n";
    
    // Fix existing chairperson users (if any)
    echo "🔧 Fixing existing chairperson users...\n";
    $stmt = $pdo->prepare("UPDATE users SET admin_type = 'chairperson' WHERE role = 'chairperson' AND (admin_type IS NULL OR admin_type = '')");
    $stmt->execute();
    $chairpersonCount = $stmt->rowCount();
    echo "✅ Fixed $chairpersonCount existing chairperson users\n";
    
    // Ensure all chairperson users are active
    echo "🔧 Ensuring chairperson users are active...\n";
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE role = 'chairperson' AND status = 'inactive'");
    $stmt->execute();
    $activeCount = $stmt->rowCount();
    echo "✅ Activated $activeCount chairperson users\n";
    
    // Add indexes for better performance
    echo "🔧 Adding performance indexes...\n";
    try {
        $pdo->exec("CREATE INDEX `idx_admin_type` ON `users` (`admin_type`)");
        echo "✅ Added idx_admin_type index\n";
    } catch (Exception $e) {
        echo "⚠️  idx_admin_type index already exists\n";
    }
    
    try {
        $pdo->exec("CREATE INDEX `idx_program` ON `users` (`program`)");
        echo "✅ Added idx_program index\n";
    } catch (Exception $e) {
        echo "⚠️  idx_program index already exists\n";
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n🎉 Database structure fixed successfully!\n\n";
    
    // Show current users
    echo "📊 Current Users in Database:\n";
    echo "==============================\n";
    $stmt = $pdo->query("SELECT user_id, full_name, email, role, admin_type, program, status FROM users ORDER BY role, admin_type");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "❌ No users found in database!\n";
    } else {
        printf("%-20s | %-25s | %-30s | %-10s | %-15s | %-40s | %s\n", 
            'User ID', 'Full Name', 'Email', 'Role', 'Admin Type', 'Program', 'Status');
        echo str_repeat('-', 150) . "\n";
        
        foreach ($users as $user) {
            $adminType = $user['admin_type'] ?: 'NULL';
            $program = $user['program'] ?: 'N/A';
            printf("%-20s | %-25s | %-30s | %-10s | %-15s | %-40s | %s\n", 
                $user['user_id'], 
                $user['full_name'], 
                $user['email'], 
                $user['role'], 
                $adminType,
                $program,
                $user['status']
            );
        }
    }
    
    echo "\n🧪 NEXT STEPS:\n";
    echo "==============\n";
    echo "1. Test login with existing chairperson users:\n";
    echo "   • doctor.philip@pampangastateu.edu.ph\n";
    echo "   • r.delossantos@pampangastateu.edu.ph\n\n";
    
    echo "2. If no chairperson users exist, create one:\n";
    echo "   POST /api/admin/create_user\n";
    echo "   {\n";
    echo "     \"role\": \"chairperson\",\n";
    echo "     \"full_name\": \"Test Chairperson\",\n";
    echo "     \"email\": \"test.chairperson@university.edu\",\n";
    echo "     \"password\": \"chairperson123\",\n";
    echo "     \"program\": \"Bachelor of Science in Computer Science\"\n";
    echo "   }\n\n";
    
    echo "3. Test login with the new user\n\n";
    
    echo "✅ Database is now ready for chairperson login!\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n\n";
    echo "💡 Troubleshooting:\n";
    echo "1. Check your Railway database credentials\n";
    echo "2. Ensure the database service is running\n";
    echo "3. Verify environment variables are set correctly\n";
    echo "4. Check if the database name exists\n\n";
    
    echo "🔧 Manual Fix:\n";
    echo "If you can't connect via script, run these SQL commands manually:\n\n";
    echo "-- 1. Add admin_type column\n";
    echo "ALTER TABLE \`users\` ADD COLUMN \`admin_type\` ENUM('main_admin', 'chairperson') NULL AFTER \`role\`;\n\n";
    echo "-- 2. Update role enum\n";
    echo "ALTER TABLE \`users\` MODIFY COLUMN \`role\` ENUM('admin', 'teacher', 'student', 'chairperson') NOT NULL;\n\n";
    echo "-- 3. Update existing admins\n";
    echo "UPDATE users SET admin_type = 'main_admin' WHERE role = 'admin';\n\n";
    echo "-- 4. Add indexes\n";
    echo "CREATE INDEX \`idx_admin_type\` ON \`users\` (\`admin_type\`);\n";
    echo "CREATE INDEX \`idx_program\` ON \`users\` (\`program\`);\n\n";
}
?>
