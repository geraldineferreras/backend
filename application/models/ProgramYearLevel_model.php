<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class ProgramYearLevel_model extends CI_Model
{
    private $table = 'program_year_levels';

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->dbforge();
        $this->ensure_table();
    }

    private function ensure_table()
    {
        if (!$this->db->table_exists($this->table)) {
            $fields = [
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'program_code' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => false
                ],
                'label' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false
                ],
                'description' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true
                ],
                'order_index' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0
                ],
                'is_archived' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0
                ],
                'archived_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ],
                'created_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true
                ],
                'updated_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true
                ],
                'created_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP'
                ],
                'updated_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP'
                ]
            ];

            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key(['program_code', 'label']);
            $this->dbforge->create_table($this->table, true);
            $this->ensure_timestamp_auto_update($this->table, 'updated_at');
            $this->seed_from_sections();
        } else {
            if (!$this->db->field_exists('order_index', $this->table)) {
                $this->dbforge->add_column($this->table, [
                    'order_index' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'default' => 0,
                        'after' => 'label'
                    ]
                ]);
            }

            if (!$this->db->field_exists('is_archived', $this->table)) {
                $this->dbforge->add_column($this->table, [
                    'is_archived' => [
                        'type' => 'TINYINT',
                        'constraint' => 1,
                        'default' => 0,
                        'after' => 'order_index'
                    ]
                ]);
            }

            $this->ensure_timestamp_auto_update($this->table, 'updated_at');
        }
    }

    private function ensure_timestamp_auto_update($table, $column)
    {
        if (!$this->db->table_exists($table) || !$this->db->field_exists($column, $table)) {
            return;
        }

        $this->db->query("ALTER TABLE `{$table}` MODIFY `{$column}` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }

    private function seed_from_sections()
    {
        if (!$this->db->table_exists('sections')) {
            return;
        }

        $sections = $this->db->select('DISTINCT program, year_level', false)
            ->from('sections')
            ->where('program IS NOT NULL')
            ->where('program !=', '')
            ->where('year_level IS NOT NULL')
            ->where('year_level !=', '')
            ->get()
            ->result_array();

        foreach ($sections as $section) {
            $program = $section['program'];
            $label = $section['year_level'];

            $exists = $this->db->select('id')
                ->from($this->table)
                ->where('program_code', $program)
                ->where('label', $label)
                ->get()
                ->row_array();

            if (!$exists) {
                $this->db->insert($this->table, [
                    'program_code' => $program,
                    'label' => $label,
                    'description' => 'Imported from existing sections',
                    'order_index' => is_numeric($label) ? (int)$label : 0
                ]);
            }
        }
    }

    public function get_all($filters = [])
    {
        $builder = $this->db->select('*')->from($this->table);

        if (!empty($filters['program'])) {
            $builder->where('program_code', $filters['program']);
        }

        if (empty($filters['include_archived'])) {
            $builder->where('is_archived', 0);
        }

        $builder->order_by('program_code', 'ASC');
        $builder->order_by('order_index', 'ASC');
        $builder->order_by('label', 'ASC');

        $rows = $builder->get()->result_array();

        if (!empty($filters['with_usage']) && !empty($rows)) {
            $usage = $this->calculate_usage($rows);
            foreach ($rows as &$row) {
                $key = $this->usage_key($row['program_code'], $row['label']);
                $row['usage'] = $usage[$key] ?? ['sections' => 0, 'students' => 0];
            }
        }

        return $rows;
    }

    public function create($data)
    {
        $payload = [
            'program_code' => strtoupper($data['program_code']),
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'order_index' => $data['order_index'] ?? 0,
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['created_by'] ?? null
        ];

        $exists = $this->db->select('id')
            ->from($this->table)
            ->where('program_code', $payload['program_code'])
            ->where('label', $payload['label'])
            ->get()
            ->row_array();

        if ($exists) {
            return ['status' => false, 'message' => 'Year level already exists for this program'];
        }

        $this->db->insert($this->table, $payload);
        $id = $this->db->insert_id();

        return [
            'status' => true,
            'message' => 'Year level created successfully',
            'data' => $this->db->get_where($this->table, ['id' => $id])->row_array()
        ];
    }

    public function set_archive_status($id, $is_archived, $updated_by = null)
    {
        $this->db->where('id', $id)->update($this->table, [
            'is_archived' => $is_archived ? 1 : 0,
            'archived_at' => $is_archived ? date('Y-m-d H:i:s') : null,
            'updated_by' => $updated_by
        ]);

        if ($this->db->affected_rows() === 0) {
            return ['status' => false, 'message' => 'Year level not updated'];
        }

        return [
            'status' => true,
            'message' => $is_archived ? 'Year level archived' : 'Year level restored',
            'data' => $this->db->get_where($this->table, ['id' => $id])->row_array()
        ];
    }

    private function calculate_usage(array $rows)
    {
        $programs = array_values(array_unique(array_map(function ($row) {
            return $row['program_code'];
        }, $rows)));

        $labels = array_values(array_unique(array_map(function ($row) {
            return $row['label'];
        }, $rows)));

        if (empty($programs) || empty($labels)) {
            return [];
        }

        $usage_rows = $this->db->select('s.program, s.year_level,
                COUNT(DISTINCT s.section_id) as section_count,
                SUM(CASE WHEN u.user_id IS NOT NULL THEN 1 ELSE 0 END) as student_count', false)
            ->from('sections s')
            ->join('users u', 'u.section_id = s.section_id AND u.role = "student"', 'left')
            ->where_in('s.program', $programs)
            ->where_in('s.year_level', $labels)
            ->group_by('s.program')
            ->group_by('s.year_level')
            ->get()
            ->result_array();

        $usage = [];
        foreach ($usage_rows as $usage_row) {
            $key = $this->usage_key($usage_row['program'], $usage_row['year_level']);
            $usage[$key] = [
                'sections' => (int)$usage_row['section_count'],
                'students' => (int)$usage_row['student_count']
            ];
        }

        return $usage;
    }

    private function usage_key($program, $label)
    {
        return strtoupper($program) . '::' . $label;
    }
}

