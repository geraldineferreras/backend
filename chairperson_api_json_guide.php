<?php
/**
 * Chairperson API Endpoints - Raw JSON Format
 * Complete guide for creating and updating Chairpersons with JSON
 */

echo "📝 Chairperson API Endpoints - Raw JSON Format\n";
echo "==============================================\n\n";

echo "🔧 CREATE CHAIRPERSON API\n";
echo "==========================\n\n";

echo "Endpoint: POST /api/admin/create_user\n";
echo "Method: POST\n";
echo "Content-Type: application/json\n";
echo "Authorization: Bearer YOUR_JWT_TOKEN\n\n";

echo "JSON Body:\n";
echo "{\n";
echo "  \"role\": \"chairperson\",\n";
echo "  \"full_name\": \"Dr. Sarah Johnson\",\n";
echo "  \"email\": \"sarah.johnson@university.edu\",\n";
echo "  \"password\": \"chairperson123\",\n";
echo "  \"program\": \"Bachelor of Science in Computer Science\",\n";
echo "  \"profile_pic\": \"uploads/profile/profile_68e3444c329f7.jpg\",\n";
echo "  \"cover_pic\": \"uploads/cover/cover_68e3411957d27.png\"\n";
echo "}\n\n";

echo "Required Fields:\n";
echo "• role: \"chairperson\"\n";
echo "• full_name: string\n";
echo "• email: string (unique)\n";
echo "• password: string\n";
echo "• program: string (must match available programs)\n\n";

echo "Optional Fields:\n";
echo "• profile_pic: string (file path)\n";
echo "• cover_pic: string (file path)\n\n";

echo "✅ Success Response (201):\n";
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
echo "    \"profile_pic\": \"https://scms-backend.up.railway.app/uploads/profile/profile_68e3444c329f7.jpg\",\n";
echo "    \"cover_pic\": \"https://scms-backend.up.railway.app/uploads/cover/cover_68e3411957d27.png\",\n";
echo "    \"created_at\": \"2025-01-08 20:30:00\"\n";
echo "  }\n";
echo "}\n\n";

echo "🔧 UPDATE CHAIRPERSON API\n";
echo "==========================\n\n";

echo "Endpoint: PUT /api/admin/update_user/{user_id}\n";
echo "Method: PUT\n";
echo "Content-Type: application/json\n";
echo "Authorization: Bearer YOUR_JWT_TOKEN\n\n";

echo "Example URL: PUT /api/admin/update_user/CHR68E33539D3266137\n\n";

echo "JSON Body (all fields optional):\n";
echo "{\n";
echo "  \"full_name\": \"Dr. Sarah Johnson Updated\",\n";
echo "  \"email\": \"sarah.johnson.new@university.edu\",\n";
echo "  \"status\": \"active\",\n";
echo "  \"program\": \"Bachelor of Science in Information Systems\",\n";
echo "  \"profile_pic\": \"uploads/profile/profile_new_68e3444c329f8.jpg\",\n";
echo "  \"cover_pic\": \"uploads/cover/cover_new_68e3411957d28.png\"\n";
echo "}\n\n";

echo "Updateable Fields:\n";
echo "• full_name: string\n";
echo "• email: string (must be unique)\n";
echo "• status: \"active\" | \"inactive\"\n";
echo "• program: string (only Main Admin can change)\n";
echo "• profile_pic: string (file path)\n";
echo "• cover_pic: string (file path)\n\n";

echo "✅ Success Response (200):\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"User updated successfully\",\n";
echo "  \"data\": {\n";
echo "    \"user_id\": \"CHR68E33539D3266137\",\n";
echo "    \"full_name\": \"Dr. Sarah Johnson Updated\",\n";
echo "    \"email\": \"sarah.johnson.new@university.edu\",\n";
echo "    \"role\": \"chairperson\",\n";
echo "    \"status\": \"active\",\n";
echo "    \"admin_type\": \"chairperson\",\n";
echo "    \"program\": \"Bachelor of Science in Information Systems\",\n";
echo "    \"profile_pic\": \"https://scms-backend.up.railway.app/uploads/profile/profile_new_68e3444c329f8.jpg\",\n";
echo "    \"cover_pic\": \"https://scms-backend.up.railway.app/uploads/cover/cover_new_68e3411957d28.png\",\n";
echo "    \"created_at\": \"2025-01-08 20:30:00\",\n";
echo "    \"updated_at\": \"2025-01-08 21:45:00\"\n";
echo "  }\n";
echo "}\n\n";

echo "🎯 AVAILABLE PROGRAMS:\n";
echo "======================\n\n";
echo "• Bachelor of Science in Computer Science\n";
echo "• Bachelor of Science in Information Systems\n";
echo "• Bachelor of Science in Information Technology\n";
echo "• Associate in Computer Technology\n\n";

echo "🔐 PERMISSIONS:\n";
echo "===============\n\n";
echo "CREATE CHAIRPERSON:\n";
echo "• Only Main Admin can create Chairpersons\n";
echo "• Chairpersons cannot create other Chairpersons\n\n";

echo "UPDATE CHAIRPERSON:\n";
echo "• Main Admin: can update all fields including program\n";
echo "• Chairperson: can update basic info but NOT program\n";
echo "• Both can update profile_pic and cover_pic\n\n";

echo "❌ COMMON ERROR RESPONSES:\n";
echo "==========================\n\n";

echo "1. Authentication Error (401):\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"Authentication required. Please login.\"\n";
echo "}\n\n";

echo "2. Access Denied (403):\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"Access denied. Main Admin privileges required.\"\n";
echo "}\n\n";

echo "3. Email Already Exists (409):\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"User with this email already exists!\"\n";
echo "}\n\n";

echo "4. Invalid Program (400):\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"Invalid program selected\"\n";
echo "}\n\n";

echo "5. User Not Found (404):\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"User not found\"\n";
echo "}\n\n";

echo "🧪 POSTMAN EXAMPLES:\n";
echo "====================\n\n";

echo "CREATE CHAIRPERSON:\n";
echo "-------------------\n";
echo "Method: POST\n";
echo "URL: https://scms-backend.up.railway.app/api/admin/create_user\n";
echo "Headers:\n";
echo "  Authorization: Bearer YOUR_TOKEN\n";
echo "  Content-Type: application/json\n";
echo "Body (raw JSON):\n";
echo "{\n";
echo "  \"role\": \"chairperson\",\n";
echo "  \"full_name\": \"Dr. Maria Garcia\",\n";
echo "  \"email\": \"maria.garcia@university.edu\",\n";
echo "  \"password\": \"chairperson123\",\n";
echo "  \"program\": \"Bachelor of Science in Information Technology\",\n";
echo "  \"profile_pic\": \"uploads/profile/profile_maria.jpg\",\n";
echo "  \"cover_pic\": \"uploads/cover/cover_maria.png\"\n";
echo "}\n\n";

echo "UPDATE CHAIRPERSON:\n";
echo "-------------------\n";
echo "Method: PUT\n";
echo "URL: https://scms-backend.up.railway.app/api/admin/update_user/CHR68E33539D3266137\n";
echo "Headers:\n";
echo "  Authorization: Bearer YOUR_TOKEN\n";
echo "  Content-Type: application/json\n";
echo "Body (raw JSON):\n";
echo "{\n";
echo "  \"full_name\": \"Dr. Maria Garcia Updated\",\n";
echo "  \"email\": \"maria.garcia.new@university.edu\",\n";
echo "  \"program\": \"Bachelor of Science in Computer Science\",\n";
echo "  \"profile_pic\": \"uploads/profile/profile_maria_new.jpg\",\n";
echo "  \"cover_pic\": \"uploads/cover/cover_maria_new.png\"\n";
echo "}\n\n";

echo "🧪 CURL EXAMPLES:\n";
echo "==================\n\n";

echo "CREATE CHAIRPERSON:\n";
echo "curl -X POST \"https://scms-backend.up.railway.app/api/admin/create_user\" \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "  -d '{\n";
echo "    \"role\": \"chairperson\",\n";
echo "    \"full_name\": \"Dr. John Smith\",\n";
echo "    \"email\": \"john.smith@university.edu\",\n";
echo "    \"password\": \"chairperson123\",\n";
echo "    \"program\": \"Bachelor of Science in Computer Science\",\n";
echo "    \"profile_pic\": \"uploads/profile/profile_john.jpg\",\n";
echo "    \"cover_pic\": \"uploads/cover/cover_john.png\"\n";
echo "  }'\n\n";

echo "UPDATE CHAIRPERSON:\n";
echo "curl -X PUT \"https://scms-backend.up.railway.app/api/admin/update_user/CHR68E33539D3266137\" \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "  -d '{\n";
echo "    \"full_name\": \"Dr. John Smith Updated\",\n";
echo "    \"email\": \"john.smith.new@university.edu\",\n";
echo "    \"program\": \"Bachelor of Science in Information Systems\",\n";
echo "    \"profile_pic\": \"uploads/profile/profile_john_new.jpg\",\n";
echo "    \"cover_pic\": \"uploads/cover/cover_john_new.png\"\n";
echo "  }'\n\n";

echo "⚠️ IMPORTANT NOTES:\n";
echo "===================\n";
echo "• Use raw JSON format (Content-Type: application/json)\n";
echo "• profile_pic and cover_pic are file paths (strings), not actual files\n";
echo "• API returns absolute URLs for images in responses\n";
echo "• Only Main Admin can create Chairpersons\n";
echo "• Program names must match exactly (case-sensitive)\n";
echo "• Email must be unique across all users\n";
echo "• JWT token must be valid and belong to Main Admin\n\n";

echo "✅ SUMMARY:\n";
echo "===========\n";
echo "• ✅ CREATE: POST /api/admin/create_user\n";
echo "• ✅ UPDATE: PUT /api/admin/update_user/{user_id}\n";
echo "• ✅ Raw JSON format for both endpoints\n";
echo "• ✅ Absolute URLs returned for profile_pic/cover_pic\n";
echo "• ✅ Complete Postman and curl examples\n";
echo "• ✅ Error handling and permissions\n\n";

echo "Ready to test! 🚀\n";
?>
