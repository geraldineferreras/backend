<?php
/**
 * Setup Password Reset Database Table
 * 
 * This script creates the necessary database table for password reset functionality.
 * Run this script once to set up the required database structure.
 */

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'scms_new';

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
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
    
    // Verify the table was created
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_reset_tokens'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table verification successful!\n";
        echo "✅ You can now test the forgot password functionality!\n\n";
        
        // Show table structure
        echo "📋 Table structure:\n";
        $stmt = $pdo->query("DESCRIBE password_reset_tokens");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
        }
    } else {
        echo "❌ Table creation failed!\n";
    }
    
} catch(PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Make sure XAMPP is running\n";
    echo "2. Check if MySQL service is started\n";
    echo "3. Verify database name 'scms_new' exists\n";
    echo "4. Check username/password in this script\n";
}

echo "\n🚀 Next steps:\n";
echo "1. Test your frontend forgot password form\n";
echo "2. Check if emails are being sent\n";
echo "3. Verify reset links point to: http://localhost:3000/auth/reset-password\n";
?>
