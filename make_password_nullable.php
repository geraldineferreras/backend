<?php
/**
 * Makes password column nullable in users table
 * This allows users to register without a password (admin will send temporary password after approval)
 */

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';
$database = getenv('DB_DATABASE') ?: 'scms_db';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$database}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database {$database}" . PHP_EOL;

    // Alter password column to allow NULL
    $alterStatement = "ALTER TABLE `users` MODIFY COLUMN `password` VARCHAR(255) NULL";

    try {
        $pdo->exec($alterStatement);
        echo "✅ Password column is now nullable" . PHP_EOL;
    } catch (PDOException $e) {
        echo "❌ Error altering password column: " . $e->getMessage() . PHP_EOL;
        throw $e;
    }

    echo PHP_EOL . "✅ Migration completed successfully!" . PHP_EOL;
    echo "Users can now register without a password. Admin will send temporary password after approval." . PHP_EOL;

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

