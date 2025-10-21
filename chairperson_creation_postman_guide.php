<?php
/**
 * Chairperson Creation FormData Guide for Postman
 * Complete guide for testing Chairperson creation with FormData
 */

echo "📝 Chairperson Creation FormData Guide for Postman\n";
echo "=================================================\n\n";

echo "✅ POSTMAN SETUP:\n";
echo "=================\n\n";

echo "Method: POST\n";
echo "URL: {{base_url}}/api/admin/create_user\n";
echo "Headers:\n";
echo "• Authorization: Bearer YOUR_TOKEN\n";
echo "• Content-Type: (Leave empty - Postman sets it automatically for FormData)\n\n";

echo "Body: form-data\n";
echo "Key-Value Pairs:\n\n";

echo "🔧 REQUIRED FIELDS:\n";
echo "===================\n\n";

echo "Key: role\n";
echo "Value: chairperson\n";
echo "Type: Text\n\n";

echo "Key: full_name\n";
echo "Value: Dr. Sarah Johnson\n";
echo "Type: Text\n\n";

echo "Key: email\n";
echo "Value: sarah.johnson@university.edu\n";
echo "Type: Text\n\n";

echo "Key: password\n";
echo "Value: chairperson123\n";
echo "Type: Text\n\n";

echo "Key: program\n";
echo "Value: Bachelor of Science in Computer Science\n";
echo "Type: Text\n\n";

echo "📸 OPTIONAL FIELDS:\n";
echo "===================\n\n";

echo "Key: profile_pic\n";
echo "Value: [Select File]\n";
echo "Type: File\n";
echo "Description: Profile picture image file (JPG, PNG, etc.)\n\n";

echo "Key: cover_pic\n";
echo "Value: [Select File]\n";
echo "Type: File\n";
echo "Description: Cover picture image file (JPG, PNG, etc.)\n\n";

echo "🎯 AVAILABLE PROGRAMS:\n";
echo "======================\n\n";

echo "• Bachelor of Science in Computer Science\n";
echo "• Bachelor of Science in Information Systems\n";
echo "• Bachelor of Science in Information Technology\n";
echo "• Associate in Computer Technology\n\n";

echo "📊 COMPLETE POSTMAN FORM-DATA EXAMPLE:\n";
echo "======================================\n\n";

echo "Form-Data Tab:\n";
echo "┌─────────────┬─────────────────────────────────────────┬─────────┐\n";
echo "│ Key         │ Value                                   │ Type    │\n";
echo "├─────────────┼─────────────────────────────────────────┼─────────┤\n";
echo "│ role        │ chairperson                             │ Text    │\n";
echo "│ full_name   │ Dr. Sarah Johnson                       │ Text    │\n";
echo "│ email       │ sarah.johnson@university.edu            │ Text    │\n";
echo "│ password    │ chairperson123                          │ Text    │\n";
echo "│ program     │ Bachelor of Science in Computer Science │ Text    │\n";
echo "│ profile_pic │ [Select File] profile.jpg               │ File    │\n";
echo "│ cover_pic   │ [Select File] cover.png                │ File    │\n";
echo "└─────────────┴─────────────────────────────────────────┴─────────┘\n\n";

echo "🧪 TESTING SCENARIOS:\n";
echo "=====================\n\n";

echo "SCENARIO 1: Basic Chairperson (No Pictures)\n";
echo "--------------------------------------------\n";
echo "• role: chairperson\n";
echo "• full_name: Dr. John Smith\n";
echo "• email: john.smith@university.edu\n";
echo "• password: chairperson123\n";
echo "• program: Bachelor of Science in Information Systems\n\n";

echo "SCENARIO 2: Chairperson with Pictures\n";
echo "-------------------------------------\n";
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

echo "✅ SUCCESS RESPONSE:\n";
echo "====================\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"Chairperson created successfully!\",\n";
echo "  \"data\": {\n";
echo "    \"user_id\": \"CHR68E33539D3266137\"\n";
echo "  }\n";
echo "}\n\n";

echo "❌ ERROR RESPONSES:\n";
echo "===================\n\n";

echo "Invalid Program:\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"Invalid program selected\"\n";
echo "}\n\n";

echo "Email Already Exists:\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"Email already exists\"\n";
echo "}\n\n";

echo "Missing Required Field:\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"Full name is required\"\n";
echo "}\n\n";

echo "Access Denied (Not Main Admin):\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"Access denied. Main Admin privileges required.\"\n";
echo "}\n\n";

echo "🔧 STEP-BY-STEP POSTMAN SETUP:\n";
echo "==============================\n\n";

echo "1. Open Postman\n";
echo "2. Create new request\n";
echo "3. Set method to POST\n";
echo "4. Enter URL: {{base_url}}/api/admin/create_user\n";
echo "5. Go to Headers tab:\n";
echo "   • Add: Authorization = Bearer YOUR_TOKEN\n";
echo "6. Go to Body tab:\n";
echo "   • Select \"form-data\"\n";
echo "   • Add all required fields as shown above\n";
echo "7. For file uploads:\n";
echo "   • Change Type from \"Text\" to \"File\"\n";
echo "   • Click \"Select Files\" and choose your image\n";
echo "8. Click \"Send\"\n\n";

echo "📁 FILE UPLOAD NOTES:\n";
echo "=====================\n\n";

echo "• Supported formats: JPG, JPEG, PNG, GIF, WebP\n";
echo "• Maximum file size: Check your server settings\n";
echo "• Files are automatically renamed with timestamps\n";
echo "• Profile pictures go to: uploads/profile/\n";
echo "• Cover pictures go to: uploads/cover/\n\n";

echo "🔍 DEBUGGING TIPS:\n";
echo "==================\n\n";

echo "• Check Console tab in Postman for detailed errors\n";
echo "• Verify your JWT token is valid and not expired\n";
echo "• Ensure you're logged in as Main Admin\n";
echo "• Check that all required fields are filled\n";
echo "• Verify program name matches exactly (case-sensitive)\n\n";

echo "🧪 QUICK TEST COMMANDS:\n";
echo "======================\n\n";

echo "# Test with curl (if you prefer command line)\n";
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
echo "• Program names must match exactly\n";
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
echo "• File upload configuration\n\n";

echo "Ready to test Chairperson creation in Postman! 📝🚀\n";
?>
