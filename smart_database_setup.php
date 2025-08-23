<?php
/**
 * Smart Database Setup - Automatically finds the right password
 * 
 * This script tries multiple common passwords to connect to your database
 * and then sets up the unified account system.
 */

// Database configuration
$host = 'localhost:3308';  // Your MySQL runs on port 3308
$username = 'root';
$database = 'scms_db';

// Common passwords to try
$passwords_to_try = ['', 'root', 'password', 'admin', '123456'];

echo "🔍 Smart Database Connection - Trying to find the right password...\n\n";

$pdo = null;
$working_password = null;

// Try each password until one works
foreach ($passwords_to_try as $password) {
    try {
        echo "Trying password: " . ($password ?: '(empty)') . "... ";
        
        $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test the connection
        $pdo->query("SELECT 1");
        
        echo "✅ SUCCESS!\n";
        $working_password = $password;
        break;
        
    } catch (PDOException $e) {
        echo "❌ Failed\n";
    }
}

if (!$pdo) {
    echo "\n❌ Could not connect to database with any password!\n";
    echo "\n🔧 Manual Setup Required:\n";
    echo "1. Open XAMPP Control Panel\n";
    echo "2. Click 'Admin' next to MySQL\n";
    echo "3. Note the username/password you use to login\n";
    echo "4. Update the script with your actual credentials\n";
    echo "\n💡 Common XAMPP passwords:\n";
    echo "• Empty password (most common)\n";
    echo "• 'root'\n";
    echo "• 'password'\n";
    echo "• 'admin'\n";
    exit;
}

echo "\n🎉 Connected successfully with password: " . ($working_password ?: '(empty)') . "\n\n";

// Now proceed with the database setup
echo "🔧 Setting up unified account system...\n\n";

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Add Google OAuth fields
    $alter_queries = [
        "ALTER TABLE `users` ADD COLUMN `google_id` VARCHAR(255) NULL AFTER `password`",
        "ALTER TABLE `users` ADD COLUMN `account_type` ENUM('local', 'google', 'unified') NOT NULL DEFAULT 'local' AFTER `google_id`",
        "ALTER TABLE `users` ADD COLUMN `google_email_verified` BOOLEAN NOT NULL DEFAULT FALSE AFTER `account_type`",
        "ALTER TABLE `users` ADD COLUMN `last_oauth_login` TIMESTAMP NULL AFTER `google_email_verified`",
        "ALTER TABLE `users` ADD COLUMN `oauth_provider` VARCHAR(50) NULL AFTER `last_oauth_login`"
    ];
    
    foreach ($alter_queries as $query) {
        try {
            $pdo->exec($query);
            echo "✅ " . str_replace("ALTER TABLE `users` ADD COLUMN ", "", $query) . "\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠️  Column already exists, skipping...\n";
            } else {
                throw $e;
            }
        }
    }
    
    // 2. Add indexes for better performance
    $index_queries = [
        "CREATE INDEX idx_users_google_id ON users(google_id)",
        "CREATE INDEX idx_users_account_type ON users(account_type)",
        "CREATE INDEX idx_users_oauth_provider ON users(oauth_provider)"
    ];
    
    foreach ($index_queries as $query) {
        try {
            $pdo->exec($query);
            echo "✅ Index created: " . str_replace("CREATE INDEX ", "", $query) . "\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "⚠️  Index already exists, skipping...\n";
            } else {
                throw $e;
            }
        }
    }
    
    // 3. Update existing users to have proper account_type
    $update_query = "UPDATE users SET account_type = 'local' WHERE account_type IS NULL OR account_type = ''";
    $pdo->exec($update_query);
    echo "✅ Updated existing users to have 'local' account type\n";
    
    // 4. Create account linking table for future use
    $create_linking_table = "
    CREATE TABLE IF NOT EXISTS `account_links` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` varchar(50) NOT NULL,
      `provider` varchar(50) NOT NULL,
      `provider_user_id` varchar(255) NOT NULL,
      `provider_email` varchar(255) NOT NULL,
      `is_verified` tinyint(1) NOT NULL DEFAULT 0,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_user_provider` (`user_id`, `provider`),
      UNIQUE KEY `unique_provider_user` (`provider`, `provider_user_id`),
      KEY `idx_user_id` (`user_id`),
      KEY `idx_provider` (`provider`),
      CONSTRAINT `fk_account_links_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($create_linking_table);
    echo "✅ Created account_links table for future account linking\n";
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n🎉 Database migration completed successfully!\n\n";
    
    // Show updated table structure
    echo "📋 Updated users table structure:\n";
    $stmt = $pdo->query("DESCRIBE users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $field = $row['Field'];
        $type = $row['Type'];
        $null = $row['Null'];
        $key = $row['Key'];
        $default = $row['Default'];
        
        // Highlight new fields
        $is_new = in_array($field, ['google_id', 'account_type', 'google_email_verified', 'last_oauth_login', 'oauth_provider']);
        $marker = $is_new ? "🆕 " : "   ";
        
        echo "{$marker}{$field}: {$type} {$null} {$key} {$default}\n";
    }
    
    echo "\n📋 New account_links table structure:\n";
    $stmt = $pdo->query("DESCRIBE account_links");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
    }
    
    echo "\n🚀 Next steps:\n";
    echo "1. Update your Auth controller to handle unified login\n";
    echo "2. Modify Google OAuth to check for existing accounts\n";
    echo "3. Update frontend to show appropriate login options\n";
    echo "4. Test the unified account system\n";
    
    echo "\n💡 Working database credentials:\n";
    echo "Host: {$host}\n";
    echo "Username: {$username}\n";
    echo "Password: " . ($working_password ?: '(empty)') . "\n";
    echo "Database: {$database}\n";
    
} catch(PDOException $e) {
    // Rollback transaction on error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "❌ Database migration failed: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Make sure XAMPP is running\n";
    echo "2. Check if MySQL service is started\n";
    echo "3. Verify database name '{$database}' exists\n";
    echo "4. Ensure you have ALTER TABLE permissions\n";
}

echo "\n💡 What this migration adds:\n";
echo "• google_id: Stores Google's unique user ID\n";
echo "• account_type: Tracks if account is local, Google, or unified\n";
echo "• google_email_verified: Whether Google email is verified\n";
echo "• last_oauth_login: Last OAuth login timestamp\n";
echo "• oauth_provider: Which OAuth provider was used\n";
echo "• account_links: Table for linking multiple OAuth providers\n";
?>
