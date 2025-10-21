<?php
/**
 * Debug JWT Token Issue
 * This script helps debug why admin_type is still null
 */

echo "🔍 Debugging JWT Token Issue\n";
echo "============================\n\n";

echo "The issue might be:\n";
echo "1. Railway hasn't deployed the latest changes yet\n";
echo "2. You're using an old JWT token\n";
echo "3. There's a caching issue\n";
echo "4. The database update didn't work properly\n\n";

echo "SOLUTION 1: Check Railway Deployment\n";
echo "====================================\n";
echo "1. Go to your Railway dashboard\n";
echo "2. Check if the latest commit is deployed\n";
echo "3. Look for deployment status\n";
echo "4. Wait for deployment to complete\n\n";

echo "SOLUTION 2: Force Fresh Login\n";
echo "=============================\n";
echo "1. Clear your browser cache\n";
echo "2. Login again with fresh credentials\n";
echo "3. Check the login response for admin_type\n";
echo "4. Use the new token for API calls\n\n";

echo "SOLUTION 3: Verify Database Update\n";
echo "==================================\n";
echo "Run this SQL in DBeaver to verify:\n\n";
echo "SELECT user_id, full_name, role, admin_type, program FROM users WHERE role = 'admin';\n\n";

echo "Expected result:\n";
echo "user_id: ADM68E33539D3266136\n";
echo "role: admin\n";
echo "admin_type: main_admin\n\n";

echo "SOLUTION 4: Test Login Response\n";
echo "==============================\n";
echo "When you login, check if the response includes admin_type:\n\n";
echo "Expected login response:\n";
echo "{\n";
echo "  \"status\": true,\n";
echo "  \"message\": \"Login successful\",\n";
echo "  \"data\": {\n";
echo "    \"role\": \"admin\",\n";
echo "    \"user_id\": \"ADM68E33539D3266136\",\n";
echo "    \"full_name\": \"Joel Canlas\",\n";
echo "    \"email\": \"loyaltyjoelaizmorales@gmail.com\",\n";
echo "    \"admin_type\": \"main_admin\",  ← Should show this\n";
echo "    \"program\": null,\n";
echo "    \"token\": \"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...\"\n";
echo "  }\n";
echo "}\n\n";

echo "SOLUTION 5: Check Token Payload\n";
echo "==============================\n";
echo "You can decode your JWT token to see what's inside:\n";
echo "1. Go to https://jwt.io\n";
echo "2. Paste your JWT token\n";
echo "3. Check if admin_type is in the payload\n\n";

echo "If admin_type is missing from the token payload,\n";
echo "it means Railway hasn't deployed the latest changes yet.\n";
?>
