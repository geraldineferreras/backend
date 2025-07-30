<?php
class Class_model extends CI_Model {
    public function get_all() {
        return $this->db->select('classes.*, subjects.subject_code, subjects.subject_name, users.full_name as teacher_name, sections.section_name')
            ->from('classes')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('users', 'classes.teacher_id = users.user_id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->order_by('classes.date_created', 'DESC')
            ->get()->result_array();
    }

    public function get_by_id($id) {
        return $this->db->select('classes.*, subjects.subject_code, subjects.subject_name, users.full_name as teacher_name, sections.section_name')
            ->from('classes')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('users', 'classes.teacher_id = users.user_id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left')
            ->where('classes.class_id', $id)
            ->get()->row_array();
    }

    public function insert($data) {
        $data['date_created'] = date('Y-m-d H:i:s');
        $this->db->insert('classes', $data);
        return $this->db->insert_id();
    }

    public function update($id, $data) {
        $this->db->where('class_id', $id);
        return $this->db->update('classes', $data);
    }

    public function delete($id) {
        $this->db->where('class_id', $id);
        return $this->db->delete('classes');
    }

    // Filtered get (for search/filter in frontend)
    public function get_filtered($filters = []) {
        $this->db->select('classes.*, subjects.subject_code, subjects.subject_name, users.full_name as teacher_name, sections.section_name')
            ->from('classes')
            ->join('subjects', 'classes.subject_id = subjects.id', 'left')
            ->join('users', 'classes.teacher_id = users.user_id', 'left')
            ->join('sections', 'classes.section_id = sections.section_id', 'left');
        if (!empty($filters['subject_id'])) {
            $this->db->where('classes.subject_id', $filters['subject_id']);
        }
        if (!empty($filters['teacher_id'])) {
            $this->db->where('classes.teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['section_id'])) {
            $this->db->where('classes.section_id', $filters['section_id']);
        }
        if (!empty($filters['semester'])) {
            $this->db->where('classes.semester', $filters['semester']);
        }
        if (!empty($filters['school_year'])) {
            $this->db->where('classes.school_year', $filters['school_year']);
        }
        return $this->db->order_by('classes.date_created', 'DESC')->get()->result_array();
    }
} 