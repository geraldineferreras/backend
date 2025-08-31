# Stream Post Editing Implementation - Teacher Role

## Overview

This document describes the implementation of stream post editing functionality for teachers in the SCMS system. The new feature allows teachers to edit both draft and published stream posts, including updating attachments (files and links).

## Problem Solved

Previously, teachers could only:
- ✅ Create new stream posts with attachments
- ✅ Update draft text content (via `PUT /api/teacher/classroom/{class_code}/stream/draft/{draft_id}`)
- ❌ **NOT edit published posts**
- ❌ **NOT update attachments on existing posts**

The new implementation provides a comprehensive solution for editing stream posts with full attachment management.

## New API Endpoint

### Edit Stream Post
```
PUT /api/teacher/classroom/{class_code}/stream/{stream_id}
```

**Authentication**: Requires teacher authentication with JWT token
**Content-Type**: Supports both `multipart/form-data` and `application/json`

## Features

### 1. Full Post Editing
- **Text Content**: Update title, content, draft status, scheduling, comments settings
- **Student Visibility**: Modify which students can see the post
- **Metadata**: Update all post properties while maintaining audit trail

### 2. Attachment Management
- **File Attachments**: Upload new files (up to 5 files)
- **Link Attachments**: Add external URLs (up to 3 links)
- **Replacement Strategy**: New attachments completely replace existing ones
- **Type Detection**: Automatic detection of single vs. multiple attachments

### 3. Flexible Input Methods
- **Multipart Form-Data**: For file uploads and form data
- **JSON Body**: For text-only updates without file changes

### 4. Security & Validation
- **Ownership Check**: Teachers can only edit their own posts
- **Class Verification**: Ensures post belongs to specified class
- **Input Validation**: Required field validation and file type checking

## Implementation Details

### Controller Method: `classroom_stream_put()`

Located in `application/controllers/api/TeacherController.php`

#### Key Features:
1. **Access Control**: Verifies teacher owns the post and has access to the class
2. **Dual Input Support**: Handles both multipart form-data and JSON requests
3. **File Processing**: Manages file uploads with proper error handling
4. **Attachment Replacement**: Deletes old attachments and inserts new ones
5. **Backward Compatibility**: Maintains compatibility with existing single-file posts
6. **Notification System**: Sends notifications when publishing updated posts

#### File Upload Handling:
```php
// Support up to 5 file attachments
$file_inputs = ['attachment_0', 'attachment_1', 'attachment_2', 'attachment_3', 'attachment_4'];

// Support up to 3 link attachments
$link_fields = ['link_0', 'link_1', 'link_2'];
```

#### Attachment Processing:
```php
if (!empty($all_attachments)) {
    // Delete existing attachments
    $this->StreamAttachment_model->delete_by_stream_id($stream_id);
    
    // Insert new attachments
    $this->StreamAttachment_model->insert_multiple($stream_id, $all_attachments);
    
    // Update main post for backward compatibility
    if (count($all_attachments) === 1) {
        $update_data['attachment_type'] = 'file';
        $update_data['attachment_url'] = $all_attachments[0]['attachment_url'];
    } else {
        $update_data['attachment_type'] = 'multiple';
        $update_data['attachment_url'] = null;
    }
}
```

### Route Configuration

Added to `application/config/routes.php`:
```php
$route['api/teacher/classroom/(:any)/stream/(:num)']['put'] = 'api/TeacherController/classroom_stream_put/$1/$2';
```

## Usage Examples

### 1. Edit Text Content Only (JSON)

```bash
curl -X PUT \
  "http://localhost/index.php/api/teacher/classroom/ABC123/stream/123" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Announcement Title",
    "content": "This is the updated content of the announcement.",
    "is_draft": 0,
    "allow_comments": 1
  }'
```

### 2. Edit with File Attachments (Multipart)

```bash
curl -X PUT \
  "http://localhost/index.php/api/teacher/classroom/ABC123/stream/123" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "title=Updated Title" \
  -F "content=Updated content with new files" \
  -F "is_draft=0" \
  -F "attachment_0=@/path/to/new_file.pdf" \
  -F "attachment_1=@/path/to/another_file.jpg"
```

### 3. Edit with Link Attachments

```bash
curl -X PUT \
  "http://localhost/index.php/api/teacher/classroom/ABC123/stream/123" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -F "title=Updated Title" \
  -F "content=Updated content with new links" \
  -F "link_0=https://example.com/document.pdf" \
  -F "link_1=https://youtube.com/watch?v=example"
```

## Database Changes

### No New Tables Required
The implementation uses existing tables:
- `classroom_stream`: Main post data
- `stream_attachments`: Attachment storage

### Data Flow
1. **Update Request**: New data received via PUT endpoint
2. **Existing Data**: Current post data loaded for comparison
3. **Attachment Replacement**: Old attachments deleted, new ones inserted
4. **Main Post Update**: Post metadata updated with new values
5. **Type Synchronization**: `attachment_type` and `attachment_url` synchronized

## Testing

### Test File: `test_stream_post_editing.html`

A comprehensive testing interface that provides:

1. **Create Test Posts**: Generate posts for editing
2. **Edit Existing Posts**: Update posts with new content and attachments
3. **View All Posts**: Browse existing posts in a class
4. **File Upload Testing**: Test multiple file attachments
5. **Link Attachment Testing**: Test external URL attachments

### Testing Workflow
1. Set JWT token for authentication
2. Create a test post with initial attachments
3. Use the returned post ID to test editing
4. Verify attachments are properly replaced
5. Check that the updated post displays correctly

## Benefits

### 1. Complete Post Management
- Teachers can now fully manage their stream posts
- No need to delete and recreate posts to update attachments
- Maintains post history and engagement metrics

### 2. Improved User Experience
- Seamless editing workflow
- Consistent attachment handling
- Real-time updates without page refresh

### 3. Better Content Management
- Easy content updates and corrections
- Flexible attachment management
- Support for both files and external links

### 4. System Consistency
- Uses existing attachment infrastructure
- Maintains backward compatibility
- Follows established API patterns

## Security Considerations

### 1. Access Control
- Teachers can only edit their own posts
- Class membership verification
- JWT token validation

### 2. File Security
- File type validation
- Secure file upload handling
- Unique filename generation

### 3. Data Integrity
- Transaction-based updates
- Proper error handling
- Rollback on failure

## Future Enhancements

### 1. Partial Attachment Updates
- Add/remove individual attachments without replacing all
- Attachment reordering capabilities

### 2. Version History
- Track post edit history
- Revert to previous versions

### 3. Bulk Operations
- Edit multiple posts simultaneously
- Batch attachment updates

### 4. Advanced Scheduling
- Edit scheduled posts before publication
- Modify publication timing

## Troubleshooting

### Common Issues

1. **"Stream post not found or access denied"**
   - Verify the post ID exists
   - Ensure you own the post
   - Check class code matches

2. **"Failed to update stream post"**
   - Check database connectivity
   - Verify file upload permissions
   - Review server error logs

3. **Attachments not updating**
   - Ensure files are properly selected
   - Check file size limits
   - Verify upload directory permissions

### Debug Steps

1. Check API response for specific error messages
2. Verify JWT token is valid and not expired
3. Confirm post ownership and class access
4. Review server logs for detailed error information
5. Test with simple text updates before adding attachments

## Conclusion

The stream post editing implementation provides teachers with comprehensive control over their classroom announcements. The solution maintains system integrity while offering flexible attachment management and maintaining backward compatibility with existing functionality.

This feature significantly improves the teacher experience by eliminating the need to recreate posts when updates are required, while providing a robust and secure editing platform for classroom content management.
