<?php
/**
 * Test Authentication Flow
 * This script helps debug authentication issues
 */

echo "🔐 Testing Authentication Flow\n";
echo "==============================\n\n";

echo "Step 1: Login to get JWT token\n";
echo "==============================\n";
echo "POST /api/login\n";
echo "Content-Type: application/json\n\n";
echo "Request Body:\n";
echo "{\n";
echo "  \"email\": \"your_admin_email@university.edu\",\n";
echo "  \"password\": \"your_admin_password\"\n";
echo "}\n\n";

echo "Expected Response:\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"Login successful\",\n";
echo "  \"data\": {\n";
echo "    \"token\": \"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...\",\n";
echo "    \"user\": {\n";
echo "      \"user_id\": \"ADM68E33539D3266136\",\n";
echo "      \"role\": \"admin\",\n";
echo "      \"admin_type\": \"main_admin\",\n";
echo "      \"full_name\": \"Joel Canlas\",\n";
echo "      \"email\": \"admin@university.edu\"\n";
echo "    }\n";
echo "  }\n";
echo "}\n\n";

echo "Step 2: Use the token to call admin endpoints\n";
echo "==============================================\n";
echo "GET /api/admin/get_user_permissions\n";
echo "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...\n\n";

echo "Step 3: Check your request format\n";
echo "=================================\n";
echo "Make sure you're using:\n";
echo "• Correct HTTP method (GET, POST, PUT, DELETE)\n";
echo "• Correct endpoint URL\n";
echo "• Valid JWT token in Authorization header\n";
echo "• Proper Content-Type header for POST requests\n\n";

echo "Common Issues:\n";
echo "=============\n";
echo "1. Token expired → Login again to get new token\n";
echo "2. Missing Authorization header → Add 'Authorization: Bearer TOKEN'\n";
echo "3. Wrong endpoint → Check the URL path\n";
echo "4. Wrong HTTP method → Use GET for retrieving data\n";
echo "5. Invalid token format → Make sure token starts with 'eyJ'\n\n";

echo "Test Commands:\n";
echo "==============\n";
echo "# Login first\n";
echo "curl -X POST https://your-railway-app.railway.app/api/login \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"email\":\"your_email\",\"password\":\"your_password\"}'\n\n";

echo "# Then use the token\n";
echo "curl -X GET https://your-railway-app.railway.app/api/admin/get_user_permissions \\\n";
echo "  -H \"Authorization: Bearer YOUR_TOKEN_HERE\"\n";
?>
