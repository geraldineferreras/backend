<?php
/**
 * Test script to check HTTP headers availability
 */

echo "=== Testing HTTP Headers ===\n\n";

// Test 1: Check if getallheaders function exists
echo "1. getallheaders() function:\n";
if (function_exists('getallheaders')) {
    echo "✅ getallheaders() function exists\n";
    $headers = getallheaders();
    if (!empty($headers)) {
        echo "   Available headers:\n";
        foreach ($headers as $name => $value) {
            echo "   - {$name}: {$value}\n";
        }
    } else {
        echo "   No headers found\n";
    }
} else {
    echo "❌ getallheaders() function not available\n";
}

// Test 2: Check $_SERVER variables
echo "\n2. \$_SERVER variables:\n";
$auth_vars = [
    'HTTP_AUTHORIZATION',
    'REDIRECT_HTTP_AUTHORIZATION',
    'HTTP_ORIGIN',
    'REQUEST_METHOD',
    'HTTP_ACCEPT',
    'HTTP_CONTENT_TYPE'
];

foreach ($auth_vars as $var) {
    if (isset($_SERVER[$var])) {
        echo "✅ \$_SERVER['{$var}']: " . $_SERVER[$var] . "\n";
    } else {
        echo "❌ \$_SERVER['{$var}']: not set\n";
    }
}

// Test 3: Check all $_SERVER variables (for debugging)
echo "\n3. All \$_SERVER variables:\n";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'HTTP_') === 0 || in_array($key, ['REQUEST_METHOD', 'CONTENT_TYPE'])) {
        echo "   {$key}: {$value}\n";
    }
}

echo "\n=== Test Complete ===\n";
?>
