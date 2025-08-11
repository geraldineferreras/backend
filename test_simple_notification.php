<?php
/**
 * Simple test to check if notification endpoint is accessible
 */

// Test the notification endpoint directly
echo "=== Testing Notification Endpoint Access ===\n\n";

// Test 1: Check if the file exists
echo "1. Checking NotificationController file...\n";
$controller_file = 'application/controllers/api/NotificationController.php';
if (file_exists($controller_file)) {
    echo "✅ NotificationController file exists\n";
} else {
    echo "❌ NotificationController file not found\n";
    exit;
}

// Test 2: Check if the model exists
echo "\n2. Checking Notification_model file...\n";
$model_file = 'application/models/Notification_model.php';
if (file_exists($model_file)) {
    echo "✅ Notification_model file exists\n";
} else {
    echo "❌ Notification_model file not found\n";
    exit;
}

// Test 3: Check if helpers exist
echo "\n3. Checking helper files...\n";
$helper_files = [
    'application/helpers/notification_helper.php',
    'application/helpers/email_notification_helper.php'
];

foreach ($helper_files as $helper_file) {
    if (file_exists($helper_file)) {
        echo "✅ " . basename($helper_file) . " exists\n";
    } else {
        echo "❌ " . basename($helper_file) . " not found\n";
    }
}

// Test 4: Check if routes are configured
echo "\n4. Checking routes configuration...\n";
$routes_file = 'application/config/routes.php';
if (file_exists($routes_file)) {
    $routes_content = file_get_contents($routes_file);
    if (strpos($routes_content, 'api/notifications') !== false) {
        echo "✅ Notification routes are configured\n";
        
        // Show the notification routes
        preg_match_all('/\$route\[.*?notifications.*?\]/', $routes_content, $matches);
        if (!empty($matches[0])) {
            echo "   Found routes:\n";
            foreach ($matches[0] as $route) {
                echo "   - " . trim($route) . "\n";
            }
        }
    } else {
        echo "❌ Notification routes not found in routes.php\n";
    }
} else {
    echo "❌ Routes file not found\n";
}

// Test 5: Check if database table exists (basic check)
echo "\n5. Checking database connection...\n";
try {
    $db_config = include 'application/config/database.php';
    if (isset($db_config['default'])) {
        echo "✅ Database configuration found\n";
        echo "   Host: " . $db_config['default']['hostname'] . "\n";
        echo "   Database: " . $db_config['default']['database'] . "\n";
    } else {
        echo "❌ Database configuration not found\n";
    }
} catch (Exception $e) {
    echo "❌ Error reading database config: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "\nTo test the actual endpoint, try:\n";
echo "GET http://localhost/scms_new_backup/index.php/api/notifications\n";
echo "\nNote: You'll need a valid JWT token in the Authorization header.\n";
?>
