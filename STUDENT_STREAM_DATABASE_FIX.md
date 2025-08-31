# Student Stream Posting Database Fix

## Problem Identified

When students post stream content with files, the files are not being saved to the database. The issue is likely that the `stream_attachments` table doesn't exist in your database.

## Root Cause Analysis

The student stream posting functionality requires:
1. **`stream_attachments` table** - Stores file attachment metadata
2. **`StreamAttachment_model`** - Handles database operations
3. **Upload directory** - Stores actual files
4. **Proper permissions** - Database and file system access

## Solution Steps

### Step 1: Create the Required Database Table

**Run this script first:**
```bash
# Navigate to your project directory
cd /path/to/your/project

# Run the table creation script
php create_stream_attachments_table.php
```

**Or manually execute this SQL:**
```sql
CREATE TABLE IF NOT EXISTS `stream_attachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` text NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `attachment_type` enum('file','link','youtube','google_drive') DEFAULT 'file',
  `attachment_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attachment_id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_attachment_type` (`attachment_type`),
  CONSTRAINT `fk_stream_attachments_stream` FOREIGN KEY (`stream_id`) REFERENCES `classroom_stream` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add performance index
CREATE INDEX `idx_stream_attachments_composite` ON `stream_attachments` (`stream_id`, `attachment_type`);
```

### Step 2: Verify Database Setup

**Run the database connection test:**
```bash
php test_student_database_connection.php
```

**Expected output:**
```
✅ Database connection successful
✅ stream_attachments table exists
✅ StreamAttachment_model loaded successfully
✅ Upload directory exists: uploads/announcement/
```

### Step 3: Test File Upload Functionality

**Use the test interface:**
1. Open `test_student_stream_file_upload.html` in your browser
2. Set your student JWT token
3. Go to "Multipart with Files" tab
4. Fill in the form and upload files
5. Click "Create Post with Files"

**Expected response:**
```json
{
  "status": true,
  "message": "Post created successfully",
  "data": {
    "id": 123,
    "title": "My Post",
    "content": "Post content",
    "attachment_type": "multiple",
    "attachments_count": 2,
    "created_at": "2024-01-15 10:30:00"
  }
}
```

### Step 4: Verify Database Records

**Check the database:**
```sql
-- Check stream posts
SELECT id, title, attachment_type, created_at 
FROM classroom_stream 
WHERE user_id IN (SELECT user_id FROM users WHERE role = 'student')
ORDER BY created_at DESC 
LIMIT 5;

-- Check attachments
SELECT sa.attachment_id, sa.stream_id, sa.file_name, sa.original_name, sa.file_path
FROM stream_attachments sa
JOIN classroom_stream cs ON sa.stream_id = cs.id
WHERE cs.user_id IN (SELECT user_id FROM users WHERE role = 'student')
ORDER BY sa.created_at DESC
LIMIT 10;
```

## Debugging Tools Created

### 1. **`create_stream_attachments_table.php`**
- Creates the required database table
- Verifies table structure
- Tests model loading
- Provides detailed feedback

### 2. **`test_student_database_connection.php`**
- Tests database connectivity
- Verifies table existence
- Tests model functionality
- Shows current database state

### 3. **`debug_student_stream_post.php`**
- Simulates file upload process
- Tests database insert operations
- Identifies specific issues
- Provides troubleshooting recommendations

### 4. **Enhanced Logging**
- Added detailed logging to `StudentController.php`
- Tracks attachment processing
- Logs database errors
- Helps identify issues

## Common Issues and Solutions

### Issue 1: "Table doesn't exist"
**Solution:** Run `create_stream_attachments_table.php`

### Issue 2: "Permission denied"
**Solution:** Check database user permissions and file system permissions

### Issue 3: "Model not found"
**Solution:** Verify `StreamAttachment_model.php` exists in `application/models/`

### Issue 4: "Upload directory not writable"
**Solution:** Set proper permissions on `uploads/announcement/` directory

### Issue 5: "Foreign key constraint fails"
**Solution:** Ensure `classroom_stream` table exists and has proper structure

## Testing Checklist

- [ ] **Database table exists**: `stream_attachments` table created
- [ ] **Model loads**: `StreamAttachment_model` loads without errors
- [ ] **Upload directory**: `uploads/announcement/` exists and writable
- [ ] **File uploads**: Files are processed and stored
- [ ] **Database inserts**: Attachments saved to database
- [ ] **API responses**: Correct response format with attachment info
- [ ] **File access**: Uploaded files are accessible via URL

## Verification Commands

```bash
# 1. Create table
php create_stream_attachments_table.php

# 2. Test database connection
php test_student_database_connection.php

# 3. Test file upload (browser)
# Open: test_student_stream_file_upload.html

# 4. Check logs
tail -f application/logs/log-*.php

# 5. Verify database records
mysql -u username -p database_name -e "SELECT COUNT(*) FROM stream_attachments;"
```

## Expected Database Schema

### `stream_attachments` Table
```sql
+----------------+------------------+------+-----+-------------------+----------------+
| Field          | Type             | Null | Key | Default           | Extra          |
+----------------+------------------+------+-----+-------------------+----------------+
| attachment_id  | int(11)          | NO   | PRI | NULL              | auto_increment |
| stream_id      | int(11)          | NO   | MUL | NULL              |                |
| file_name      | varchar(255)     | NO   |     | NULL              |                |
| original_name  | varchar(255)     | NO   |     | NULL              |                |
| file_path      | text             | NO   |     | NULL              |                |
| file_size      | int(11)          | YES  |     | NULL              |                |
| mime_type      | varchar(100)     | YES  |     | NULL              |                |
| attachment_type| enum(...)        | YES  |     | file              |                |
| attachment_url | text             | YES  |     | NULL              |                |
| created_at     | timestamp        | NO   |     | CURRENT_TIMESTAMP |                |
+----------------+------------------+------+-----+-------------------+----------------+
```

## Success Indicators

✅ **Table created successfully**
✅ **Model loads without errors**
✅ **File uploads work**
✅ **Database records created**
✅ **API returns correct response**
✅ **Files accessible via URL**

## Next Steps After Fix

1. **Test with real student accounts**
2. **Verify file downloads work**
3. **Test multiple file uploads**
4. **Check file size limits**
5. **Test different file types**
6. **Monitor for any errors**

## Support Files

- `create_stream_attachments_table.php` - Creates required database table
- `test_student_database_connection.php` - Tests database setup
- `debug_student_stream_post.php` - Debug file upload process
- `test_student_stream_file_upload.html` - Test interface for file uploads
- `STUDENT_STREAM_FILE_UPLOAD_FIX.md` - Original file upload fix documentation

## Conclusion

The main issue is likely the missing `stream_attachments` table. Run the provided scripts to:

1. **Create the table** using `create_stream_attachments_table.php`
2. **Verify setup** using `test_student_database_connection.php`
3. **Test functionality** using `test_student_stream_file_upload.html`

Once the table is created, student stream posting with files should work correctly and save to the database as expected.
