<?php
class Subject_model extends CI_Model {
    private $subjects_field_cache = [];
    
    public function get_all() {
        // Fetch all subjects without ordering in SQL to avoid column name issues
        // We'll sort in PHP to handle different column name variations
        $query = $this->db->get('subjects');
        
        if (!$query) {
            return [];
        }
        
        $subjects = $query->result_array();
        $subjects = array_map([$this, 'normalize_subject_dates'], $subjects);
        
        // Sort by created_at or date_created if available, otherwise by id (descending)
        usort($subjects, function($a, $b) {
            // Try created_at first
            if (!empty($a['created_at']) && !empty($b['created_at'])) {
                $time_a = strtotime($a['created_at']);
                $time_b = strtotime($b['created_at']);
                if ($time_a !== false && $time_b !== false) {
                    return $time_b - $time_a; // Descending order
                }
            }
            // Fallback to date_created
            if (!empty($a['date_created']) && !empty($b['date_created'])) {
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
        $this->apply_timestamp_defaults($data, true);
        $this->db->insert('subjects', $data);
        return $this->db->insert_id();
    }
    public function update($id, $data) {
        $this->apply_timestamp_defaults($data, false);
        $this->db->where('id', $id);
        return $this->db->update('subjects', $data);
    }
    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete('subjects');
    }
    private function normalize_subject_dates($subject) {
        $raw = $subject['date_created']
            ?? $subject['created_at']
            ?? $subject['createdAt']
            ?? $subject['createdDate']
            ?? null;
        
        $timestamp = $raw ? strtotime($raw) : false;
        if ($timestamp !== false) {
            $normalized = date('Y-m-d H:i:s', $timestamp);
            $subject['date_created'] = $normalized;
            if (empty($subject['created_at'])) {
                $subject['created_at'] = $normalized;
            }
        } else {
            $subject['date_created'] = null;
        }
        
        return $subject;
    }
    
    private function apply_timestamp_defaults(&$data, $is_insert = false) {
        $now = date('Y-m-d H:i:s');
        if ($is_insert) {
            if ($this->subject_field_exists('created_at') && empty($data['created_at'])) {
                $data['created_at'] = $now;
            }
            if ($this->subject_field_exists('date_created') && empty($data['date_created'])) {
                $data['date_created'] = $now;
            }
        }
        if ($this->subject_field_exists('updated_at')) {
            $data['updated_at'] = $now;
        }
    }
    
    private function subject_field_exists($field_name) {
        if (!array_key_exists($field_name, $this->subjects_field_cache)) {
            $this->subjects_field_cache[$field_name] = $this->db->field_exists($field_name, 'subjects');
        }
        return $this->subjects_field_cache[$field_name];
    }
}
