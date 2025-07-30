# Troubleshooting "Failed to Fetch" Error

## Problem
You're seeing "Backend connection error: Failed to fetch" on the upload_test.html page.

## Quick Fix Steps

### Step 1: Check XAMPP Server
1. **Start XAMPP Control Panel**
2. **Start Apache** (click "Start" next to Apache)
3. **Start MySQL** (click "Start" next to MySQL)
4. **Verify both are running** (should show green status)

### Step 2: Test Backend Directly
Open your browser and go to:
```
http://localhost/scms_new/index.php/api/test/upload
```

**Expected Result:** You should see a JSON response like:
```json
{
  "status": true,
  "message": "Upload test endpoint is working",
  "endpoints": {...}
}
```

**If you get a 404 error:**
- Make sure XAMPP Apache is running
- Check if the URL is correct
- Try: `http://localhost/scms_new/` (should show your main page)

### Step 3: Check URL Configuration
The test page is configured to use: `http://localhost/scms_new`

If your setup is different, edit `upload_test.html` and change this line:
```javascript
const baseUrl = 'http://localhost/scms_new';
```

**Common alternatives:**
- `http://localhost:8080/scms_new` (if using different port)
- `http://127.0.0.1/scms_new` (alternative localhost)
- `http://localhost/scms_new/index.php` (if URL rewriting not working)

### Step 4: Test the Updated Page
1. **Open:** `http://localhost/scms_new/upload_test.html`
2. **Click:** "Test Backend Connection" button
3. **Check:** Status should show "Connected ✓"

## Common Issues & Solutions

### Issue 1: XAMPP Not Running
**Symptoms:** 404 errors, connection refused
**Solution:** Start XAMPP Apache and MySQL services

### Issue 2: Wrong Port
**Symptoms:** Connection refused, timeout
**Solution:** Check if Apache is running on port 80 or 8080
- Default: `http://localhost/scms_new`
- Alternative: `http://localhost:8080/scms_new`

### Issue 3: URL Rewriting Not Working
**Symptoms:** 404 errors on API endpoints
**Solution:** Use `index.php` in URLs:
- Change: `http://localhost/scms_new/api/test/upload`
- To: `http://localhost/scms_new/index.php/api/test/upload`

### Issue 4: CORS Issues
**Symptoms:** CORS errors in browser console
**Solution:** The backend already has CORS headers configured

### Issue 5: File Permissions
**Symptoms:** Upload directories not writable
**Solution:** Check that `uploads/profile/` and `uploads/cover/` directories exist and are writable

## Testing Checklist

✅ **XAMPP Apache is running**
✅ **XAMPP MySQL is running**
✅ **Direct API access works:** `http://localhost/scms_new/index.php/api/test/upload`
✅ **Test page loads:** `http://localhost/scms_new/upload_test.html`
✅ **Backend connection test passes**
✅ **Upload directories exist and are writable**

## Manual Testing

If the test page still doesn't work, you can test manually:

### Test 1: Upload Profile Image
```bash
curl -X POST http://localhost/scms_new/index.php/api/upload/profile \
  -F "image=@/path/to/test/image.jpg"
```

### Test 2: Upload Cover Image
```bash
curl -X POST http://localhost/scms_new/index.php/api/upload/cover \
  -F "image=@/path/to/test/image.jpg"
```

### Test 3: Register User
```bash
curl -X POST http://localhost/scms_new/index.php/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "role": "student",
    "profile_pic": "uploads/profile/test.jpg",
    "cover_pic": "uploads/cover/test.jpg"
  }'
```

## Still Having Issues?

1. **Check browser console** (F12 → Console) for detailed error messages
2. **Check XAMPP error logs** in `C:\xampp\apache\logs\error.log`
3. **Check CodeIgniter logs** in `application/logs/`
4. **Try a different browser** to rule out browser-specific issues

## Success Indicators

When everything is working correctly:
- ✅ Backend connection test shows "Connected ✓"
- ✅ Upload buttons work without errors
- ✅ Files are saved in `uploads/profile/` and `uploads/cover/`
- ✅ Registration includes profile and cover photo paths
- ✅ Images can be accessed via `/image/{type}/{filename}`

Your upload functionality should work perfectly once the backend connection is established! 🎉 