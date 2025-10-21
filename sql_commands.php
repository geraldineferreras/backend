<?php
/**
 * Simple SQL Commands for Adding admin_type Column
 * Run this if you prefer to execute SQL directly
 */

echo "🔧 SQL Commands to Add admin_type Column\n";
echo "========================================\n\n";

echo "Run these SQL commands in your Railway database:\n\n";

echo "-- 1. Add admin_type column\n";
echo "ALTER TABLE \`users\` ADD COLUMN \`admin_type\` ENUM('main_admin', 'chairperson') NULL AFTER \`role\`;\n\n";

echo "-- 2. Update role enum to include chairperson\n";
echo "ALTER TABLE \`users\` MODIFY COLUMN \`role\` ENUM('admin', 'teacher', 'student', 'chairperson') NOT NULL;\n\n";

echo "-- 3. Update existing admin users to be main_admin\n";
echo "UPDATE users SET admin_type = 'main_admin' WHERE role = 'admin' AND (admin_type IS NULL OR admin_type = '');\n\n";

echo "-- 4. Add indexes for better performance\n";
echo "CREATE INDEX \`idx_admin_type\` ON \`users\` (\`admin_type\`);\n";
echo "CREATE INDEX \`idx_program\` ON \`users\` (\`program\`);\n\n";

echo "✅ After running these commands, your database will be ready!\n";
echo "You can then test the role-based admin hierarchy system.\n";
?>
