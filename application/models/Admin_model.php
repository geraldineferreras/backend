<?php
class Admin_model extends CI_Model {
    public function get_by_email($email) {
        $result = $this->db->get_where('admins', ['email' => $email])->row_array();
        log_message('debug', 'Admin query for email ' . $email . ': ' . $this->db->last_query());
        log_message('debug', 'Admin result: ' . ($result ? 'Found' : 'Not found'));
        return $result;
    }   

    public function insert($data) {
        return $this->db->insert('admins', $data);
    }

    public function get_all() {
        return $this->db->get('admins')->result_array();
    }

    public function get_by_id($admin_id) {
        return $this->db->get_where('admins', ['admin_id' => $admin_id])->row_array();
    }

    public function update($admin_id, $data) {
        $this->db->where('admin_id', $admin_id);
        return $this->db->update('admins', $data);
    }

    public function delete($admin_id) {
        $this->db->where('admin_id', $admin_id);
        return $this->db->delete('admins');
    }
}
