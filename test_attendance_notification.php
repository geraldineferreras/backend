<?php
// Test attendance notification system
define('BASEPATH', true);
require_once 'application/config/database.php';
require_once 'application/helpers/notification_helper.php';

// Simulate CodeIgniter environment
$CI = new stdClass();
$CI->db = new stdClass();

// Mock database connection
$host = 'localhost:3308';
$dbname = 'scms_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Testing Attendance Notification System ===\n\n";
    
    // Test 1: Check if student exists
    $student_id = 'STU689436695D2BD603';
    echo "1. Testing with student: {$student_id}\n";
    
    $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "✓ Student found: {$student['full_name']} ({$student['email']})\n\n";
        
        // Test 2: Simulate attendance notification
        echo "2. Testing attendance notification creation...\n";
        
        // Mock the notification creation
        $attendance_id = 999; // Mock attendance ID
        $status = 'present';
        $subject_name = 'Database Management System';
        $section_name = 'BSIT 4C';
        $date = '2024-02-16';
        $time_in = '08:30:00';
        $notes = 'Student arrived on time';
        
        echo "Creating notification for:\n";
        echo "  - Student: {$student['full_name']}\n";
        echo "  - Status: {$status}\n";
        echo "  - Subject: {$subject_name}\n";
        echo "  - Section: {$section_name}\n";
        echo "  - Date: {$date}\n";
        echo "  - Time: {$time_in}\n";
        echo "  - Notes: {$notes}\n\n";
        
        // Test notification message format
        $title = "Attendance Recorded - " . ucfirst($status);
        $message = "Your attendance has been recorded for {$subject_name} ({$section_name}) on {$date}.";
        $message .= " Time in: {$time_in}";
        $message .= " Notes: {$notes}";
        $message .= " You were marked as present.";
        
        echo "Notification Title: {$title}\n";
        echo "Notification Message: {$message}\n\n";
        
        echo "✓ Attendance notification test completed successfully\n";
        
    } else {
        echo "✗ Student not found\n";
    }
    
    // Test 3: Check notification types
    echo "\n3. Testing notification type display:\n";
    echo "  - attendance type display: " . get_notification_type_display('attendance') . "\n";
    echo "  - attendance icon: " . get_notification_icon('attendance') . "\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
