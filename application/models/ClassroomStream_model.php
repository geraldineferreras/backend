<?php
class ClassroomStream_model extends CI_Model {
    // Insert a new stream post
    public function insert($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        if (isset($data['student_ids'])) {
            $data['visible_to_student_ids'] = json_encode($data['student_ids']);
            unset($data['student_ids']);
        }
        $this->db->insert('classroom_stream', $data);
        return $this->db->insert_id();
    }

    // Update a stream post by id
    public function update($id, $data) {
        if (isset($data['student_ids'])) {
            $data['visible_to_student_ids'] = json_encode($data['student_ids']);
            unset($data['student_ids']);
        }
        $this->db->where('id', $id);
        return $this->db->update('classroom_stream', $data);
    }

    // Get all posts for a classroom, with optional filters, and filter by student_id if provided
    public function get_by_class_code($class_code, $filters = [], $student_id = null) {
        $this->db->where('class_code', $class_code);
        if (isset($filters['is_draft'])) {
            $this->db->where('is_draft', $filters['is_draft']);
        }
        if (isset($filters['is_scheduled'])) {
            $this->db->where('is_scheduled', $filters['is_scheduled']);
        }
        // When requesting published-only for student UI: exclude drafts and only include
        // scheduled posts that are due (scheduled_at <= now)
        if (isset($filters['published_only']) && $filters['published_only']) {
            $this->db->where('is_draft', 0);
            $this->db->group_start();
            $this->db->where('is_scheduled', 0);
            $this->db->or_group_start();
            $this->db->where('is_scheduled', 1);
            $this->db->where('scheduled_at <=', date('Y-m-d H:i:s'));
            $this->db->group_end();
            $this->db->group_end();
        }
        if (isset($filters['scheduled_only']) && $filters['scheduled_only']) {
            $this->db->where('is_scheduled', 1);
            $this->db->where('scheduled_at >', date('Y-m-d H:i:s'));
        }
        $this->db->order_by('created_at', 'DESC');
        $posts = $this->db->get('classroom_stream')->result_array();
        if ($student_id) {
            // Filter posts: show if visible_to_student_ids is null/empty or contains student_id
            $posts = array_filter($posts, function($post) use ($student_id) {
                if (empty($post['visible_to_student_ids'])) return true;
                $ids = json_decode($post['visible_to_student_ids'], true);
                return is_array($ids) && in_array($student_id, $ids);
            });
            $posts = array_values($posts);
        }
        
        // Load StreamAttachment model for multiple attachments
        $this->load->model('StreamAttachment_model');
        
        // Process attachments for each post
        foreach ($posts as &$post) {
            // Handle multiple attachments
            if ($post['attachment_type'] === 'multiple') {
                $attachments = $this->StreamAttachment_model->get_by_stream_id($post['id']);
                $post['attachments'] = [];
                foreach ($attachments as $attachment) {
                    $post['attachments'][] = [
                        'attachment_id' => $attachment['attachment_id'],
                        'file_name' => $attachment['file_name'],
                        'original_name' => $attachment['original_name'],
                        'file_path' => $attachment['file_path'],
                        'file_size' => $attachment['file_size'],
                        'mime_type' => $attachment['mime_type'],
                        'attachment_type' => $attachment['attachment_type'],
                        'attachment_url' => $attachment['attachment_url'],
                        'serving_url' => get_file_url($attachment['file_path']),
                        'file_type' => get_file_type($attachment['file_path'])
                    ];
                }
                // Keep backward compatibility
                $post['attachment_serving_url'] = !empty($attachments) ? get_file_url($attachments[0]['file_path']) : null;
                $post['attachment_file_type'] = !empty($attachments) ? get_file_type($attachments[0]['file_path']) : null;
            } else {
                // Single attachment (backward compatibility)
                if (!empty($post['attachment_url'])) {
                    $post['attachment_serving_url'] = get_file_url($post['attachment_url']);
                    $post['attachment_file_type'] = get_file_type($post['attachment_url']);
                }
            }
        }
        
        return $posts;
    }

    // Get all stream posts for UI
    public function get_stream_for_classroom_ui($class_code) {
        $this->db->select('cs.id, u.full_name as user_name, u.profile_pic as user_avatar, cs.created_at, cs.is_pinned, cs.title, cs.content, cs.liked_by_user_ids, cs.attachment_url, cs.attachment_type');
        $this->db->from('classroom_stream cs');
        $this->db->join('users u', 'cs.user_id = u.user_id', 'left');
        $this->db->where('cs.class_code', $class_code);
        $this->db->where('cs.is_draft', 0); // Exclude drafts from stream
        // Only show posts that are not scheduled, or scheduled posts whose scheduled_at is now or in the past
        $this->db->group_start();
        $this->db->where('cs.is_scheduled', 0);
        $this->db->or_group_start();
        $this->db->where('cs.is_scheduled', 1);
        $this->db->where('cs.scheduled_at <=', date('Y-m-d H:i:s'));
        $this->db->group_end();
        $this->db->group_end();
        $this->db->order_by('cs.is_pinned', 'DESC');
        $this->db->order_by('cs.created_at', 'DESC');
        $posts = $this->db->get()->result_array();
        
        // Add comment count for each post
        foreach ($posts as &$post) {
            $comment_count = $this->db->where('stream_id', $post['id'])->count_all_results('classroom_stream_comments');
            $post['comment_count'] = $comment_count;
        }
        
        // Load StreamAttachment model for multiple attachments
        $this->load->model('StreamAttachment_model');
        
        foreach ($posts as &$post) {
            $likes = json_decode($post['liked_by_user_ids'], true) ?: [];
            $post['like_count'] = count($likes);
            unset($post['liked_by_user_ids']);
            
            // Process avatar URL
            if (empty($post['user_avatar']) || $post['user_avatar'] === '') {
                $post['user_avatar'] = null; // Set to null for users without profile pictures
            }
            // Keep the raw path for users with profile pictures (like profile_pic in user API)
            
            // Handle multiple attachments
            if ($post['attachment_type'] === 'multiple') {
                $attachments = $this->StreamAttachment_model->get_by_stream_id($post['id']);
                $post['attachments'] = [];
                foreach ($attachments as $attachment) {
                    $post['attachments'][] = [
                        'attachment_id' => $attachment['attachment_id'],
                        'file_name' => $attachment['file_name'],
                        'original_name' => $attachment['original_name'],
                        'file_path' => $attachment['file_path'],
                        'file_size' => $attachment['file_size'],
                        'mime_type' => $attachment['mime_type'],
                        'attachment_type' => $attachment['attachment_type'],
                        'attachment_url' => $attachment['attachment_url'],
                        'serving_url' => get_file_url($attachment['file_path']),
                        'file_type' => get_file_type($attachment['file_path'])
                    ];
                }
                // Keep backward compatibility
                $post['attachment_serving_url'] = !empty($attachments) ? get_file_url($attachments[0]['file_path']) : null;
                $post['attachment_file_type'] = !empty($attachments) ? get_file_type($attachments[0]['file_path']) : null;
            } else {
                // Single attachment (backward compatibility)
                if (!empty($post['attachment_url'])) {
                    $post['attachment_serving_url'] = get_file_url($post['attachment_url']);
                    $post['attachment_file_type'] = get_file_type($post['attachment_url']);
                }
            }
        }
        return $posts;
    }

    // Get all scheduled posts for UI
    public function get_scheduled_for_classroom_ui($class_code) {
        $this->db->select('cs.id, u.full_name as user_name, u.profile_pic as user_avatar, cs.created_at, cs.is_pinned, cs.title, cs.content, cs.liked_by_user_ids, cs.scheduled_at, cs.attachment_url, cs.attachment_type');
        $this->db->from('classroom_stream cs');
        $this->db->join('users u', 'cs.user_id = u.user_id', 'left');
        $this->db->where('cs.class_code', $class_code);
        $this->db->where('cs.is_scheduled', 1);
        $this->db->where('cs.scheduled_at >', date('Y-m-d H:i:s'));
        $this->db->order_by('cs.scheduled_at', 'ASC');
        $posts = $this->db->get()->result_array();
        
        // Load StreamAttachment model for multiple attachments
        $this->load->model('StreamAttachment_model');
        
        foreach ($posts as &$post) {
            $likes = json_decode($post['liked_by_user_ids'], true) ?: [];
            $post['like_count'] = count($likes);
            unset($post['liked_by_user_ids']);
            
            // Process avatar URL
            if (empty($post['user_avatar']) || $post['user_avatar'] === '') {
                $post['user_avatar'] = null; // Set to null for users without profile pictures
            }
            // Keep the raw path for users with profile pictures (like profile_pic in user API)
            
            // Handle multiple attachments
            if ($post['attachment_type'] === 'multiple') {
                $attachments = $this->StreamAttachment_model->get_by_stream_id($post['id']);
                $post['attachments'] = [];
                foreach ($attachments as $attachment) {
                    $post['attachments'][] = [
                        'attachment_id' => $attachment['attachment_id'],
                        'file_name' => $attachment['file_name'],
                        'original_name' => $attachment['original_name'],
                        'file_path' => $attachment['file_path'],
                        'file_size' => $attachment['file_size'],
                        'mime_type' => $attachment['mime_type'],
                        'attachment_type' => $attachment['attachment_type'],
                        'attachment_url' => $attachment['attachment_url'],
                        'serving_url' => get_file_url($attachment['file_path']),
                        'file_type' => get_file_type($attachment['file_path'])
                    ];
                }
                // Keep backward compatibility
                $post['attachment_serving_url'] = !empty($attachments) ? get_file_url($attachments[0]['file_path']) : null;
                $post['attachment_file_type'] = !empty($attachments) ? get_file_type($attachments[0]['file_path']) : null;
            } else {
                // Single attachment (backward compatibility)
                if (!empty($post['attachment_url'])) {
                    $post['attachment_serving_url'] = get_file_url($post['attachment_url']);
                    $post['attachment_file_type'] = get_file_type($post['attachment_url']);
                }
            }
        }
        return $posts;
    }

    // Get all drafts for UI
    public function get_drafts_for_classroom_ui($class_code) {
        $this->db->select('cs.id, u.full_name as user_name, u.profile_pic as user_avatar, cs.created_at, cs.is_pinned, cs.title, cs.content, cs.liked_by_user_ids, cs.attachment_url, cs.attachment_type');
        $this->db->from('classroom_stream cs');
        $this->db->join('users u', 'cs.user_id = u.user_id', 'left');
        $this->db->where('cs.class_code', $class_code);
        $this->db->where('cs.is_draft', 1);
        $this->db->order_by('cs.created_at', 'DESC');
        $posts = $this->db->get()->result_array();
        
        // Load StreamAttachment model for multiple attachments
        $this->load->model('StreamAttachment_model');
        
        foreach ($posts as &$post) {
            $likes = json_decode($post['liked_by_user_ids'], true) ?: [];
            $post['like_count'] = count($likes);
            unset($post['liked_by_user_ids']);
            
            // Process avatar URL
            if (empty($post['user_avatar']) || $post['user_avatar'] === '') {
                $post['user_avatar'] = null; // Set to null for users without profile pictures
            }
            // Keep the raw path for users with profile pictures (like profile_pic in user API)
            
            // Handle multiple attachments
            if ($post['attachment_type'] === 'multiple') {
                $attachments = $this->StreamAttachment_model->get_by_stream_id($post['id']);
                $post['attachments'] = [];
                foreach ($attachments as $attachment) {
                    $post['attachments'][] = [
                        'attachment_id' => $attachment['attachment_id'],
                        'file_name' => $attachment['file_name'],
                        'original_name' => $attachment['original_name'],
                        'file_path' => $attachment['file_path'],
                        'file_size' => $attachment['file_size'],
                        'mime_type' => $attachment['mime_type'],
                        'attachment_type' => $attachment['attachment_type'],
                        'attachment_url' => $attachment['attachment_url'],
                        'serving_url' => get_file_url($attachment['file_path']),
                        'file_type' => get_file_type($attachment['file_path'])
                    ];
                }
                // Keep backward compatibility
                $post['attachment_serving_url'] = !empty($attachments) ? get_file_url($attachments[0]['file_path']) : null;
                $post['attachment_file_type'] = !empty($attachments) ? get_file_type($attachments[0]['file_path']) : null;
            } else {
                // Single attachment (backward compatibility)
                if (!empty($post['attachment_url'])) {
                    $post['attachment_serving_url'] = get_file_url($post['attachment_url']);
                    $post['attachment_file_type'] = get_file_type($post['attachment_url']);
                }
            }
        }
        return $posts;
    }

    // Add a comment to a stream post
    public function add_comment($stream_id, $user_id, $comment) {
        $data = [
            'stream_id' => $stream_id,
            'user_id' => $user_id,
            'comment' => $comment,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('classroom_stream_comments', $data);
        return $this->db->insert_id();
    }

    // Update a comment
    public function update_comment($comment_id, $user_id, $comment) {
        $this->db->where('id', $comment_id);
        $this->db->where('user_id', $user_id);
        return $this->db->update('classroom_stream_comments', [
            'comment' => $comment,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }

    // Delete a comment
    public function delete_comment($comment_id, $user_id) {
        $this->db->where('id', $comment_id);
        $this->db->where('user_id', $user_id);
        return $this->db->delete('classroom_stream_comments');
    }

    // Get all comments for a stream post
    public function get_comments($stream_id) {
        $this->db->select('c.id, c.comment, c.created_at, u.user_id, u.full_name as user_name, u.profile_pic as user_avatar');
        $this->db->from('classroom_stream_comments c');
        $this->db->join('users u', 'c.user_id = u.user_id', 'left');
        $this->db->where('c.stream_id', $stream_id);
        $this->db->order_by('c.created_at', 'ASC');
        $comments = $this->db->get()->result_array();
        
        // Process avatar URLs for each comment
        foreach ($comments as &$comment) {
            if (empty($comment['user_avatar']) || $comment['user_avatar'] === '') {
                $comment['user_avatar'] = null; // Set to null for users without profile pictures
            }
            // Keep the raw path for users with profile pictures (like profile_pic in user API)
        }
        
        return $comments;
    }
    
    // Get comment count for a stream post
    public function get_comment_count($stream_id) {
        return $this->db->where('stream_id', $stream_id)->count_all_results('classroom_stream_comments');
    }

    // Get a single post by id
    public function get_by_id($id) {
        $post = $this->db->get_where('classroom_stream', ['id' => $id])->row_array();
        
        if ($post && $post['attachment_type'] === 'multiple') {
            // Load StreamAttachment model for multiple attachments
            $this->load->model('StreamAttachment_model');
            $attachments = $this->StreamAttachment_model->get_by_stream_id($id);
            $post['attachments'] = [];
            foreach ($attachments as $attachment) {
                $post['attachments'][] = [
                    'attachment_id' => $attachment['attachment_id'],
                    'file_name' => $attachment['file_name'],
                    'original_name' => $attachment['original_name'],
                    'file_path' => $attachment['file_path'],
                    'file_size' => $attachment['file_size'],
                    'mime_type' => $attachment['mime_type'],
                    'attachment_type' => $attachment['attachment_type'],
                    'attachment_url' => $attachment['attachment_url'],
                    'serving_url' => get_file_url($attachment['file_path']),
                    'file_type' => get_file_type($attachment['file_path'])
                ];
            }
        }
        
        return $post;
    }
} 