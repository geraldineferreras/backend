<?php
/**
 * Adds created_source column to users table for auditing user origin.
 */

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';
$database = getenv('DB_DATABASE') ?: 'scms_db';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$database}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database {$database}" . PHP_EOL;

    $alterStatement = "
        ALTER TABLE `users`
        ADD COLUMN `created_source` VARCHAR(32) NULL DEFAULT NULL
            AFTER `created_at`
    ";

    try {
        $pdo->exec($alterStatement);
        echo "🆕 Column created_source added to users table" . PHP_EOL;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "⚠️  created_source already exists, skipping." . PHP_EOL;
        } else {
            throw $e;
        }
    }

    echo "🎉 Migration finished successfully." . PHP_EOL;
} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}


