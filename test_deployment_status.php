<?php
// Test deployment status
header('Content-Type: text/plain');

echo "🔍 Railway Deployment Status Test\n";
echo "=================================\n";

echo "📅 Current time: " . date('Y-m-d H:i:s') . "\n";
echo "📁 Current directory: " . __DIR__ . "\n";

// Check if NotificationStreamController file exists
$controllerPath = __DIR__ . '/application/controllers/api/NotificationStreamController.php';
echo "📁 NotificationStreamController file exists: " . (file_exists($controllerPath) ? "YES" : "NO") . "\n";

// Check if our test files exist
$testFiles = [
    'test_controller_existence.php',
    'test_sse_final_verification.html',
    'test_railway_database_connection.php'
];

echo "📁 Test files status:\n";
foreach ($testFiles as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo "  - $file: " . ($exists ? "EXISTS" : "MISSING") . "\n";
}

// Check routes file
$routesPath = __DIR__ . '/application/config/routes.php';
if (file_exists($routesPath)) {
    $routesContent = file_get_contents($routesPath);
    $hasStreamRoute = strpos($routesContent, 'api/notifications/stream/(:any)') !== false;
    $hasLegacyRoute = strpos($routesContent, 'api/notifications/stream-legacy') !== false;
    $hasCommentedRoutes = strpos($routesContent, '// $route[\'api/notifications/stream-legacy\']') !== false;
    
    echo "📋 Routes status:\n";
    echo "  - Stream route exists: " . ($hasStreamRoute ? "YES" : "NO") . "\n";
    echo "  - Legacy route exists: " . ($hasLegacyRoute ? "YES" : "NO") . "\n";
    echo "  - Legacy routes commented: " . ($hasCommentedRoutes ? "YES" : "NO") . "\n";
} else {
    echo "❌ Routes file not found\n";
}

echo "\n✅ Deployment status test completed\n";
?>
