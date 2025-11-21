<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Program_model extends CI_Model
{
    protected $table = 'programs';

    /**
     * Get all programs with optional filters
     *
     * @param array $options
     * @return array
     */
    public function get_all(array $options = [])
    {
        $include_archived = !empty($options['include_archived']);
        $status = $options['status'] ?? null;
        $search = isset($options['search']) ? trim($options['search']) : null;
        $with_usage = !empty($options['with_usage']);

        $this->db->select('program_id, code, name, description, status, archived_at, created_at, updated_at');
        $this->db->from($this->table);

        if ($status && in_array($status, ['active', 'archived'])) {
            $this->db->where('status', $status);
        } elseif (!$include_archived) {
            $this->db->where('status', 'active');
        }

        if (!empty($options['ids']) && is_array($options['ids'])) {
            $this->db->where_in('program_id', $options['ids']);
        }

        if (!empty($options['codes']) && is_array($options['codes'])) {
            $this->db->where_in('code', array_map('strtoupper', $options['codes']));
        }

        if ($search) {
            $this->db->group_start()
                ->like('code', $search)
                ->or_like('name', $search)
            ->group_end();
        }

        $this->db->order_by('name', 'ASC')
            ->order_by('code', 'ASC');

        $programs = $this->db->get()->result_array();

        if ($with_usage && !empty($programs)) {
            $programs = $this->attach_usage_counts($programs);
        }

        foreach ($programs as &$program) {
            $program['program'] = $program['code'];
        }
        unset($program);

        return $programs;
    }

    /**
     * Get all active programs
     *
     * @return array
     */
    public function get_active()
    {
        return $this->get_all();
    }

    /**
     * Get a single program by ID
     */
    public function get_by_id($program_id, $allow_archived = true)
    {
        if (empty($program_id)) {
            return null;
        }

        $this->db->from($this->table)
            ->where('program_id', $program_id);

        if (!$allow_archived) {
            $this->db->where('status', 'active');
        }

        return $this->db->get()->row_array();
    }

    /**
     * Get a single program by code
     */
    public function get_by_code($code, $allow_archived = true)
    {
        if (empty($code)) {
            return null;
        }

        $this->db->from($this->table)
            ->where('code', strtoupper(trim($code)));

        if (!$allow_archived) {
            $this->db->where('status', 'active');
        }

        return $this->db->get()->row_array();
    }

    /**
     * Create a new program entry
     */
    public function create(array $data)
    {
        if (empty($data['code']) && empty($data['name'])) {
            return null;
        }

        $now = date('Y-m-d H:i:s');
        $payload = [
            'code' => strtoupper(trim($data['code'] ?? $data['name'])),
            'name' => trim($data['name'] ?? $data['code']),
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'status' => $data['status'] ?? 'active',
            'created_at' => $now,
            'updated_at' => $now,
            'archived_at' => null
        ];

        $this->db->insert($this->table, $payload);
        $program_id = $this->db->insert_id();

        return $this->get_by_id($program_id);
    }

    /**
     * Update an existing program
     */
    public function update($program_id, array $data)
    {
        if (empty($program_id) || empty($data)) {
            return false;
        }

        $payload = [];

        if (isset($data['code'])) {
            $payload['code'] = strtoupper(trim($data['code']));
        }

        if (isset($data['name'])) {
            $payload['name'] = trim($data['name']);
        }

        if (array_key_exists('description', $data)) {
            $payload['description'] = $data['description'] !== null
                ? trim($data['description'])
                : null;
        }

        if (isset($data['status']) && in_array($data['status'], ['active', 'archived'])) {
            $payload['status'] = $data['status'];
        }

        if (array_key_exists('archived_at', $data)) {
            $payload['archived_at'] = $data['archived_at'];
        }

        if (empty($payload)) {
            return false;
        }

        $payload['updated_at'] = date('Y-m-d H:i:s');

        $this->db->where('program_id', $program_id);
        $this->db->update($this->table, $payload);

        return $this->get_by_id($program_id);
    }

    /**
     * Archive (deactivate) a program
     */
    public function archive($program_id)
    {
        return $this->update($program_id, [
            'status' => 'archived',
            'archived_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Restore an archived program
     */
    public function restore($program_id)
    {
        return $this->update($program_id, [
            'status' => 'active',
            'archived_at' => null
        ]);
    }

    /**
     * Normalize any program string to a valid program record
     */
    public function normalize_program_input($input, $allow_archived = false)
    {
        if (!$input || !is_string($input)) {
            return null;
        }

        $value = trim($input);
        if ($value === '') {
            return null;
        }

        $upper_value = strtoupper($value);
        $lower_value = strtolower($value);

        // First, try exact code or name matches (case-insensitive)
        $this->db->from($this->table)
            ->group_start()
                ->where('code', $upper_value)
                ->or_where('LOWER(name) =', $lower_value)
            ->group_end();

        if (!$allow_archived) {
            $this->db->where('status', 'active');
        }

        $program = $this->db->get()->row_array();
        if ($program) {
            return $program;
        }

        // Fallback: partial match on name
        $this->db->from($this->table)
            ->like('name', $value);

        if (!$allow_archived) {
            $this->db->where('status', 'active');
        }

        return $this->db->get()->row_array() ?: null;
    }

    /**
     * Check if a code already exists (case-insensitive)
     */
    public function exists_by_code($code, $exclude_program_id = null)
    {
        if (empty($code)) {
            return false;
        }

        $this->db->from($this->table)
            ->where('code', strtoupper(trim($code)));

        if (!empty($exclude_program_id)) {
            $this->db->where('program_id !=', $exclude_program_id);
        }

        return $this->db->count_all_results() > 0;
    }

    /**
     * Attach usage counts to program data
     */
    protected function attach_usage_counts(array $programs)
    {
        $codes = array_column($programs, 'code');
        if (empty($codes)) {
            return $programs;
        }

        // Section counts
        $section_counts = $this->db->select('program, COUNT(*) as total_sections', false)
            ->from('sections')
            ->where_in('program', $codes)
            ->group_by('program')
            ->get()
            ->result_array();

        $section_map = [];
        foreach ($section_counts as $row) {
            $section_map[$row['program']] = (int) $row['total_sections'];
        }

        // User counts
        $user_counts = $this->db->select(
            'program,
             COUNT(*) as total_users,
             SUM(CASE WHEN role = "student" THEN 1 ELSE 0 END) as student_count,
             SUM(CASE WHEN role = "teacher" THEN 1 ELSE 0 END) as teacher_count,
             SUM(CASE WHEN role = "admin" THEN 1 ELSE 0 END) as admin_count',
            false
        )
        ->from('users')
        ->where_in('program', $codes)
        ->group_by('program')
        ->get()
        ->result_array();

        $user_map = [];
        foreach ($user_counts as $row) {
            $user_map[$row['program']] = [
                'total_users' => (int) $row['total_users'],
                'student_count' => (int) $row['student_count'],
                'teacher_count' => (int) $row['teacher_count'],
                'admin_count' => (int) $row['admin_count']
            ];
        }

        foreach ($programs as &$program) {
            $code = $program['code'];
            $program['section_count'] = $section_map[$code] ?? 0;
            $program['total_users'] = $user_map[$code]['total_users'] ?? 0;
            $program['student_count'] = $user_map[$code]['student_count'] ?? 0;
            $program['teacher_count'] = $user_map[$code]['teacher_count'] ?? 0;
            $program['admin_count'] = $user_map[$code]['admin_count'] ?? 0;
        }

        return $programs;
    }
}

