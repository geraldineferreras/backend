<?php
class Student_model extends CI_Model {
    public function get_by_email($email) {
        return $this->db->get_where('students', ['email' => $email])->row_array();
    }

    public function insert($data) {
        return $this->db->insert('students', $data);
    }

    public function get_all() {
        return $this->db->get('students')->result_array();
    }

    public function get_by_id($student_id) {
        return $this->db->get_where('students', ['student_id' => $student_id])->row_array();
    }

    public function update($student_id, $data) {
        $this->db->where('student_id', $student_id);
        return $this->db->update('students', $data);
    }

    public function delete($student_id) {
        $this->db->where('student_id', $student_id);
        return $this->db->delete('students');
    }
}
