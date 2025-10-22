<?php
/**
 * Chairperson API Endpoints Guide - Chairperson Only
 * Complete guide for getting and managing Chairpersons only
 */

echo "📝 Chairperson API Endpoints Guide\n";
echo "==================================\n\n";

echo "🔧 CHAIRPERSON ENDPOINTS\n";
echo "=========================\n\n";

echo "1. GET ALL CHAIRPERSONS (Main Admin Only)\n";
echo "------------------------------------------\n";
echo "Endpoint: GET /api/admin/get_chairpersons\n";
echo "Method: GET\n";
echo "Authorization: Bearer YOUR_JWT_TOKEN\n";
echo "Access: Main Admin only\n\n";

echo "✅ Success Response:\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"Chairpersons retrieved successfully\",\n";
echo "  \"data\": [\n";
echo "    {\n";
echo "      \"user_id\": \"CHA68F7AEA2493A9205\",\n";
echo "      \"full_name\": \"Philip DoctorR\",\n";
echo "      \"email\": \"doctor.philip@pampangastateu.edu.ph\",\n";
echo "      \"program\": \"Bachelor of Science in Computer Science\",\n";
echo "      \"status\": \"active\",\n";
echo "      \"profile_pic\": \"https://scms-backend.up.railway.app/uploads/profile/profile_68e3444c329f7.jpg\",\n";
echo "      \"cover_pic\": \"https://scms-backend.up.railway.app/uploads/cover/cover_68e3411957d27.png\",\n";
echo "      \"created_at\": \"2025-10-22 00:02:42\"\n";
echo "    },\n";
echo "    {\n";
echo "      \"user_id\": \"CHA68F8B9A3469AF470\",\n";
echo "      \"full_name\": \"Ronnel Delos Santos\",\n";
echo "      \"email\": \"r.delossantos@pampangastateu.edu.ph\",\n";
echo "      \"program\": \"Bachelor of Science in Information Technology\",\n";
echo "      \"status\": \"active\",\n";
echo "      \"profile_pic\": \"https://scms-backend.up.railway.app/uploads/profile/profile_68e3444c329f8.jpg\",\n";
echo "      \"cover_pic\": \"https://scms-backend.up.railway.app/uploads/cover/cover_68e3411957d28.png\",\n";
echo "      \"created_at\": \"2025-10-22 19:01:55\"\n";
echo "    }\n";
echo "  ]\n";
echo "}\n\n";

echo "2. GET ALL ADMINS (Main Admin + Chairpersons)\n";
echo "---------------------------------------------\n";
echo "Endpoint: GET /api/admin/get_admins\n";
echo "Method: GET\n";
echo "Authorization: Bearer YOUR_JWT_TOKEN\n";
echo "Access: Main Admin only\n";
echo "Note: Returns Main Admin + all Chairpersons\n\n";

echo "✅ Success Response:\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"Admins retrieved successfully\",\n";
echo "  \"data\": [\n";
echo "    {\n";
echo "      \"user_id\": \"ADM68E33539D3266136\",\n";
echo "      \"full_name\": \"Joel Canlas\",\n";
echo "      \"email\": \"loyaltyjoelaizmorales@gmail.com\",\n";
echo "      \"role\": \"admin\",\n";
echo "      \"admin_type\": \"main_admin\",\n";
echo "      \"program\": null,\n";
echo "      \"status\": \"active\",\n";
echo "      \"profile_pic\": \"https://scms-backend.up.railway.app/uploads/profile/profile_main.jpg\",\n";
echo "      \"cover_pic\": \"https://scms-backend.up.railway.app/uploads/cover/cover_main.png\",\n";
echo "      \"created_at\": \"2025-10-06 03:19:21\"\n";
echo "    },\n";
echo "    {\n";
echo "      \"user_id\": \"CHA68F7AEA2493A9205\",\n";
echo "      \"full_name\": \"Philip DoctorR\",\n";
echo "      \"email\": \"doctor.philip@pampangastateu.edu.ph\",\n";
echo "      \"role\": \"chairperson\",\n";
echo "      \"admin_type\": \"chairperson\",\n";
echo "      \"program\": \"Bachelor of Science in Computer Science\",\n";
echo "      \"status\": \"active\",\n";
echo "      \"profile_pic\": \"https://scms-backend.up.railway.app/uploads/profile/profile_68e3444c329f7.jpg\",\n";
echo "      \"cover_pic\": \"https://scms-backend.up.railway.app/uploads/cover/cover_68e3411957d27.png\",\n";
echo "      \"created_at\": \"2025-10-22 00:02:42\"\n";
echo "    }\n";
echo "  ]\n";
echo "}\n\n";

echo "🔧 CHAIRPERSON MANAGEMENT ENDPOINTS\n";
echo "====================================\n\n";

echo "3. CREATE CHAIRPERSON\n";
echo "---------------------\n";
echo "Endpoint: POST /api/admin/create_user\n";
echo "Method: POST\n";
echo "Content-Type: application/json\n";
echo "Authorization: Bearer YOUR_JWT_TOKEN\n";
echo "Access: Main Admin only\n\n";

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

echo "✅ Success Response:\n";
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

echo "4. UPDATE CHAIRPERSON\n";
echo "---------------------\n";
echo "Endpoint: PUT /api/admin/update_user/{user_id}\n";
echo "Method: PUT\n";
echo "Content-Type: application/json\n";
echo "Authorization: Bearer YOUR_JWT_TOKEN\n";
echo "Access: Main Admin (all fields) | Chairperson (basic info only)\n\n";

echo "Example URL: PUT /api/admin/update_user/CHA68F7AEA2493A9205\n\n";

echo "JSON Body (all fields optional):\n";
echo "{\n";
echo "  \"full_name\": \"Dr. Philip Doctor Updated\",\n";
echo "  \"email\": \"philip.doctor.new@university.edu\",\n";
echo "  \"status\": \"active\",\n";
echo "  \"program\": \"Bachelor of Science in Information Systems\",\n";
echo "  \"profile_pic\": \"uploads/profile/profile_new_68e3444c329f8.jpg\",\n";
echo "  \"cover_pic\": \"uploads/cover/cover_new_68e3411957d28.png\"\n";
echo "}\n\n";

echo "✅ Success Response:\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"User updated successfully\",\n";
echo "  \"data\": {\n";
echo "    \"user_id\": \"CHA68F7AEA2493A9205\",\n";
echo "    \"full_name\": \"Dr. Philip Doctor Updated\",\n";
echo "    \"email\": \"philip.doctor.new@university.edu\",\n";
echo "    \"role\": \"chairperson\",\n";
echo "    \"status\": \"active\",\n";
echo "    \"admin_type\": \"chairperson\",\n";
echo "    \"program\": \"Bachelor of Science in Information Systems\",\n";
echo "    \"profile_pic\": \"https://scms-backend.up.railway.app/uploads/profile/profile_new_68e3444c329f8.jpg\",\n";
echo "    \"cover_pic\": \"https://scms-backend.up.railway.app/uploads/cover/cover_new_68e3411957d28.png\",\n";
echo "    \"created_at\": \"2025-10-22 00:02:42\",\n";
echo "    \"updated_at\": \"2025-01-08 21:45:00\"\n";
echo "  }\n";
echo "}\n\n";

echo "5. DELETE CHAIRPERSON\n";
echo "---------------------\n";
echo "Endpoint: DELETE /api/admin/delete_chairperson/{user_id}\n";
echo "Method: DELETE\n";
echo "Authorization: Bearer YOUR_JWT_TOKEN\n";
echo "Access: Main Admin only\n\n";

echo "Example URL: DELETE /api/admin/delete_chairperson/CHA68F7AEA2493A9205\n\n";

echo "✅ Success Response:\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"Chairperson deleted successfully\"\n";
echo "}\n\n";

echo "🎯 AVAILABLE PROGRAMS FOR CHAIRPERSONS:\n";
echo "=======================================\n\n";
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

echo "DELETE CHAIRPERSON:\n";
echo "• Only Main Admin can delete Chairpersons\n";
echo "• Cannot delete Main Admin\n";
echo "• Cannot delete yourself\n\n";

echo "🔧 POSTMAN EXAMPLES\n";
echo "===================\n\n";

echo "GET ALL CHAIRPERSONS:\n";
echo "---------------------\n";
echo "Method: GET\n";
echo "URL: https://scms-backend.up.railway.app/api/admin/get_chairpersons\n";
echo "Headers:\n";
echo "  Authorization: Bearer YOUR_TOKEN\n\n";

echo "GET ALL ADMINS (Main Admin + Chairpersons):\n";
echo "-------------------------------------------\n";
echo "Method: GET\n";
echo "URL: https://scms-backend.up.railway.app/api/admin/get_admins\n";
echo "Headers:\n";
echo "  Authorization: Bearer YOUR_TOKEN\n\n";

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
echo "URL: https://scms-backend.up.railway.app/api/admin/update_user/CHA68F7AEA2493A9205\n";
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

echo "DELETE CHAIRPERSON:\n";
echo "-------------------\n";
echo "Method: DELETE\n";
echo "URL: https://scms-backend.up.railway.app/api/admin/delete_chairperson/CHA68F7AEA2493A9205\n";
echo "Headers:\n";
echo "  Authorization: Bearer YOUR_TOKEN\n\n";

echo "🧪 CURL EXAMPLES\n";
echo "================\n\n";

echo "# Get all Chairpersons\n";
echo "curl -X GET \"https://scms-backend.up.railway.app/api/admin/get_chairpersons\" \\\n";
echo "  -H \"Authorization: Bearer YOUR_TOKEN\"\n\n";

echo "# Get all Admins (Main Admin + Chairpersons)\n";
echo "curl -X GET \"https://scms-backend.up.railway.app/api/admin/get_admins\" \\\n";
echo "  -H \"Authorization: Bearer YOUR_TOKEN\"\n\n";

echo "# Create Chairperson\n";
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

echo "# Update Chairperson\n";
echo "curl -X PUT \"https://scms-backend.up.railway.app/api/admin/update_user/CHA68F7AEA2493A9205\" \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
echo "  -d '{\n";
echo "    \"full_name\": \"Dr. John Smith Updated\",\n";
echo "    \"email\": \"john.smith.new@university.edu\",\n";
echo "    \"program\": \"Bachelor of Science in Information Systems\",\n";
echo "    \"profile_pic\": \"uploads/profile/profile_john_new.jpg\",\n";
echo "    \"cover_pic\": \"uploads/cover/cover_john_new.png\"\n";
echo "  }'\n\n";

echo "# Delete Chairperson\n";
echo "curl -X DELETE \"https://scms-backend.up.railway.app/api/admin/delete_chairperson/CHA68F7AEA2493A9205\" \\\n";
echo "  -H \"Authorization: Bearer YOUR_TOKEN\"\n\n";

echo "❌ COMMON ERROR RESPONSES\n";
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

echo "⚠️ IMPORTANT NOTES\n";
echo "==================\n";
echo "• All endpoints return absolute URLs for profile_pic and cover_pic\n";
echo "• profile_pic and cover_pic are file paths (strings), not actual files\n";
echo "• Only Main Admin can create/delete Chairpersons\n";
echo "• Program names must match exactly (case-sensitive)\n";
echo "• Email must be unique across all users\n";
echo "• JWT token must be valid and belong to Main Admin\n";
echo "• URLs are ready to use directly in frontend <img> tags\n\n";

echo "✅ SUMMARY\n";
echo "==========\n";
echo "• ✅ GET: /api/admin/get_chairpersons (Main Admin only)\n";
echo "• ✅ GET: /api/admin/get_admins (Main Admin + Chairpersons)\n";
echo "• ✅ POST: /api/admin/create_user (Create Chairperson)\n";
echo "• ✅ PUT: /api/admin/update_user/{user_id} (Update Chairperson)\n";
echo "• ✅ DELETE: /api/admin/delete_chairperson/{user_id} (Delete Chairperson)\n";
echo "• ✅ All endpoints return absolute URLs for images\n";
echo "• ✅ Complete Postman and curl examples\n";
echo "• ✅ Permission matrix and error handling\n\n";

echo "Chairperson management ready! 🚀\n";
?>
