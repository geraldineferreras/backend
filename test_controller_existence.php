<?php
// Test if NotificationStreamController still exists
header('Content-Type: text/plain');

echo "🔍 Controller Existence Test\n";
echo "============================\n";

// Check if the file exists
$controllerPath = __DIR__ . '/application/controllers/api/NotificationStreamController.php';
echo "📁 Controller file path: $controllerPath\n";
echo "📁 File exists: " . (file_exists($controllerPath) ? "YES" : "NO") . "\n";

// Check if the class exists
echo "🔍 Class exists: " . (class_exists('NotificationStreamController') ? "YES" : "NO") . "\n";
echo "🔍 Disabled class exists: " . (class_exists('NotificationStreamController_DISABLED') ? "YES" : "NO") . "\n";

// Check current routes
echo "\n📋 Current Routes:\n";
require_once 'application/config/routes.php';

// Find routes that contain 'stream'
foreach ($route as $pattern => $target) {
    if (strpos($pattern, 'stream') !== false || strpos($target, 'stream') !== false) {
        echo "  $pattern => $target\n";
    }
}

echo "\n✅ Test completed\n";
?>
