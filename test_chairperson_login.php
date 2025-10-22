<?php
/**
 * Test Chairperson Login
 * This script tests if existing chairperson users can login
 */

echo "🔐 Testing Chairperson Login\n";
echo "============================\n\n";

// Test credentials from the documentation
$test_credentials = [
    [
        'email' => 'doctor.philip@pampangastateu.edu.ph',
        'password' => 'chairperson123', // Default password
        'name' => 'Philip DoctorR'
    ],
    [
        'email' => 'r.delossantos@pampangastateu.edu.ph',
        'password' => 'chairperson123', // Default password
        'name' => 'Ronnel Delos Santos'
    ]
];

$base_url = 'https://scms-backend.up.railway.app';

echo "🧪 Testing Chairperson Login Credentials\n";
echo "=========================================\n\n";

foreach ($test_credentials as $index => $credential) {
    echo "TEST " . ($index + 1) . ": {$credential['name']}\n";
    echo "Email: {$credential['email']}\n";
    echo "Password: {$credential['password']}\n";
    echo "URL: {$base_url}/api/auth/login\n\n";
    
    echo "📝 cURL Command:\n";
    echo "curl -X POST \"{$base_url}/api/auth/login\" \\\n";
    echo "  -H \"Content-Type: application/json\" \\\n";
    echo "  -d '{\n";
    echo "    \"email\": \"{$credential['email']}\",\n";
    echo "    \"password\": \"{$credential['password']}\"\n";
    echo "  }'\n\n";
    
    echo "✅ Expected Success Response:\n";
    echo "{\n";
    echo "  \"status\": true,\n";
    echo "  \"message\": \"Login successful\",\n";
    echo "  \"data\": {\n";
    echo "    \"role\": \"chairperson\",\n";
    echo "    \"user_id\": \"CHA[random_id]\",\n";
    echo "    \"full_name\": \"{$credential['name']}\",\n";
    echo "    \"email\": \"{$credential['email']}\",\n";
    echo "    \"admin_type\": \"chairperson\",\n";
    echo "    \"program\": \"[Program Name]\",\n";
    echo "    \"status\": \"active\",\n";
    echo "    \"token\": \"[JWT_TOKEN]\",\n";
    echo "    \"token_type\": \"Bearer\",\n";
    echo "    \"expires_in\": \"[expiration_time]\"\n";
    echo "  }\n";
    echo "}\n\n";
    
    echo "❌ Possible Error Responses:\n";
    echo "1. Invalid email or password (401):\n";
    echo "{\n";
    echo "  \"status\": false,\n";
    echo "  \"message\": \"Invalid email or password\"\n";
    echo "}\n\n";
    
    echo "2. Account inactive (403):\n";
    echo "{\n";
    echo "  \"status\": false,\n";
    echo "  \"message\": \"Account is inactive. Please contact administrator.\"\n";
    echo "}\n\n";
    
    echo "3. Invalid email format (400):\n";
    echo "{\n";
    echo "  \"status\": false,\n";
    echo "  \"message\": \"Invalid email format\"\n";
    echo "}\n\n";
    
    echo "---\n\n";
}

echo "🔍 TROUBLESHOOTING STEPS:\n";
echo "=========================\n\n";

echo "1. CHECK DATABASE STRUCTURE:\n";
echo "   • Verify 'chairperson' is in the role ENUM\n";
echo "   • Check if admin_type column exists\n";
echo "   • Ensure users have correct role and admin_type values\n\n";

echo "2. CHECK USER STATUS:\n";
echo "   • Verify user status is 'active'\n";
echo "   • Check if password is properly hashed\n";
echo "   • Ensure email exists in database\n\n";

echo "3. CHECK AUTHENTICATION FLOW:\n";
echo "   • Verify Auth controller is working\n";
echo "   • Check if User_model->get_by_email() works\n";
echo "   • Ensure password_verify() is working\n\n";

echo "4. COMMON ISSUES:\n";
echo "   • Password might be different from 'chairperson123'\n";
echo "   • User might not exist in database\n";
echo "   • Role might not be 'chairperson'\n";
echo "   • admin_type might not be set\n";
echo "   • User status might be 'inactive'\n\n";

echo "5. QUICK FIXES:\n";
echo "   • Try creating a new chairperson user\n";
echo "   • Check database directly for existing users\n";
echo "   • Verify password hash format\n";
echo "   • Test with a known working admin account first\n\n";

echo "📋 NEXT STEPS:\n";
echo "==============\n";
echo "1. Run this test with actual API calls\n";
echo "2. Check database for existing chairperson users\n";
echo "3. Verify password hashes match\n";
echo "4. Test with a fresh chairperson creation\n";
echo "5. Check server logs for authentication errors\n\n";

echo "🚀 Ready to test! Use the cURL commands above or Postman.\n";
?>
