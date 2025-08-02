<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ExcuseLetter_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get excuse letter by ID
     */
    public function get_by_id($letter_id)
    {
        return $this->db->select('
            excuse_letters.*,
            subjects.subject_name,
            subjects.subject_code,
            sections.section_name,
            students.full_name as student_name,
            students.student_num,
            students.email as student_email,
            teachers.full_name as teacher_name
        ')
        ->from('excuse_letters')
        ->join('classes', 'excuse_letters.class_id = classes.class_id', 'left')
        ->join('subjects', 'classes.subject_id = subjects.id', 'left')
        ->join('sections', 'classes.section_id = sections.section_id', 'left')
        ->join('users as students', 'excuse_letters.student_id = students.user_id', 'left')
        ->join('users as teachers', 'classes.teacher_id = teachers.user_id', 'left')
        ->where('excuse_letters.letter_id', $letter_id)
        ->get()->row_array();
    }

    /**
     * Get student's excuse letters
     */
    public function get_student_letters($student_id, $filters = [])
    {
        $this->db->select('
            excuse_letters.*,
            subjects.subject_name,
            subjects.subject_code,
            sections.section_name,
            teachers.full_name as teacher_name
        ')
        ->from('excuse_letters')
        ->join('classes', 'excuse_letters.class_id = classes.class_id', 'left')
        ->join('subjects', 'classes.subject_id = subjects.id', 'left')
        ->join('sections', 'classes.section_id = sections.section_id', 'left')
        ->join('users as teachers', 'classes.teacher_id = teachers.user_id', 'left')
        ->where('excuse_letters.student_id', $student_id);

        // Apply filters
        if (isset($filters['class_id'])) {
            $this->db->where('excuse_letters.class_id', $filters['class_id']);
        }
        if (isset($filters['status'])) {
            $this->db->where('excuse_letters.status', $filters['status']);
        }
        if (isset($filters['date_from'])) {
            $this->db->where('excuse_letters.date_absent >=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $this->db->where('excuse_letters.date_absent <=', $filters['date_to']);
        }

        $this->db->order_by('excuse_letters.created_at', 'DESC');

        return $this->db->get()->result_array();
    }

    /**
     * Get teacher's excuse letters to review
     */
    public function get_teacher_letters($teacher_id, $filters = [])
    {
        $this->db->select('
            excuse_letters.*,
            subjects.subject_name,
            subjects.subject_code,
            sections.section_name,
            students.full_name as student_name,
            students.student_num,
            students.email as student_email
        ')
        ->from('excuse_letters')
        ->join('classes', 'excuse_letters.class_id = classes.class_id', 'left')
        ->join('subjects', 'classes.subject_id = subjects.id', 'left')
        ->join('sections', 'classes.section_id = sections.section_id', 'left')
        ->join('users as students', 'excuse_letters.student_id = students.user_id', 'left')
        ->where('excuse_letters.teacher_id', $teacher_id);

        // Apply filters
        if (isset($filters['class_id'])) {
            $this->db->where('excuse_letters.class_id', $filters['class_id']);
        }
        if (isset($filters['status'])) {
            $this->db->where('excuse_letters.status', $filters['status']);
        }
        if (isset($filters['student_id'])) {
            $this->db->where('excuse_letters.student_id', $filters['student_id']);
        }

        $this->db->order_by('excuse_letters.created_at', 'DESC');

        return $this->db->get()->result_array();
    }

    /**
     * Create new excuse letter
     */
    public function create($data)
    {
        $this->db->insert('excuse_letters', $data);
        return $this->db->insert_id();
    }

    /**
     * Update excuse letter
     */
    public function update($letter_id, $data)
    {
        $this->db->where('letter_id', $letter_id);
        return $this->db->update('excuse_letters', $data);
    }

    /**
     * Delete excuse letter
     */
    public function delete($letter_id)
    {
        $this->db->where('letter_id', $letter_id);
        return $this->db->delete('excuse_letters');
    }

    /**
     * Check if excuse letter exists for student, class, and date
     */
    public function exists($student_id, $class_id, $date_absent)
    {
        return $this->db->where('student_id', $student_id)
            ->where('class_id', $class_id)
            ->where('date_absent', $date_absent)
            ->get('excuse_letters')
            ->num_rows() > 0;
    }

    /**
     * Get excuse letter statistics
     */
    public function get_statistics($user_id, $role = 'student')
    {
        if ($role === 'student') {
            $this->db->where('student_id', $user_id);
        } else {
            $this->db->where('teacher_id', $user_id);
        }

        $this->db->select('status, COUNT(*) as count')
            ->from('excuse_letters')
            ->group_by('status');

        $result = $this->db->get()->result_array();

        $statistics = [
            'pending' => 0,
            'approved' => 0,
            'rejected' => 0,
            'total' => 0
        ];

        foreach ($result as $row) {
            $statistics[strtolower($row['status'])] = (int)$row['count'];
            $statistics['total'] += (int)$row['count'];
        }

        return $statistics;
    }
} 