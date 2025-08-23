<?php
/**
 * 2FA System Setup Script
 * 
 * This script sets up the database tables and fields required for
 * Two-Factor Authentication in your SCMS system.
 */

// Database configuration
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

echo "🔐 Setting up Two-Factor Authentication System...\n\n";

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database successfully!\n\n";

    // Check if 2FA fields already exist
    echo "🔍 Checking current users table structure...\n";
    $stmt = $pdo->query("DESCRIBE users");
    $existing_fields = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_fields[] = $row['Field'];
    }

    $needs_2fa_fields = !in_array('two_factor_enabled', $existing_fields);

    if ($needs_2fa_fields) {
        echo "📝 Adding 2FA fields to users table...\n";
        
        // Add 2FA fields to users table
        $alter_users_sql = "
        ALTER TABLE `users` 
        ADD COLUMN `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Whether 2FA is enabled for this user',
        ADD COLUMN `two_factor_secret` VARCHAR(64) NULL COMMENT 'Secret key for 2FA',
        ADD COLUMN `two_factor_enabled_at` TIMESTAMP NULL COMMENT 'When 2FA was enabled',
        ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last update timestamp'
        ";
        
        $pdo->exec($alter_users_sql);
        echo "✅ Added 2FA fields to users table\n";
        
        // Add indexes
        echo "🔗 Adding indexes for better performance...\n";
        $pdo->exec("ALTER TABLE `users` ADD INDEX `idx_two_factor_enabled` (`two_factor_enabled`)");
        $pdo->exec("ALTER TABLE `users` ADD INDEX `idx_two_factor_secret` (`two_factor_secret`)");
        echo "✅ Added performance indexes\n";
    } else {
        echo "✅ 2FA fields already exist in users table\n";
    }

    // Check if backup_codes table exists
    echo "\n🔍 Checking for backup_codes table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'backup_codes'");
    $backup_table_exists = $stmt->rowCount() > 0;

    if (!$backup_table_exists) {
        echo "📝 Creating backup_codes table...\n";
        
        // Create backup codes table
        $create_backup_table_sql = "
        CREATE TABLE `backup_codes` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` varchar(20) NOT NULL COMMENT 'Reference to users table',
          `codes` JSON NOT NULL COMMENT 'Hashed backup codes',
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Backup codes for 2FA recovery'
        ";
        
        $pdo->exec($create_backup_table_sql);
        echo "✅ Created backup_codes table\n";
    } else {
        echo "✅ backup_codes table already exists\n";
    }

    // Show final table structures
    echo "\n📋 Final users table structure (2FA fields):\n";
    $stmt = $pdo->query("DESCRIBE users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (strpos($row['Field'], 'two_factor') !== false || $row['Field'] === 'updated_at') {
            echo "   {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Default']}\n";
        }
    }

    echo "\n📋 backup_codes table structure:\n";
    $stmt = $pdo->query("DESCRIBE backup_codes");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Default']}\n";
    }

    echo "\n🎉 2FA System Setup Complete!\n\n";
    echo "📚 Available API Endpoints:\n";
    echo "   POST /api/2fa/setup          - Set up 2FA for user\n";
    echo "   POST /api/2fa/verify         - Verify and enable 2FA\n";
    echo "   POST /api/2fa/disable        - Disable 2FA\n";
    echo "   GET  /api/2fa/status         - Check 2FA status\n";
    echo "   POST /api/2fa/login-verify   - Verify 2FA during login\n";
    echo "   POST /api/2fa/backup-code    - Use backup code\n\n";
    
    echo "🚀 Next Steps:\n";
    echo "1. Test the 2FA setup endpoint with Postman\n";
    echo "2. Install Google Authenticator or Authy on your phone\n";
    echo "3. Integrate 2FA into your frontend login flow\n";
    echo "4. Update your login process to check for 2FA requirement\n\n";

} catch(PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n\n";
    echo "🔧 Troubleshooting:\n";
    echo "1. Make sure XAMPP is running\n";
    echo "2. Check MySQL service is started\n";
    echo "3. Verify database connection details\n";
    echo "4. Ensure you have CREATE/ALTER permissions\n";
} catch(Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "\n";
}
?>
