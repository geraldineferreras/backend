<?php
class Teacher_model extends CI_Model {
    public function get_by_email($email) {
        return $this->db->get_where('teachers', ['email' => $email])->row_array();
    }

    public function insert($data) {
        return $this->db->insert('teachers', $data);
    }

    public function get_all() {
        return $this->db->get('teachers')->result_array();
    }

    public function get_by_id($teacher_id) {
        return $this->db->get_where('teachers', ['teacher_id' => $teacher_id])->row_array();
    }

    public function update($teacher_id, $data) {
        $this->db->where('teacher_id', $teacher_id);
        return $this->db->update('teachers', $data);
    }

    public function delete($teacher_id) {
        $this->db->where('teacher_id', $teacher_id);
        return $this->db->delete('teachers');
    }
}
