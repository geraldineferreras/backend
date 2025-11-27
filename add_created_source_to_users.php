<?php
/**
 * Adds created_source tracking to the users table.
 * This helps identify whether an account came from bulk upload,
 * self sign-up, admin manual creation, etc.
 */

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';
$database = getenv('DB_DATABASE') ?: 'scms_db';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$database}", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "✅ Connected to database {$database}" . PHP_EOL;

    $alterStatement = "ALTER TABLE `users` ADD COLUMN `created_source` VARCHAR(32) NULL DEFAULT NULL AFTER `created_at`";

    try {
        $pdo->exec($alterStatement);
        echo "🆕 Column created_source added to users table." . PHP_EOL;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "⚠️  Column created_source already exists, skipping ALTER." . PHP_EOL;
        } else {
            throw $e;
        }
    }

    $updateStatement = "
        UPDATE users
        SET created_source = COALESCE(created_source, 'legacy')
        WHERE created_source IS NULL OR created_source = ''
    ";
    $affected = $pdo->exec($updateStatement);
    echo "✅ Backfilled created_source for {$affected} existing users." . PHP_EOL;

    echo PHP_EOL . "🎉 created_source column is ready for use!" . PHP_EOL;
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

