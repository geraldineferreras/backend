<?php
/**
 * Migration Script: Convert Year Levels to Numeric Format
 * 
 * This script converts existing sections with descriptive year_level values
 * (like "1st Year", "2nd Year", etc.) to numeric values (1, 2, 3, 4).
 * 
 * Run this script after updating the auto-create sections feature to ensure
 * consistency across all existing sections.
 */

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'scms_test';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔄 Migrating Year Levels to Numeric Format\n";
    echo "==========================================\n\n";
    
    // Check current state
    echo "📊 Current year_level values in sections table:\n";
    $stmt = $pdo->query("SELECT year_level, COUNT(*) as count FROM sections GROUP BY year_level ORDER BY year_level");
    $current_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($current_levels as $level) {
        $type = is_numeric($level['year_level']) ? 'NUMERIC' : 'STRING';
        echo "  - '{$level['year_level']}' ({$type}): {$level['count']} sections\n";
    }
    echo "\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Update mapping for descriptive to numeric
    $year_mapping = [
        '1st Year' => 1,
        '1st year' => 1,
        '2nd Year' => 2,
        '2nd year' => 2,
        '3rd Year' => 3,
        '3rd year' => 3,
        '4th Year' => 4,
        '4th year' => 4
    ];
    
    $total_updated = 0;
    
    foreach ($year_mapping as $old_value => $new_value) {
        $stmt = $pdo->prepare("UPDATE sections SET year_level = ? WHERE year_level = ?");
        $stmt->execute([$new_value, $old_value]);
        $affected = $stmt->rowCount();
        
        if ($affected > 0) {
            echo "✅ Updated {$affected} sections: '{$old_value}' → {$new_value}\n";
            $total_updated += $affected;
        }
    }
    
    // Check if there are any other descriptive formats that need updating
    $stmt = $pdo->query("SELECT year_level, COUNT(*) as count FROM sections WHERE year_level LIKE '%Year%' OR year_level LIKE '%year%' GROUP BY year_level");
    $remaining_descriptive = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($remaining_descriptive)) {
        echo "\n⚠️  Found additional descriptive formats that need manual review:\n";
        foreach ($remaining_descriptive as $level) {
            echo "  - '{$level['year_level']}': {$level['count']} sections\n";
        }
        echo "   Please review these manually and update as needed.\n";
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "\n📊 Migration Summary:\n";
    echo "  - Total sections updated: {$total_updated}\n";
    echo "  - All standard year level formats converted to numeric\n";
    echo "  - Transaction committed successfully\n\n";
    
    // Verify final state
    echo "🔍 Final year_level values in sections table:\n";
    $stmt = $pdo->query("SELECT year_level, COUNT(*) as count FROM sections GROUP BY year_level ORDER BY year_level");
    $final_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($final_levels as $level) {
        $type = is_numeric($level['year_level']) ? 'NUMERIC' : 'STRING';
        echo "  - '{$level['year_level']}' ({$type}): {$level['count']} sections\n";
    }
    echo "\n";
    
    // Check for any remaining non-numeric values
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sections WHERE year_level NOT REGEXP '^[1-4]$'");
    $non_numeric_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($non_numeric_count > 0) {
        echo "⚠️  Warning: {$non_numeric_count} sections still have non-numeric year_level values.\n";
        echo "   These may need manual review and correction.\n\n";
        
        // Show examples
        $stmt = $pdo->query("SELECT section_id, section_name, year_level FROM sections WHERE year_level NOT REGEXP '^[1-4]$' LIMIT 5");
        $examples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Examples:\n";
        foreach ($examples as $example) {
            echo "     - ID {$example['section_id']}: {$example['section_name']} → {$example['year_level']}\n";
        }
    } else {
        echo "✅ All sections now use numeric year_level values (1, 2, 3, 4).\n";
    }
    
    echo "\n🎯 Migration completed successfully!\n";
    echo "   New auto-create sections will use numeric year levels.\n";
    echo "   Manual section creation now validates numeric year levels 1-4.\n";
    
} catch (PDOException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "   Transaction rolled back.\n";
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Transaction rolled back.\n";
}
?>
