<?php
/**
 * Fix Existing Admin User
 * This script updates existing admin users to have admin_type = 'main_admin'
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

echo "🔧 Fixing Existing Admin User\n";
echo "==============================\n\n";

try {
    echo "📡 Connecting to Railway Database...\n";
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected successfully!\n\n";
    
    // Check existing admin users
    echo "🔍 Checking existing admin users...\n";
    $stmt = $pdo->query("SELECT user_id, full_name, email, role, admin_type FROM users WHERE role = 'admin'");
    $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($adminUsers)) {
        echo "❌ No admin users found!\n";
        exit;
    }
    
    echo "📊 Found " . count($adminUsers) . " admin user(s):\n";
    foreach ($adminUsers as $admin) {
        $adminType = $admin['admin_type'] ?: 'NULL';
        echo "• {$admin['full_name']} ({$admin['email']}) - admin_type: $adminType\n";
    }
    
    // Update admin users to have admin_type = 'main_admin'
    echo "\n🔧 Updating admin users...\n";
    $stmt = $pdo->prepare("UPDATE users SET admin_type = 'main_admin' WHERE role = 'admin' AND (admin_type IS NULL OR admin_type = '')");
    $stmt->execute();
    $updatedCount = $stmt->rowCount();
    
    echo "✅ Updated $updatedCount admin user(s) to admin_type = 'main_admin'\n";
    
    // Verify the update
    echo "\n🔍 Verifying update...\n";
    $stmt = $pdo->query("SELECT user_id, full_name, email, role, admin_type FROM users WHERE role = 'admin'");
    $updatedAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Updated admin users:\n";
    foreach ($updatedAdmins as $admin) {
        echo "• {$admin['full_name']} ({$admin['email']}) - admin_type: {$admin['admin_type']}\n";
    }
    
    echo "\n🎉 Fix completed successfully!\n";
    echo "Now you can login again and the admin_type field will be available.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n💡 This script needs Railway database connection.\n";
    echo "You can also run this SQL command directly in DBeaver:\n\n";
    echo "UPDATE users SET admin_type = 'main_admin' WHERE role = 'admin' AND (admin_type IS NULL OR admin_type = '');\n";
}
?>
