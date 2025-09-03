<?php
// Test script for notification system
// This script tests the notification helper functions and email sending

// Load CodeIgniter
require_once('index.php');

$CI =& get_instance();
$CI->load->model('Notification_model');
$CI->load->model('User_model');
$CI->load->helper('notification');

echo "=== Notification System Test ===\n";

try {
    // Test 1: Check if notification helper functions exist
    echo "1. Testing helper function availability...\n";
    
    if (function_exists('create_notification')) {
        echo "✅ create_notification function exists\n";
    } else {
        echo "❌ create_notification function not found\n";
    }
    
    if (function_exists('create_notifications_for_users')) {
        echo "✅ create_notifications_for_users function exists\n";
    } else {
        echo "❌ create_notifications_for_users function not found\n";
    }
    
    if (function_exists('get_class_students')) {
        echo "✅ get_class_students function exists\n";
    } else {
        echo "❌ get_class_students function not found\n";
    }
    
    // Test 2: Check database connection
    echo "\n2. Testing database connection...\n";
    $test_query = $CI->db->query("SELECT 1 as test");
    if ($test_query) {
        echo "✅ Database connection successful\n";
    } else {
        echo "❌ Database connection failed\n";
        exit;
    }
    
    // Test 3: Check if notifications table exists
    echo "\n3. Testing notifications table...\n";
    $table_exists = $CI->db->table_exists('notifications');
    if ($table_exists) {
        echo "✅ Notifications table exists\n";
        
        // Count existing notifications
        $count = $CI->db->count_all('notifications');
        echo "   Current notifications count: {$count}\n";
    } else {
        echo "❌ Notifications table does not exist\n";
    }
    
    // Test 4: Check if users table exists and has data
    echo "\n4. Testing users table...\n";
    $users_table_exists = $CI->db->table_exists('users');
    if ($users_table_exists) {
        echo "✅ Users table exists\n";
        
        // Get a sample user
        $sample_user = $CI->db->get('users', 1)->row_array();
        if ($sample_user) {
            echo "   Sample user found: {$sample_user['full_name']} ({$sample_user['user_id']})\n";
            $test_user_id = $sample_user['user_id'];
        } else {
            echo "❌ No users found in database\n";
            exit;
        }
    } else {
        echo "❌ Users table does not exist\n";
        exit;
    }
    
    // Test 5: Check if classrooms table exists
    echo "\n5. Testing classrooms table...\n";
    $classrooms_table_exists = $CI->db->table_exists('classrooms');
    if ($classrooms_table_exists) {
        echo "✅ Classrooms table exists\n";
        
        // Get a sample classroom
        $sample_classroom = $CI->db->get('classrooms', 1)->row_array();
        if ($sample_classroom) {
            echo "   Sample classroom found: {$sample_classroom['title']} ({$sample_classroom['class_code']})\n";
            $test_class_code = $sample_classroom['class_code'];
        } else {
            echo "❌ No classrooms found in database\n";
            exit;
        }
    } else {
        echo "❌ Classrooms table does not exist\n";
        exit;
    }
    
    // Test 6: Test get_class_students function
    echo "\n6. Testing get_class_students function...\n";
    if (isset($test_class_code)) {
        $students = get_class_students($test_class_code);
        if (is_array($students)) {
            echo "✅ get_class_students function works\n";
            echo "   Found " . count($students) . " students in class {$test_class_code}\n";
            
            if (!empty($students)) {
                $sample_student = $students[0];
                echo "   Sample student: {$sample_student['full_name']} ({$sample_student['user_id']})\n";
                $test_student_id = $sample_student['user_id'];
            }
        } else {
            echo "❌ get_class_students function failed\n";
        }
    }
    
    // Test 7: Test creating a single notification
    echo "\n7. Testing single notification creation...\n";
    if (isset($test_user_id)) {
        $notification_id = create_notification(
            $test_user_id,
            'task',
            'Test Task Notification',
            'This is a test notification for task creation',
            999, // test task id
            'task',
            $test_class_code ?? 'TEST123',
            false
        );
        
        if ($notification_id) {
            echo "✅ Single notification created successfully (ID: {$notification_id})\n";
        } else {
            echo "❌ Failed to create single notification\n";
        }
    }
    
    // Test 8: Test email notification helper
    echo "\n8. Testing email notification helper...\n";
    if (function_exists('send_email_notification')) {
        echo "✅ send_email_notification function exists\n";
        
        // Test with a sample user
        if (isset($test_user_id)) {
            $user = $CI->User_model->get_by_id($test_user_id);
            if ($user && !empty($user['email'])) {
                echo "   Testing email to: {$user['email']}\n";
                
                // Try to send a test email
                $email_result = send_email_notification(
                    $test_user_id,
                    'task',
                    'Test Email Notification',
                    'This is a test email notification',
                    999,
                    'task',
                    $test_class_code ?? 'TEST123'
                );
                
                if ($email_result) {
                    echo "✅ Email notification sent successfully\n";
                } else {
                    echo "❌ Email notification failed\n";
                }
            } else {
                echo "❌ User email not found\n";
            }
        }
    } else {
        echo "❌ send_email_notification function not found\n";
    }
    
    echo "\n=== Test completed ===\n";
    
} catch (Exception $e) {
    echo "❌ Error during testing: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
