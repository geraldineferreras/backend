<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class AcademicYear_model extends CI_Model
{
    private $table = 'academic_years';
    private $promotionTable = 'academic_year_promotions';
    private $promotionStudentsTable = 'academic_year_promotion_students';
    private $sectionsArchiveFieldExists = false;

    public function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->dbforge();
        $this->ensure_schema();
        $this->sectionsArchiveFieldExists = $this->db->field_exists('is_archived', 'sections');
    }

    /**
     * Ensure all required tables/columns exist before we run any queries.
     * This allows the feature to bootstrap itself even on older databases.
     */
    private function ensure_schema()
    {
        $this->ensure_academic_years_table();
        $this->ensure_promotions_table();
        $this->ensure_promotion_students_table();
        $this->ensure_sections_columns();
    }

    private function ensure_academic_years_table()
    {
        if (!$this->db->table_exists($this->table)) {
            $fields = [
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false
                ],
                'start_date' => [
                    'type' => 'DATE',
                    'null' => false
                ],
                'end_date' => [
                    'type' => 'DATE',
                    'null' => false
                ],
                'sem1_start_date' => [
                    'type' => 'DATE',
                    'null' => false
                ],
                'sem1_end_date' => [
                    'type' => 'DATE',
                    'null' => false
                ],
                'sem2_start_date' => [
                    'type' => 'DATE',
                    'null' => true
                ],
                'sem2_end_date' => [
                    'type' => 'DATE',
                    'null' => true
                ],
                'status' => [
                    'type' => "ENUM('draft','active','archived','closed')",
                    'default' => 'draft'
                ],
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0
                ],
                'lock_data' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0
                ],
                'activated_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ],
                'archived_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ],
                'locked_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ],
                'closed_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ],
                'activation_notes' => [
                    'type' => 'TEXT',
                    'null' => true
                ],
                'closing_notes' => [
                    'type' => 'TEXT',
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
            $this->dbforge->add_key('name');
            $this->dbforge->create_table($this->table, true);
            $this->ensure_timestamp_auto_update($this->table, 'updated_at');
            $this->db->query("ALTER TABLE `{$this->table}` ADD UNIQUE KEY `uniq_year_name` (`name`)");
        } else {
            $this->add_column_if_missing($this->table, 'lock_data', [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'after' => 'is_active'
            ]);
            $this->add_column_if_missing($this->table, 'locked_at', [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'archived_at'
            ]);
            $this->add_column_if_missing($this->table, 'closed_at', [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'locked_at'
            ]);
            $this->add_column_if_missing($this->table, 'activation_notes', [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'archived_at'
            ]);
            $this->add_column_if_missing($this->table, 'closing_notes', [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'activation_notes'
            ]);
            if (!$this->db->field_exists('updated_at', $this->table)) {
                $this->dbforge->add_column($this->table, [
                    'updated_at' => [
                        'type' => 'TIMESTAMP',
                        'null' => false,
                        'default' => 'CURRENT_TIMESTAMP',
                        'after' => 'created_at'
                    ]
                ]);
            }
            $this->ensure_timestamp_auto_update($this->table, 'updated_at');
        }
    }

    private function ensure_promotions_table()
    {
        if (!$this->db->table_exists($this->promotionTable)) {
            $fields = [
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'academic_year_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false
                ],
                'status' => [
                    'type' => "ENUM('draft','in_progress','finalized','cancelled')",
                    'default' => 'draft'
                ],
                'initiated_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true
                ],
                'initiated_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP'
                ],
                'finalized_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true
                ],
                'finalized_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ],
                'eligible_count' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0
                ],
                'issue_count' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0
                ],
                'promoted_count' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0
                ],
                'retained_count' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'default' => 0
                ],
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true
                ]
            ];

            $this->dbforge->add_field($fields);
            $this->dbforge->add_key('id', true);
            $this->dbforge->add_key('academic_year_id');
            $this->dbforge->create_table($this->promotionTable, true);
        }
    }

    private function ensure_promotion_students_table()
    {
        if (!$this->db->table_exists($this->promotionStudentsTable)) {
            $fields = [
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true
                ],
                'promotion_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => false
                ],
                'student_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => false
                ],
                'student_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => false
                ],
                'program' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true
                ],
                'current_year_level' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true
                ],
                'target_year_level' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true
                ],
                'section_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true
                ],
                'section_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true
                ],
                'evaluation_status' => [
                    'type' => "ENUM('eligible','issue')",
                    'default' => 'eligible'
                ],
                'decision_status' => [
                    'type' => "ENUM('pending','promoted','retained','irregular')",
                    'default' => 'pending'
                ],
                'issue_reason' => [
                    'type' => 'TEXT',
                    'null' => true
                ],
                'decision_notes' => [
                    'type' => 'TEXT',
                    'null' => true
                ],
                'decision_by' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true
                ],
                'decision_at' => [
                    'type' => 'DATETIME',
                    'null' => true
                ],
                'target_section_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true
                ],
                'target_section_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true
                ],
                'target_academic_year_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true
                ],
                'target_academic_year_name' => [
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
            $this->dbforge->add_key(['promotion_id', 'student_id']);
            $this->dbforge->create_table($this->promotionStudentsTable, true);
            $this->ensure_timestamp_auto_update($this->promotionStudentsTable, 'updated_at');
        }

        $this->add_column_if_missing($this->promotionStudentsTable, 'target_section_id', [
            'type' => 'INT',
            'constraint' => 11,
            'null' => true,
            'after' => 'decision_at'
        ]);

        $this->add_column_if_missing($this->promotionStudentsTable, 'target_section_name', [
            'type' => 'VARCHAR',
            'constraint' => 150,
            'null' => true,
            'after' => 'target_section_id'
        ]);

        $this->add_column_if_missing($this->promotionStudentsTable, 'target_academic_year_id', [
            'type' => 'INT',
            'constraint' => 11,
            'null' => true,
            'after' => 'target_section_name'
        ]);

        $this->add_column_if_missing($this->promotionStudentsTable, 'target_academic_year_name', [
            'type' => 'VARCHAR',
            'constraint' => 50,
            'null' => true,
            'after' => 'target_academic_year_id'
        ]);
    }

    private function ensure_sections_columns()
    {
        $this->add_column_if_missing('sections', 'academic_year_id', [
            'type' => 'INT',
            'constraint' => 11,
            'null' => true,
            'after' => 'academic_year'
        ]);

        $this->add_column_if_missing('sections', 'is_archived', [
            'type' => 'TINYINT',
            'constraint' => 1,
            'default' => 0,
            'after' => 'status'
        ]);

        $this->add_column_if_missing('sections', 'archived_at', [
            'type' => 'DATETIME',
            'null' => true,
            'after' => 'is_archived'
        ]);

        $this->add_column_if_missing('sections', 'archive_reason', [
            'type' => 'TEXT',
            'null' => true,
            'after' => 'archived_at'
        ]);
    }

    private function add_column_if_missing($table, $column, $definition)
    {
        if (!$this->db->table_exists($table)) {
            return;
        }

        if (!$this->db->field_exists($column, $table)) {
            $this->dbforge->add_column($table, [$column => $definition]);
        }
    }

    private function ensure_timestamp_auto_update($table, $column)
    {
        if (!$this->db->table_exists($table) || !$this->db->field_exists($column, $table)) {
            return;
        }

        $this->db->query("ALTER TABLE `{$table}` MODIFY `{$column}` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    }

    public function get_active_year($filters = [])
    {
        $builder = $this->db->select('*')
            ->from($this->table)
            ->where('is_active', 1)
            ->order_by('activated_at', 'DESC')
            ->limit(1);

        $year = $builder->get()->row_array();
        if (!$year) {
            return null;
        }

        return $this->attach_year_summary($year, $filters);
    }

    public function get_year($year_id, $filters = [])
    {
        $year = $this->db->get_where($this->table, ['id' => $year_id])->row_array();
        if (!$year) {
            return null;
        }

        return $this->attach_year_summary($year, $filters);
    }

    public function get_years($filters = [])
    {
        $builder = $this->db->select('*')->from($this->table);

        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : [$filters['status']];
            $builder->where_in('status', $statuses);
        }

        if (empty($filters['include_archived'])) {
            $builder->where('status !=', 'archived');
        }

        $builder->order_by('start_date', 'DESC');
        $rows = $builder->get()->result_array();

        return array_map(function ($row) use ($filters) {
            return $this->attach_year_summary($row, $filters);
        }, $rows);
    }

    public function create_year($payload, $options = [])
    {
        $exists = $this->db->select('id')
            ->from($this->table)
            ->where('name', $payload['name'])
            ->get()
            ->row_array();

        if ($exists) {
            return [
                'status' => false,
                'message' => 'Academic year already exists'
            ];
        }

        $data = [
            'name' => $payload['name'],
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'],
            'sem1_start_date' => $payload['sem1_start_date'],
            'sem1_end_date' => $payload['sem1_end_date'],
            'sem2_start_date' => $payload['sem2_start_date'] ?? null,
            'sem2_end_date' => $payload['sem2_end_date'] ?? null,
            'status' => 'draft',
            'created_by' => $payload['created_by'] ?? null,
            'updated_by' => $payload['created_by'] ?? null
        ];

        $this->db->insert($this->table, $data);
        $year_id = $this->db->insert_id();

        $autoSections = null;
        if (!empty($options['auto_create_sections'])) {
            $year = $this->db->get_where($this->table, ['id' => $year_id])->row_array();
            $autoSections = $this->auto_create_sections_for_year($year);
        }

        $auto_activate = !empty($options['auto_activate']);
        $activation_result = null;
        if ($auto_activate) {
            $activation_result = $this->activate_year(
                $year_id,
                $options['activated_by'] ?? ($payload['created_by'] ?? null),
                [
                    'notes' => $options['activation_notes'] ?? null,
                    'force' => true
                ]
            );

            if ($activation_result['status'] === false) {
                return $activation_result;
            }
        }

        return [
            'status' => true,
            'message' => $auto_activate ? 'Academic year created and activated successfully' : 'Academic year created successfully',
            'data' => [
                'year' => $this->get_year($year_id),
                'auto_activated' => $auto_activate,
                'activation' => $activation_result,
                'auto_created_sections' => $autoSections
            ]
        ];
    }

    public function update_year($year_id, $payload, $updated_by = null)
    {
        $year = $this->db->get_where($this->table, ['id' => $year_id])->row_array();
        if (!$year) {
            return ['status' => false, 'message' => 'Academic year not found'];
        }

        if ((int)$year['lock_data'] === 1 || $year['status'] === 'closed') {
            return ['status' => false, 'message' => 'This academic year is locked and cannot be edited'];
        }

        $allowed = [
            'name',
            'start_date',
            'end_date',
            'sem1_start_date',
            'sem1_end_date',
            'sem2_start_date',
            'sem2_end_date'
        ];

        $update = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $payload)) {
                $update[$field] = $payload[$field];
            }
        }

        if (empty($update)) {
            return ['status' => false, 'message' => 'No editable fields were provided'];
        }

        if (isset($update['name']) && $update['name'] !== $year['name']) {
            $conflict = $this->db->select('id')
                ->from($this->table)
                ->where('name', $update['name'])
                ->where('id !=', $year_id)
                ->get()
                ->row_array();

            if ($conflict) {
                return ['status' => false, 'message' => 'Another academic year already uses this name'];
            }
        }

        $merged = array_merge($year, $update);
        $validation = $this->validate_date_sequence($merged);
        if (!$validation['status']) {
            return $validation;
        }

        $update['updated_by'] = $updated_by;
        $this->db->where('id', $year_id)->update($this->table, $update);

        if ($this->db->affected_rows() === 0) {
            return ['status' => false, 'message' => 'No changes were saved'];
        }

        return [
            'status' => true,
            'message' => 'Academic year updated successfully',
            'data' => $this->get_year($year_id)
        ];
    }

    public function activate_year($year_id, $activated_by = null, $options = [])
    {
        $year = $this->db->get_where($this->table, ['id' => $year_id])->row_array();
        if (!$year) {
            return ['status' => false, 'message' => 'Academic year not found'];
        }

        if ($year['status'] === 'active' && empty($options['force'])) {
            return ['status' => false, 'message' => 'Academic year is already active'];
        }

        if (empty($year['sem1_start_date']) || empty($year['sem1_end_date']) || empty($year['sem2_start_date']) || empty($year['sem2_end_date'])) {
            return ['status' => false, 'message' => 'Semester dates must be defined before activation'];
        }

        $this->db->trans_start();
        $movementResult = null;
        $autoSections = null;

        // Archive previously active academic year (only one can be active)
        $previous_active = $this->db->select('*')
            ->from($this->table)
            ->where('is_active', 1)
            ->where('id !=', $year_id)
            ->order_by('activated_at', 'DESC')
            ->get()
            ->row_array();

        if ($previous_active) {
            $this->db->where('id', $previous_active['id'])
                ->update($this->table, [
                    'is_active' => 0,
                    'status' => 'archived',
                    'archived_at' => date('Y-m-d H:i:s')
                ]);
        }

        $this->db->where('id', $year_id)->update($this->table, [
            'status' => 'active',
            'is_active' => 1,
            'activated_at' => date('Y-m-d H:i:s'),
            'activation_notes' => $options['notes'] ?? null,
            'updated_by' => $activated_by
        ]);

        if ($previous_active) {
            $movementResult = $this->move_promoted_students_to_next_year($previous_active, $year);
            if (!$movementResult['status']) {
                $this->db->trans_rollback();
                return $movementResult;
            }
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === false) {
            return ['status' => false, 'message' => 'Failed to activate academic year'];
        }

        $data = $this->get_year($year_id);
        if ($movementResult) {
            $data['promotion_migration'] = array_merge([
                'message' => $movementResult['message'],
                'source_academic_year' => [
                    'id' => $previous_active['id'],
                    'name' => $previous_active['name']
                ]
            ], $movementResult['data'] ?? []);
        }

        if ($autoSections) {
            $data['auto_created_sections'] = $autoSections;
        }

        return [
            'status' => true,
            'message' => 'Academic year activated successfully',
            'data' => $data
        ];
    }

    public function close_year($year_id, $closed_by = null, $notes = null, $lock_data = true)
    {
        $year = $this->db->get_where($this->table, ['id' => $year_id])->row_array();
        if (!$year) {
            return ['status' => false, 'message' => 'Academic year not found'];
        }

        if ($year['status'] === 'closed') {
            return ['status' => false, 'message' => 'Academic year is already closed'];
        }

        $this->db->where('id', $year_id)->update($this->table, [
            'status' => 'closed',
            'lock_data' => $lock_data ? 1 : 0,
            'locked_at' => $lock_data ? date('Y-m-d H:i:s') : null,
            'closed_at' => date('Y-m-d H:i:s'),
            'closing_notes' => $notes,
            'is_active' => 0,
            'updated_by' => $closed_by
        ]);

        if ($this->db->affected_rows() === 0) {
            return ['status' => false, 'message' => 'Failed to close academic year'];
        }

        return [
            'status' => true,
            'message' => 'Academic year closed successfully',
            'data' => $this->get_year($year_id)
        ];
    }

    private function attach_year_summary(array $year, array $filters = [])
    {
        $year['summary'] = [
            'sections' => $this->get_section_stats($year, $filters),
            'students' => $this->get_student_stats($year, $filters),
            'promotion' => $this->get_latest_promotion($year['id'])
        ];

        $year['flags'] = [
            'is_locked' => (bool)$year['lock_data'],
            'can_activate' => $year['status'] !== 'active',
            'can_close' => $year['status'] === 'active',
            'can_archive' => $year['status'] !== 'active'
        ];

        return $year;
    }

    private function get_section_stats(array $year, array $filters = [])
    {
        $builder = $this->db->select([
                'COUNT(*) as total_sections',
                'SUM(CASE WHEN sections.is_archived = 1 THEN 1 ELSE 0 END) as archived_sections'
            ])
            ->from('sections');

        $this->apply_year_filter($builder, $year);
        $this->apply_program_scope($builder, $filters);

        $row = $builder->get()->row_array();
        return [
            'total' => (int)($row['total_sections'] ?? 0),
            'archived' => (int)($row['archived_sections'] ?? 0)
        ];
    }

    private function get_student_stats(array $year, array $filters = [])
    {
        $builder = $this->db->select('COUNT(DISTINCT users.user_id) as total_students', false)
            ->from('users')
            ->join('sections', 'users.section_id = sections.section_id', 'left')
            ->where('users.role', 'student');

        $this->apply_year_filter($builder, $year);
        $this->apply_program_scope($builder, $filters);

        $row = $builder->get()->row_array();
        return [
            'total' => (int)($row['total_students'] ?? 0)
        ];
    }

    private function get_latest_promotion($year_id)
    {
        $promotion = $this->db->select('*')
            ->from($this->promotionTable)
            ->where('academic_year_id', $year_id)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$promotion) {
            return null;
        }

        return [
            'status' => $promotion['status'],
            'eligible_count' => (int)$promotion['eligible_count'],
            'issue_count' => (int)$promotion['issue_count'],
            'promoted_count' => (int)$promotion['promoted_count'],
            'retained_count' => (int)$promotion['retained_count'],
            'finalized_at' => $promotion['finalized_at']
        ];
    }

    private function apply_year_filter(CI_DB_query_builder $builder, array $year)
    {
        $builder->group_start();
        $builder->where('sections.academic_year_id', $year['id']);
        $builder->or_where('sections.academic_year', $year['name']);
        $builder->group_end();
    }

    private function apply_program_scope(CI_DB_query_builder $builder, array $filters = [])
    {
        if (!empty($filters['program'])) {
            $builder->where('sections.program', $filters['program']);
        }
    }

    private function validate_date_sequence(array $data)
    {
        $required = [
            'start_date',
            'end_date',
            'sem1_start_date',
            'sem1_end_date',
            'sem2_start_date',
            'sem2_end_date'
        ];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['status' => false, 'message' => ucfirst(str_replace('_', ' ', $field)) . ' is required'];
            }
        }

        $start = strtotime($data['start_date']);
        $end = strtotime($data['end_date']);
        $sem1_start = strtotime($data['sem1_start_date']);
        $sem1_end = strtotime($data['sem1_end_date']);
        $sem2_start = strtotime($data['sem2_start_date']);
        $sem2_end = strtotime($data['sem2_end_date']);

        if ($start === false || $end === false || $sem1_start === false || $sem1_end === false || $sem2_start === false || $sem2_end === false) {
            return ['status' => false, 'message' => 'Invalid date values provided'];
        }

        if ($start > $end) {
            return ['status' => false, 'message' => 'Start date must be before end date'];
        }

        if ($sem1_start > $sem1_end) {
            return ['status' => false, 'message' => 'Semester 1 start date must be before its end date'];
        }

        if ($sem2_start > $sem2_end) {
            return ['status' => false, 'message' => 'Semester 2 start date must be before its end date'];
        }

        if ($sem1_end > $sem2_start) {
            return ['status' => false, 'message' => 'Semester 1 must end before Semester 2 begins'];
        }

        return ['status' => true];
    }

    /**
     * Returns promotion snapshot (eligible vs issues) for a specific AY.
     */
    public function get_promotion_snapshot($year_id, $options = [])
    {
        $year = $this->db->get_where($this->table, ['id' => $year_id])->row_array();
        if (!$year) {
            return ['status' => false, 'message' => 'Academic year not found'];
        }

        $promotion = $this->get_or_create_promotion_cycle($year_id, $options);
        if (!$promotion) {
            return ['status' => false, 'message' => 'Unable to prepare promotion cycle'];
        }

        if ($promotion['status'] === 'finalized') {
            $options['force_refresh'] = false;
        }

        $preservedDecisions = [];
        if (!empty($options['force_refresh'])) {
            $preservedDecisions = $this->db->get_where($this->promotionStudentsTable, [
                'promotion_id' => $promotion['id']
            ])->result_array();

            $this->db->where('promotion_id', $promotion['id'])->delete($this->promotionStudentsTable);
        }

        $existing = $this->db->select('COUNT(*) as total')
            ->from($this->promotionStudentsTable)
            ->where('promotion_id', $promotion['id'])
            ->get()
            ->row_array();

        $preservedMap = [];
        if (!empty($preservedDecisions)) {
            $preservedMap = $this->map_preserved_decisions($preservedDecisions);
        }

        if ((int)$existing['total'] === 0) {
            $this->seed_promotion_students($promotion['id'], $year, $options);
            if (!empty($preservedDecisions)) {
                $this->restore_promotion_decisions($promotion['id'], $preservedDecisions);
            }
        }

        $students = $this->db->select('*')
            ->from($this->promotionStudentsTable)
            ->where('promotion_id', $promotion['id'])
            ->order_by('student_name', 'ASC')
            ->get()
            ->result_array();

        if (!empty($preservedMap)) {
            $students = $this->apply_preserved_decisions($students, $preservedMap);
        }

        if (!empty($options['program'])) {
            $students = array_filter($students, function ($student) use ($options) {
                return strtoupper($student['program']) === strtoupper($options['program']);
            });
        }

        $eligible = [];
        $issues = [];
        foreach ($students as $student) {
            if ($student['evaluation_status'] === 'issue') {
                $issues[] = $student;
            } else {
                $eligible[] = $student;
            }
        }

        $decisionCounts = $this->db->select([
                'SUM(CASE WHEN decision_status = "promoted" THEN 1 ELSE 0 END) as promoted_total',
                'SUM(CASE WHEN decision_status = "retained" THEN 1 ELSE 0 END) as retained_total'
            ])
            ->from($this->promotionStudentsTable)
            ->where('promotion_id', $promotion['id'])
            ->get()
            ->row_array();

        return [
            'status' => true,
            'message' => 'Promotion snapshot retrieved successfully',
            'data' => [
                'promotion' => $promotion,
                'eligible_students' => array_values($eligible),
                'students_with_issues' => array_values($issues),
                'totals' => [
                    'eligible' => count($eligible),
                    'issues' => count($issues),
                    'promoted' => (int)($decisionCounts['promoted_total'] ?? 0),
                    'retained' => (int)($decisionCounts['retained_total'] ?? 0)
                ]
            ]
        ];
    }

    public function update_promotion_student($year_id, $student_id, $payload)
    {
        $promotion = $this->get_active_promotion_cycle($year_id);
        if (!$promotion) {
            return ['status' => false, 'message' => 'No active promotion cycle found'];
        }

        $student = $this->db->get_where($this->promotionStudentsTable, [
            'promotion_id' => $promotion['id'],
            'student_id' => $student_id
        ])->row_array();

        if (!$student) {
            return ['status' => false, 'message' => 'Student not part of promotion snapshot'];
        }

        $update = [];

        if (isset($payload['evaluation_status']) && in_array($payload['evaluation_status'], ['eligible', 'issue'])) {
            $update['evaluation_status'] = $payload['evaluation_status'];
        }

        if (isset($payload['decision_status']) && in_array($payload['decision_status'], ['pending', 'promoted', 'retained', 'irregular'])) {
            $update['decision_status'] = $payload['decision_status'];
            $update['decision_by'] = $payload['updated_by'] ?? null;
            $update['decision_at'] = date('Y-m-d H:i:s');
        }

        if (array_key_exists('target_year_level', $payload)) {
            $update['target_year_level'] = $payload['target_year_level'];
            if (!array_key_exists('target_section_name', $payload) && !empty($student['section_name'])) {
                $update['target_section_name'] = $this->build_target_section_name(
                    $student['section_name'],
                    $payload['target_year_level']
                );
            }
        }

        foreach (['target_section_id', 'target_section_name', 'target_academic_year_id', 'target_academic_year_name'] as $targetField) {
            if (array_key_exists($targetField, $payload)) {
                $update[$targetField] = $payload[$targetField];
            }
        }

        if (array_key_exists('issue_reason', $payload)) {
            $update['issue_reason'] = $payload['issue_reason'];
        }

        if (array_key_exists('decision_notes', $payload)) {
            $update['decision_notes'] = $payload['decision_notes'];
        }

        if (empty($update)) {
            return ['status' => false, 'message' => 'Nothing to update'];
        }

        $this->db->where('id', $student['id'])->update($this->promotionStudentsTable, $update);

        return [
            'status' => true,
            'message' => 'Promotion record updated',
            'data' => $this->db->get_where($this->promotionStudentsTable, ['id' => $student['id']])->row_array()
        ];
    }

    public function finalize_promotion($year_id, $finalized_by = null, $notes = null)
    {
        $year = $this->get_year($year_id);
        if (!$year) {
            return ['status' => false, 'message' => 'Academic year not found'];
        }

        $promotion = $this->get_active_promotion_cycle($year_id);
        if (!$promotion) {
            $promotion = $this->get_latest_promotion_cycle_record($year_id);
            if (!$promotion) {
                return ['status' => false, 'message' => 'No promotion cycle found to finalize'];
            }
        }

        if ($promotion['status'] === 'finalized') {
            return [
                'status' => true,
                'message' => 'Promotion already finalized',
                'data' => $promotion
            ];
        }

        $students = $this->db->get_where($this->promotionStudentsTable, [
            'promotion_id' => $promotion['id']
        ])->result_array();

        if (empty($students)) {
            return ['status' => false, 'message' => 'Promotion snapshot is empty'];
        }

        $eligible = array_filter($students, function ($student) {
            return $student['evaluation_status'] === 'eligible'
                && $student['decision_status'] === 'pending';
        });

        $issues = array_filter($students, function ($student) {
            return $student['evaluation_status'] === 'issue'
                && $student['decision_status'] === 'pending';
        });

        foreach ($eligible as $student) {
            $this->db->where('id', $student['id'])->update($this->promotionStudentsTable, [
                'decision_status' => 'promoted',
                'decision_by' => $finalized_by,
                'decision_at' => date('Y-m-d H:i:s'),
                'decision_notes' => $student['decision_notes'] ?: 'Auto-promoted during finalization'
            ]);
        }

        foreach ($issues as $student) {
            $this->db->where('id', $student['id'])->update($this->promotionStudentsTable, [
                'decision_status' => 'retained',
                'decision_by' => $finalized_by,
                'decision_at' => date('Y-m-d H:i:s'),
                'decision_notes' => $student['decision_notes'] ?: 'Retained due to outstanding issues'
            ]);
        }

        $final_counts = $this->db->select([
                'SUM(CASE WHEN decision_status = "promoted" THEN 1 ELSE 0 END) as promoted_total',
                'SUM(CASE WHEN decision_status = "retained" THEN 1 ELSE 0 END) as retained_total',
                'SUM(CASE WHEN evaluation_status = "eligible" THEN 1 ELSE 0 END) as eligible_total',
                'SUM(CASE WHEN evaluation_status = "issue" THEN 1 ELSE 0 END) as issue_total'
            ])
            ->from($this->promotionStudentsTable)
            ->where('promotion_id', $promotion['id'])
            ->get()
            ->row_array();

        $this->db->where('id', $promotion['id'])->update($this->promotionTable, [
            'status' => 'finalized',
            'finalized_by' => $finalized_by,
            'finalized_at' => date('Y-m-d H:i:s'),
            'notes' => $notes,
            'eligible_count' => (int)$final_counts['eligible_total'],
            'issue_count' => (int)$final_counts['issue_total'],
            'promoted_count' => (int)$final_counts['promoted_total'],
            'retained_count' => (int)$final_counts['retained_total']
        ]);

        $activeYear = $this->get_active_year();
        if ($activeYear && (int)$activeYear['id'] !== (int)$year_id) {
            $currentStart = strtotime($year['start_date']);
            $activeStart = strtotime($activeYear['start_date']);
            if ($activeStart !== false && $currentStart !== false && $activeStart >= $currentStart) {
                $this->move_promoted_students_to_next_year($year, $activeYear);
            }
        }

        return [
            'status' => true,
            'message' => 'Promotion finalized successfully',
            'data' => $this->db->get_where($this->promotionTable, ['id' => $promotion['id']])->row_array()
        ];
    }

    private function get_or_create_promotion_cycle($year_id, $options = [])
    {
        $promotion = $this->get_active_promotion_cycle($year_id);
        if ($promotion) {
            return $promotion;
        }

        $latest = $this->get_latest_promotion_cycle_record($year_id);
        if ($latest) {
            return $latest;
        }

        $data = [
            'academic_year_id' => $year_id,
            'status' => 'draft',
            'initiated_by' => $options['initiated_by'] ?? null
        ];

        $this->db->insert($this->promotionTable, $data);
        $inserted = $this->db->insert_id();

        return $this->db->get_where($this->promotionTable, ['id' => $inserted])->row_array();
    }

    private function get_active_promotion_cycle($year_id)
    {
        return $this->db->select('*')
            ->from($this->promotionTable)
            ->where('academic_year_id', $year_id)
            ->where_in('status', ['draft', 'in_progress'])
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
    }

    private function get_latest_promotion_cycle_record($year_id)
    {
        return $this->db->select('*')
            ->from($this->promotionTable)
            ->where('academic_year_id', $year_id)
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();
    }

    private function seed_promotion_students($promotion_id, array $year, array $options = [])
    {
        $students = $this->db->select('users.user_id, users.full_name, users.email, users.status as account_status, users.program,
                sections.section_id, sections.section_name, sections.program as section_program, sections.year_level')
            ->from('users')
            ->join('sections', 'users.section_id = sections.section_id', 'left')
            ->where('users.role', 'student')
            ->group_start()
                ->where('sections.academic_year_id', $year['id'])
                ->or_where('sections.academic_year', $year['name'])
            ->group_end();

        $rows = $students->get()->result_array();
        if (empty($rows)) {
            return;
        }

        $issue_flags = $this->build_student_issue_map($year['name']);

        foreach ($rows as $row) {
            if (empty($row['section_id'])) {
                continue;
            }

            $currentYearLevel = is_numeric($row['year_level']) ? (int)$row['year_level'] : null;
            $issue_reasons = [];

            if (!empty($row['account_status']) && strtolower($row['account_status']) !== 'active') {
                $issue_reasons[] = 'Account status: ' . ucfirst($row['account_status']);
            }

            if (!empty($issue_flags[$row['user_id']])) {
                $issue_reasons[] = $issue_flags[$row['user_id']];
            }

            $evaluationStatus = empty($issue_reasons) ? 'eligible' : 'issue';

            $targetYearLevel = $currentYearLevel ? min($currentYearLevel + 1, 4) : null;

            $insert = [
                'promotion_id' => $promotion_id,
                'student_id' => $row['user_id'],
                'student_name' => $row['full_name'],
                'program' => $row['section_program'] ?? $row['program'],
                'current_year_level' => $currentYearLevel,
                'target_year_level' => $targetYearLevel,
                'target_section_name' => $this->build_target_section_name($row['section_name'], $targetYearLevel),
                'section_id' => $row['section_id'],
                'section_name' => $row['section_name'],
                'evaluation_status' => $evaluationStatus,
                'issue_reason' => empty($issue_reasons) ? null : implode('; ', $issue_reasons)
            ];

            $this->db->insert($this->promotionStudentsTable, $insert);
        }
    }

    private function move_promoted_students_to_next_year(array $sourceYear, array $targetYear)
    {
        $promotion = $this->db->select('*')
            ->from($this->promotionTable)
            ->where('academic_year_id', $sourceYear['id'])
            ->where('status', 'finalized')
            ->order_by('id', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$promotion) {
            return [
                'status' => true,
                'message' => 'No finalized promotion found for previous academic year'
            ];
        }

        $students = $this->db->get_where($this->promotionStudentsTable, [
            'promotion_id' => $promotion['id'],
            'decision_status' => 'promoted'
        ])->result_array();

        if (empty($students)) {
            return [
                'status' => true,
                'message' => 'No promoted students to move for the previous academic year',
                'data' => ['moved' => 0, 'promotion_id' => $promotion['id']]
            ];
        }

        $moved = 0;
        $failures = [];

        foreach ($students as $student) {
            $targetYearLevel = $student['target_year_level'];
            if (!$targetYearLevel && !empty($student['current_year_level'])) {
                $targetYearLevel = min((int)$student['current_year_level'] + 1, 4);
            }

            if (empty($targetYearLevel)) {
                $failures[] = [
                    'student_id' => $student['student_id'],
                    'student_name' => $student['student_name'],
                    'reason' => 'Target year level is missing'
                ];
                continue;
            }

            $targetSectionName = $student['target_section_name'] ?: $this->build_target_section_name(
                $student['section_name'],
                $targetYearLevel
            );

            $section = $this->resolve_target_section($student, $targetSectionName, $targetYearLevel, $targetYear);
            if (!$section) {
                $failures[] = [
                    'student_id' => $student['student_id'],
                    'student_name' => $student['student_name'],
                    'reason' => sprintf('No section found for %s year level %s in %s', $student['program'], $targetYearLevel, $targetYear['name'])
                ];
                continue;
            }

            $userUpdate = [
                'section_id' => $section['section_id'],
                'program' => $section['program'] ?? $student['program']
            ];

            $this->db->where('user_id', $student['student_id'])->update('users', $userUpdate);
            if ($this->db->affected_rows() === 0 && $this->db->error()['code'] !== 0) {
                $failures[] = [
                    'student_id' => $student['student_id'],
                    'student_name' => $student['student_name'],
                    'reason' => 'Failed to update student record'
                ];
                continue;
            }

            $this->db->where('id', $student['id'])->update($this->promotionStudentsTable, [
                'target_year_level' => $targetYearLevel,
                'target_section_id' => $section['section_id'],
                'target_section_name' => $section['section_name'],
                'target_academic_year_id' => $targetYear['id'],
                'target_academic_year_name' => $targetYear['name']
            ]);

            $moved++;
        }

        if (!empty($failures)) {
            return [
                'status' => false,
                'message' => 'Unable to move all promoted students to the next academic year',
                'data' => [
                    'moved' => $moved,
                    'promotion_id' => $promotion['id'],
                    'failures' => $failures
                ]
            ];
        }

        return [
            'status' => true,
            'message' => 'Promoted students moved to the next academic year',
            'data' => [
                'moved' => $moved,
                'promotion_id' => $promotion['id']
            ]
        ];
    }

    private function restore_promotion_decisions($promotionId, array $records)
    {
        if (empty($records)) {
            return;
        }

        $preserveFields = [
            'decision_status',
            'decision_notes',
            'decision_by',
            'decision_at',
            'target_year_level',
            'target_section_id',
            'target_section_name',
            'target_academic_year_id',
            'target_academic_year_name'
        ];

        foreach ($records as $record) {
            $updates = [];
            foreach ($preserveFields as $field) {
                if (array_key_exists($field, $record) && $record[$field] !== null && $record[$field] !== '') {
                    $updates[$field] = $record[$field];
                }
            }

            if (empty($updates)) {
                continue;
            }

            $this->db->where('promotion_id', $promotionId)
                ->where('student_id', $record['student_id'])
                ->update($this->promotionStudentsTable, $updates);
        }
    }

    private function map_preserved_decisions(array $records)
    {
        $map = [];
        foreach ($records as $record) {
            if (!empty($record['student_id'])) {
                $map[$record['student_id']] = $record;
            }
        }
        return $map;
    }

    private function apply_preserved_decisions(array $students, array $preservedMap)
    {
        if (empty($students) || empty($preservedMap)) {
            return $students;
        }

        $preserveFields = [
            'decision_status',
            'decision_notes',
            'decision_by',
            'decision_at',
            'target_year_level',
            'target_section_id',
            'target_section_name',
            'target_academic_year_id',
            'target_academic_year_name'
        ];

        foreach ($students as &$student) {
            $studentId = $student['student_id'] ?? null;

            if (!$studentId || !isset($preservedMap[$studentId])) {
                continue;
            }

            $record = $preservedMap[$studentId];
            foreach ($preserveFields as $field) {
                if (isset($record[$field]) && $record[$field] !== null && $record[$field] !== '') {
                    $student[$field] = $record[$field];
                }
            }
        }

        return $students;
    }

    private function resolve_target_section(array $student, ?string $targetSectionName, $targetYearLevel, array $targetYear)
    {
        if (empty($student['program']) || empty($targetYearLevel)) {
            return null;
        }

        $program = strtoupper(trim($student['program']));

        $builder = $this->db->select('*')
            ->from('sections')
            ->where('program', $program)
            ->where('year_level', $targetYearLevel);

        if ($this->sectionsArchiveFieldExists) {
            $builder->where('is_archived', 0);
        }

        $this->apply_year_filter($builder, $targetYear);

        if ($targetSectionName) {
            $section = $builder->where('section_name', $targetSectionName)->get()->row_array();
            if ($section) {
                return $section;
            }
        }

        // Fallback: try any section with matching program/year level in target year
        $fallbackBuilder = $this->db->select('*')
            ->from('sections')
            ->where('program', $program)
            ->where('year_level', $targetYearLevel);

        if ($this->sectionsArchiveFieldExists) {
            $fallbackBuilder->where('is_archived', 0);
        }

        $this->apply_year_filter($fallbackBuilder, $targetYear);

        return $fallbackBuilder->order_by('section_name', 'ASC')->limit(1)->get()->row_array();
    }

    private function build_target_section_name(?string $currentSectionName, $targetYearLevel)
    {
        if (empty($currentSectionName) || empty($targetYearLevel)) {
            return null;
        }

        if (preg_match('/\d+/', $currentSectionName, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0];
            $start = $match[1];
            $length = strlen($match[0]);
            return substr($currentSectionName, 0, $start) . $targetYearLevel . substr($currentSectionName, $start + $length);
        }

        return trim($currentSectionName . ' ' . $targetYearLevel);
    }

    private function build_student_issue_map($year_name)
    {
        $builder = $this->db->select('ce.student_id,
                SUM(CASE WHEN ce.status != "active" THEN 1 ELSE 0 END) as inactive_classes', false)
            ->from('classroom_enrollments ce')
            ->join('classrooms c', 'ce.classroom_id = c.id', 'inner')
            ->join('sections s', 'c.section_id = s.section_id', 'left')
            ->where('c.school_year', $year_name)
            ->group_by('ce.student_id');

        $rows = $builder->get()->result_array();

        $map = [];
        foreach ($rows as $row) {
            if ((int)$row['inactive_classes'] > 0) {
                $map[$row['student_id']] = 'Has ' . $row['inactive_classes'] . ' inactive/dropped classes';
            }
        }

        return $map;
    }

    private function auto_create_sections_for_year(array $year)
    {
        if (empty($year)) {
            return null;
        }

        $this->load->model('Section_model');
        $this->load->model('Program_model');

        $programs = $this->Program_model->get_active();
        if (empty($programs)) {
            return ['message' => 'No active programs available to auto-create sections'];
        }

        $yearLevels = [1, 2, 3, 4];
        $sectionSuffixes = range('A', 'K'); // A-K
        $created = [];
        $totalCreated = 0;

        foreach ($programs as $program) {
            $programCode = strtoupper($program['code'] ?? $program['program_code'] ?? $program['name'] ?? '');
            if (empty($programCode)) {
                continue;
            }

            foreach ($yearLevels as $yearLevel) {
                foreach ($sectionSuffixes as $suffix) {
                    $sectionName = sprintf('%s %d%s', $programCode, $yearLevel, $suffix);

                    $exists = $this->db->get_where('sections', [
                        'section_name' => $sectionName,
                        'program' => $programCode,
                        'academic_year' => $year['name'],
                        'semester' => '1st'
                    ])->row_array();

                    if ($exists) {
                        continue;
                    }

                    $sectionData = [
                        'section_name' => $sectionName,
                        'program' => $programCode,
                        'year_level' => $yearLevel,
                        'semester' => '1st',
                        'academic_year' => $year['name'],
                        'academic_year_id' => $year['id'],
                        'created_at' => date('Y-m-d H:i:s')
                    ];

                    $this->db->insert('sections', $sectionData);
                    $created[$programCode]['sections'][] = $sectionName;
                    $totalCreated++;
                }
            }
        }

        return [
            'message' => 'Sections auto-created successfully',
            'programs' => $created,
            'total_sections_created' => $totalCreated
        ];
    }
}

