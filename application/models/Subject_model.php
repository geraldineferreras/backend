<?php
class Subject_model extends CI_Model {
    public function get_all() {
        return $this->db->order_by('date_created', 'DESC')->get('subjects')->result_array();
    }
    public function get_by_id($id) {
        return $this->db->get_where('subjects', ['id' => $id])->row_array();
    }
    public function insert($data) {
        $data['date_created'] = date('Y-m-d H:i:s');
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
