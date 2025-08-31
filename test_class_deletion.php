<?php
/**
 * Test Script: Test Class Deletion Logic
 * 
 * This script tests the updated class deletion logic to ensure it works correctly
 * without foreign key constraint violations.
 */

// Database configuration
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

echo "🧪 Testing Class Deletion Logic...\n\n";

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database successfully!\n\n";

    // Check if classes table exists and has the required fields
    echo "🔍 Checking classes table structure...\n";
    $stmt = $pdo->query("DESCRIBE classes");
    $columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $columns[] = $row['Field'];
    }
    
    $required_fields = ['class_id', 'subject_id', 'section_id', 'teacher_id', 'semester', 'school_year'];
    $missing_fields = array_diff($required_fields, $columns);
    
    if (!empty($missing_fields)) {
        echo "❌ Missing required fields: " . implode(', ', $missing_fields) . "\n";
        exit(1);
    }
    echo "✅ All required fields exist\n\n";

    // Check if there are any classes to test with
    echo "🔍 Checking for existing classes...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM classes");
    $class_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($class_count == 0) {
        echo "ℹ️  No classes exist in the database\n";
        echo "Creating a test class...\n";
        
        // Create a test class
        $pdo->exec("INSERT INTO classes (class_id, subject_id, section_id, teacher_id, semester, school_year, status) VALUES (999, 1, 1, 'TCH001', 1, '2024', 'active')");
        echo "✅ Test class created with ID 999\n";
        $class_count = 1;
    }
    
    echo "📊 Found {$class_count} classes\n\n";

    // Check for related data that might prevent deletion
    echo "🔍 Checking for related data...\n";
    
    // Check attendance records
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM attendance");
    $attendance_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "  - Attendance records: {$attendance_count}\n";
    
    // Check excuse letters
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM excuse_letters");
    $excuse_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "  - Excuse letters: {$excuse_count}\n";
    
    // Check classrooms
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM classrooms");
    $classroom_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "  - Classrooms: {$classroom_count}\n";
    
    // Check class_tasks
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM class_tasks");
    $task_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "  - Class tasks: {$task_count}\n";
    
    // Check task_submissions
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM task_submissions");
    $submission_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "  - Task submissions: {$submission_count}\n";
    
    // Check classroom_stream
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM classroom_stream");
    $stream_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "  - Classroom stream posts: {$stream_count}\n\n";

    // Test the deletion logic step by step
    echo "🧪 Testing deletion logic step by step...\n";
    
    // Get a test class
    $stmt = $pdo->query("SELECT * FROM classes LIMIT 1");
    $test_class = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test_class) {
        echo "❌ No test class found\n";
        exit(1);
    }
    
    echo "📋 Test class: ID {$test_class['class_id']}, Subject {$test_class['subject_id']}, Section {$test_class['section_id']}, Teacher {$test_class['teacher_id']}\n\n";
    
    // Start transaction for testing
    $pdo->beginTransaction();
    
    try {
        echo "1️⃣ Testing attendance deletion...\n";
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE class_id = ?");
        $stmt->execute([$test_class['class_id']]);
        $deleted = $stmt->rowCount();
        echo "   ✅ Deleted {$deleted} attendance records\n";
        
        echo "2️⃣ Testing excuse letters deletion...\n";
        $stmt = $pdo->prepare("DELETE FROM excuse_letters WHERE class_id = ?");
        $stmt->execute([$test_class['class_id']]);
        $deleted = $stmt->rowCount();
        echo "   ✅ Deleted {$deleted} excuse letters\n";
        
        echo "3️⃣ Testing classroom cleanup...\n";
        // Find classrooms that correspond to this class
        $stmt = $pdo->prepare("SELECT id, class_code FROM classrooms WHERE subject_id = ? AND section_id = ? AND teacher_id = ?");
        $stmt->execute([$test_class['subject_id'], $test_class['section_id'], $test_class['teacher_id']]);
        $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($classrooms as $classroom) {
            echo "   🔍 Found classroom: {$classroom['class_code']}\n";
            
            // Clean up class_tasks
            $stmt = $pdo->prepare("UPDATE class_tasks SET class_codes = JSON_REMOVE(class_codes, JSON_UNQUOTE(JSON_SEARCH(class_codes, 'one', ?))) WHERE JSON_CONTAINS(class_codes, ?)");
            $stmt->execute([$classroom['class_code'], json_encode($classroom['class_code'])]);
            $updated = $stmt->rowCount();
            echo "      ✅ Updated {$updated} class_tasks\n";
            
            // Delete task submissions
            $stmt = $pdo->prepare("DELETE FROM task_submissions WHERE class_code = ?");
            $stmt->execute([$classroom['class_code']]);
            $deleted = $stmt->rowCount();
            echo "      ✅ Deleted {$deleted} task submissions\n";
            
            // Delete task student assignments
            $stmt = $pdo->prepare("DELETE FROM task_student_assignments WHERE class_code = ?");
            $stmt->execute([$classroom['class_code']]);
            $deleted = $stmt->rowCount();
            echo "      ✅ Deleted {$deleted} task student assignments\n";
            
            // Delete classroom stream posts
            $stmt = $pdo->prepare("DELETE FROM classroom_stream WHERE class_code = ?");
            $stmt->execute([$classroom['class_code']]);
            $deleted = $stmt->rowCount();
            echo "      ✅ Deleted {$deleted} classroom stream posts\n";
            
            // Delete classroom enrollments
            $stmt = $pdo->prepare("DELETE FROM classroom_enrollments WHERE classroom_id = ?");
            $stmt->execute([$classroom['id']]);
            $deleted = $stmt->rowCount();
            echo "      ✅ Deleted {$deleted} classroom enrollments\n";
            
            // Delete the classroom
            $stmt = $pdo->prepare("DELETE FROM classrooms WHERE id = ?");
            $stmt->execute([$classroom['id']]);
            echo "      ✅ Deleted classroom\n";
        }
        
        echo "4️⃣ Testing class deletion...\n";
        $stmt = $pdo->prepare("DELETE FROM classes WHERE class_id = ?");
        $stmt->execute([$test_class['class_id']]);
        $deleted = $stmt->rowCount();
        echo "   ✅ Deleted {$deleted} class record\n";
        
        // Rollback the transaction (we don't want to actually delete the test data)
        $pdo->rollback();
        echo "\n🔄 Transaction rolled back (test data preserved)\n";
        
        echo "\n✅ All deletion logic tests passed!\n";
        echo "The class deletion should now work without foreign key constraint violations.\n";
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo "❌ Error during deletion test: " . $e->getMessage() . "\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
