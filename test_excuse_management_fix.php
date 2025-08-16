<?php
/**
 * Test Script for Excuse Management System
 * This script verifies that the excuse management system is working correctly
 * and that attendance records have proper section names.
 */

// Database configuration
$host = 'localhost';
$dbname = 'scms_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful\n\n";
    
    // Test 1: Check attendance table structure
    echo "🔍 Test 1: Checking attendance table structure...\n";
    $stmt = $pdo->query("DESCRIBE attendance");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $section_name_column = null;
    foreach ($columns as $column) {
        if ($column['Field'] === 'section_name') {
            $section_name_column = $column;
            break;
        }
    }
    
    if ($section_name_column) {
        echo "✅ section_name column found\n";
        echo "   - Type: {$section_name_column['Type']}\n";
        echo "   - Null: {$section_name_column['Null']}\n";
        echo "   - Default: {$section_name_column['Default']}\n";
        
        if ($section_name_column['Null'] === 'NO') {
            echo "✅ section_name column is NOT NULL (correct)\n";
        } else {
            echo "❌ section_name column allows NULL (needs fixing)\n";
        }
    } else {
        echo "❌ section_name column not found\n";
    }
    
    // Test 2: Check for null section_name values
    echo "\n🔍 Test 2: Checking for null section_name values...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as null_count FROM attendance WHERE section_name IS NULL OR section_name = ''");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['null_count'] == 0) {
        echo "✅ No null section_name values found\n";
    } else {
        echo "❌ Found {$result['null_count']} records with null/empty section_name\n";
        
        // Show examples of null section_name records
        $stmt = $pdo->query("SELECT attendance_id, student_id, class_id, section_name, date, status FROM attendance WHERE section_name IS NULL OR section_name = '' LIMIT 5");
        $null_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Examples of problematic records:\n";
        foreach ($null_records as $record) {
            echo "   - ID: {$record['attendance_id']}, Student: {$record['student_id']}, Date: {$record['date']}, Status: {$record['status']}\n";
        }
    }
    
    // Test 3: Check excuse letters table
    echo "\n🔍 Test 3: Checking excuse letters table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM excuse_letters");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Total excuse letters: {$result['total']}\n";
    
    // Check excuse letter statuses
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM excuse_letters GROUP BY status");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Status breakdown:\n";
    foreach ($statuses as $status) {
        echo "   - {$status['status']}: {$status['count']}\n";
    }
    
    // Test 4: Check attendance records with excuse-related notes
    echo "\n🔍 Test 4: Checking attendance records with excuse-related notes...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM attendance WHERE notes LIKE '%excuse letter%'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Attendance records with excuse notes: {$result['count']}\n";
    
    if ($result['count'] > 0) {
        // Show examples
        $stmt = $pdo->query("SELECT attendance_id, student_id, section_name, status, notes FROM attendance WHERE notes LIKE '%excuse letter%' ORDER BY date DESC LIMIT 3");
        $excuse_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Examples:\n";
        foreach ($excuse_records as $record) {
            echo "   - ID: {$record['attendance_id']}, Student: {$record['student_id']}, Section: {$record['section_name']}, Status: {$record['status']}\n";
            echo "     Notes: {$record['notes']}\n";
        }
    }
    
    // Test 5: Check sections table
    echo "\n🔍 Test 5: Checking sections table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sections");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Total sections: {$result['count']}\n";
    
    if ($result['count'] > 0) {
        $stmt = $pdo->query("SELECT section_name FROM sections LIMIT 5");
        $sections = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   Sample sections: " . implode(', ', $sections) . "\n";
    }
    
    // Test 6: Check classes table
    echo "\n🔍 Test 6: Checking classes table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Total classes: {$result['count']}\n";
    
    // Test 7: Check for potential data inconsistencies
    echo "\n🔍 Test 7: Checking for data inconsistencies...\n";
    
    // Check if there are attendance records without corresponding classes
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM attendance a 
        LEFT JOIN classes c ON a.class_id = c.class_id 
        WHERE c.class_id IS NULL
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        echo "✅ All attendance records have valid class references\n";
    } else {
        echo "❌ Found {$result['count']} attendance records without valid class references\n";
    }
    
    // Check if there are attendance records without corresponding sections
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM attendance a 
        LEFT JOIN sections s ON a.section_name = s.section_name 
        WHERE s.section_name IS NULL AND a.section_name != 'Unknown Section'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        echo "✅ All attendance records have valid section references\n";
    } else {
        echo "❌ Found {$result['count']} attendance records with invalid section references\n";
    }
    
    echo "\n🎯 Summary:\n";
    echo "The excuse management system should now:\n";
    echo "1. ✅ Automatically create attendance records when excuse letters are approved/rejected\n";
    echo "2. ✅ Ensure section_name is never null in attendance records\n";
    echo "3. ✅ Mark students as 'excused' when excuse letters are approved\n";
    echo "4. ✅ Mark students as 'absent' when excuse letters are rejected\n";
    echo "5. ✅ Include proper notes explaining the attendance status\n";
    
    echo "\n📋 Next Steps:\n";
    echo "1. Test the excuse letter approval/rejection process\n";
    echo "2. Verify that attendance records are created with proper section names\n";
    echo "3. Check that the frontend displays the correct attendance statuses\n";
    echo "4. Monitor the system for any issues\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
