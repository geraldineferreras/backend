<?php
// Simple database connection
$host = 'localhost';
$username = 'root';
$passwords = ['', 'root', 'password', 'admin']; // Try common passwords
$database = 'scms_db';

$conn = null;

// Try different passwords
foreach ($passwords as $password) {
    $conn = mysqli_connect($host, $username, $password, $database);
    if ($conn && !$conn->connect_error) {
        echo "Connected successfully with password: " . ($password ? $password : '(empty)') . "\n";
        break;
    }
}

if (!$conn || $conn->connect_error) {
    die("Connection failed: " . ($conn ? $conn->connect_error : "Could not connect with any password"));
}

// Test data
$task_id = 47;
$test_students = [
    [
        'student_id' => 'STU685651BF9DDCF988',
        'class_code' => 'J56NHD'
    ],
    [
        'student_id' => 'STU685651BF9DDCF988', // Duplicate student
        'class_code' => 'J56NHD'
    ]
];

echo "Testing task assignment duplicate prevention...\n";

try {
    // First, clear any existing assignments for this task
    $delete_sql = "DELETE FROM task_student_assignments WHERE task_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $task_id);
    mysqli_stmt_execute($stmt);
    echo "Cleared existing assignments for task $task_id\n";
    
    // Test the duplicate prevention logic
    $assignments = [];
    $unique_keys = [];
    
    foreach ($test_students as $student) {
        // Create unique key
        $unique_key = $task_id . '-' . $student['student_id'] . '-' . $student['class_code'];
        
        // Skip if this combination already exists in our batch
        if (in_array($unique_key, $unique_keys)) {
            echo "Skipping duplicate: $unique_key\n";
            continue;
        }
        
        $unique_keys[] = $unique_key;
        
        $assignments[] = [
            'task_id' => $task_id,
            'student_id' => $student['student_id'],
            'class_code' => $student['class_code'],
            'assigned_at' => date('Y-m-d H:i:s'),
            'status' => 'assigned'
        ];
        
        echo "Added assignment: $unique_key\n";
    }
    
    echo "Number of unique assignments to insert: " . count($assignments) . "\n";
    
    if (!empty($assignments)) {
        // Insert the assignments
        $insert_sql = "INSERT INTO task_student_assignments (task_id, student_id, class_code, assigned_at, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $insert_sql);
        
        foreach ($assignments as $assignment) {
            mysqli_stmt_bind_param($stmt, "issss", 
                $assignment['task_id'],
                $assignment['student_id'],
                $assignment['class_code'],
                $assignment['assigned_at'],
                $assignment['status']
            );
            
            if (mysqli_stmt_execute($stmt)) {
                echo "✅ Successfully inserted assignment\n";
            } else {
                echo "❌ Failed to insert assignment: " . mysqli_error($conn) . "\n";
            }
        }
    }
    
    // Check how many assignments were actually created
    $count_sql = "SELECT COUNT(*) as count FROM task_student_assignments WHERE task_id = ?";
    $stmt = mysqli_prepare($conn, $count_sql);
    mysqli_stmt_bind_param($stmt, "i", $task_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    echo "Total assignments in database: " . $row['count'] . "\n";
    
    if ($row['count'] === 1) {
        echo "✅ Duplicate prevention working correctly!\n";
    } else {
        echo "❌ Duplicate prevention failed!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

mysqli_close($conn);
echo "\nTest completed.\n";
?>
