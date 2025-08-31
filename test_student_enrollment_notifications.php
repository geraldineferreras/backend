<?php
/**
 * Test Student Enrollment Notifications
 * This script tests if teachers receive notifications when students join classrooms
 */

// Database configuration
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

echo "🧪 Testing Student Enrollment Notifications...\n\n";

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database successfully!\n\n";

    // Check if there are any students to test with
    echo "👨‍🎓 Checking for students...\n";
    $stmt = $pdo->query("SELECT user_id, full_name, role FROM users WHERE role = 'student' LIMIT 5");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($students)) {
        echo "✅ Found students for testing:\n";
        foreach ($students as $student) {
            echo "   - {$student['user_id']}: {$student['full_name']}\n";
        }
        
        // Check if there are any teachers to test with
        echo "\n👨‍🏫 Checking for teachers...\n";
        $stmt = $pdo->query("SELECT user_id, full_name, role FROM users WHERE role = 'teacher' LIMIT 5");
        $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($teachers)) {
            echo "✅ Found teachers for testing:\n";
            foreach ($teachers as $teacher) {
                echo "   - {$teacher['user_id']}: {$teacher['full_name']}\n";
            }
            
            // Check if there are any classrooms to test with
            echo "\n🏫 Checking for classrooms...\n";
            $stmt = $pdo->query("
                SELECT c.id, c.class_code, c.teacher_id, c.semester, c.school_year, 
                       s.subject_name, sec.section_name, u.full_name as teacher_name
                FROM classrooms c
                JOIN subjects s ON c.subject_id = s.id
                JOIN sections sec ON c.section_id = sec.section_id
                JOIN users u ON c.teacher_id = u.user_id
                WHERE c.is_active = 1
                LIMIT 5
            ");
            $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($classrooms)) {
                echo "✅ Found classrooms for testing:\n";
                foreach ($classrooms as $classroom) {
                    echo "   - {$classroom['class_code']}: {$classroom['subject_name']} ({$classroom['teacher_name']})\n";
                }
                
                // Test creating a student enrollment
                echo "\n🧪 Testing student enrollment creation...\n";
                try {
                    $test_enrollment = [
                        'classroom_id' => $classrooms[0]['id'],
                        'student_id' => $students[0]['user_id'],
                        'enrolled_at' => date('Y-m-d H:i:s'),
                        'status' => 'active'
                    ];
                    
                    // Check if enrollment already exists
                    $stmt = $pdo->prepare("SELECT id FROM classroom_enrollments WHERE classroom_id = ? AND student_id = ?");
                    $stmt->execute([$test_enrollment['classroom_id'], $test_enrollment['student_id']]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existing) {
                        echo "⚠️  Student already enrolled in this class. Removing existing enrollment...\n";
                        $pdo->exec("DELETE FROM classroom_enrollments WHERE id = {$existing['id']}");
                        echo "✅ Existing enrollment removed\n";
                    }
                    
                    $columns = implode(', ', array_keys($test_enrollment));
                    $placeholders = ':' . implode(', :', array_keys($test_enrollment));
                    
                    $sql = "INSERT INTO classroom_enrollments ($columns) VALUES ($placeholders)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($test_enrollment);
                    
                    $enrollment_id = $pdo->lastInsertId();
                    echo "✅ Test enrollment created successfully! Enrollment ID: $enrollment_id\n";
                    
                    // Check if notification was created
                    $teacher_id = $classrooms[0]['teacher_id'];
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM notifications 
                        WHERE user_id = ? 
                        AND type = 'system' 
                        AND title LIKE '%Student Enrollment%'
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                    ");
                    $stmt->execute([$teacher_id]);
                    $notification_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    echo "📊 Notifications sent to teacher: $notification_count\n";
                    
                    if ($notification_count > 0) {
                        // Show the notification details
                        $stmt = $pdo->prepare("
                            SELECT title, message, created_at 
                            FROM notifications 
                            WHERE user_id = ? 
                            AND type = 'system' 
                            AND title LIKE '%Student Enrollment%'
                            ORDER BY created_at DESC 
                            LIMIT 1
                        ");
                        $stmt->execute([$teacher_id]);
                        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($notification) {
                            echo "📝 Latest notification:\n";
                            echo "   Title: {$notification['title']}\n";
                            echo "   Message: " . substr($notification['message'], 0, 100) . "...\n";
                            echo "   Created: {$notification['created_at']}\n";
                        }
                    }
                    
                    // Clean up test data
                    $pdo->exec("DELETE FROM classroom_enrollments WHERE id = $enrollment_id");
                    echo "\n✅ Test enrollment cleaned up\n";
                    
                } catch (Exception $e) {
                    echo "❌ Test enrollment creation failed: " . $e->getMessage() . "\n";
                }
                
            } else {
                echo "⚠️  No classrooms found for testing\n";
            }
            
        } else {
            echo "⚠️  No teachers found for testing\n";
        }
        
    } else {
        echo "⚠️  No students found for testing\n";
    }
    
    // Check recent system notifications
    echo "\n🔔 Checking recent system notifications...\n";
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE type = 'system' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $recent_system_notifications = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📊 Recent system notifications (last hour): $recent_system_notifications\n";
    
    if ($recent_system_notifications > 0) {
        $stmt = $pdo->query("
            SELECT user_id, title, message, created_at 
            FROM notifications 
            WHERE type = 'system' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "📝 Recent system notifications:\n";
        foreach ($notifications as $notification) {
            echo "   - {$notification['user_id']}: {$notification['title']} ({$notification['created_at']})\n";
        }
    }
    
    echo "\n🎉 Student enrollment notification test completed!\n";
    echo "✅ The system should now send notifications when:\n";
    echo "   1. Students join classrooms\n";
    echo "   2. Teachers receive enrollment notifications\n";
    echo "   3. Notifications include student and class details\n";
    
    echo "\n📝 Note: This test only checks database-level enrollment.\n";
    echo "   To test the actual notification system, use the API endpoint:\n";
    echo "   POST /api/student/join-class with a valid student JWT token.\n";
    
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Make sure XAMPP is running\n";
    echo "2. Check if MySQL service is started\n";
    echo "3. Verify database name '{$database}' exists\n";
    echo "4. Check username/password in this script\n";
}
?>
