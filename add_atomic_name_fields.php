<?php
/**
 * Adds atomic name fields (first_name, middle_name, last_name) to the users table.
 * These fields allow for proper name splitting and full_name generation.
 */

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';
$database = getenv('DB_DATABASE') ?: 'scms_db';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$database}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database {$database}" . PHP_EOL;

    // Add atomic name columns
    $alterStatements = [
        "ALTER TABLE `users` ADD COLUMN `first_name` VARCHAR(100) NULL AFTER `full_name`",
        "ALTER TABLE `users` ADD COLUMN `middle_name` VARCHAR(100) NULL AFTER `first_name`",
        "ALTER TABLE `users` ADD COLUMN `last_name` VARCHAR(100) NULL AFTER `middle_name`"
    ];

    foreach ($alterStatements as $statement) {
        try {
            $pdo->exec($statement);
            echo "🆕 " . $statement . PHP_EOL;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠️  Column already exists, skipping." . PHP_EOL;
            } else {
                throw $e;
            }
        }
    }

    // Add indexes for better search performance
    $indexStatements = [
        "CREATE INDEX idx_users_first_name ON users(first_name)",
        "CREATE INDEX idx_users_last_name ON users(last_name)"
    ];

    foreach ($indexStatements as $statement) {
        try {
            $pdo->exec($statement);
            echo "🆕 Index created: {$statement}" . PHP_EOL;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "⚠️  Index already exists, skipping." . PHP_EOL;
            } else {
                throw $e;
            }
        }
    }

    // Backfill existing users: Try to parse full_name into atomic fields
    echo PHP_EOL . "🔄 Attempting to backfill existing users from full_name..." . PHP_EOL;
    
    $stmt = $pdo->query("SELECT user_id, full_name FROM users WHERE (first_name IS NULL OR first_name = '') AND full_name IS NOT NULL AND full_name != ''");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    foreach ($users as $user) {
        $full_name = trim($user['full_name']);
        if (empty($full_name)) {
            continue;
        }
        
        // Try to parse the name
        // Common formats: "First Last", "First Middle Last", "First M. Last", "Last, First M."
        $parts = preg_split('/\s+/', $full_name);
        
        if (count($parts) >= 2) {
            $first_name = $parts[0];
            $last_name = end($parts);
            $middle_name = (count($parts) > 2) ? implode(' ', array_slice($parts, 1, -1)) : null;
            
            // Clean up middle name if it's just an initial
            if ($middle_name && strlen($middle_name) <= 3 && strpos($middle_name, '.') !== false) {
                // It's likely already an initial, keep as is
            } elseif ($middle_name) {
                // Take first letter of middle name if multiple words
                $middle_parts = explode(' ', $middle_name);
                $middle_name = $middle_parts[0]; // Use first word of middle name
            }
            
            $updateStmt = $pdo->prepare("UPDATE users SET first_name = ?, middle_name = ?, last_name = ? WHERE user_id = ?");
            $updateStmt->execute([$first_name, $middle_name, $last_name, $user['user_id']]);
            $updated++;
        }
    }
    
    echo "✅ Backfilled {$updated} existing users" . PHP_EOL;

    echo PHP_EOL . "🎉 Atomic name fields are ready!" . PHP_EOL;
    echo "You can now update the application to use first_name, middle_name, and last_name." . PHP_EOL;
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

