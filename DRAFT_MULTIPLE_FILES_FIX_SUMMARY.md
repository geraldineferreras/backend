# Draft Multiple Files Fix Summary

## Problem Description

The draft functionality was not working correctly with multiple files because:

1. **When saving as draft**: The system only saved attachments to the `stream_attachments` table if there were more than 1 file (`count($all_attachments) > 1`)
2. **When loading drafts**: The system only loaded attachments from `stream_attachments` table if `attachment_type === 'multiple'`
3. **Single file drafts**: Single files were not being saved to the attachments table, so they couldn't be loaded when viewing drafts

This caused the following issues:
- Drafts with single files would not show the attached files when loaded
- After publishing drafts, the files would not be displayed
- Inconsistent behavior between single and multiple file handling

## Root Cause

The original logic was designed with the assumption that:
- Multiple files (>1) → save to `stream_attachments` table and set `attachment_type = 'multiple'`
- Single file (1) → save to main table fields (`attachment_url`, `attachment_type = 'file'`)

However, this created inconsistency in how attachments were stored and retrieved, especially for drafts.

## Solution Implemented

### 1. Updated TeacherController.php

**File**: `application/controllers/api/TeacherController.php`

**Changes Made**:
- Modified the attachment handling logic to save ALL attachments (single and multiple) to the `stream_attachments` table
- Added logic to update the main post table with attachment information for backward compatibility
- Ensured consistent storage regardless of file count

**Before**:
```php
// Handle multiple attachments if we have more than one
if (!empty($all_attachments) && count($all_attachments) > 1) {
    $this->StreamAttachment_model->insert_multiple($id, $all_attachments);
}
```

**After**:
```php
// Handle attachments - save ALL attachments to the attachments table for consistency
if (!empty($all_attachments)) {
    $this->StreamAttachment_model->insert_multiple($id, $all_attachments);
    
    // Update the main post with attachment info for backward compatibility
    if (count($all_attachments) === 1) {
        // Single file - set main table fields for backward compatibility
        $this->db->where('id', $id)->update('classroom_stream', [
            'attachment_type' => 'file',
            'attachment_url' => $all_attachments[0]['attachment_url']
        ]);
    } else {
        // Multiple files - set type to multiple
        $this->db->where('id', $id)->update('classroom_stream', [
            'attachment_type' => 'multiple',
            'attachment_url' => null
        ]);
    }
}
```

### 2. Updated ClassroomStream_model.php

**File**: `application/models/ClassroomStream_model.php`

**Methods Updated**:
- `get_stream_for_classroom_ui()` - Regular stream posts
- `get_drafts_for_classroom_ui()` - Draft posts  
- `get_by_id()` - Individual post retrieval

**Changes Made**:
- Unified attachment loading logic across all methods
- Always attempt to load from `stream_attachments` table first
- Fall back to main table fields if no attachments found
- Maintain backward compatibility while ensuring consistency

**Before**:
```php
// Handle multiple attachments
if ($post['attachment_type'] === 'multiple') {
    $attachments = $this->StreamAttachment_model->get_by_stream_id($post['id']);
    // ... process attachments
} else {
    // Single attachment (backward compatibility)
    if (!empty($post['attachment_url'])) {
        $post['attachment_serving_url'] = get_file_url($post['attachment_url']);
        $post['attachment_file_type'] = get_file_type($post['attachment_url']);
    }
}
```

**After**:
```php
// Handle attachments - load from stream_attachments table for consistency
if (!empty($post['attachment_type']) && $post['attachment_type'] !== 'none') {
    $attachments = $this->StreamAttachment_model->get_by_stream_id($post['id']);
    if (!empty($attachments)) {
        // ... process attachments
        // Keep backward compatibility
        $post['attachment_serving_url'] = get_file_url($attachments[0]['file_path']);
        $post['attachment_file_type'] = get_file_type($attachments[0]['file_path']);
        
        // For single files, also set the main attachment fields for consistency
        if (count($attachments) === 1) {
            $post['attachment_url'] = $attachments[0]['attachment_url'];
            $post['attachment_type'] = 'file';
        }
    } else {
        // Fallback to main table fields if no attachments found
        if (!empty($post['attachment_url'])) {
            $post['attachment_serving_url'] = get_file_url($post['attachment_url']);
            $post['attachment_file_type'] = get_file_type($post['attachment_url']);
        }
    }
}
```

## Benefits of the Fix

1. **Consistency**: All attachments (single and multiple) are now stored and retrieved using the same mechanism
2. **Reliability**: Drafts with single files now properly display attachments when loaded
3. **Backward Compatibility**: Existing functionality continues to work as expected
4. **Maintainability**: Unified logic makes the code easier to maintain and debug
5. **User Experience**: Users can now reliably save drafts with files and see them when loading

## Testing

A test file `test_draft_multiple_files.html` has been created to verify the fix works correctly. The test file allows users to:

1. Create drafts with single or multiple files
2. Save drafts and verify attachments are stored
3. Load drafts and verify attachments are displayed
4. Publish drafts and verify attachments remain visible

## API Endpoints Affected

- `POST /api/teacher/classroom/{class_code}/stream` - Creating posts/drafts
- `GET /api/teacher/classroom/{class_code}/stream` - Loading stream posts
- `GET /api/teacher/classroom/{class_code}/stream/drafts` - Loading draft posts
- `PUT /api/teacher/classroom/{class_code}/stream/draft/{draft_id}` - Updating/publishing drafts

## Database Impact

- **No schema changes required** - Uses existing `stream_attachments` table
- **Improved data consistency** - All attachments now stored in dedicated table
- **Better query performance** - Consistent attachment loading pattern

## Future Considerations

1. **Migration**: Consider migrating existing single-file posts to use the attachments table for consistency
2. **Cleanup**: Implement cleanup for orphaned files in the main table fields
3. **Validation**: Add validation to ensure attachment data consistency between main table and attachments table

## Conclusion

This fix resolves the draft multiple files issue by ensuring consistent attachment handling across the entire system. All attachments are now stored in the dedicated `stream_attachments` table while maintaining backward compatibility with existing functionality.
