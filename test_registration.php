<?php
// Simple test to verify registration functionality
echo "=== Registration Functionality Test ===\n\n";

// Test 1: Check if Auth controller has the new methods
echo "1. Checking Auth controller for multipart/form-data handling...\n";
$auth_file = "application/controllers/api/Auth.php";
if (file_exists($auth_file)) {
    $auth_content = file_get_contents($auth_file);
    if (strpos($auth_content, 'register_with_images') !== false) {
        echo "✓ register_with_images method exists\n";
    } else {
        echo "✗ register_with_images method missing\n";
    }
    
    if (strpos($auth_content, 'upload_image') !== false) {
        echo "✓ upload_image method exists\n";
    } else {
        echo "✗ upload_image method missing\n";
    }
    
    if (strpos($auth_content, 'register_json') !== false) {
        echo "✓ register_json method exists (backward compatibility)\n";
    } else {
        echo "✗ register_json method missing\n";
    }
} else {
    echo "✗ Auth controller file not found\n";
}

// Test 2: Check if upload directories exist
echo "\n2. Checking upload directories...\n";
$profile_dir = "uploads/profile";
$cover_dir = "uploads/cover";

if (is_dir($profile_dir) && is_writable($profile_dir)) {
    echo "✓ Profile directory exists and is writable\n";
} else {
    echo "✗ Profile directory issue\n";
}

if (is_dir($cover_dir) && is_writable($cover_dir)) {
    echo "✓ Cover directory exists and is writable\n";
} else {
    echo "✗ Cover directory issue\n";
}

// Test 3: Check if test HTML file is updated
echo "\n3. Checking test HTML file...\n";
$test_html = "upload_test.html";
if (file_exists($test_html)) {
    $html_content = file_get_contents($test_html);
    if (strpos($html_content, 'FormData') !== false) {
        echo "✓ Test HTML uses FormData for multipart/form-data\n";
    } else {
        echo "✗ Test HTML still uses JSON\n";
    }
    
    if (strpos($html_content, 'profile_pic') !== false) {
        echo "✓ Test HTML includes profile_pic field\n";
    } else {
        echo "✗ Test HTML missing profile_pic field\n";
    }
    
    if (strpos($html_content, 'cover_pic') !== false) {
        echo "✓ Test HTML includes cover_pic field\n";
    } else {
        echo "✗ Test HTML missing cover_pic field\n";
    }
} else {
    echo "✗ Test HTML file not found\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo test the full functionality:\n";
echo "1. Make sure XAMPP Apache is running\n";
echo "2. Open http://localhost/scms_new/upload_test.html in your browser\n";
echo "3. Select profile and cover images\n";
echo "4. Click 'Prepare Images'\n";
echo "5. Fill in the registration form\n";
echo "6. Click 'Register User'\n";
echo "7. Check that images are saved in uploads/profile/ and uploads/cover/\n";
echo "8. Verify the database has the image paths saved\n";
?> 