<?php
// Load CodeIgniter
require_once('index.php');

// Get database instance
$CI =& get_instance();
$CI->load->database();
$CI->load->model('Task_model');

echo "Testing task assignment duplicate fix...\n";

// Test data
$task_id = 47; // The task ID from the error
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

echo "Testing with duplicate student data...\n";

try {
    // First, clear any existing assignments for this task
    $CI->db->where('task_id', $task_id)->delete('task_student_assignments');
    echo "Cleared existing assignments for task $task_id\n";
    
    // Test the safe assignment method
    $result = $CI->Task_model->safe_assign_students_to_task($task_id, $test_students);
    
    if ($result) {
        echo "✅ Safe assignment successful!\n";
        
        // Check how many assignments were actually created
        $assignments = $CI->Task_model->get_assigned_students($task_id);
        echo "Number of assignments created: " . count($assignments) . "\n";
        
        if (count($assignments) === 1) {
            echo "✅ Duplicate prevention working correctly!\n";
        } else {
            echo "❌ Duplicate prevention failed!\n";
        }
    } else {
        echo "❌ Assignment failed!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>
