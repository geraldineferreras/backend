<?php
class Subject_model extends CI_Model {
    public function get_all() {
        // Fetch all subjects without ordering in SQL to avoid column name issues
        // We'll sort in PHP to handle different column name variations
        $query = $this->db->get('subjects');
        
        if (!$query) {
            return [];
        }
        
        $subjects = $query->result_array();
        
        // Sort by created_at or date_created if available, otherwise by id (descending)
        usort($subjects, function($a, $b) {
            // Try created_at first
            if (isset($a['created_at']) && isset($b['created_at'])) {
                $time_a = strtotime($a['created_at']);
                $time_b = strtotime($b['created_at']);
                if ($time_a !== false && $time_b !== false) {
                    return $time_b - $time_a; // Descending order
                }
            }
            // Fallback to date_created
            if (isset($a['date_created']) && isset($b['date_created'])) {
                $time_a = strtotime($a['date_created']);
                $time_b = strtotime($b['date_created']);
                if ($time_a !== false && $time_b !== false) {
                    return $time_b - $time_a; // Descending order
                }
            }
            // Final fallback: sort by id descending
            return (int)$b['id'] - (int)$a['id'];
        });
        
        return $subjects;
    }
    public function get_by_id($id) {
        return $this->db->get_where('subjects', ['id' => $id])->row_array();
    }
    public function insert($data) {
        // Don't manually set created_at - let database DEFAULT CURRENT_TIMESTAMP handle it
        // This avoids column name mismatch issues
        $this->db->insert('subjects', $data);
        return $this->db->insert_id();
    }
    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('subjects', $data);
    }
    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete('subjects');
    }
}
