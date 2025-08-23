<?php
require_once(APPPATH . 'controllers/api/BaseController.php');

defined('BASEPATH') OR exit('No direct script access allowed');

class TwoFactor extends BaseController {
    
    public function __construct() {
        parent::__construct();
        $this->load->library('TwoFactorAuth');
        $this->load->model('User_model');
        $this->load->helper(['response', 'audit']);
    }
    
    /**
     * Setup 2FA for a user
     * POST /api/2fa/setup
     */
    public function setup() {
        try {
            // Require authentication
            $user_data = require_auth($this);
            if (!$user_data) {
                return; // Error response already sent
            }
            
            $user_id = $user_data['user_id'];
            
            // Check if 2FA is already enabled
            if ($this->twofactorauth->is_2fa_enabled($user_id)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => '2FA is already enabled for this account'
                    ]));
                return;
            }
            
            // Generate new secret
            $secret = $this->twofactorauth->generate_secret();
            
            // Get user email for QR code
            $user = $this->User_model->get_by_id($user_id);
            if (!$user) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'User not found'
                    ]));
                return;
            }
            
            // Generate QR code URL
            $qr_url = $this->twofactorauth->get_qr_code_url($user['email'], $secret);
            
            // Generate backup codes
            $backup_codes = $this->twofactorauth->generate_backup_codes($user_id);
            
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => '2FA setup initiated',
                    'data' => [
                        'secret' => $secret,
                        'qr_code_url' => $qr_url,
                        'backup_codes' => $backup_codes,
                        'instructions' => [
                            '1. Install Google Authenticator, Authy, or similar app',
                            '2. Scan the QR code or enter the secret manually',
                            '3. Enter the 6-digit code from your app to verify',
                            '4. Save your backup codes in a secure location'
                        ]
                    ]
                ]));
                
        } catch (Exception $e) {
            log_message('error', '2FA setup error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Internal server error during 2FA setup'
                ]));
        }
    }
    
    /**
     * Verify and enable 2FA
     * POST /api/2fa/verify
     */
    public function verify() {
        try {
            // Require authentication
            $user_data = require_auth($this);
            if (!$user_data) {
                return; // Error response already sent
            }
            
            $data = json_decode(file_get_contents('php://input'));
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Invalid JSON format'
                    ]));
                return;
            }
            
            $user_id = $user_data['user_id'];
            $code = isset($data->code) ? trim($data->code) : null;
            $secret = isset($data->secret) ? trim($data->secret) : null;
            
            if (empty($code) || empty($secret)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Code and secret are required'
                    ]));
                return;
            }
            
            // Verify the code
            if (!$this->twofactorauth->verify_code($secret, $code)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Invalid 2FA code. Please try again.'
                    ]));
                return;
            }
            
            // Enable 2FA for the user
            if ($this->twofactorauth->enable_2fa($user_id, $secret)) {
                // Log the event
                log_audit_event(
                    '2FA_ENABLED',
                    'SECURITY',
                    "Two-factor authentication enabled for user: {$user_id}",
                    [
                        'ip_address' => $this->input->ip_address(),
                        'user_agent' => $this->input->user_agent()
                    ]
                );
                
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => true,
                        'message' => '2FA has been enabled successfully',
                        'data' => [
                            'two_factor_enabled' => true,
                            'enabled_at' => date('Y-m-d H:i:s')
                        ]
                    ]));
            } else {
                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Failed to enable 2FA. Please try again.'
                    ]));
            }
            
        } catch (Exception $e) {
            log_message('error', '2FA verify error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Internal server error during 2FA verification'
                ]));
        }
    }
    
    /**
     * Disable 2FA for a user
     * POST /api/2fa/disable
     */
    public function disable() {
        try {
            // Require authentication
            $user_data = require_auth($this);
            if (!$user_data) {
                return; // Error response already sent
            }
            
            $data = json_decode(file_get_contents('php://input'));
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Invalid JSON format'
                    ]));
                return;
            }
            
            $user_id = $user_data['user_id'];
            $code = isset($data->code) ? trim($data->code) : null;
            
            if (empty($code)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => '2FA code is required to disable 2FA'
                    ]));
                return;
            }
            
            // Get user's 2FA secret
            $secret = $this->twofactorauth->get_user_secret($user_id);
            if (!$secret) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => '2FA is not enabled for this account'
                    ]));
                return;
            }
            
            // Verify the code before disabling
            if (!$this->twofactorauth->verify_code($secret, $code)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Invalid 2FA code. Cannot disable 2FA.'
                    ]));
                return;
            }
            
            // Disable 2FA
            if ($this->twofactorauth->disable_2fa($user_id)) {
                // Log the event
                log_audit_event(
                    '2FA_DISABLED',
                    'SECURITY',
                    "Two-factor authentication disabled for user: {$user_id}",
                    [
                        'ip_address' => $this->input->ip_address(),
                        'user_agent' => $this->input->user_agent()
                    ]
                );
                
                $this->output
                    ->set_status_header(200)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => true,
                        'message' => '2FA has been disabled successfully'
                    ]));
            } else {
                $this->output
                    ->set_status_header(500)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Failed to disable 2FA. Please try again.'
                    ]));
            }
            
        } catch (Exception $e) {
            log_message('error', '2FA disable error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Internal server error during 2FA disable'
                ]));
        }
    }
    
    /**
     * Get 2FA status for current user
     * GET /api/2fa/status
     */
    public function status() {
        try {
            // Require authentication
            $user_data = require_auth($this);
            if (!$user_data) {
                return; // Error response already sent
            }
            
            $user_id = $user_data['user_id'];
            $is_enabled = $this->twofactorauth->is_2fa_enabled($user_id);
            
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'data' => [
                        'two_factor_enabled' => $is_enabled,
                        'user_id' => $user_id
                    ]
                ]));
                
        } catch (Exception $e) {
            log_message('error', '2FA status error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Internal server error while getting 2FA status'
                ]));
        }
    }
    
    /**
     * Verify 2FA code during login
     * POST /api/2fa/login-verify
     */
    public function login_verify() {
        try {
            $data = json_decode(file_get_contents('php://input'));
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Invalid JSON format'
                    ]));
                return;
            }
            
            $email = isset($data->email) ? trim($data->email) : null;
            $code = isset($data->code) ? trim($data->code) : null;
            
            if (empty($email) || empty($code)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Email and 2FA code are required'
                    ]));
                return;
            }
            
            // Get user
            $user = $this->User_model->get_by_email($email);
            if (!$user) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'User not found'
                    ]));
                return;
            }
            
            // Check if 2FA is enabled
            if (!$this->twofactorauth->is_2fa_enabled($user['user_id'])) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => '2FA is not enabled for this account'
                    ]));
                return;
            }
            
            // Get user's 2FA secret
            $secret = $this->twofactorauth->get_user_secret($user['user_id']);
            if (!$secret) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => '2FA secret not found'
                    ]));
                return;
            }
            
            // Verify the code
            if (!$this->twofactorauth->verify_code($secret, $code)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Invalid 2FA code'
                    ]));
                return;
            }
            
            // 2FA verification successful
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => '2FA verification successful',
                    'data' => [
                        'user_id' => $user['user_id'],
                        'email' => $user['email'],
                        'two_factor_verified' => true
                    ]
                ]));
                
        } catch (Exception $e) {
            log_message('error', '2FA login verify error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Internal server error during 2FA verification'
                ]));
        }
    }
    
    /**
     * Use backup code during login
     * POST /api/2fa/backup-code
     */
    public function backup_code() {
        try {
            $data = json_decode(file_get_contents('php://input'));
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Invalid JSON format'
                    ]));
                return;
            }
            
            $email = isset($data->email) ? trim($data->email) : null;
            $backup_code = isset($data->backup_code) ? trim($data->backup_code) : null;
            
            if (empty($email) || empty($backup_code)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Email and backup code are required'
                    ]));
                return;
            }
            
            // Get user
            $user = $this->User_model->get_by_email($email);
            if (!$user) {
                $this->output
                    ->set_status_header(404)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'User not found'
                    ]));
                return;
            }
            
            // Verify backup code
            if (!$this->twofactorauth->verify_backup_code($user['user_id'], $backup_code)) {
                $this->output
                    ->set_status_header(400)
                    ->set_content_type('application/json')
                    ->set_output(json_encode([
                        'status' => false,
                        'message' => 'Invalid backup code'
                    ]));
                return;
            }
            
            // Backup code verification successful
            $this->output
                ->set_status_header(200)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'Backup code verification successful',
                    'data' => [
                        'user_id' => $user['user_id'],
                        'email' => $user['email'],
                        'backup_code_used' => true
                    ]
                ]));
                
        } catch (Exception $e) {
            log_message('error', '2FA backup code error: ' . $e->getMessage());
            $this->output
                ->set_status_header(500)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Internal server error during backup code verification'
                ]));
        }
    }
    
    /**
     * Handle OPTIONS preflight requests (CORS)
     */
    public function options() {
        // The BaseController constructor handles CORS and exits for OPTIONS requests.
    }
}
