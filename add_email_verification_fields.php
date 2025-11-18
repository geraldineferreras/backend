<?php
/**
 * Adds email verification support columns to the users table.
 */

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';
$database = getenv('DB_DATABASE') ?: 'scms_db';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$database}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database {$database}" . PHP_EOL;

    $alterStatements = [
        "ALTER TABLE `users` ADD COLUMN `email_verification_status` ENUM('pending','verified') NOT NULL DEFAULT 'verified' AFTER `status`",
        "ALTER TABLE `users` ADD COLUMN `email_verification_token` VARCHAR(128) NULL AFTER `email_verification_status`",
        "ALTER TABLE `users` ADD COLUMN `email_verification_expires_at` DATETIME NULL AFTER `email_verification_token`",
        "ALTER TABLE `users` ADD COLUMN `email_verification_sent_at` DATETIME NULL AFTER `email_verification_expires_at`",
        "ALTER TABLE `users` ADD COLUMN `email_verified_at` DATETIME NULL AFTER `email_verification_sent_at`"
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

    $indexStatements = [
        "CREATE INDEX idx_users_email_verification_status ON users(email_verification_status)",
        "CREATE INDEX idx_users_email_verification_token ON users(email_verification_token)"
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

    $updateStatement = "
        UPDATE users
        SET email_verification_status = 'verified',
            email_verified_at = COALESCE(email_verified_at, NOW())
        WHERE email_verification_status IS NULL OR email_verification_status = ''
    ";
    $affected = $pdo->exec($updateStatement);
    echo "✅ Backfilled existing users ({$affected} rows updated)" . PHP_EOL;

    echo PHP_EOL . "🎉 Email verification columns are ready!" . PHP_EOL;
    echo "You can now update the application to use the new verification endpoints." . PHP_EOL;
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

