<?php
/**
 * Test Teacher Assignment Notifications
 * This script tests if teachers receive notifications when assigned to subjects
 */

// Database configuration
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

echo "🧪 Testing Teacher Assignment Notifications...\n\n";

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to database successfully!\n\n";

    // Check if there are any teachers to test with
    echo "👨‍🏫 Checking for teachers...\n";
    $stmt = $pdo->query("SELECT user_id, full_name, role FROM users WHERE role = 'teacher' LIMIT 5");
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($teachers)) {
        echo "✅ Found teachers for testing:\n";
        foreach ($teachers as $teacher) {
            echo "   - {$teacher['user_id']}: {$teacher['full_name']}\n";
        }
        
        // Check if there are any subjects to test with
        echo "\n📚 Checking for subjects...\n";
        $stmt = $pdo->query("SELECT id, subject_name, subject_code FROM subjects LIMIT 5");
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($subjects)) {
            echo "✅ Found subjects for testing:\n";
            foreach ($subjects as $subject) {
                echo "   - ID: {$subject['id']}, {$subject['subject_name']} ({$subject['subject_code']})\n";
            }
            
            // Check if there are any sections to test with
            echo "\n👥 Checking for sections...\n";
            $stmt = $pdo->query("SELECT section_id, section_name FROM sections LIMIT 5");
            $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($sections)) {
                echo "✅ Found sections for testing:\n";
                foreach ($sections as $section) {
                    echo "   - ID: {$section['section_id']}, {$section['section_name']}\n";
                }
                
                // Test creating a class assignment
                echo "\n🧪 Testing class assignment creation...\n";
                try {
                    $test_class = [
                        'subject_id' => $subjects[0]['id'],
                        'teacher_id' => $teachers[0]['user_id'],
                        'section_id' => $sections[0]['section_id'],
                        'semester' => '1st',
                        'school_year' => '2024-2025',
                        'status' => 'active'
                    ];
                    
                    $columns = implode(', ', array_keys($test_class));
                    $placeholders = ':' . implode(', :', array_keys($test_class));
                    
                    $sql = "INSERT INTO classes ($columns) VALUES ($placeholders)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($test_class);
                    
                    $class_id = $pdo->lastInsertId();
                    echo "✅ Test class assignment created successfully! Class ID: $class_id\n";
                    
                    // Check if notification was created
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) as count 
                        FROM notifications 
                        WHERE user_id = ? 
                        AND type = 'system' 
                        AND title LIKE '%Subject Assignment%'
                        AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                    ");
                    $stmt->execute([$teachers[0]['user_id']]);
                    $notification_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    
                    echo "📊 Notifications sent to teacher: $notification_count\n";
                    
                    if ($notification_count > 0) {
                        // Show the notification details
                        $stmt = $pdo->prepare("
                            SELECT title, message, created_at 
                            FROM notifications 
                            WHERE user_id = ? 
                            AND type = 'system' 
                            AND title LIKE '%Subject Assignment%'
                            ORDER BY created_at DESC 
                            LIMIT 1
                        ");
                        $stmt->execute([$teachers[0]['user_id']]);
                        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($notification) {
                            echo "📝 Latest notification:\n";
                            echo "   Title: {$notification['title']}\n";
                            echo "   Message: " . substr($notification['message'], 0, 100) . "...\n";
                            echo "   Created: {$notification['created_at']}\n";
                        }
                    }
                    
                    // Clean up test data
                    $pdo->exec("DELETE FROM classes WHERE class_id = $class_id");
                    echo "\n✅ Test class assignment cleaned up\n";
                    
                } catch (Exception $e) {
                    echo "❌ Test class assignment creation failed: " . $e->getMessage() . "\n";
                }
                
            } else {
                echo "⚠️  No sections found for testing\n";
            }
            
        } else {
            echo "⚠️  No subjects found for testing\n";
        }
        
    } else {
        echo "⚠️  No teachers found for testing\n";
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
    
    echo "\n🎉 Teacher assignment notification test completed!\n";
    echo "✅ The system should now send notifications when:\n";
    echo "   1. Admin assigns a teacher to a subject\n";
    echo "   2. Admin updates teacher assignments\n";
    echo "   3. Admin removes teacher assignments\n";
    
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "\n🔧 Troubleshooting:\n";
    echo "1. Make sure XAMPP is running\n";
    echo "2. Check if MySQL service is started\n";
    echo "3. Verify database name '{$database}' exists\n";
    echo "4. Check username/password in this script\n";
}
?>
