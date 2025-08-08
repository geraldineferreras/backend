<?php
// Simple test for attendance notification logic
$host = 'localhost:3308';
$dbname = 'scms_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Simple Attendance Notification Test ===\n\n";
    
    // Test 1: Check if student exists
    $student_id = 'STU689436695D2BD603';
    echo "1. Testing with student: {$student_id}\n";
    
    $stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo "✓ Student found: {$student['full_name']} ({$student['email']})\n\n";
        
        // Test 2: Simulate attendance notification message
        echo "2. Testing attendance notification message format...\n";
        
        $attendance_id = 999; // Mock attendance ID
        $status = 'present';
        $subject_name = 'Database Management System';
        $section_name = 'BSIT 4C';
        $date = '2024-02-16';
        $time_in = '08:30:00';
        $notes = 'Student arrived on time';
        
        // Simulate the notification creation logic
        $status_display = ucfirst($status);
        $title = "Attendance Recorded - {$status_display}";
        
        $message = "Your attendance has been recorded for {$subject_name} ({$section_name}) on {$date}.";
        
        if ($time_in) {
            $message .= " Time in: {$time_in}";
        }
        
        if ($notes) {
            $message .= " Notes: {$notes}";
        }
        
        // Add status-specific information
        switch (strtolower($status)) {
            case 'present':
                $message .= " You were marked as present.";
                break;
            case 'late':
                $message .= " You were marked as late.";
                break;
            case 'absent':
                $message .= " You were marked as absent.";
                break;
            case 'excused':
                $message .= " You were marked as excused (approved excuse letter).";
                break;
        }
        
        echo "Notification Details:\n";
        echo "  - Student: {$student['full_name']}\n";
        echo "  - Status: {$status}\n";
        echo "  - Subject: {$subject_name}\n";
        echo "  - Section: {$section_name}\n";
        echo "  - Date: {$date}\n";
        echo "  - Time: {$time_in}\n";
        echo "  - Notes: {$notes}\n\n";
        
        echo "Generated Notification:\n";
        echo "  - Title: {$title}\n";
        echo "  - Message: {$message}\n\n";
        
        echo "✓ Attendance notification message test completed successfully\n";
        
    } else {
        echo "✗ Student not found\n";
    }
    
    // Test 3: Test different attendance statuses
    echo "\n3. Testing different attendance statuses:\n";
    $statuses = ['present', 'late', 'absent', 'excused'];
    
    foreach ($statuses as $status) {
        $status_display = ucfirst($status);
        $title = "Attendance Recorded - {$status_display}";
        
        $message = "Your attendance has been recorded for Database Management System (BSIT 4C) on 2024-02-16.";
        $message .= " Time in: 08:30:00";
        $message .= " Notes: Student arrived on time";
        
        switch (strtolower($status)) {
            case 'present':
                $message .= " You were marked as present.";
                break;
            case 'late':
                $message .= " You were marked as late.";
                break;
            case 'absent':
                $message .= " You were marked as absent.";
                break;
            case 'excused':
                $message .= " You were marked as excused (approved excuse letter).";
                break;
        }
        
        echo "  - {$status}: {$title}\n";
        echo "    Message: {$message}\n\n";
    }
    
    echo "✓ All attendance notification tests completed successfully\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
