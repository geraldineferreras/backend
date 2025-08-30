<?php
/**
 * Test Authentication Helper
 * This script tests if the authentication helper functions are working
 */

echo "🔐 Testing Authentication Helper...\n\n";

// Check if the auth helper file exists
$auth_helper_path = 'application/helpers/auth_helper.php';
if (file_exists($auth_helper_path)) {
    echo "✅ auth_helper.php exists\n";
    
    // Check if we can include it
    try {
        // Include CodeIgniter core files first
        if (file_exists('system/core/Common.php')) {
            echo "✅ CodeIgniter core files exist\n";
        } else {
            echo "❌ CodeIgniter core files not found\n";
        }
        
        // Check if the helper functions are accessible
        if (function_exists('require_auth')) {
            echo "✅ require_auth function exists\n";
        } else {
            echo "❌ require_auth function not found\n";
        }
        
        if (function_exists('check_auth')) {
            echo "✅ check_auth function exists\n";
        } else {
            echo "❌ check_auth function not found\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error including auth helper: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ auth_helper.php not found\n";
}

// Check if Token_lib exists
$token_lib_path = 'application/libraries/Token_lib.php';
if (file_exists($token_lib_path)) {
    echo "✅ Token_lib.php exists\n";
} else {
    echo "❌ Token_lib.php not found\n";
}

// Check if Audit_model exists
$audit_model_path = 'application/models/Audit_model.php';
if (file_exists($audit_model_path)) {
    echo "✅ Audit_model.php exists\n";
} else {
    echo "❌ Audit_model.php not found\n";
}

// Check PHP configuration
echo "\n🔧 PHP Configuration:\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Error Reporting: " . (error_reporting() ? 'Enabled' : 'Disabled') . "\n";
echo "Display Errors: " . (ini_get('display_errors') ? 'On' : 'Off') . "\n";
echo "Log Errors: " . (ini_get('log_errors') ? 'On' : 'Off') . "\n";

// Check if there are any PHP errors in the logs
$error_log_paths = [
    'error_log',
    'php_errors.log',
    'logs/error.log',
    'application/logs/log-*.php'
];

echo "\n📋 Checking for error logs...\n";
foreach ($error_log_paths as $log_path) {
    if (file_exists($log_path)) {
        echo "✅ Found log file: $log_path\n";
        
        // Check if it's readable and has content
        if (is_readable($log_path)) {
            $size = filesize($log_path);
            echo "   Size: " . ($size > 0 ? $size . " bytes" : "Empty") . "\n";
            
            if ($size > 0) {
                // Show last few lines
                $lines = file($log_path);
                $last_lines = array_slice($lines, -5);
                echo "   Last 5 lines:\n";
                foreach ($last_lines as $line) {
                    echo "     " . trim($line) . "\n";
                }
            }
        }
    }
}

// Check CodeIgniter configuration
echo "\n🔧 CodeIgniter Configuration:\n";
$config_paths = [
    'application/config/config.php',
    'application/config/database.php',
    'application/config/routes.php'
];

foreach ($config_paths as $config_path) {
    if (file_exists($config_path)) {
        echo "✅ " . basename($config_path) . " exists\n";
    } else {
        echo "❌ " . basename($config_path) . " not found\n";
    }
}

echo "\n🎯 Summary:\n";
echo "The 500 error is likely caused by:\n";
echo "1. JWT token validation issues\n";
echo "2. Missing CodeIgniter dependencies\n";
echo "3. PHP memory or execution time limits\n";
echo "4. File permission issues\n";

echo "\n💡 Next steps:\n";
echo "1. Check the browser's Network tab for the exact error response\n";
echo "2. Check the CodeIgniter error logs\n";
echo "3. Verify the JWT token is valid and not expired\n";
echo "4. Test with a simple API endpoint first\n";
?>
