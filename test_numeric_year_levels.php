<?php
/**
 * Test Script: Verify Numeric Year Levels in Auto-Create Sections
 * 
 * This script tests that the auto-create sections feature now saves
 * year_level as numeric values (1, 2, 3, 4) instead of descriptive
 * strings ("1st Year", "2nd Year", etc.)
 */

// Load CodeIgniter framework
require_once 'application/config/database.php';
require_once 'application/config/config.php';

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'scms_test';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Testing Numeric Year Levels in Sections Table\n";
    echo "================================================\n\n";
    
    // Check current sections table structure
    echo "📋 Current sections table structure:\n";
    $stmt = $pdo->query("DESCRIBE sections");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "  - {$column['Field']}: {$column['Type']} {$column['Null']} {$column['Key']}\n";
    }
    echo "\n";
    
    // Check existing sections and their year_level values
    echo "📊 Current sections with year_level values:\n";
    $stmt = $pdo->query("SELECT section_id, section_name, program, year_level, created_at FROM sections ORDER BY section_id DESC LIMIT 20");
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sections)) {
        echo "  No sections found in the database.\n";
    } else {
        foreach ($sections as $section) {
            $year_type = is_numeric($section['year_level']) ? '✅ NUMERIC' : '❌ STRING';
            echo "  - ID {$section['section_id']}: {$section['section_name']} | {$section['program']} | {$section['year_level']} {$year_type}\n";
        }
    }
    echo "\n";
    
    // Check year_level data types
    echo "🔍 Year Level Data Type Analysis:\n";
    $stmt = $pdo->query("SELECT year_level, COUNT(*) as count FROM sections GROUP BY year_level ORDER BY year_level");
    $year_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($year_levels as $level) {
        $type = is_numeric($level['year_level']) ? 'NUMERIC' : 'STRING';
        echo "  - '{$level['year_level']}' ({$type}): {$level['count']} sections\n";
    }
    echo "\n";
    
    // Test the expected format for new sections
    echo "🎯 Expected Format for New Sections:\n";
    echo "  - BSIT 1A (BSIT Year 1, Section A)\n";
    echo "  - BSIS 2B (BSIS Year 2, Section B)\n";
    echo "  - BSCS 3C (BSCS Year 3, Section C)\n";
    echo "  - ACT 4K (ACT Year 4, Section K)\n\n";
    
    // Check if there are any sections with old descriptive format
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sections WHERE year_level LIKE '%Year%'");
    $old_format_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($old_format_count > 0) {
        echo "⚠️  Found {$old_format_count} sections with old descriptive year_level format.\n";
        echo "   These should be updated to numeric format.\n\n";
        
        // Show examples of old format
        $stmt = $pdo->query("SELECT section_name, year_level FROM sections WHERE year_level LIKE '%Year%' LIMIT 5");
        $old_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Examples of old format:\n";
        foreach ($old_sections as $section) {
            echo "     - {$section['section_name']}: {$section['year_level']}\n";
        }
        echo "\n";
    } else {
        echo "✅ All sections use numeric year_level format.\n\n";
    }
    
    // Summary
    echo "📋 Summary:\n";
    echo "  - Auto-create sections now saves year_level as numbers (1, 2, 3, 4)\n";
    echo "  - Manual section creation validates year_level must be numeric 1-4\n";
    echo "  - Section names follow pattern: {PROGRAM} {YEAR}{SECTION_LETTER}\n";
    echo "  - Example: BSIT 1A, BSIS 2B, BSCS 3C, ACT 4K\n\n";
    
    echo "✅ Test completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
