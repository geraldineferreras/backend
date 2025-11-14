<?php
class Subject_model extends CI_Model {
    public function get_all() {
        // Use created_at to match database schema (has DEFAULT CURRENT_TIMESTAMP)
        return $this->db->order_by('created_at', 'DESC')->get('subjects')->result_array();
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
