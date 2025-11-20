<?php
/**
 * Expands the users.status column to support the new registration workflow.
 */

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';
$database = getenv('DB_DATABASE') ?: 'scms_db';

$allowed_statuses = [
    'active',
    'inactive',
    'pending_verification',
    'pending_approval',
    'rejected'
];

try {
    $pdo = new PDO("mysql:host={$host};dbname={$database}", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    echo "✅ Connected to database {$database}" . PHP_EOL;

    // Ensure existing rows won't block the ENUM alteration
    $placeholders = "'" . implode("','", $allowed_statuses) . "'";
    $cleanupSql = "
        UPDATE `users`
        SET `status` = 'active'
        WHERE `status` IS NULL
           OR `status` = ''
           OR `status` NOT IN ({$placeholders})
    ";
    $updatedRows = $pdo->exec($cleanupSql);
    echo "ℹ️  Normalized {$updatedRows} user status value(s)" . PHP_EOL;

    // Alter the column definition
    $enumDefinition = "ENUM('active','inactive','pending_verification','pending_approval','rejected') NOT NULL DEFAULT 'active'";
    $alterSql = "ALTER TABLE `users` MODIFY COLUMN `status` {$enumDefinition}";
    $pdo->exec($alterSql);
    echo "🎉 Updated users.status column to support registration approvals" . PHP_EOL;

} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

echo "Done." . PHP_EOL;

