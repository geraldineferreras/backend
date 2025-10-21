<?php
/**
 * Test Creating a Chairperson User
 * This script tests the role-based user creation
 */

echo "👥 Testing Chairperson User Creation\n";
echo "====================================\n\n";

echo "📝 To test creating a Chairperson user, use this API call:\n\n";

echo "POST /api/admin/create_user\n";
echo "Content-Type: application/json\n";
echo "Authorization: Bearer [MAIN_ADMIN_TOKEN]\n\n";

echo "Request Body:\n";
echo "{\n";
echo "  \"role\": \"chairperson\",\n";
echo "  \"full_name\": \"Dr. Sarah Johnson\",\n";
echo "  \"email\": \"sarah.johnson@university.edu\",\n";
echo "  \"password\": \"chairperson123\",\n";
echo "  \"program\": \"Bachelor of Science in Computer Science\"\n";
echo "}\n\n";

echo "Expected Response (Success):\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"Chairperson created successfully!\",\n";
echo "  \"data\": {\n";
echo "    \"user_id\": \"CHR[random_id]\"\n";
echo "  }\n";
echo "}\n\n";

echo "Expected Response (Error - if not Main Admin):\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"Access denied. Main Admin privileges required.\"\n";
echo "}\n\n";

echo "🧪 Test Steps:\n";
echo "==============\n";
echo "1. Login as Main Admin and get JWT token\n";
echo "2. Use the token to create a Chairperson\n";
echo "3. Login as the new Chairperson\n";
echo "4. Test that Chairperson can only see students in their program\n";
echo "5. Try to create another admin (should fail)\n";
?>
