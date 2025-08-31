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

    echo "🔍 Checking users in database...\n\n";

    // Check users table
    $stmt = $pdo->query("SELECT user_id, full_name, role, status FROM users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Users found:\n";
    foreach ($users as $user) {
        echo "  - {$user['user_id']}: {$user['full_name']} ({$user['role']}) - {$user['status']}\n";
    }
    
    // Check subjects table
    echo "\n📚 Subjects found:\n";
    $stmt = $pdo->query("SELECT id, subject_code, subject_name FROM subjects LIMIT 5");
    $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($subjects as $subject) {
        echo "  - {$subject['id']}: {$subject['subject_code']} - {$subject['subject_name']}\n";
    }
    
    // Check sections table
    echo "\n🏫 Sections found:\n";
    $stmt = $pdo->query("SELECT section_id, section_name FROM sections LIMIT 5");
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($sections as $section) {
        echo "  - {$section['section_id']}: {$section['section_name']}\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
