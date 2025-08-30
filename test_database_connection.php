<?php
/**
 * Test Database Connection and Classroom Stream Table
 */

// Database configuration
$host = 'localhost:3308';
$username = 'root';
$password = '';
$database = 'scms_db';

echo "🔍 Testing Database Connection and Table Structure...\n\n";

try {
    // Test connection
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful!\n\n";
    
    // Test classroom_stream table
    echo "📋 Testing classroom_stream table...\n";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'classroom_stream'");
    if ($stmt->rowCount() > 0) {
        echo "✅ classroom_stream table exists\n";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE classroom_stream");
        echo "📋 Table structure:\n";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "   {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']} {$row['Default']}\n";
        }
        
        // Test inserting a sample record
        echo "\n🧪 Testing insert operation...\n";
        try {
            $test_data = [
                'class_code' => 'TEST123',
                'classroom_id' => 1,
                'user_id' => 'test_user',
                'title' => 'Test Post',
                'content' => 'This is a test post',
                'is_draft' => 0,
                'is_scheduled' => 0,
                'scheduled_at' => null,
                'allow_comments' => 1,
                'attachment_type' => null,
                'attachment_url' => null,
                'status' => 'published',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $columns = implode(', ', array_keys($test_data));
            $placeholders = ':' . implode(', :', array_keys($test_data));
            
            $sql = "INSERT INTO classroom_stream ($columns) VALUES ($placeholders)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($test_data);
            
            $insert_id = $pdo->lastInsertId();
            echo "✅ Insert test successful! Insert ID: $insert_id\n";
            
            // Clean up test data
            $pdo->exec("DELETE FROM classroom_stream WHERE id = $insert_id");
            echo "✅ Test data cleaned up\n";
            
        } catch (Exception $e) {
            echo "❌ Insert test failed: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ classroom_stream table does not exist!\n";
    }
    
    // Test classrooms table
    echo "\n📋 Testing classrooms table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'classrooms'");
    if ($stmt->rowCount() > 0) {
        echo "✅ classrooms table exists\n";
        
        // Count classrooms
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM classrooms");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "   Total classrooms: $count\n";
        
        // Show sample classroom
        if ($count > 0) {
            $stmt = $pdo->query("SELECT class_code, subject_id, section_id FROM classrooms LIMIT 1");
            $classroom = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "   Sample classroom: " . $classroom['class_code'] . "\n";
        }
        
    } else {
        echo "❌ classrooms table does not exist!\n";
    }
    
    // Test classroom_enrollments table
    echo "\n📋 Testing classroom_enrollments table...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'classroom_enrollments'");
    if ($stmt->rowCount() > 0) {
        echo "✅ classroom_enrollments table exists\n";
        
        // Count enrollments
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM classroom_enrollments");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "   Total enrollments: $count\n";
        
    } else {
        echo "❌ classroom_enrollments table does not exist!\n";
    }
    
    echo "\n🎉 Database test completed successfully!\n";
    
} catch(PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
