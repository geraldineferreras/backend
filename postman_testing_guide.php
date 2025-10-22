<?php
/**
 * Postman Testing Guide for Chairperson Creation
 * Complete step-by-step guide for testing in Postman
 */

echo "🧪 Postman Testing Guide for Chairperson Creation\n";
echo "=================================================\n\n";

echo "📋 STEP-BY-STEP POSTMAN SETUP:\n";
echo "===============================\n\n";

echo "STEP 1: Open Postman\n";
echo "--------------------\n";
echo "• Open Postman application\n";
echo "• Click \"New\" to create a new request\n";
echo "• Or use the \"+\" button to add a new tab\n\n";

echo "STEP 2: Configure Request Method and URL\n";
echo "---------------------------------------\n";
echo "• Set method to: POST\n";
echo "• Enter URL: https://scms-backend.up.railway.app/api/admin/create_user\n";
echo "• Or use your local URL: http://localhost/scms_new/api/admin/create_user\n\n";

echo "STEP 3: Set Headers\n";
echo "-------------------\n";
echo "• Click on \"Headers\" tab\n";
echo "• Add header:\n";
echo "  Key: Authorization\n";
echo "  Value: Bearer YOUR_JWT_TOKEN\n";
echo "• Example: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...\n";
echo "• DO NOT add Content-Type header (Postman sets it automatically for FormData)\n\n";

echo "STEP 4: Configure Body (Form-Data)\n";
echo "----------------------------------\n";
echo "• Click on \"Body\" tab\n";
echo "• Select \"form-data\" radio button\n";
echo "• Add the following key-value pairs:\n\n";

echo "Required Fields:\n";
echo "┌─────────────┬─────────────────────────────────────────┬─────────┐\n";
echo "│ Key         │ Value                                   │ Type    │\n";
echo "├─────────────┼─────────────────────────────────────────┼─────────┤\n";
echo "│ role        │ chairperson                             │ Text    │\n";
echo "│ full_name   │ Dr. Sarah Johnson                       │ Text    │\n";
echo "│ email       │ sarah.johnson@university.edu            │ Text    │\n";
echo "│ password    │ chairperson123                          │ Text    │\n";
echo "│ program     │ Bachelor of Science in Computer Science │ Text    │\n";
echo "└─────────────┴─────────────────────────────────────────┴─────────┘\n\n";

echo "Optional Fields (File Uploads):\n";
echo "┌─────────────┬─────────────────────────────────────────┬─────────┐\n";
echo "│ Key         │ Value                                   │ Type    │\n";
echo "├─────────────┼─────────────────────────────────────────┼─────────┤\n";
echo "│ profile_pic │ [Select File] profile.jpg               │ File    │\n";
echo "│ cover_pic   │ [Select File] cover.png                │ File    │\n";
echo "└─────────────┴─────────────────────────────────────────┴─────────┘\n\n";

echo "STEP 5: Add Form-Data Fields\n";
echo "-----------------------------\n";
echo "• Click \"Add\" button to add each field\n";
echo "• For text fields: Leave Type as \"Text\"\n";
echo "• For file fields: Change Type to \"File\"\n";
echo "• Click \"Select Files\" to choose image files\n\n";

echo "STEP 6: Send Request\n";
echo "--------------------\n";
echo "• Click the blue \"Send\" button\n";
echo "• Wait for response\n";
echo "• Check the response in the bottom panel\n\n";

echo "📊 EXPECTED SUCCESS RESPONSE:\n";
echo "=============================\n\n";
echo "Status: 201 Created\n";
echo "Response Body:\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"Chairperson created successfully!\",\n";
echo "  \"data\": {\n";
echo "    \"user_id\": \"CHR68E33539D3266137\",\n";
echo "    \"full_name\": \"Dr. Sarah Johnson\",\n";
echo "    \"email\": \"sarah.johnson@university.edu\",\n";
echo "    \"role\": \"chairperson\",\n";
echo "    \"status\": \"active\",\n";
echo "    \"admin_type\": \"chairperson\",\n";
echo "    \"program\": \"Bachelor of Science in Computer Science\",\n";
echo "    \"profile_pic\": \"uploads/profile/profile_68e3444c329f7.jpg\",\n";
echo "    \"cover_pic\": \"uploads/cover/cover_68e3411957d27.png\",\n";
echo "    \"created_at\": \"2025-01-08 20:30:00\"\n";
echo "  }\n";
echo "}\n\n";

echo "❌ COMMON ERROR RESPONSES:\n";
echo "==========================\n\n";

echo "1. Authentication Error (401):\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"Authentication required. Please login.\"\n";
echo "}\n";
echo "Solution: Check your JWT token\n\n";

echo "2. Access Denied (403):\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"Access denied. Main Admin privileges required.\"\n";
echo "}\n";
echo "Solution: Login as Main Admin\n\n";

echo "3. Email Already Exists (400):\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"Email already exists\"\n";
echo "}\n";
echo "Solution: Use a different email\n\n";

echo "4. Invalid Program (400):\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"Invalid program selected\"\n";
echo "}\n";
echo "Solution: Use exact program name\n\n";

echo "🎯 AVAILABLE PROGRAMS:\n";
echo "======================\n\n";
echo "• Bachelor of Science in Computer Science\n";
echo "• Bachelor of Science in Information Systems\n";
echo "• Bachelor of Science in Information Technology\n";
echo "• Associate in Computer Technology\n\n";

echo "🔧 TESTING SCENARIOS:\n";
echo "=====================\n\n";

echo "SCENARIO 1: Basic Chairperson (No Files)\n";
echo "-----------------------------------------\n";
echo "• role: chairperson\n";
echo "• full_name: Dr. John Smith\n";
echo "• email: john.smith@university.edu\n";
echo "• password: chairperson123\n";
echo "• program: Bachelor of Science in Information Systems\n\n";

echo "SCENARIO 2: Chairperson with Profile Pictures\n";
echo "---------------------------------------------\n";
echo "• role: chairperson\n";
echo "• full_name: Dr. Maria Garcia\n";
echo "• email: maria.garcia@university.edu\n";
echo "• password: chairperson123\n";
echo "• program: Bachelor of Science in Information Technology\n";
echo "• profile_pic: [Upload profile.jpg]\n";
echo "• cover_pic: [Upload cover.png]\n\n";

echo "SCENARIO 3: Different Program\n";
echo "-----------------------------\n";
echo "• role: chairperson\n";
echo "• full_name: Prof. Michael Chen\n";
echo "• email: michael.chen@university.edu\n";
echo "• password: chairperson123\n";
echo "• program: Associate in Computer Technology\n\n";

echo "🔍 DEBUGGING TIPS:\n";
echo "==================\n\n";

echo "1. Check Console Tab:\n";
echo "   • Click \"Console\" tab in Postman\n";
echo "   • Look for detailed error messages\n\n";

echo "2. Verify JWT Token:\n";
echo "   • Make sure token is valid and not expired\n";
echo "   • Login first to get a fresh token\n\n";

echo "3. Check Required Fields:\n";
echo "   • Ensure all required fields are filled\n";
echo "   • Verify program name matches exactly\n\n";

echo "4. File Upload Issues:\n";
echo "   • Check file size (not too large)\n";
echo "   • Use supported formats (JPG, PNG, etc.)\n";
echo "   • Make sure Type is set to \"File\"\n\n";

echo "5. Network Issues:\n";
echo "   • Check internet connection\n";
echo "   • Verify server URL is correct\n";
echo "   • Try with different network if needed\n\n";

echo "📁 FILE UPLOAD NOTES:\n";
echo "=====================\n\n";

echo "• Supported formats: JPG, JPEG, PNG, GIF, WebP\n";
echo "• Recommended size: Under 5MB\n";
echo "• Files are automatically renamed with timestamps\n";
echo "• Profile pictures stored in: uploads/profile/\n";
echo "• Cover pictures stored in: uploads/cover/\n\n";

echo "🧪 QUICK TEST COMMANDS:\n";
echo "=======================\n\n";

echo "# Test with curl (alternative to Postman)\n";
echo "curl -X POST \"https://scms-backend.up.railway.app/api/admin/create_user\" \\\n";
echo "  -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "  -F \"role=chairperson\" \\\n";
echo "  -F \"full_name=Dr. Sarah Johnson\" \\\n";
echo "  -F \"email=sarah.johnson@university.edu\" \\\n";
echo "  -F \"password=chairperson123\" \\\n";
echo "  -F \"program=Bachelor of Science in Computer Science\" \\\n";
echo "  -F \"profile_pic=@/path/to/profile.jpg\" \\\n";
echo "  -F \"cover_pic=@/path/to/cover.png\"\n\n";

echo "⚠️ IMPORTANT NOTES:\n";
echo "==================\n";
echo "• Only Main Admin can create Chairpersons\n";
echo "• Program names must match exactly (case-sensitive)\n";
echo "• Email must be unique across all users\n";
echo "• Password will be hashed automatically\n";
echo "• Files are optional but recommended\n";
echo "• All operations are logged for audit\n\n";

echo "✅ SUMMARY:\n";
echo "===========\n";
echo "You now have everything needed to test Chairperson creation:\n";
echo "• Complete Postman setup instructions\n";
echo "• All required and optional fields\n";
echo "• Multiple testing scenarios\n";
echo "• Expected responses and error handling\n";
echo "• File upload configuration\n";
echo "• Debugging tips and troubleshooting\n\n";

echo "Ready to test Chairperson creation in Postman! 🧪🚀\n";
?>

