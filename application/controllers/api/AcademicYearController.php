<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class AcademicYearController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model([
            'AcademicYear_model',
            'ProgramYearLevel_model',
            'Section_model'
        ]);
        $this->load->helper(['response', 'auth']);
    }

    public function active_get()
    {
        $user = require_role($this, ['admin', 'chairperson']);
        if (!$user) {
            return;
        }

        $filters = $this->program_scope_filters($user);
        $active = $this->AcademicYear_model->get_active_year($filters);

        if (!$active) {
            return json_response(false, 'No active academic year found', null, 404);
        }

        return json_response(true, 'Active academic year retrieved successfully', $active);
    }

    public function index_get()
    {
        $user = require_role($this, ['admin', 'chairperson']);
        if (!$user) {
            return;
        }

        $status = $this->input->get('status');
        $include_archived = filter_var($this->input->get('include_archived'), FILTER_VALIDATE_BOOLEAN);

        $filters = $this->program_scope_filters($user);
        if (!empty($status)) {
            $filters['status'] = array_map('trim', explode(',', $status));
        }
        $filters['include_archived'] = $include_archived;

        $years = $this->AcademicYear_model->get_years($filters);
        return json_response(true, 'Academic years retrieved successfully', $years);
    }

    public function archived_get()
    {
        $user = require_role($this, ['admin', 'chairperson']);
        if (!$user) {
            return;
        }

        $filters = $this->program_scope_filters($user);
        $filters['status'] = ['archived'];
        $filters['include_archived'] = true;

        $years = $this->AcademicYear_model->get_years($filters);
        return json_response(true, 'Archived academic years retrieved successfully', $years);
    }

    public function show_get($year_id = null)
    {
        $user = require_role($this, ['admin', 'chairperson']);
        if (!$user) {
            return;
        }

        if (empty($year_id) || !is_numeric($year_id)) {
            return json_response(false, 'Academic year ID is required', null, 400);
        }

        $year = $this->AcademicYear_model->get_year($year_id, $this->program_scope_filters($user));
        if (!$year) {
            return json_response(false, 'Academic year not found', null, 404);
        }

        return json_response(true, 'Academic year retrieved successfully', $year);
    }

    public function create_post()
    {
        $user = require_admin($this);
        if (!$user) {
            return;
        }

        $payloadResult = $this->decode_request_body();
        if ($payloadResult['error']) {
            return json_response(false, 'Invalid JSON payload', null, 400);
        }
        $payload = $payloadResult['data'];

        $required = ['name', 'start_date', 'end_date', 'sem1_start_date', 'sem1_end_date', 'sem2_start_date', 'sem2_end_date'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                return json_response(false, "$field is required", null, 422);
            }
        }

        if (!$this->validate_date_order($payload)) {
            return;
        }

        $options = [
            'auto_activate' => !empty($payload['auto_activate']),
            'activated_by' => $user['user_id'],
            'activation_notes' => $payload['activation_notes'] ?? null
        ];

        $result = $this->AcademicYear_model->create_year(array_merge($payload, [
            'created_by' => $user['user_id']
        ]), $options);

        $status_code = $result['status'] ? 201 : 400;
        return json_response($result['status'], $result['message'], $result['data'] ?? null, $status_code);
    }

    public function activate_post($year_id = null)
    {
        $user = require_admin($this);
        if (!$user) {
            return;
        }

        if (empty($year_id) || !is_numeric($year_id)) {
            return json_response(false, 'Academic year ID is required', null, 400);
        }

        $payloadResult = $this->decode_request_body();
        if ($payloadResult['error']) {
            return json_response(false, 'Invalid JSON payload', null, 400);
        }
        $payload = $payloadResult['data'];

        $result = $this->AcademicYear_model->activate_year($year_id, $user['user_id'], [
            'notes' => $payload['notes'] ?? null
        ]);

        $status_code = $result['status'] ? 200 : 400;
        return json_response($result['status'], $result['message'], $result['data'] ?? null, $status_code);
    }

    public function close_post($year_id = null)
    {
        $user = require_admin($this);
        if (!$user) {
            return;
        }

        if (empty($year_id) || !is_numeric($year_id)) {
            return json_response(false, 'Academic year ID is required', null, 400);
        }

        $payloadResult = $this->decode_request_body();
        if ($payloadResult['error']) {
            return json_response(false, 'Invalid JSON payload', null, 400);
        }
        $payload = $payloadResult['data'];

        $lock_data = isset($payload['lock_data']) ? (bool)$payload['lock_data'] : true;
        $result = $this->AcademicYear_model->close_year($year_id, $user['user_id'], $payload['notes'] ?? null, $lock_data);

        $status_code = $result['status'] ? 200 : 400;
        return json_response($result['status'], $result['message'], $result['data'] ?? null, $status_code);
    }

    public function promotion_get($year_id = null)
    {
        $user = require_role($this, ['admin', 'chairperson']);
        if (!$user) {
            return;
        }

        if (empty($year_id) || !is_numeric($year_id)) {
            return json_response(false, 'Academic year ID is required', null, 400);
        }

        $filters = $this->program_scope_filters($user);
        if ($user['role'] === 'admin' && $this->input->get('program')) {
            $filters['program'] = strtoupper($this->input->get('program'));
        }

        $options = array_merge($filters, [
            'initiated_by' => $user['user_id'],
            'force_refresh' => filter_var($this->input->get('refresh'), FILTER_VALIDATE_BOOLEAN)
        ]);

        $result = $this->AcademicYear_model->get_promotion_snapshot($year_id, $options);
        $status_code = $result['status'] ? 200 : 400;
        return json_response($result['status'], $result['message'], $result['data'] ?? null, $status_code);
    }

    public function promotion_student_patch($year_id = null, $student_id = null)
    {
        $user = require_role($this, ['admin', 'chairperson']);
        if (!$user) {
            return;
        }

        if (empty($year_id) || empty($student_id)) {
            return json_response(false, 'Academic year ID and student ID are required', null, 400);
        }

        $payloadResult = $this->decode_request_body();
        if ($payloadResult['error']) {
            return json_response(false, 'Invalid JSON payload', null, 400);
        }

        $payloadData = $payloadResult['data'];
        $payloadData['updated_by'] = $user['user_id'];
        $result = $this->AcademicYear_model->update_promotion_student($year_id, $student_id, $payloadData);
        $status_code = $result['status'] ? 200 : 400;
        return json_response($result['status'], $result['message'], $result['data'] ?? null, $status_code);
    }

    public function promotion_finalize_post($year_id = null)
    {
        $user = require_admin($this);
        if (!$user) {
            return;
        }

        if (empty($year_id) || !is_numeric($year_id)) {
            return json_response(false, 'Academic year ID is required', null, 400);
        }

        $payloadResult = $this->decode_request_body();
        if ($payloadResult['error']) {
            return json_response(false, 'Invalid JSON payload', null, 400);
        }

        $payload = $payloadResult['data'];
        $notes = $payload['notes'] ?? null;
        $result = $this->AcademicYear_model->finalize_promotion($year_id, $user['user_id'], $notes);
        $status_code = $result['status'] ? 200 : 400;
        return json_response($result['status'], $result['message'], $result['data'] ?? null, $status_code);
    }

    public function year_levels_get()
    {
        $user = require_role($this, ['admin', 'chairperson']);
        if (!$user) {
            return;
        }

        $include_archived = filter_var($this->input->get('include_archived'), FILTER_VALIDATE_BOOLEAN);
        $with_usage = filter_var($this->input->get('with_usage'), FILTER_VALIDATE_BOOLEAN);

        $filters = [
            'include_archived' => $include_archived,
            'with_usage' => $with_usage
        ];

        $scope = $this->program_scope_filters($user);
        if (!empty($scope['program'])) {
            $filters['program'] = $scope['program'];
        } elseif ($this->input->get('program')) {
            $filters['program'] = strtoupper($this->input->get('program'));
        }

        $levels = $this->ProgramYearLevel_model->get_all($filters);
        return json_response(true, 'Year levels retrieved successfully', $levels);
    }

    public function year_levels_post()
    {
        $user = require_admin($this);
        if (!$user) {
            return;
        }

        $payloadResult = $this->decode_request_body();
        if ($payloadResult['error']) {
            return json_response(false, 'Invalid JSON payload', null, 400);
        }

        $payload = $payloadResult['data'];
        $required = ['program_code', 'label'];
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                return json_response(false, "$field is required", null, 422);
            }
        }

        $result = $this->ProgramYearLevel_model->create(array_merge($payload, [
            'created_by' => $user['user_id']
        ]));

        $status_code = $result['status'] ? 201 : 400;
        return json_response($result['status'], $result['message'], $result['data'] ?? null, $status_code);
    }

    public function year_level_archive_post($level_id = null)
    {
        $user = require_admin($this);
        if (!$user) {
            return;
        }

        if (empty($level_id)) {
            return json_response(false, 'Year level ID is required', null, 400);
        }

        $result = $this->ProgramYearLevel_model->set_archive_status($level_id, true, $user['user_id']);
        $status_code = $result['status'] ? 200 : 400;
        return json_response($result['status'], $result['message'], $result['data'] ?? null, $status_code);
    }

    public function year_level_unarchive_post($level_id = null)
    {
        $user = require_admin($this);
        if (!$user) {
            return;
        }

        if (empty($level_id)) {
            return json_response(false, 'Year level ID is required', null, 400);
        }

        $result = $this->ProgramYearLevel_model->set_archive_status($level_id, false, $user['user_id']);
        $status_code = $result['status'] ? 200 : 400;
        return json_response($result['status'], $result['message'], $result['data'] ?? null, $status_code);
    }

    public function sections_management_get()
    {
        $user = require_role($this, ['admin', 'chairperson']);
        if (!$user) {
            return;
        }

        $filters = [
            'include_archived' => filter_var($this->input->get('include_archived'), FILTER_VALIDATE_BOOLEAN)
        ];

        $scope = $this->program_scope_filters($user);
        if (!empty($scope['program'])) {
            $filters['program'] = $scope['program'];
        } elseif ($this->input->get('program')) {
            $filters['program'] = strtoupper($this->input->get('program'));
        }

        $sections = $this->Section_model->get_management_overview($filters);
        return json_response(true, 'Sections retrieved successfully', $sections);
    }

    public function section_archive_post($section_id = null)
    {
        $user = require_admin($this);
        if (!$user) {
            return;
        }

        if (empty($section_id)) {
            return json_response(false, 'Section ID is required', null, 400);
        }

        $payloadResult = $this->decode_request_body();
        if ($payloadResult['error']) {
            return json_response(false, 'Invalid JSON payload', null, 400);
        }

        $payload = $payloadResult['data'];
        $success = $this->Section_model->set_archive_status($section_id, true, $payload['reason'] ?? null);
        if (!$success) {
            return json_response(false, 'Failed to archive section or it is already archived', null, 400);
        }

        return json_response(true, 'Section archived successfully');
    }

    public function section_unarchive_post($section_id = null)
    {
        $user = require_admin($this);
        if (!$user) {
            return;
        }

        if (empty($section_id)) {
            return json_response(false, 'Section ID is required', null, 400);
        }

        $success = $this->Section_model->set_archive_status($section_id, false);
        if (!$success) {
            return json_response(false, 'Failed to restore section or it is already active', null, 400);
        }

        return json_response(true, 'Section restored successfully');
    }

    private function program_scope_filters($user)
    {
        $filters = [];
        if (($user['role'] ?? null) === 'chairperson') {
            $filters['program'] = $user['program'] ?? $user['assigned_program'] ?? null;
            if (!empty($filters['program'])) {
                $filters['program'] = strtoupper($filters['program']);
            }
        }
        return $filters;
    }

    private function validate_date_order($payload)
    {
        $start = strtotime($payload['start_date']);
        $end = strtotime($payload['end_date']);
        $sem1_start = strtotime($payload['sem1_start_date']);
        $sem1_end = strtotime($payload['sem1_end_date']);
        $sem2_start = strtotime($payload['sem2_start_date']);
        $sem2_end = strtotime($payload['sem2_end_date']);

        if ($start > $end) {
            json_response(false, 'Start date must be before end date', null, 422);
            return false;
        }

        if ($sem1_start > $sem1_end) {
            json_response(false, 'Semester 1 start date must be before its end date', null, 422);
            return false;
        }

        if ($sem2_start > $sem2_end) {
            json_response(false, 'Semester 2 start date must be before its end date', null, 422);
            return false;
        }

        if ($sem1_end > $sem2_start) {
            json_response(false, 'Semester 1 must end before Semester 2 begins', null, 422);
            return false;
        }

        return true;
    }
    private function decode_request_body()
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return ['data' => [], 'error' => null];
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['data' => null, 'error' => json_last_error_msg()];
        }

        return ['data' => $data, 'error' => null];
    }
}

