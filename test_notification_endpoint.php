<?php
/**
 * Test script to verify notification endpoint functionality
 * Run this script to test if the notification API is working
 */

// Include CodeIgniter bootstrap
require_once 'index.php';

// Test the notification endpoint
echo "=== Testing Notification Endpoint ===\n\n";

// Test 1: Check if Token_lib can be loaded
echo "1. Testing Token_lib library...\n";
try {
    $CI =& get_instance();
    $CI->load->library('Token_lib');
    echo "✅ Token_lib library loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to load Token_lib: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Check if getallheaders function exists
echo "\n2. Testing getallheaders function...\n";
if (function_exists('getallheaders')) {
    echo "✅ getallheaders() function exists\n";
} else {
    echo "⚠️  getallheaders() function not available\n";
}

// Test 3: Check $_SERVER variables
echo "\n3. Testing \$_SERVER variables...\n";
$auth_vars = ['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION'];
foreach ($auth_vars as $var) {
    if (isset($_SERVER[$var])) {
        echo "✅ \$_SERVER['{$var}'] is set: " . $_SERVER[$var] . "\n";
    } else {
        echo "❌ \$_SERVER['{$var}'] is not set\n";
    }
}

// Test 4: Check if NotificationController can be loaded
echo "\n4. Testing NotificationController...\n";
try {
    $CI->load->controller('api/NotificationController');
    echo "✅ NotificationController loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to load NotificationController: " . $e->getMessage() . "\n";
}

// Test 5: Check if Notification_model can be loaded
echo "\n5. Testing Notification_model...\n";
try {
    $CI->load->model('Notification_model');
    echo "✅ Notification_model loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to load Notification_model: " . $e->getMessage() . "\n";
}

// Test 6: Check if notification helper can be loaded
echo "\n6. Testing notification helper...\n";
try {
    $CI->load->helper('notification');
    echo "✅ Notification helper loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to load notification helper: " . $e->getMessage() . "\n";
}

// Test 7: Check if routes are configured
echo "\n7. Testing routes configuration...\n";
$routes_file = 'application/config/routes.php';
if (file_exists($routes_file)) {
    $routes_content = file_get_contents($routes_file);
    if (strpos($routes_content, 'api/notifications') !== false) {
        echo "✅ Notification routes are configured\n";
    } else {
        echo "❌ Notification routes not found in routes.php\n";
    }
} else {
    echo "❌ Routes file not found\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo test the actual endpoint, try:\n";
echo "GET http://localhost/scms_new_backup/index.php/api/notifications\n";
echo "Headers: Authorization: Bearer <your_jwt_token>\n";
?>
