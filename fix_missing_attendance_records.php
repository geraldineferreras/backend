<?php
/**
 * Fix Missing Attendance Records Script
 * This script creates missing attendance records for excuse letters that don't have
 * corresponding attendance records, especially for rejected excuse letters.
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
    
    // Step 1: Check which excuse letters don't have corresponding attendance records
    echo "🔍 Step 1: Checking for missing attendance records...\n";
    
    $stmt = $pdo->query("
        SELECT 
            el.letter_id,
            el.student_id,
            el.class_id,
            el.teacher_id,
            el.date_absent,
            el.reason,
            el.status,
            a.attendance_id
        FROM excuse_letters el
        LEFT JOIN attendance a ON 
            el.student_id = a.student_id 
            AND el.class_id = a.class_id 
            AND el.date_absent = a.date
        WHERE a.attendance_id IS NULL
        ORDER BY el.date_absent DESC, el.student_id
    ");
    
    $missing_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($missing_records)) {
        echo "✅ All excuse letters have corresponding attendance records\n";
    } else {
        echo "❌ Found " . count($missing_records) . " excuse letters without attendance records:\n";
        
        foreach ($missing_records as $record) {
            echo "   - Letter ID: {$record['letter_id']}, Student: {$record['student_id']}, Date: {$record['date_absent']}, Status: {$record['status']}\n";
        }
        
        echo "\n🔧 Step 2: Creating missing attendance records...\n";
        
        $created_count = 0;
        $error_count = 0;
        
        foreach ($missing_records as $excuse_letter) {
            try {
                // Get class information
                $class_stmt = $pdo->prepare("
                    SELECT 
                        c.*, 
                        s.section_name,
                        sub.subject_name
                    FROM classes c
                    LEFT JOIN sections s ON c.section_id = s.section_id
                    LEFT JOIN subjects sub ON c.subject_id = sub.id
                    WHERE c.class_id = ?
                ");
                $class_stmt->execute([$excuse_letter['class_id']]);
                $class = $class_stmt->fetch(PDO::FETCH_ASSOC);
                
                // If not found in classes table, try classrooms table
                if (!$class) {
                    $classroom_stmt = $pdo->prepare("
                        SELECT 
                            cr.*, 
                            s.section_name,
                            sub.subject_name
                        FROM classrooms cr
                        LEFT JOIN sections s ON cr.section_id = s.section_id
                        LEFT JOIN subjects sub ON cr.subject_id = sub.id
                        WHERE cr.id = ?
                    ");
                    $classroom_stmt->execute([$excuse_letter['class_id']]);
                    $classroom = $classroom_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($classroom) {
                        // Find corresponding class in classes table
                        $class_stmt = $pdo->prepare("
                            SELECT 
                                c.*, 
                                s.section_name,
                                sub.subject_name
                            FROM classes c
                            LEFT JOIN sections s ON c.section_id = s.section_id
                            LEFT JOIN subjects sub ON c.subject_id = sub.id
                            WHERE c.subject_id = ? 
                            AND c.section_id = ? 
                            AND c.teacher_id = ?
                        ");
                        $class_stmt->execute([
                            $classroom['subject_id'], 
                            $classroom['section_id'], 
                            $excuse_letter['teacher_id']
                        ]);
                        $class = $class_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$class) {
                            // Use classroom data as fallback
                            $class = [
                                'class_id' => $excuse_letter['class_id'],
                                'subject_id' => $classroom['subject_id'],
                                'section_name' => $classroom['section_name'] ?: 'Unknown Section'
                            ];
                        }
                    }
                }
                
                // Ensure we have section_name
                if (!$class || empty($class['section_name'])) {
                    // Try to get section name directly
                    if (isset($class['section_id'])) {
                        $section_stmt = $pdo->prepare("SELECT section_name FROM sections WHERE section_id = ?");
                        $section_stmt->execute([$class['section_id']]);
                        $section = $section_stmt->fetch(PDO::FETCH_ASSOC);
                        if ($section) {
                            $class['section_name'] = $section['section_name'];
                        }
                    }
                    
                    // Final fallback
                    if (empty($class['section_name'])) {
                        $class['section_name'] = 'Unknown Section';
                    }
                }
                
                // Determine attendance status and notes
                $status = 'absent'; // Default for rejected/pending
                $notes = '';
                
                if ($excuse_letter['status'] === 'approved') {
                    $status = 'excused';
                    $notes = 'Automatically marked as excused due to approved excuse letter';
                } elseif ($excuse_letter['status'] === 'rejected') {
                    $status = 'absent';
                    $notes = 'Automatically marked as absent due to rejected excuse letter';
                } else {
                    // pending status
                    $status = 'absent';
                    $notes = 'Automatically marked as absent due to pending excuse letter';
                }
                
                // Create attendance record
                $insert_stmt = $pdo->prepare("
                    INSERT INTO attendance (
                        student_id, 
                        class_id, 
                        subject_id, 
                        section_name, 
                        date, 
                        time_in, 
                        status, 
                        notes, 
                        teacher_id, 
                        created_at, 
                        updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                
                $insert_stmt->execute([
                    $excuse_letter['student_id'],
                    $excuse_letter['class_id'],
                    $class['subject_id'] ?? 0,
                    $class['section_name'],
                    $excuse_letter['date_absent'],
                    date('H:i:s'),
                    $status,
                    $notes,
                    $excuse_letter['teacher_id']
                ]);
                
                $created_count++;
                echo "   ✅ Created attendance record for student {$excuse_letter['student_id']} on {$excuse_letter['date_absent']} with status '{$status}'\n";
                
            } catch (Exception $e) {
                $error_count++;
                echo "   ❌ Error creating attendance for letter {$excuse_letter['letter_id']}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n📊 Summary:\n";
        echo "   - Created: {$created_count} attendance records\n";
        echo "   - Errors: {$error_count}\n";
    }
    
    // Step 3: Verify all excuse letters now have attendance records
    echo "\n🔍 Step 3: Verifying all excuse letters have attendance records...\n";
    
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_excuse_letters,
            COUNT(a.attendance_id) as total_with_attendance
        FROM excuse_letters el
        LEFT JOIN attendance a ON 
            el.student_id = a.student_id 
            AND el.class_id = a.class_id 
            AND el.date_absent = a.date
    ");
    
    $verification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($verification['total_excuse_letters'] == $verification['total_with_attendance']) {
        echo "✅ All excuse letters now have corresponding attendance records\n";
    } else {
        echo "❌ Still missing some attendance records\n";
        echo "   - Total excuse letters: {$verification['total_excuse_letters']}\n";
        echo "   - With attendance: {$verification['total_with_attendance']}\n";
    }
    
    // Step 4: Check for any remaining null section_name values
    echo "\n🔍 Step 4: Checking for null section_name values...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as null_count FROM attendance WHERE section_name IS NULL OR section_name = ''");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['null_count'] == 0) {
        echo "✅ No null section_name values found\n";
    } else {
        echo "❌ Found {$result['null_count']} records with null/empty section_name\n";
        
        // Show examples
        $stmt = $pdo->query("SELECT attendance_id, student_id, class_id, section_name, date, status FROM attendance WHERE section_name IS NULL OR section_name = '' LIMIT 5");
        $null_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Examples:\n";
        foreach ($null_records as $record) {
            echo "   - ID: {$record['attendance_id']}, Student: {$record['student_id']}, Date: {$record['date']}, Status: {$record['status']}\n";
        }
    }
    
    // Step 5: Show final status
    echo "\n🎯 Final Status:\n";
    
    $stmt = $pdo->query("
        SELECT 
            el.status as excuse_status,
            COUNT(*) as count
        FROM excuse_letters el
        JOIN attendance a ON 
            el.student_id = a.student_id 
            AND el.class_id = a.class_id 
            AND el.date_absent = a.date
        GROUP BY el.status
        ORDER BY el.status
    ");
    
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Excuse letter status counts with attendance records:\n";
    foreach ($status_counts as $status) {
        echo "   - {$status['excuse_status']}: {$status['count']}\n";
    }
    
    echo "\n📋 Next Steps:\n";
    echo "1. Check the frontend to verify all attendance records are displayed\n";
    echo "2. Verify that rejected excuse letters show as 'absent' in attendance\n";
    echo "3. Verify that approved excuse letters show as 'excused' in attendance\n";
    echo "4. Check that section names are properly populated\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
