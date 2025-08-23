<?php
/**
 * Setup Password Reset Table
 * This script creates the password_reset_tokens table needed for forgot password functionality
 */

// Database configuration - adjust these values according to your setup
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'scms_db'; // Change this to your actual database name

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully\n";
    
    // SQL to create the table
    $sql = "
    CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `email` varchar(255) NOT NULL,
      `token` varchar(255) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `expires_at` timestamp NOT NULL,
      `used` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`),
      KEY `token` (`token`),
      KEY `expires_at` (`expires_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    // Execute the SQL
    $pdo->exec($sql);
    echo "✅ Table 'password_reset_tokens' created successfully\n";
    
    // Create indexes for better performance
    $indexes = [
        "CREATE INDEX idx_password_reset_tokens_email_token ON password_reset_tokens(email, token)",
        "CREATE INDEX idx_password_reset_tokens_expires_at ON password_reset_tokens(expires_at)"
    ];
    
    foreach ($indexes as $index_sql) {
        try {
            $pdo->exec($index_sql);
            echo "✅ Index created successfully\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "ℹ️  Index already exists\n";
            } else {
                echo "⚠️  Warning creating index: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Verify table structure
    $stmt = $pdo->query("DESCRIBE password_reset_tokens");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📋 Table structure:\n";
    echo str_repeat("-", 50) . "\n";
    foreach ($columns as $column) {
        printf("%-15s %-20s %-10s %-5s %-5s %-5s\n", 
               $column['Field'], 
               $column['Type'], 
               $column['Null'], 
               $column['Key'], 
               $column['Default'], 
               $column['Extra']);
    }
    
    echo "\n🎉 Password reset table setup completed successfully!\n";
    echo "You can now use the forgot password functionality.\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "\nPlease check your database configuration:\n";
    echo "- Host: $host\n";
    echo "- Username: $username\n";
    echo "- Database: $database\n";
    echo "- Make sure MySQL is running\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
