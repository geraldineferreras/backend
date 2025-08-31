<?php
// Database configuration
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

try {
    // Create connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔍 Checking classes in database...\n\n";

    // Check classes table
    $stmt = $pdo->query("SELECT * FROM classes LIMIT 5");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($classes)) {
        echo "📋 No classes found in the database\n";
    } else {
        echo "📋 Classes found:\n";
        foreach ($classes as $class) {
            echo "  - Class ID: {$class['class_id']}\n";
            echo "    Subject ID: {$class['subject_id']}\n";
            echo "    Section ID: {$class['section_id']}\n";
            echo "    Teacher ID: {$class['teacher_id']}\n";
            echo "    Semester: {$class['semester']}\n";
            echo "    School Year: {$class['school_year']}\n";
            echo "    Status: {$class['status']}\n";
            echo "    ---\n";
        }
    }
    
    // Check classrooms table
    echo "\n🏫 Classrooms found:\n";
    $stmt = $pdo->query("SELECT * FROM classrooms LIMIT 5");
    $classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($classrooms)) {
        echo "  No classrooms found\n";
    } else {
        foreach ($classrooms as $classroom) {
            echo "  - ID: {$classroom['id']}\n";
            echo "    Class Code: {$classroom['class_code']}\n";
            echo "    Subject ID: {$classroom['subject_id']}\n";
            echo "    Section ID: {$classroom['section_id']}\n";
            echo "    Teacher ID: {$classroom['teacher_id']}\n";
            echo "    ---\n";
        }
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
