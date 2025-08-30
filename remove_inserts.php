<?php
// Script to remove all INSERT INTO statements from SQL file

$inputFile = 'c:/Users/ferre/Downloads/scms_test.sql';
$outputFile = 'c:/Users/ferre/Downloads/scms_test_clean.sql';

if (!file_exists($inputFile)) {
    die("Input file not found: $inputFile\n");
}

echo "Reading file: $inputFile\n";
$content = file_get_contents($inputFile);

// Remove all INSERT INTO statements
$pattern = '/INSERT INTO.*?;/s';
$cleanedContent = preg_replace($pattern, '', $content);

// Clean up multiple empty lines
$cleanedContent = preg_replace('/\n\s*\n\s*\n/', "\n\n", $cleanedContent);

echo "Writing cleaned file: $outputFile\n";
file_put_contents($outputFile, $cleanedContent);

echo "Done! Removed all INSERT INTO statements.\n";
echo "Original file size: " . strlen($content) . " bytes\n";
echo "Cleaned file size: " . strlen($cleanedContent) . " bytes\n";
echo "Removed " . (strlen($content) - strlen($cleanedContent)) . " bytes\n";
?>
