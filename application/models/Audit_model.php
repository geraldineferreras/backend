<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Audit_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Create audit log entry (compatible with audit_helper.php)
     * @param array $audit_data Audit data array
     * @return int|false The audit log ID on success, false on failure
     */
    public function create_log($audit_data)
    {
        try {
            $log_data = [
                'user_id' => $audit_data['user_id'] ?? 'system',
                'user_name' => $audit_data['user_name'] ?? 'Unknown User',
                'user_role' => $audit_data['user_role'] ?? 'system',
                'action_type' => $audit_data['action_type'] ?? $audit_data['action'] ?? 'UNKNOWN',
                'module' => $audit_data['module'] ?? 'UNKNOWN',
                'table_name' => $audit_data['table_name'] ?? null,
                'record_id' => $audit_data['record_id'] ?? null,
                'details' => isset($audit_data['table_name']) || isset($audit_data['record_id']) ? json_encode([
                    'table_name' => $audit_data['table_name'] ?? null,
                    'record_id' => $audit_data['record_id'] ?? null,
                    'description' => $audit_data['details'] ?? $audit_data['description'] ?? '',
                    'additional_data' => array_diff_key($audit_data, array_flip(['user_id', 'user_name', 'user_role', 'action_type', 'action', 'module', 'details', 'description', 'table_name', 'record_id', 'ip_address']))
                ]) : null,
                'ip_address' => $audit_data['ip_address'] ?? $this->input->ip_address(),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('audit_logs', $log_data);
            return $this->db->insert_id();
        } catch (Exception $e) {
            log_message('error', 'Audit log failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Log an audit event
     * @param string $action The action performed
     * @param string $module The module where the action occurred
     * @param string $description Description of the action
     * @param array $details Additional details (optional)
     * @return int|false The audit log ID on success, false on failure
     */
    public function log_event($action, $module, $description, $details = null)
    {
        try {
            $user_id = $this->session->userdata('user_id') ?? 'system';
            $user_role = $this->session->userdata('role') ?? 'system';
            $user_name = $this->session->userdata('full_name') ?? 'Unknown User';
            
            $audit_data = [
                'user_id' => $user_id,
                'user_name' => $user_name,
                'user_role' => $user_role,
                'action_type' => $action,
                'module' => $module,
                'details' => json_encode([
                    'description' => $description,
                    'additional_data' => $details
                ]),
                'ip_address' => $this->input->ip_address(),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->insert('audit_logs', $audit_data);
            return $this->db->insert_id();
        } catch (Exception $e) {
            log_message('error', 'Audit log failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get audit logs with filtering and pagination
     * @param array $filters Filter criteria
     * @param int $limit Number of records to return
     * @param int $offset Starting offset
     * @return array Array of audit logs
     */
    public function get_audit_logs($filters = [], $limit = 50, $offset = 0)
    {
        $this->db->select('audit_logs.*')
            ->from('audit_logs')
            ->order_by('audit_logs.created_at', 'DESC');

        // Apply filters
        if (isset($filters['user_id'])) {
            $this->db->where('audit_logs.user_id', $filters['user_id']);
        }
        if (isset($filters['user_role'])) {
            $this->db->where('audit_logs.user_role', $filters['user_role']);
        }
        if (isset($filters['action'])) {
            $this->db->where('audit_logs.action_type', $filters['action']);
        }
        if (isset($filters['module'])) {
            $this->db->where('audit_logs.module', $filters['module']);
        }
        if (isset($filters['date_from'])) {
            $this->db->where('audit_logs.created_at >=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $this->db->where('audit_logs.created_at <=', $filters['date_to']);
        }

        // Apply pagination
        if ($limit > 0) {
            $this->db->limit($limit, $offset);
        }

        return $this->db->get()->result_array();
    }

    /**
     * Get audit log by ID
     * @param int $log_id The audit log ID
     * @return array|false The audit log data or false if not found
     */
    public function get_audit_log($log_id)
    {
        return $this->db->select('audit_logs.*')
            ->from('audit_logs')
            ->where('audit_logs.log_id', $log_id)
            ->get()->row_array();
    }

    /**
     * Get unique modules from audit logs
     * @return array Array of unique modules
     */
    public function get_modules()
    {
        return $this->db->select('module')
            ->distinct()
            ->from('audit_logs')
            ->where('module IS NOT NULL')
            ->where('module !=', '')
            ->order_by('module', 'ASC')
            ->get()->result_array();
    }

    /**
     * Get unique roles from audit logs
     * @return array Array of unique roles
     */
    public function get_roles()
    {
        return $this->db->select('user_role')
            ->distinct()
            ->from('audit_logs')
            ->where('user_role IS NOT NULL')
            ->where('user_role !=', '')
            ->order_by('user_role', 'ASC')
            ->get()->result_array();
    }

    /**
     * Get audit statistics
     * @param array $filters Filter criteria
     * @return array Statistics data
     */
    public function get_statistics($filters = [])
    {
        $this->db->select('
            COUNT(*) as total_logs,
            COUNT(DISTINCT user_id) as unique_users,
            COUNT(DISTINCT module) as unique_modules,
            COUNT(DISTINCT action_type) as unique_actions
        ')
        ->from('audit_logs');

        // Apply filters
        if (isset($filters['date_from'])) {
            $this->db->where('created_at >=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $this->db->where('created_at <=', $filters['date_to']);
        }

        $stats = $this->db->get()->row_array();

        // Get top actions
        $top_actions = $this->db->select('action_type, COUNT(*) as count')
            ->from('audit_logs')
            ->group_by('action_type')
            ->order_by('count', 'DESC')
            ->limit(10)
            ->get()->result_array();

        // Get top modules
        $top_modules = $this->db->select('module, COUNT(*) as count')
            ->from('audit_logs')
            ->where('module IS NOT NULL')
            ->where('module !=', '')
            ->group_by('module')
            ->order_by('count', 'DESC')
            ->limit(10)
            ->get()->result_array();

        return [
            'overall' => $stats,
            'top_actions' => $top_actions,
            'top_modules' => $top_modules
        ];
    }

    /**
     * Export audit logs to CSV
     * @param array $filters Filter criteria
     * @return string CSV content
     */
    public function export_csv($filters = [])
    {
        $logs = $this->get_audit_logs($filters, 0, 0); // Get all logs

        $csv = "Log ID,User ID,User Name,User Role,Action Type,Module,Table Name,Record ID,Details,IP Address,Created At\n";

        foreach ($logs as $log) {
            $details = json_decode($log['details'], true);
            $description = $details['description'] ?? '';
            
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $log['log_id'],
                $log['user_id'],
                $log['user_name'] ?? 'Unknown',
                $log['user_role'],
                $log['action_type'],
                $log['module'],
                $log['table_name'] ?? '',
                $log['record_id'] ?? '',
                str_replace('"', '""', $description),
                $log['ip_address'],
                $log['created_at']
            );
        }

        return $csv;
    }

    /**
     * Clean old audit logs (older than specified days)
     * @param int $days Number of days to keep
     * @return int Number of deleted records
     */
    public function clean_old_logs($days = 365)
    {
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $this->db->where('created_at <', $cutoff_date);
        $this->db->delete('audit_logs');
        
        return $this->db->affected_rows();
    }
}
