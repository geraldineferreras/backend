<?php
/**
 * Check Current Railway Database Structure - CORRECTED VERSION
 * 
 * This script checks the actual current structure of the users table
 * to see what fields already exist and what needs to be added.
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

echo "🔍 Checking ACTUAL Railway Database Structure\n";
echo "=============================================\n\n";

try {
    echo "📡 Connecting to Railway Database...\n";
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected successfully!\n\n";
    
    // Get complete users table structure
    echo "📋 Complete Users Table Structure:\n";
    echo "==================================\n";
    $stmt = $pdo->query('DESCRIBE users');
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
        echo sprintf("%-25s %-30s %-5s %-10s %-10s\n", 
            $row['Field'], 
            $row['Type'], 
            $row['Null'], 
            $row['Key'], 
            $row['Default'] ?? 'NULL'
        );
    }
    
    echo "\n📊 Current User Roles Distribution:\n";
    echo "=====================================\n";
    $stmt = $pdo->query('SELECT role, COUNT(*) as count FROM users GROUP BY role');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-15s: %d users\n", $row['role'], $row['count']);
    }
    
    // Check program field specifically
    echo "\n🔍 Program Field Analysis:\n";
    echo "=========================\n";
    if (in_array('program', $columns)) {
        echo "✅ Program field EXISTS\n";
        
        // Show program distribution
        $stmt = $pdo->query('SELECT program, COUNT(*) as count FROM users WHERE program IS NOT NULL AND program != "" GROUP BY program ORDER BY count DESC');
        echo "\nProgram Distribution:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("%-50s: %d users\n", $row['program'], $row['count']);
        }
        
        // Show programs by role
        echo "\nPrograms by Role:\n";
        $stmt = $pdo->query('SELECT role, program, COUNT(*) as count FROM users WHERE program IS NOT NULL AND program != "" GROUP BY role, program ORDER BY role, count DESC');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("%-10s | %-40s: %d users\n", $row['role'], $row['program'], $row['count']);
        }
    } else {
        echo "❌ Program field does NOT exist\n";
    }
    
    // Check for admin_type field
    echo "\n🔍 Admin Type Field Analysis:\n";
    echo "=============================\n";
    if (in_array('admin_type', $columns)) {
        echo "✅ Admin type field EXISTS\n";
        $stmt = $pdo->query('SELECT admin_type, COUNT(*) as count FROM users WHERE admin_type IS NOT NULL GROUP BY admin_type');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("%-20s: %d users\n", $row['admin_type'], $row['count']);
        }
    } else {
        echo "❌ Admin type field does NOT exist\n";
    }
    
    // Check for department field
    echo "\n🔍 Department Field Analysis:\n";
    echo "============================\n";
    if (in_array('department', $columns)) {
        echo "✅ Department field EXISTS\n";
        $stmt = $pdo->query('SELECT department, COUNT(*) as count FROM users WHERE department IS NOT NULL GROUP BY department');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("%-30s: %d users\n", $row['department'], $row['count']);
        }
    } else {
        echo "❌ Department field does NOT exist\n";
    }
    
    // Check if programs table exists
    echo "\n🔍 Programs Table Analysis:\n";
    echo "===========================\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'programs'");
    $programsTable = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($programsTable) {
        echo "✅ Programs table EXISTS\n";
        $stmt = $pdo->query('SELECT * FROM programs');
        echo "\nPrograms in table:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo sprintf("%-10s: %s (%s)\n", $row['program_code'], $row['program_name'], $row['department']);
        }
    } else {
        echo "❌ Programs table does NOT exist\n";
    }
    
    echo "\n🎯 MINIMAL CHANGES NEEDED:\n";
    echo "==========================\n";
    
    $neededChanges = [];
    
    if (!in_array('admin_type', $columns)) {
        $neededChanges[] = "Add admin_type field to distinguish Main Admin from Chairpersons";
    }
    
    if (!in_array('department', $columns)) {
        $neededChanges[] = "Add department field for better organization";
    }
    
    if (!$programsTable) {
        $neededChanges[] = "Create programs table for better data integrity";
    }
    
    if (!in_array('chairperson', $pdo->query("SHOW COLUMNS FROM users WHERE Field = 'role'")->fetch(PDO::FETCH_ASSOC)['Type'])) {
        $neededChanges[] = "Update role enum to include 'chairperson'";
    }
    
    if (empty($neededChanges)) {
        echo "✅ No database changes needed! Your table already has all required fields.\n";
        echo "You can proceed directly to updating your backend code.\n";
    } else {
        echo "The following changes are needed:\n";
        foreach ($neededChanges as $i => $change) {
            echo sprintf("%d. %s\n", $i + 1, $change);
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n💡 Troubleshooting:\n";
    echo "- Check your Railway environment variables\n";
    echo "- Ensure DATABASE_URL or DB_* variables are set correctly\n";
    echo "- Verify the database service is running on Railway\n";
}
?>
