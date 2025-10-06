<?php
/**
 * Production Password Reset Table Setup
 * This script creates the password_reset_tokens table for production use
 */

// Database configuration for Railway/Production
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'scms_new';

echo "=== Password Reset Table Setup (Production) ===\n\n";

echo "Database Configuration:\n";
echo "Host: {$host}\n";
echo "Username: {$username}\n";
echo "Database: {$database}\n\n";

try {
    // Create connection
    $pdo = new PDO("mysql:host={$host};dbname={$database}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully!\n\n";
    
    // SQL to create the password reset tokens table
    $sql = "
    CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `email` varchar(255) NOT NULL,
      `token` varchar(255) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `expires_at` datetime NOT NULL,
      `used` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`),
      KEY `token` (`token`),
      KEY `expires_at` (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Execute the SQL
    $pdo->exec($sql);
    echo "✅ Password reset tokens table created successfully!\n\n";
    
    // Create indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_email_token ON password_reset_tokens(email, token)",
        "CREATE INDEX IF NOT EXISTS idx_password_reset_tokens_expires_at ON password_reset_tokens(expires_at)"
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            $pdo->exec($index_sql);
            echo "✅ Index created successfully\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "ℹ️  Index already exists\n";
            } else {
                echo "⚠️  Index creation warning: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Verify the table was created
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_reset_tokens'");
    if ($stmt->rowCount() > 0) {
        echo "\n✅ Table verification successful!\n";
        echo "✅ Password reset functionality is now ready!\n\n";
        
        // Show table structure
        echo "📋 Table structure:\n";
        $stmt = $pdo->query("DESCRIBE password_reset_tokens");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
        }
        
        // Test insert and delete
        echo "\n🧪 Testing table functionality...\n";
        try {
            // Test insert
            $test_token = bin2hex(random_bytes(32));
            $test_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (email, token, expires_at, used) VALUES (?, ?, ?, ?)");
            $stmt->execute(['test@example.com', $test_token, $test_expires, 0]);
            echo "✅ Test insert successful\n";
            
            // Test select
            $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE email = ?");
            $stmt->execute(['test@example.com']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                echo "✅ Test select successful\n";
            }
            
            // Test delete
            $stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE email = ?");
            $stmt->execute(['test@example.com']);
            echo "✅ Test delete successful\n";
            
            echo "\n🎉 All tests passed! The password reset functionality is ready to use.\n";
            
        } catch (PDOException $e) {
            echo "❌ Table test failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ Table creation failed!\n";
    }
    
} catch(PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Check your database credentials\n";
    echo "2. Ensure the database exists\n";
    echo "3. Verify network connectivity\n";
    echo "4. Check if the user has CREATE TABLE permissions\n";
}

echo "\n=== Setup Complete ===\n";
?>
