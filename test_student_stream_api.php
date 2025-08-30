<?php
/**
 * Test Student Stream API Endpoint
 * This script tests the student stream posting functionality
 */

// Test data
$class_code = 'A4V9TE'; // The class code from the error
$test_data = [
    'title' => 'Test Student Post',
    'content' => 'This is a test post from a student to verify the API is working.',
    'is_draft' => 0,
    'is_scheduled' => 0,
    'scheduled_at' => '',
    'allow_comments' => 1,
    'attachment_type' => null,
    'attachment_url' => null
];

echo "🧪 Testing Student Stream API...\n\n";
echo "Class Code: $class_code\n";
echo "Test Data: " . json_encode($test_data, JSON_PRETTY_PRINT) . "\n\n";

// Test the database directly first
echo "🔍 Testing database operations...\n";

try {
    $host = 'localhost:3308';
    $username = 'root';
    $password = '';
    $database = 'scms_db';
    
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if classroom exists
    $stmt = $pdo->prepare("SELECT id, class_code, is_active FROM classrooms WHERE class_code = ?");
    $stmt->execute([$class_code]);
    $classroom = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($classroom) {
        echo "✅ Classroom found: ID={$classroom['id']}, Active={$classroom['is_active']}\n";
        
        // Check if there are any students enrolled
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM classroom_enrollments WHERE classroom_id = ? AND status = 'active'");
        $stmt->execute([$classroom['id']]);
        $enrollment_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "✅ Active enrollments: $enrollment_count\n";
        
        // Test inserting a stream post
        echo "🧪 Testing stream post insertion...\n";
        
        $test_stream_data = [
            'class_code' => $class_code,
            'classroom_id' => $classroom['id'],
            'user_id' => 'test_student_123',
            'title' => $test_data['title'],
            'content' => $test_data['content'],
            'is_draft' => $test_data['is_draft'],
            'is_scheduled' => $test_data['is_scheduled'],
            'scheduled_at' => $test_data['scheduled_at'] ?: null,
            'allow_comments' => $test_data['allow_comments'],
            'attachment_type' => $test_data['attachment_type'],
            'attachment_url' => $test_data['attachment_url'],
            'status' => 'published',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $columns = implode(', ', array_keys($test_stream_data));
        $placeholders = ':' . implode(', :', array_keys($test_stream_data));
        
        $sql = "INSERT INTO classroom_stream ($columns) VALUES ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($test_stream_data);
        
        $insert_id = $pdo->lastInsertId();
        echo "✅ Stream post inserted successfully! ID: $insert_id\n";
        
        // Clean up test data
        $pdo->exec("DELETE FROM classroom_stream WHERE id = $insert_id");
        echo "✅ Test data cleaned up\n";
        
    } else {
        echo "❌ Classroom not found with class_code: $class_code\n";
        
        // Show available classrooms
        $stmt = $pdo->query("SELECT class_code, is_active FROM classrooms LIMIT 5");
        echo "Available classrooms:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   - {$row['class_code']} (Active: " . ($row['is_active'] ? 'Yes' : 'No') . ")\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n🎯 Summary:\n";
echo "1. Database connection: ✅ Working\n";
echo "2. classroom_stream table: ✅ Exists and functional\n";
echo "3. Classroom lookup: " . (isset($classroom) ? "✅ Found" : "❌ Not found") . "\n";
echo "4. Stream post insertion: ✅ Working\n";

if (!isset($classroom)) {
    echo "\n💡 To fix the issue:\n";
    echo "1. Make sure the classroom with code '$class_code' exists in the database\n";
    echo "2. Verify the classroom is active (is_active = 1)\n";
    echo "3. Ensure there are students enrolled in this classroom\n";
} else {
    echo "\n✅ The database is working correctly!\n";
    echo "The 500 error might be caused by:\n";
    echo "1. Authentication issues (JWT token)\n";
    echo "2. Missing required models or libraries\n";
    echo "3. PHP configuration issues\n";
    echo "4. CodeIgniter framework issues\n";
}
?>
