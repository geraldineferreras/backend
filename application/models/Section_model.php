<?php
class Section_model extends CI_Model {
    private $supportsArchive = false;
    private $hasAcademicYearId = false;

    public function __construct() {
        parent::__construct();
        $this->supportsArchive = $this->db->field_exists('is_archived', 'sections');
        $this->hasAcademicYearId = $this->db->field_exists('academic_year_id', 'sections');
    }
    public function get_all($options = []) {
        $include_archived = $this->include_archived_flag($options);

        $this->db->select('sections.*, users.full_name as adviser_name, users.email as adviser_email, users.profile_pic as adviser_profile_pic,
                                 (SELECT COUNT(*) FROM users WHERE users.section_id = sections.section_id AND users.role = "student") as enrolled_count')
            ->from('sections')
            ->join('users', 'sections.adviser_id = users.user_id', 'left');

        if ($this->supportsArchive && !$include_archived) {
            $this->db->where('sections.is_archived', 0);
        }

        if (!empty($options['program'])) {
            $this->db->where('sections.program', strtoupper(trim($options['program'])));
        }

        if (!empty($options['year_level'])) {
            $this->db->where('sections.year_level', trim($options['year_level']));
        }

        if (!empty($options['semester'])) {
            $this->db->where('sections.semester', trim($options['semester']));
        }

        if (!empty($options['academic_year_id']) && $this->hasAcademicYearId) {
            $this->db->where('sections.academic_year_id', (int)$options['academic_year_id']);
        } elseif (!empty($options['academic_year'])) {
            $this->db->where('sections.academic_year', trim($options['academic_year']));
        }

        return $this->db->order_by('sections.academic_year', 'DESC')
            ->order_by('sections.semester', 'ASC')
            ->order_by('sections.year_level', 'ASC')
            ->order_by('sections.section_name', 'ASC')
            ->get()->result_array();
    }

    public function get_by_year_level($year_level = null, $options = []) {
        $include_archived = $this->include_archived_flag($options);
        $this->db->select('sections.*, users.full_name as adviser_name, users.email as adviser_email, users.profile_pic as adviser_profile_pic')
            ->from('sections')
            ->join('users', 'sections.adviser_id = users.user_id', 'left');

        if ($this->supportsArchive && !$include_archived) {
            $this->db->where('sections.is_archived', 0);
        }
        
        if ($year_level && $year_level !== 'all') {
            // Handle different possible formats
            $year_level_clean = trim($year_level);
            $this->db->where('sections.year_level', $year_level_clean);
        }
        
        $this->db->order_by('sections.academic_year', 'DESC')
            ->order_by('sections.semester', 'ASC')
            ->order_by('sections.section_name', 'ASC');
        
        return $this->db->get()->result_array();
    }

    public function get_by_semester_and_year($semester = null, $academic_year = null, $options = []) {
        $include_archived = $this->include_archived_flag($options);
        $this->db->select('sections.*, users.full_name as adviser_name, users.email as adviser_email, users.profile_pic as adviser_profile_pic')
            ->from('sections')
            ->join('users', 'sections.adviser_id = users.user_id', 'left');

        if ($this->supportsArchive && !$include_archived) {
            $this->db->where('sections.is_archived', 0);
        }
        
        if ($semester && $semester !== 'all') {
            $this->db->where('sections.semester', $semester);
        }
        
        if ($academic_year && $academic_year !== 'all') {
            $this->db->where('sections.academic_year', $academic_year);
        }
        
        $this->db->order_by('sections.academic_year', 'DESC')
            ->order_by('sections.semester', 'ASC')
            ->order_by('sections.year_level', 'ASC')
            ->order_by('sections.section_name', 'ASC');
        
        return $this->db->get()->result_array();
    }

    public function get_by_id($section_id) {
        return $this->db->select('sections.*, users.full_name as adviser_name, users.email as adviser_email, users.profile_pic as adviser_profile_pic')
            ->from('sections')
            ->join('users', 'sections.adviser_id = users.user_id', 'left')
            ->where('sections.section_id', $section_id)
            ->get()->row_array();
    }

    public function get_section_by_id($section_id) {
        return $this->db->select('sections.*, users.full_name as adviser_name, users.email as adviser_email, users.profile_pic as adviser_profile_pic')
            ->from('sections')
            ->join('users', 'sections.adviser_id = users.user_id', 'left')
            ->where('sections.section_id', $section_id)
            ->get()->row_array();
    }

    public function insert($data) {
        // Set created_at to current timestamp
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert('sections', $data);
        return $this->db->insert_id();
    }

    public function update($section_id, $data) {
        $this->db->where('section_id', $section_id);
        return $this->db->update('sections', $data);
    }

    public function delete($section_id) {
        $this->db->where('section_id', $section_id);
        return $this->db->delete('sections');
    }

    public function get_students($section_id) {
        return $this->db->select('user_id, full_name, email, student_num, contact_num, address, program, status')
            ->from('users')
            ->where('section_id', $section_id)
            ->where('role', 'student')
            ->get()->result_array();
    }

    public function is_section_linked($section_id) {
        $this->db->where('section_id', $section_id);
        $this->db->where('role', 'student');
        $count = $this->db->count_all_results('users');
        return $count > 0;
    }

    public function get_available_advisers() {
        return $this->db->select('user_id, full_name, email')
            ->from('users')
            ->where('role', 'teacher')
            ->where('status', 'active')
            ->get()->result_array();
    }

    public function get_programs($include_archived = false) {
        $this->load->model('Program_model');
        return $this->Program_model->get_all([
            'include_archived' => $include_archived
        ]);
    }

    public function get_year_levels() {
        $this->db->select('DISTINCT(year_level) as year_level', false)
            ->from('sections')
            ->where('year_level IS NOT NULL')
            ->where('year_level !=', '');

        if ($this->supportsArchive) {
            $this->db->where('is_archived', 0);
        }

        return $this->db->order_by('year_level', 'ASC')
            ->get()->result_array();
    }

    public function get_semesters() {
        $this->db->select('DISTINCT(semester) as semester', false)
            ->from('sections')
            ->where('semester IS NOT NULL')
            ->where('semester !=', '');

        if ($this->supportsArchive) {
            $this->db->where('is_archived', 0);
        }

        return $this->db->order_by('semester', 'ASC')
            ->get()->result_array();
    }

    public function get_academic_years() {
        $this->db->select('DISTINCT(academic_year) as academic_year', false)
            ->from('sections')
            ->where('academic_year IS NOT NULL')
            ->where('academic_year !=', '');

        if ($this->supportsArchive) {
            $this->db->where('is_archived', 0);
        }

        return $this->db->order_by('academic_year', 'DESC')
            ->get()->result_array();
    }

    public function assign_students_to_section($section_id, $student_ids) {
        $assigned_students = [];
        
        foreach ($student_ids as $student_id) {
            // Check if student exists and is a student
            $student = $this->db->get_where('users', [
                'user_id' => $student_id,
                'role' => 'student'
            ])->row_array();
            
            if ($student) {
                // Update student's section_id
                $this->db->where('user_id', $student_id);
                $this->db->update('users', ['section_id' => $section_id]);
                
                $assigned_students[] = [
                    'user_id' => $student_id,
                    'full_name' => $student['full_name'],
                    'email' => $student['email'],
                    'student_num' => $student['student_num']
                ];
            }
        }
        
        return $assigned_students;
    }

    public function remove_students_from_section($section_id, $student_ids) {
        $removed_students = [];
        
        foreach ($student_ids as $student_id) {
            // Check if student is actually in this section
            $student = $this->db->get_where('users', [
                'user_id' => $student_id,
                'section_id' => $section_id,
                'role' => 'student'
            ])->row_array();
            
            if ($student) {
                // Remove student from section (set section_id to NULL)
                $this->db->where('user_id', $student_id);
                $this->db->update('users', ['section_id' => NULL]);
                
                $removed_students[] = [
                    'user_id' => $student_id,
                    'full_name' => $student['full_name'],
                    'email' => $student['email'],
                    'student_num' => $student['student_num']
                ];
            }
        }
        
        return $removed_students;
    }

    public function get_available_students() {
        return $this->db->select('user_id, full_name, email, student_num, contact_num, address, program, status')
            ->from('users')
            ->where('role', 'student')
            ->where('(section_id IS NULL OR section_id = 0)', NULL, FALSE)
            ->where('status', 'active')
            ->order_by('full_name', 'ASC')
            ->get()->result_array();
    }

    public function get_all_students_with_sections() {
        return $this->db->select('users.user_id, users.full_name, users.email, users.student_num, users.contact_num, users.address, users.program, users.status, sections.section_name, sections.section_id')
            ->from('users')
            ->join('sections', 'users.section_id = sections.section_id', 'left')
            ->where('users.role', 'student')
            ->where('users.status !=', 'rejected')
            ->order_by('users.full_name', 'ASC')
            ->get()->result_array();
    }

    // Get all sections grouped by program
    public function get_sections_grouped_by_program($options = []) {
        $sections = $this->get_all($options);
        $grouped = [];
        foreach ($sections as $section) {
            $program = $section['program'];
            if (!isset($grouped[$program])) {
                $grouped[$program] = [];
            }
            $grouped[$program][] = $section;
        }
        return $grouped;
    }

    // Get all sections for a specific program
    public function get_by_program($program, $options = []) {
        $include_archived = $this->include_archived_flag($options);

        $this->db->select('sections.*, users.full_name as adviser_name, users.email as adviser_email, users.profile_pic as adviser_profile_pic,
                                 (SELECT COUNT(*) FROM users WHERE users.section_id = sections.section_id AND users.role = "student") as enrolled_count')
            ->from('sections')
            ->join('users', 'sections.adviser_id = users.user_id', 'left')
            ->where('sections.program', $program);

        if ($this->supportsArchive && !$include_archived) {
            $this->db->where('sections.is_archived', 0);
        }

        return $this->db->order_by('sections.academic_year', 'DESC')
            ->order_by('sections.semester', 'ASC')
            ->order_by('sections.year_level', 'ASC')
            ->order_by('sections.section_name', 'ASC')
            ->get()->result_array();
    }

    // Get sections grouped by program and year level
    public function get_by_program_grouped_by_year($program, $options = []) {
        $sections = $this->get_by_program($program, $options);
        $grouped = [];
        
        foreach ($sections as $section) {
            $year_level = $section['year_level'];
            if (!isset($grouped[$year_level])) {
                $grouped[$year_level] = [];
            }
            $grouped[$year_level][] = $section;
        }
        
        // Sort by year level (1st, 2nd, 3rd, 4th)
        ksort($grouped);
        
        return $grouped;
    }

    // Get sections by program and specific year level
    public function get_by_program_and_year_level($program, $year_level = null, $options = []) {
        $include_archived = $this->include_archived_flag($options);
        $this->db->select('sections.*, users.full_name as adviser_name, users.email as adviser_email, users.profile_pic as adviser_profile_pic,
                          (SELECT COUNT(*) FROM users WHERE users.section_id = sections.section_id AND users.role = "student") as enrolled_count')
            ->from('sections')
            ->join('users', 'sections.adviser_id = users.user_id', 'left')
            ->where('sections.program', $program);

        if ($this->supportsArchive && !$include_archived) {
            $this->db->where('sections.is_archived', 0);
        }
        
        if ($year_level && $year_level !== 'all') {
            // Handle different possible formats
            $year_level_clean = trim($year_level);
            $this->db->where('sections.year_level', $year_level_clean);
        }
        
        $this->db->order_by('sections.academic_year', 'DESC')
            ->order_by('sections.semester', 'ASC')
            ->order_by('sections.section_name', 'ASC');
        
        return $this->db->get()->result_array();
    }

    // Get adviser (teacher) for a section from the classes table
    public function get_section_adviser_from_classes($section_id) {
        $this->db->select('users.user_id, users.full_name as adviser_name, users.email as adviser_email')
            ->from('classes')
            ->join('users', 'classes.teacher_id = users.user_id', 'left')
            ->where('classes.section_id', $section_id)
            ->limit(1);
        return $this->db->get()->row_array();
    }

    public function set_archive_status($section_id, $is_archived, $reason = null) {
        if (!$this->supportsArchive) {
            return false;
        }

        $data = [
            'is_archived' => $is_archived ? 1 : 0,
            'archived_at' => $is_archived ? date('Y-m-d H:i:s') : null,
            'archive_reason' => $reason
        ];

        $this->db->where('section_id', $section_id)->update('sections', $data);
        return $this->db->affected_rows() > 0;
    }

    public function get_management_overview($filters = []) {
        $include_archived = !empty($filters['include_archived']);

        $base = $this->db->select('sections.*, users.full_name as adviser_name, users.email as adviser_email, users.profile_pic as adviser_profile_pic,
                                 (SELECT COUNT(*) FROM users WHERE users.section_id = sections.section_id AND users.role = "student") as enrolled_count')
            ->from('sections')
            ->join('users', 'sections.adviser_id = users.user_id', 'left');

        if (!empty($filters['program'])) {
            $base->where('sections.program', $filters['program']);
        }

        $active_query = clone $base;
        $archived_query = clone $base;

        if ($this->supportsArchive) {
            $active_query->where('sections.is_archived', 0);
            $archived_query->where('sections.is_archived', 1);
        }

        $active = $active_query->order_by('sections.academic_year', 'DESC')
            ->order_by('sections.year_level', 'ASC')
            ->order_by('sections.section_name', 'ASC')
            ->get()->result_array();

        $archived = $include_archived
            ? $archived_query->order_by('sections.academic_year', 'DESC')
                ->order_by('sections.year_level', 'ASC')
                ->order_by('sections.section_name', 'ASC')
                ->get()->result_array()
            : [];

        return [
            'active' => $active,
            'archived' => $archived
        ];
    }

    private function include_archived_flag($options) {
        if (is_array($options)) {
            return !empty($options['include_archived']);
        }

        if (is_bool($options)) {
            return $options;
        }

        return false;
    }
}
