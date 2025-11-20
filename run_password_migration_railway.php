<?php
/**
 * Run password nullable migration on Railway database
 * This script reads Railway environment variables and applies the migration
 * 
 * Usage: Run this script on Railway via Railway Shell or one-off deploy
 */

// Resolve environment variables from multiple providers (supports getenv, $_SERVER, $_ENV)
$get = function($key) {
    if (getenv($key) !== false) return getenv($key);
    if (isset($_SERVER[$key])) return $_SERVER[$key];
    if (isset($_ENV[$key])) return $_ENV[$key];
    return null;
};

$envHost = $get('DB_HOST'); if (!$envHost) $envHost = $get('MYSQLHOST');
$envUser = $get('DB_USER'); if (!$envUser) $envUser = $get('MYSQLUSER');
$envPass = $get('DB_PASS'); if (!$envPass) $envPass = $get('MYSQLPASSWORD');
$envName = $get('DB_NAME'); if (!$envName) $envName = $get('MYSQLDATABASE');
$envPort = $get('DB_PORT'); if (!$envPort) $envPort = $get('MYSQLPORT');

// Support DATABASE_URL
$dbUrl = $get('DATABASE_URL');
if ($dbUrl) {
    $parts = parse_url($dbUrl);
    if ($parts !== false) {
        if (isset($parts['host'])) { $envHost = $parts['host']; }
        if (isset($parts['port'])) { $envPort = $parts['port']; }
        if (isset($parts['user'])) { $envUser = $parts['user']; }
        if (isset($parts['pass'])) { $envPass = $parts['pass']; }
        if (isset($parts['path'])) { $envName = ltrim($parts['path'], '/'); }
    }
}

// Support Railway's MYSQL_URL/MYSQL_PUBLIC_URL
foreach (['MYSQL_URL', 'MYSQL_PUBLIC_URL'] as $urlVar) {
    $url = $get($urlVar);
    if ($url) {
        $parts = parse_url($url);
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

// Defaults
$host = $envHost ? $envHost : '127.0.0.1';
$username = $envUser ? $envUser : 'root';
$password = $envPass ? $envPass : '';
$database = $envName ? $envName : 'scms_db';
$port = $envPort ? (int)$envPort : 3306;

echo "🔧 Running Password Nullable Migration on Railway Database\n";
echo "===========================================================\n\n";
echo "Database: $database\n";
echo "Host: $host:$port\n";
echo "User: $username\n\n";

try {
    // Create connection
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully!\n\n";
    
    // Check current state
    $checkQuery = "SHOW COLUMNS FROM `users` WHERE Field = 'password'";
    $stmt = $pdo->query($checkQuery);
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        echo "Current password column state:\n";
        echo "  - Type: {$column['Type']}\n";
        echo "  - Null: {$column['Null']}\n";
        echo "  - Default: " . ($column['Default'] ?? 'NULL') . "\n\n";
        
        if ($column['Null'] === 'YES') {
            echo "✅ Password column is already nullable. No migration needed.\n";
            exit(0);
        }
    }
    
    // Run migration
    echo "🔄 Applying migration...\n";
    $alterStatement = "ALTER TABLE `users` MODIFY COLUMN `password` VARCHAR(255) NULL";
    $pdo->exec($alterStatement);
    echo "✅ Password column is now nullable!\n\n";
    
    // Verify the change
    $stmt = $pdo->query($checkQuery);
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column && $column['Null'] === 'YES') {
        echo "✅ Migration verified successfully!\n";
        echo "  - Type: {$column['Type']}\n";
        echo "  - Null: {$column['Null']}\n";
        echo "  - Default: " . ($column['Default'] ?? 'NULL') . "\n\n";
        echo "🎉 Users can now register without a password!\n";
    } else {
        echo "⚠️  Warning: Migration may not have applied correctly.\n";
        exit(1);
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

