<?php
/**
 * Adds student_type field to users table
 * This field indicates if a student is 'regular' or 'irregular'
 */

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';
$database = getenv('DB_DATABASE') ?: 'scms_db';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$database}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database {$database}" . PHP_EOL;

    // Add student_type column to users table
    $alterStatement = "ALTER TABLE `users` ADD COLUMN `student_type` ENUM('regular', 'irregular') NULL DEFAULT 'regular' AFTER `section_id`";

    try {
        $pdo->exec($alterStatement);
        echo "✅ Added student_type column to users table" . PHP_EOL;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "⚠️  Column student_type already exists, skipping..." . PHP_EOL;
        } else {
            throw $e;
        }
    }

    // Add index for better performance
    $indexStatement = "CREATE INDEX idx_users_student_type ON users(student_type)";
    try {
        $pdo->exec($indexStatement);
        echo "✅ Created index on student_type column" . PHP_EOL;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "⚠️  Index already exists, skipping..." . PHP_EOL;
        } else {
            throw $e;
        }
    }

    echo PHP_EOL . "✅ Migration completed successfully!" . PHP_EOL;

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

