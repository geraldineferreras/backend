<?php
/**
 * Creates the programs table used by the Admin Program Management module.
 * Run via: php create_programs_table.php
 */

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: 'root';
$database = getenv('DB_DATABASE') ?: 'scms_db';
$port = getenv('DB_PORT') ?: 3306;

$default_programs = [
    ['code' => 'BSIT', 'name' => 'Bachelor of Science in Information Technology'],
    ['code' => 'BSIS', 'name' => 'Bachelor of Science in Information Systems'],
    ['code' => 'BSCS', 'name' => 'Bachelor of Science in Computer Science'],
    ['code' => 'ACT',  'name' => 'Associate in Computer Technology'],
    ['code' => 'BSHM', 'name' => 'Bachelor of Science in Hospitality Management'],
    ['code' => 'BSED', 'name' => 'Bachelor of Secondary Education'],
];

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$database}";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✅ Connected to database {$database}" . PHP_EOL;

    $createSql = <<<SQL
CREATE TABLE IF NOT EXISTS `programs` (
    `program_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(32) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NULL,
    `status` ENUM('active', 'archived') NOT NULL DEFAULT 'active',
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    `archived_at` DATETIME NULL,
    PRIMARY KEY (`program_id`),
    UNIQUE KEY `uq_programs_code` (`code`),
    KEY `idx_programs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

    $pdo->exec($createSql);
    echo "📦 Ensured programs table exists" . PHP_EOL;

    $insertSql = <<<SQL
INSERT INTO `programs` (`code`, `name`, `description`, `status`, `created_at`, `updated_at`)
VALUES (:code, :name, NULL, 'active', NOW(), NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
SQL;
    $stmt = $pdo->prepare($insertSql);

    foreach ($default_programs as $program) {
        $stmt->execute([
            ':code' => strtoupper($program['code']),
            ':name' => $program['name'],
        ]);
    }

    echo "✨ Seeded default programs (" . count($default_programs) . " entries)" . PHP_EOL;
    echo "Done." . PHP_EOL;
} catch (PDOException $e) {
    echo "❌ Failed to create programs table: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

