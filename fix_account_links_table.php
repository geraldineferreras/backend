<?php
/**
 * Fix Account Links Table Creation
 * 
 * This script checks the users table structure and creates the account_links
 * table with proper foreign key constraints.
 */

// Database configuration
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database successfully!\n\n";
    
    // Check users table structure
    echo "🔍 Checking users table structure...\n";
    $stmt = $pdo->query("DESCRIBE users");
    $users_fields = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $users_fields[$row['Field']] = $row;
        echo "   {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
    }
    
    echo "\n🔍 Looking for primary key column...\n";
    $primary_key = null;
    foreach ($users_fields as $field => $info) {
        if ($info['Key'] === 'PRI') {
            $primary_key = $field;
            echo "✅ Found primary key: {$field} ({$info['Type']})\n";
            break;
        }
    }
    
    if (!$primary_key) {
        echo "❌ No primary key found in users table!\n";
        exit;
    }
    
    // Drop existing account_links table if it exists
    echo "\n🧹 Cleaning up existing account_links table...\n";
    try {
        $pdo->exec("DROP TABLE IF EXISTS `account_links`");
        echo "✅ Dropped existing account_links table\n";
    } catch (PDOException $e) {
        echo "⚠️  Could not drop table: " . $e->getMessage() . "\n";
    }
    
    // Create account_links table with foreign key constraint in one statement
    echo "\n🔧 Creating account_links table with foreign key...\n";
    
    $create_linking_table = "
    CREATE TABLE `account_links` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` varchar(20) NOT NULL,
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
      CONSTRAINT `fk_account_links_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`{$primary_key}`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    echo "Debug: Creating table with SQL:\n" . $create_linking_table . "\n";
    
    try {
        $pdo->exec($create_linking_table);
        echo "✅ Created account_links table with foreign key successfully!\n";
    } catch (PDOException $e) {
        echo "⚠️  Foreign key constraint failed, trying without it...\n";
        echo "Error: " . $e->getMessage() . "\n\n";
        
        // Fallback: Create table without foreign key
        $create_linking_table_fallback = "
        CREATE TABLE `account_links` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` varchar(20) NOT NULL,
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
          KEY `idx_provider` (`provider`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($create_linking_table_fallback);
        echo "✅ Created account_links table without foreign key (fallback)\n";
        echo "⚠️  Note: You'll need to handle referential integrity in your application code\n";
    }
    
    // Show final table structure
    echo "\n📋 Final account_links table structure:\n";
    $stmt = $pdo->query("DESCRIBE account_links");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
    }
    
    // Show constraints
    echo "\n🔗 Foreign key constraints:\n";
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = '{$database}' 
        AND TABLE_NAME = 'account_links' 
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['CONSTRAINT_NAME']}: {$row['COLUMN_NAME']} → {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
    }
    
    echo "\n🎉 Account links table created successfully!\n";
    echo "\n🚀 Your unified account system is now ready!\n";
    echo "Next steps:\n";
    echo "1. Update your Auth controller for unified login\n";
    echo "2. Test the system with both local and Google accounts\n";
    
} catch(PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Check if the users table exists\n";
    echo "2. Verify the primary key column type\n";
    echo "3. Ensure you have CREATE TABLE permissions\n";
}
?>
