<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$passwords = ['', 'root', 'password', 'admin']; // Try common passwords
$database = 'scms_db';

$conn = null;

// Try different passwords
foreach ($passwords as $password) {
    $conn = new mysqli($host, $username, $password, $database);
    if (!$conn->connect_error) {
        echo "Connected successfully with password: " . ($password ? $password : '(empty)') . "\n";
        break;
    }
}

// Check connection
if (!$conn || $conn->connect_error) {
    die("Connection failed: " . ($conn ? $conn->connect_error : "Could not connect with any password"));
}

// SQL to create the table
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

// Execute the query
if ($conn->query($sql) === TRUE) {
    echo "Table task_student_assignments created successfully\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
?> 