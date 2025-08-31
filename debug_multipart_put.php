<?php
/**
 * Debug script for multipart PUT requests
 * This helps troubleshoot why form fields aren't being parsed correctly
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Multipart PUT Request Debug</h1>";

// Check request method
echo "<h2>Request Method: " . $_SERVER['REQUEST_METHOD'] . "</h2>";

// Check Content-Type header
$content_type = $_SERVER['CONTENT_TYPE'] ?? 'Not set';
echo "<h2>Content-Type: " . htmlspecialchars($content_type) . "</h2>";

// Check if it's multipart
$is_multipart = strpos($content_type, 'multipart/form-data') !== false;
echo "<h2>Is Multipart: " . ($is_multipart ? 'Yes' : 'No') . "</h2>";

// Show raw input
$raw_input = file_get_contents('php://input');
echo "<h2>Raw Input Length: " . strlen($raw_input) . " bytes</h2>";

if ($is_multipart && !empty($raw_input)) {
    echo "<h2>Parsing Multipart Data...</h2>";
    
    // Extract boundary
    if (preg_match('/boundary=(.*)$/', $content_type, $matches)) {
        $boundary = $matches[1];
        echo "<p><strong>Boundary:</strong> " . htmlspecialchars($boundary) . "</p>";
        
        // Parse multipart data
        $parts = explode('--' . $boundary, $raw_input);
        echo "<p><strong>Number of parts:</strong> " . count($parts) . "</p>";
        
        $data = [];
        foreach ($parts as $index => $part) {
            if (empty($part) || $part === '--') continue;
            
            echo "<h3>Part " . ($index + 1) . ":</h3>";
            echo "<pre>" . htmlspecialchars($part) . "</pre>";
            
            // Parse each part
            if (preg_match('/name="([^"]+)"/', $part, $name_matches)) {
                $field_name = $name_matches[1];
                echo "<p><strong>Field Name:</strong> " . htmlspecialchars($field_name) . "</p>";
                
                // Check if this is a file upload
                if (preg_match('/filename="([^"]+)"/', $part)) {
                    echo "<p><strong>Type:</strong> File Upload</p>";
                    continue;
                }
                
                // Extract the value (text field)
                $value_start = strpos($part, "\r\n\r\n") + 4;
                $value_end = strrpos($part, "\r\n");
                if ($value_start !== false && $value_end !== false && $value_end > $value_start) {
                    $value = substr($part, $value_start, $value_end - $value_start);
                    $data[$field_name] = trim($value);
                    echo "<p><strong>Value:</strong> " . htmlspecialchars($value) . "</p>";
                }
            }
        }
        
        echo "<h2>Parsed Data:</h2>";
        echo "<pre>" . print_r($data, true) . "</pre>";
        
        // Check required fields
        $required = ['content'];
        $missing = [];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            echo "<h2 style='color: red;'>Missing Required Fields:</h2>";
            echo "<ul>";
            foreach ($missing as $field) {
                echo "<li>" . htmlspecialchars($field) . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<h2 style='color: green;'>All Required Fields Present!</h2>";
        }
    } else {
        echo "<p style='color: red;'>Could not extract boundary from Content-Type</p>";
    }
} else {
    echo "<p>Not a multipart request or no raw input</p>";
}

// Show $_POST and $_FILES for comparison
echo "<h2>POST Data:</h2>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

echo "<h2>FILES Data:</h2>";
echo "<pre>" . print_r($_FILES, true) . "</pre>";

// Show all headers
echo "<h2>All Headers:</h2>";
echo "<pre>";
foreach (getallheaders() as $name => $value) {
    echo "$name: $value\n";
}
echo "</pre>";
?>
