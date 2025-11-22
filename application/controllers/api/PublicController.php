<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class PublicController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Program_model');
        $this->load->helper('response');
    }

    /**
     * Get all active programs (public endpoint for registration forms)
     * GET /api/programs
     * 
     * No authentication required - safe to expose active program list
     */
    public function programs_get() {
        try {
            $programs = $this->Program_model->get_active();
            
            // Format response for frontend compatibility
            $formatted_programs = array_map(function($program) {
                return [
                    'program_id' => $program['program_id'],
                    'code' => $program['code'],
                    'name' => $program['name'],
                    'description' => $program['description'] ?? null,
                    'program' => $program['code'] // Alias for backward compatibility
                ];
            }, $programs);
            
            return json_response(true, 'Active programs retrieved successfully', $formatted_programs);
        } catch (Exception $e) {
            log_message('error', 'Public programs_get error: ' . $e->getMessage());
            return json_response(false, 'Failed to retrieve programs', null, 500);
        }
    }
}

