<?php
/**
 * User Retrieval API Endpoints Guide
 * Complete guide for getting users with consistent profile_pic/cover_pic URLs
 */

echo "📝 User Retrieval API Endpoints Guide\n";
echo "=====================================\n\n";

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
echo "    }\n";
echo "  ]\n";
echo "}\n\n";

echo "2. GET ALL ADMINS (Main Admin + Chairpersons)\n";
echo "---------------------------------------------\n";
echo "Endpoint: GET /api/admin/get_admins\n";
echo "Method: GET\n";
echo "Authorization: Bearer YOUR_JWT_TOKEN\n";
echo "Access: Main Admin only\n\n";

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

echo "3. GET MAIN ADMIN DETAILS\n";
echo "------------------------\n";
echo "Endpoint: GET /api/admin/get_main_admin\n";
echo "Method: GET\n";
echo "Authorization: Bearer YOUR_JWT_TOKEN\n";
echo "Access: Main Admin only\n\n";

echo "✅ Success Response:\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"Main Admin retrieved successfully\",\n";
echo "  \"data\": {\n";
echo "    \"user_id\": \"ADM68E33539D3266136\",\n";
echo "    \"full_name\": \"Joel Canlas\",\n";
echo "    \"email\": \"loyaltyjoelaizmorales@gmail.com\",\n";
echo "    \"role\": \"admin\",\n";
echo "    \"admin_type\": \"main_admin\",\n";
echo "    \"program\": null,\n";
echo "    \"status\": \"active\",\n";
echo "    \"profile_pic\": \"https://scms-backend.up.railway.app/uploads/profile/profile_main.jpg\",\n";
echo "    \"cover_pic\": \"https://scms-backend.up.railway.app/uploads/cover/cover_main.png\",\n";
echo "    \"created_at\": \"2025-10-06 03:19:21\"\n";
echo "  }\n";
echo "}\n\n";

echo "🔧 OTHER USER ENDPOINTS\n";
echo "=======================\n\n";

echo "4. GET STUDENTS\n";
echo "---------------\n";
echo "Endpoint: GET /api/admin/get_students\n";
echo "Method: GET\n";
echo "Authorization: Bearer YOUR_JWT_TOKEN\n";
echo "Access: Main Admin (all students) | Chairperson (their program only)\n\n";

echo "✅ Success Response:\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"Students retrieved successfully\",\n";
echo "  \"data\": [\n";
echo "    {\n";
echo "      \"user_id\": \"STU68F7AEA2493A9205\",\n";
echo "      \"full_name\": \"John Doe\",\n";
echo "      \"email\": \"john.doe@student.edu\",\n";
echo "      \"student_num\": \"2024-001\",\n";
echo "      \"program\": \"Bachelor of Science in Computer Science\",\n";
echo "      \"section_name\": \"CS-1A\",\n";
echo "      \"year_level\": \"1st Year\",\n";
echo "      \"status\": \"active\",\n";
echo "      \"profile_pic\": \"https://scms-backend.up.railway.app/uploads/profile/profile_student.jpg\",\n";
echo "      \"cover_pic\": \"https://scms-backend.up.railway.app/uploads/cover/cover_student.png\",\n";
echo "      \"created_at\": \"2025-10-22 00:02:42\"\n";
echo "    }\n";
echo "  ]\n";
echo "}\n\n";

echo "5. GET CURRENT USER PROFILE\n";
echo "---------------------------\n";
echo "Endpoint: GET /api/user/me\n";
echo "Method: GET\n";
echo "Authorization: Bearer YOUR_JWT_TOKEN\n";
echo "Access: Any authenticated user\n\n";

echo "✅ Success Response:\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"Current user profile retrieved successfully\",\n";
echo "  \"data\": {\n";
echo "    \"user_id\": \"CHA68F7AEA2493A9205\",\n";
echo "    \"full_name\": \"Philip DoctorR\",\n";
echo "    \"email\": \"doctor.philip@pampangastateu.edu.ph\",\n";
echo "    \"role\": \"chairperson\",\n";
echo "    \"admin_type\": \"chairperson\",\n";
echo "    \"program\": \"Bachelor of Science in Computer Science\",\n";
echo "    \"status\": \"active\",\n";
echo "    \"profile_pic\": \"https://scms-backend.up.railway.app/uploads/profile/profile_68e3444c329f7.jpg\",\n";
echo "    \"cover_pic\": \"https://scms-backend.up.railway.app/uploads/cover/cover_68e3411957d27.png\",\n";
echo "    \"created_at\": \"2025-10-22 00:02:42\"\n";
echo "  }\n";
echo "}\n\n";

echo "6. GET USERS BY ROLE (Legacy)\n";
echo "-----------------------------\n";
echo "Endpoint: GET /api/auth/get_users?role=chairperson\n";
echo "Method: GET\n";
echo "Authorization: Bearer YOUR_JWT_TOKEN\n";
echo "Access: Any authenticated user\n";
echo "Note: Returns raw database values (relative paths)\n\n";

echo "🔧 POSTMAN EXAMPLES\n";
echo "===================\n\n";

echo "GET ALL CHAIRPERSONS:\n";
echo "---------------------\n";
echo "Method: GET\n";
echo "URL: https://scms-backend.up.railway.app/api/admin/get_chairpersons\n";
echo "Headers:\n";
echo "  Authorization: Bearer YOUR_TOKEN\n\n";

echo "GET ALL ADMINS:\n";
echo "---------------\n";
echo "Method: GET\n";
echo "URL: https://scms-backend.up.railway.app/api/admin/get_admins\n";
echo "Headers:\n";
echo "  Authorization: Bearer YOUR_TOKEN\n\n";

echo "GET MAIN ADMIN:\n";
echo "---------------\n";
echo "Method: GET\n";
echo "URL: https://scms-backend.up.railway.app/api/admin/get_main_admin\n";
echo "Headers:\n";
echo "  Authorization: Bearer YOUR_TOKEN\n\n";

echo "GET STUDENTS:\n";
echo "-------------\n";
echo "Method: GET\n";
echo "URL: https://scms-backend.up.railway.app/api/admin/get_students\n";
echo "Headers:\n";
echo "  Authorization: Bearer YOUR_TOKEN\n\n";

echo "GET CURRENT USER:\n";
echo "-----------------\n";
echo "Method: GET\n";
echo "URL: https://scms-backend.up.railway.app/api/user/me\n";
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

echo "# Get Main Admin details\n";
echo "curl -X GET \"https://scms-backend.up.railway.app/api/admin/get_main_admin\" \\\n";
echo "  -H \"Authorization: Bearer YOUR_TOKEN\"\n\n";

echo "# Get Students\n";
echo "curl -X GET \"https://scms-backend.up.railway.app/api/admin/get_students\" \\\n";
echo "  -H \"Authorization: Bearer YOUR_TOKEN\"\n\n";

echo "# Get current user profile\n";
echo "curl -X GET \"https://scms-backend.up.railway.app/api/user/me\" \\\n";
echo "  -H \"Authorization: Bearer YOUR_TOKEN\"\n\n";

echo "🔐 PERMISSIONS SUMMARY\n";
echo "======================\n\n";
echo "• get_chairpersons: Main Admin only\n";
echo "• get_admins: Main Admin only\n";
echo "• get_main_admin: Main Admin only\n";
echo "• get_students: Main Admin (all) | Chairperson (their program)\n";
echo "• get_current_user: Any authenticated user\n\n";

echo "✅ URL CONSISTENCY FIXED\n";
echo "=======================\n\n";
echo "All endpoints now return absolute URLs for profile_pic and cover_pic:\n";
echo "• ✅ Main Admin includes profile_pic/cover_pic\n";
echo "• ✅ Chairpersons include profile_pic/cover_pic\n";
echo "• ✅ Students include profile_pic/cover_pic\n";
echo "• ✅ Current user includes profile_pic/cover_pic\n";
echo "• ✅ All URLs are absolute (https://scms-backend.up.railway.app/...)\n\n";

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

echo "3. User Not Found (404):\n";
echo "{\n";
echo "  \"status\": false,\n";
echo "  \"message\": \"User not found\"\n";
echo "}\n\n";

echo "⚠️ IMPORTANT NOTES\n";
echo "==================\n";
echo "• All endpoints now return absolute URLs for images\n";
echo "• Use these endpoints instead of legacy /api/auth/get_users\n";
echo "• profile_pic and cover_pic are always included (null if not set)\n";
echo "• URLs are ready to use directly in frontend <img> tags\n";
echo "• Main Admin can see all users, Chairpersons see only their program\n\n";

echo "✅ SUMMARY\n";
echo "==========\n";
echo "• ✅ Fixed Main Admin profile_pic/cover_pic in get_admins\n";
echo "• ✅ Added profile_pic/cover_pic to get_main_admin\n";
echo "• ✅ Fixed get_current_user to return absolute URLs\n";
echo "• ✅ All user endpoints now consistent with absolute URLs\n";
echo "• ✅ Complete Postman and curl examples\n";
echo "• ✅ Permission matrix for all endpoints\n\n";

echo "Ready to use! 🚀\n";
?>
