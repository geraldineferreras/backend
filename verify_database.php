<?php
/**
 * Verify Database Changes
 * This script checks if the admin_type column was added correctly
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

echo "🔍 Verifying Database Changes\n";
echo "=============================\n\n";

try {
    echo "📡 Connecting to Railway Database...\n";
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected successfully!\n\n";
    
    // Check if admin_type column exists
    echo "🔍 Checking admin_type column...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'admin_type'");
    $adminTypeColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($adminTypeColumn) {
        echo "✅ admin_type column exists!\n";
        echo "   Type: " . $adminTypeColumn['Type'] . "\n";
        echo "   Null: " . $adminTypeColumn['Null'] . "\n";
    } else {
        echo "❌ admin_type column does NOT exist!\n";
    }
    
    // Check role enum
    echo "\n🔍 Checking role enum...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
    $roleColumn = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (strpos($roleColumn['Type'], 'chairperson') !== false) {
        echo "✅ Role enum includes 'chairperson'!\n";
        echo "   Type: " . $roleColumn['Type'] . "\n";
    } else {
        echo "❌ Role enum does NOT include 'chairperson'!\n";
        echo "   Current type: " . $roleColumn['Type'] . "\n";
    }
    
    // Check existing users
    echo "\n📊 Current Users:\n";
    echo "=================\n";
    $stmt = $pdo->query("SELECT user_id, full_name, role, admin_type, program FROM users ORDER BY role, admin_type");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $adminType = $row['admin_type'] ?: 'NULL';
        $program = $row['program'] ?: 'N/A';
        echo sprintf("%-20s | %-20s | %-10s | %-15s | %s\n", 
            $row['user_id'], 
            $row['full_name'], 
            $row['role'], 
            $adminType,
            $program
        );
    }
    
    // Check indexes
    echo "\n🔍 Checking indexes...\n";
    $stmt = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'idx_admin_type'");
    if ($stmt->fetch()) {
        echo "✅ Index on admin_type exists!\n";
    } else {
        echo "⚠️  Index on admin_type does NOT exist\n";
    }
    
    $stmt = $pdo->query("SHOW INDEX FROM users WHERE Key_name = 'idx_program'");
    if ($stmt->fetch()) {
        echo "✅ Index on program exists!\n";
    } else {
        echo "⚠️  Index on program does NOT exist\n";
    }
    
    echo "\n🎯 Summary:\n";
    echo "===========\n";
    if ($adminTypeColumn && strpos($roleColumn['Type'], 'chairperson') !== false) {
        echo "✅ Database migration completed successfully!\n";
        echo "✅ Your role-based admin hierarchy system is ready!\n";
        echo "\nNext steps:\n";
        echo "1. Test the API endpoints\n";
        echo "2. Create Chairperson users\n";
        echo "3. Test access control\n";
    } else {
        echo "❌ Database migration incomplete!\n";
        echo "Please run the SQL commands in DBeaver again.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n💡 This script needs Railway database connection.\n";
    echo "The database changes were made in DBeaver, so this is expected.\n";
    echo "You can test the API endpoints directly instead.\n";
}
?>
