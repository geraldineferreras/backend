<?php
class Classroom_model extends CI_Model {
    public function get_all() {
        return $this->db->select('classrooms.*, users.full_name as teacher_name')
            ->from('classrooms')
            ->join('users', 'classrooms.teacher_id = users.user_id', 'left')
            ->order_by('classrooms.created_at', 'DESC')
            ->get()->result_array();
    }

    public function get_by_id($id) {
        return $this->db->select('classrooms.*, users.full_name as teacher_name')
            ->from('classrooms')
            ->join('users', 'classrooms.teacher_id = users.user_id', 'left')
            ->where('classrooms.id', $id)
            ->get()->row_array();
    }

    public function get_by_code($class_code) {
        return $this->db->select('classrooms.*, users.full_name as teacher_name')
            ->from('classrooms')
            ->join('users', 'classrooms.teacher_id = users.user_id', 'left')
            ->where('classrooms.class_code', $class_code)
            ->get()->row_array();
    }

    public function insert($data) {
        // Generate unique class code if not provided
        if (empty($data['class_code'])) {
            $data['class_code'] = $this->generate_unique_code();
        }
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('classrooms', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('id', $id);
        return $this->db->update('classrooms', $data);
    }

    public function delete($id) {
        $this->db->where('id', $id);
        return $this->db->delete('classrooms');
    }

    private function generate_unique_code($length = 6) {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, $length));
            $exists = $this->db->get_where('classrooms', ['class_code' => $code])->row_array();
        } while ($exists);
        return $code;
    }
} 