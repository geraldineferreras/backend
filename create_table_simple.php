<?php
// Simple database connection
$host = 'localhost';
$username = 'root';
$password = ''; // Try empty password first
$database = 'scms_db';

// Try to connect
$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    // Try with 'root' password
    $conn = mysqli_connect($host, $username, 'root', $database);
}

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected successfully!\n";

// Check if table exists
$result = mysqli_query($conn, "SHOW TABLES LIKE 'task_student_assignments'");
$table_exists = mysqli_num_rows($result) > 0;

if (!$table_exists) {
    echo "Table task_student_assignments does not exist. Creating it...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS `task_student_assignments` (
      `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
      `task_id` int(11) NOT NULL,
      `student_id` varchar(50) NOT NULL,
      `class_code` varchar(20) NOT NULL,
      `assigned_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      `status` enum('assigned','completed','overdue') DEFAULT 'assigned',
      PRIMARY KEY (`assignment_id`),
      UNIQUE KEY `unique_task_student` (`task_id`, `student_id`, `class_code`),
      KEY `task_id` (`task_id`),
      KEY `student_id` (`student_id`),
      KEY `class_code` (`class_code`),
      CONSTRAINT `fk_task_assignments_task` FOREIGN KEY (`task_id`) REFERENCES `class_tasks` (`task_id`) ON DELETE CASCADE,
      CONSTRAINT `fk_task_assignments_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if (mysqli_query($conn, $sql)) {
        echo "Table task_student_assignments created successfully!\n";
    } else {
        echo "Error creating table: " . mysqli_error($conn) . "\n";
    }
} else {
    echo "Table task_student_assignments already exists!\n";
}

mysqli_close($conn);
?> 