<?php
class StreamAttachment_model extends CI_Model {
    
    // Insert a new stream attachment
    public function insert($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('stream_attachments', $data);
        return $this->db->insert_id();
    }
    
    // Insert multiple attachments for a stream
    public function insert_multiple($stream_id, $attachments) {
        $data = [];
        foreach ($attachments as $attachment) {
            $data[] = [
                'stream_id' => $stream_id,
                'file_name' => $attachment['file_name'],
                'original_name' => $attachment['original_name'],
                'file_path' => $attachment['file_path'],
                'file_size' => $attachment['file_size'],
                'mime_type' => $attachment['mime_type'],
                'attachment_type' => $attachment['attachment_type'] ?? 'file',
                'attachment_url' => $attachment['attachment_url'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        
        if (!empty($data)) {
            return $this->db->insert_batch('stream_attachments', $data);
        }
        return false;
    }
    
    // Get all attachments for a stream
    public function get_by_stream_id($stream_id) {
        $this->db->where('stream_id', $stream_id);
        $this->db->order_by('created_at', 'ASC');
        return $this->db->get('stream_attachments')->result_array();
    }
    
    // Get a single attachment by ID
    public function get_by_id($attachment_id) {
        return $this->db->get_where('stream_attachments', ['attachment_id' => $attachment_id])->row_array();
    }
    
    // Update an attachment
    public function update($attachment_id, $data) {
        $this->db->where('attachment_id', $attachment_id);
        return $this->db->update('stream_attachments', $data);
    }
    
    // Delete an attachment
    public function delete($attachment_id) {
        $this->db->where('attachment_id', $attachment_id);
        return $this->db->delete('stream_attachments');
    }
    
    // Delete all attachments for a stream
    public function delete_by_stream_id($stream_id) {
        $this->db->where('stream_id', $stream_id);
        return $this->db->delete('stream_attachments');
    }
    
    // Count attachments for a stream
    public function count_by_stream_id($stream_id) {
        return $this->db->where('stream_id', $stream_id)->count_all_results('stream_attachments');
    }
}
