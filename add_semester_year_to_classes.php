<?php
/**
 * Migration Script: Add semester and school_year fields to classes table
 * 
 * This script adds the missing semester and school_year fields that the code expects
 * but are missing from the database schema.
 */

// Database configuration
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

echo "🔧 Adding semester and school_year fields to classes table...\n\n";

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database successfully!\n\n";

    // Check if classes table exists
    echo "🔍 Checking if classes table exists...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'classes'");
    if ($stmt->rowCount() == 0) {
        echo "❌ classes table does not exist!\n";
        exit(1);
    }
    echo "✅ classes table exists\n\n";

    // Check current table structure
    echo "📋 Current classes table structure:\n";
    $stmt = $pdo->query("DESCRIBE classes");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
        echo "  - {$row['Field']} ({$row['Type']})\n";
    }
    echo "\n";

    // Check if semester field exists
    if (!in_array('semester', $columns)) {
        echo "🔧 Adding semester field...\n";
        $pdo->exec("ALTER TABLE `classes` ADD COLUMN `semester` INT NOT NULL DEFAULT 1 AFTER `teacher_id`");
        echo "✅ semester field added successfully!\n";
    } else {
        echo "✅ semester field already exists\n";
    }

    // Check if school_year field exists
    if (!in_array('school_year', $columns)) {
        echo "🔧 Adding school_year field...\n";
        $pdo->exec("ALTER TABLE `classes` ADD COLUMN `school_year` VARCHAR(10) NOT NULL DEFAULT '2024' AFTER `semester`");
        echo "✅ school_year field added successfully!\n";
    } else {
        echo "✅ school_year field already exists\n";
    }

    // Add indexes if they don't exist
    echo "🔧 Adding indexes...\n";
    try {
        $pdo->exec("ALTER TABLE `classes` ADD INDEX `idx_semester` (`semester`)");
        echo "✅ semester index added\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "ℹ️  semester index already exists\n";
        } else {
            echo "⚠️  Could not add semester index: " . $e->getMessage() . "\n";
        }
    }

    try {
        $pdo->exec("ALTER TABLE `classes` ADD INDEX `idx_school_year` (`school_year`)");
        echo "✅ school_year index added\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "ℹ️  school_year index already exists\n";
        } else {
            echo "⚠️  Could not add school_year index: " . $e->getMessage() . "\n";
        }
    }

    // Update existing records with default values
    echo "🔧 Updating existing records...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        $pdo->exec("UPDATE `classes` SET `semester` = 1 WHERE `semester` IS NULL");
        $pdo->exec("UPDATE `classes` SET `school_year` = '2024' WHERE `school_year` IS NULL");
        echo "✅ Updated {$count} existing records with default values\n";
    } else {
        echo "ℹ️  No existing records to update\n";
    }

    // Show final table structure
    echo "\n📋 Final classes table structure:\n";
    $stmt = $pdo->query("DESCRIBE classes");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$row['Field']} ({$row['Type']}) {$row['Null']} {$row['Default']}\n";
    }

    echo "\n✅ Migration completed successfully!\n";
    echo "The classes table now has the semester and school_year fields that the code expects.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
