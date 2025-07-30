<?php
class Attendance_model extends CI_Model {
    /**
     * Fetch attendance logs with filters and joins for reporting/export.
     * Filters: date_from, date_to, subject_id, section_id, teacher_id, attendance_status, excuse_status, search
     */
    public function get_attendance_report($filters = []) {
        $this->db->select('
            attendance.*, 
            users.full_name as student_name, users.student_num as student_id, users.email as student_email,
            sections.section_name,
            subjects.subject_name, subjects.subject_code,
            teachers.full_name as teacher_name
        ')
        ->from('attendance')
        ->join('users', 'attendance.student_id = users.user_id', 'left')
        ->join('sections', 'attendance.section_id = sections.section_id', 'left')
        ->join('subjects', 'attendance.subject_id = subjects.id', 'left')
        ->join('users as teachers', 'attendance.teacher_id = teachers.user_id', 'left');

        if (!empty($filters['date_from'])) {
            $this->db->where('attendance.date >=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $this->db->where('attendance.date <=', $filters['date_to']);
        }
        if (!empty($filters['subject_id'])) {
            $this->db->where('attendance.subject_id', $filters['subject_id']);
        }
        if (!empty($filters['section_id'])) {
            $this->db->where('attendance.section_id', $filters['section_id']);
        }
        if (!empty($filters['teacher_id'])) {
            $this->db->where('attendance.teacher_id', $filters['teacher_id']);
        }
        if (!empty($filters['attendance_status'])) {
            $this->db->where('attendance.attendance_status', $filters['attendance_status']);
        }
        if (!empty($filters['excuse_status'])) {
            $this->db->where('attendance.excuse_status', $filters['excuse_status']);
        }
        if (!empty($filters['search'])) {
            $this->db->group_start();
            $this->db->like('users.full_name', $filters['search']);
            $this->db->or_like('users.student_num', $filters['search']);
            $this->db->group_end();
        }
        $this->db->order_by('attendance.date', 'DESC');
        return $this->db->get()->result_array();
    }
}
