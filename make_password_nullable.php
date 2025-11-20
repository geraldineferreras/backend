<?php
/**
 * Makes password column nullable in users table
 * This allows users to register without a password (admin will send temporary password after approval)
 * 
 * Supports Railway environment variables: DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT
 * Also supports: MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, MYSQLPORT
 * And: DATABASE_URL, MYSQL_URL, MYSQL_PUBLIC_URL
 */

// Resolve environment variables from multiple providers
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

$host = $envHost ? $envHost : 'localhost';
$username = $envUser ? $envUser : 'root';
$password = $envPass ? $envPass : 'root';
$database = $envName ? $envName : 'scms_db';
$port = $envPort ? (int)$envPort : 3306;

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database {$database} on {$host}:{$port}" . PHP_EOL;

    // Alter password column to allow NULL
    $alterStatement = "ALTER TABLE `users` MODIFY COLUMN `password` VARCHAR(255) NULL";

    try {
        $pdo->exec($alterStatement);
        echo "✅ Password column is now nullable" . PHP_EOL;
    } catch (PDOException $e) {
        echo "❌ Error altering password column: " . $e->getMessage() . PHP_EOL;
        throw $e;
    }

    echo PHP_EOL . "✅ Migration completed successfully!" . PHP_EOL;
    echo "Users can now register without a password. Admin will send temporary password after approval." . PHP_EOL;

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

