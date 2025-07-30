# Upload Functionality Testing Guide

## Overview
This guide explains how to test the profile picture and cover photo upload functionality that has been implemented in your SCMS application.

## What's Been Implemented

### 1. Upload Endpoints
- **Profile Upload**: `POST /api/upload/profile`
- **Cover Upload**: `POST /api/upload/cover`

### 2. Image Serving
- **Image Display**: `GET /image/{type}/{filename}`
  - Example: `http://localhost/scms_new/image/profile/abc123.jpg`
  - Example: `http://localhost/scms_new/image/cover/def456.png`

### 3. Database Integration
- Profile and cover photo paths are now saved to the database during user registration and updates
- The `Auth` controller has been updated to handle `profile_pic` and `cover_pic` fields

### 4. Directory Structure
```
uploads/
├── profile/     # Profile pictures stored here
└── cover/       # Cover photos stored here
```

## How to Test

### Option 1: Using the Test HTML Page (Recommended)

1. **Start your XAMPP server** (Apache and MySQL)
2. **Open the test page**: `http://localhost/scms_new/upload_test.html`
3. **Upload images**:
   - Select a profile image
   - Select a cover image
   - Click "Upload Images"
4. **Register a test user**:
   - Fill in the registration form
   - Click "Register User"
   - The uploaded image paths will be automatically included

### Option 2: Manual API Testing

#### Step 1: Upload Profile Image
```bash
curl -X POST http://localhost/scms_new/api/upload/profile \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "image=@/path/to/your/profile.jpg"
```

Expected Response:
```json
{
  "status": true,
  "message": "Profile image uploaded successfully",
  "data": {
    "file_path": "uploads/profile/abc123.jpg",
    "file_name": "abc123.jpg",
    "file_size": 12345,
    "image_width": 800,
    "image_height": 600
  }
}
```

#### Step 2: Upload Cover Image
```bash
curl -X POST http://localhost/scms_new/api/upload/cover \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "image=@/path/to/your/cover.jpg"
```

#### Step 3: Register User with Image Paths
```bash
curl -X POST http://localhost/scms_new/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "full_name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "role": "student",
    "profile_pic": "uploads/profile/abc123.jpg",
    "cover_pic": "uploads/cover/def456.jpg"
  }'
```

## File Validation

The upload system validates:
- **File types**: gif, jpg, jpeg, png, webp
- **File size**: Maximum 5MB
- **Image dimensions**: Maximum 2048x2048 pixels
- **Unique filenames**: Generated automatically to prevent conflicts

## Database Schema Requirements

Make sure your `users` table has these columns:
```sql
ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN cover_pic VARCHAR(255) NULL;
```

## Troubleshooting

### Common Issues

1. **404 Error on upload endpoints**
   - Check if XAMPP Apache is running
   - Verify routes are properly configured

2. **Upload fails with "No image file uploaded"**
   - Make sure you're sending the file with the field name `image`
   - Check file size and type restrictions

3. **Images not saving to database**
   - Verify the `profile_pic` and `cover_pic` columns exist in your database
   - Check the Auth controller logs for debugging information

4. **Images not displaying**
   - Verify the image files exist in the correct directories
   - Check the image serving URL format

### Debug Information

The system includes logging for debugging:
- Check `application/logs/` for detailed error logs
- The Auth controller logs incoming data and database operations

## API Endpoints Summary

| Endpoint | Method | Purpose | Authentication |
|----------|--------|---------|----------------|
| `/api/upload/profile` | POST | Upload profile image | Required |
| `/api/upload/cover` | POST | Upload cover image | Required |
| `/image/{type}/{filename}` | GET | Serve uploaded images | None |
| `/api/register` | POST | Register user with images | None |
| `/api/user` | PUT | Update user with images | Required |

## Success Indicators

✅ **Upload directories exist and are writable**
✅ **Auth controller handles profile_pic and cover_pic**
✅ **Upload controller has profile and cover methods**
✅ **Image controller exists with serve method**
✅ **Routes are properly configured**
✅ **Test HTML file is available**

Your upload functionality is ready to test! 🎉 