<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Two-Factor Authentication Library
 * Implements TOTP (Time-based One-Time Password) algorithm
 * Compatible with Google Authenticator, Authy, and other TOTP apps
 */
class TwoFactorAuth {
    
    private $CI;
    private $secret_length = 32;
    private $time_step = 30; // 30 seconds
    private $code_length = 6;
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->database();
    }
    
    /**
     * Generate a new secret key for 2FA
     */
    public function generate_secret($length = null) {
        $length = $length ?: $this->secret_length;
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $secret;
    }
    
    /**
     * Generate QR code URL for authenticator apps
     */
    public function get_qr_code_url($email, $secret, $issuer = 'SCMS') {
        $url = 'otpauth://totp/' . urlencode($issuer . ':' . $email) . '?secret=' . $secret . '&issuer=' . urlencode($issuer);
        return $url;
    }
    
    /**
     * Validate email format and check if user exists
     */
    public function validate_email($email) {
        // Basic email format validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Invalid email format'];
        }
        
        // Check if user exists with this email
        $user = $this->CI->db->where('email', $email)->get('users')->row_array();
        if (!$user) {
            return ['valid' => false, 'message' => 'No user found with this email address'];
        }
        
        return ['valid' => true, 'user' => $user];
    }
    
    /**
     * Verify TOTP code
     */
    public function verify_code($secret, $code, $time_step = null) {
        $time_step = $time_step ?: $this->time_step;
        
        // Check current time step and adjacent ones (for clock skew)
        $current_time = floor(time() / $time_step);
        
        for ($i = -1; $i <= 1; $i++) {
            $time = $current_time + $i;
            $generated_code = $this->generate_totp($secret, $time);
            
            if ($this->timing_safe_equals($generated_code, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate TOTP code for a specific time
     */
    private function generate_totp($secret, $time) {
        // Convert secret to binary
        $secret_binary = $this->base32_decode($secret);
        
        // Pack time into 8 bytes (big-endian)
        $time_binary = pack('N*', 0) . pack('N*', $time);
        
        // Generate HMAC-SHA1
        $hash = hash_hmac('sha1', $time_binary, $secret_binary, true);
        
        // Get offset from last 4 bits of hash
        $offset = ord($hash[19]) & 0xf;
        
        // Generate 4-byte code from offset
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, $this->code_length);
        
        // Pad with leading zeros
        return str_pad($code, $this->code_length, '0', STR_PAD_LEFT);
    }
    
    /**
     * Base32 decode (RFC 4648)
     */
    private function base32_decode($secret) {
        $base32_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32_chars_flipped = array_flip(str_split($base32_chars));
        
        $padding = strlen($secret) % 8;
        if ($padding !== 0) {
            $secret .= str_repeat('=', 8 - $padding);
        }
        
        $secret = strtoupper($secret);
        $secret = str_replace('=', '', $secret);
        
        $binary = '';
        $len = strlen($secret);
        
        for ($i = 0; $i < $len; $i += 8) {
            $chunk = substr($secret, $i, 8);
            $chunk_len = strlen($chunk);
            
            $bits = 0;
            $bit_count = 0;
            
            for ($j = 0; $j < $chunk_len; $j++) {
                $bits = $bits << 5;
                $bits = $bits | $base32_chars_flipped[$chunk[$j]];
                $bit_count += 5;
            }
            
            $bit_count -= 8;
            while ($bit_count >= 0) {
                $binary .= chr(($bits >> $bit_count) & 0xFF);
                $bit_count -= 8;
            }
        }
        
        return $binary;
    }
    
    /**
     * Timing-safe string comparison
     */
    private function timing_safe_equals($known_string, $user_string) {
        if (function_exists('hash_equals')) {
            return hash_equals($known_string, $user_string);
        }
        
        $known_length = strlen($known_string);
        $user_length = strlen($user_string);
        
        if ($known_length !== $user_length) {
            return false;
        }
        
        $result = 0;
        for ($i = 0; $i < $known_length; $i++) {
            $result |= ord($known_string[$i]) ^ ord($user_string[$i]);
        }
        
        return $result === 0;
    }
    
    /**
     * Enable 2FA for a user
     */
    public function enable_2fa($user_id, $secret) {
        $data = [
            'two_factor_enabled' => 1,
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->CI->db->where('user_id', $user_id)->update('users', $data);
    }
    
    /**
     * Disable 2FA for a user
     */
    public function disable_2fa($user_id) {
        $data = [
            'two_factor_enabled' => 0,
            'two_factor_secret' => null,
            'two_factor_enabled_at' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->CI->db->where('user_id', $user_id)->update('users', $data);
    }
    
    /**
     * Check if user has 2FA enabled
     */
    public function is_2fa_enabled($user_id) {
        $user = $this->CI->db->where('user_id', $user_id)
                             ->where('two_factor_enabled', 1)
                             ->get('users')
                             ->row_array();
        
        return !empty($user);
    }
    
    /**
     * Get user's 2FA secret
     */
    public function get_user_secret($user_id) {
        $user = $this->CI->db->select('two_factor_secret')
                             ->where('user_id', $user_id)
                             ->get('users')
                             ->row_array();
        
        return $user ? $user['two_factor_secret'] : null;
    }
    
    /**
     * Generate backup codes for user
     */
    public function generate_backup_codes($user_id, $count = 8) {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(md5(uniqid() . random_bytes(16)), 0, 8));
        }
        
        // Hash and store backup codes
        $hashed_codes = array_map('password_hash', $codes, array_fill(0, $count, PASSWORD_BCRYPT));
        
        $backup_codes_data = [
            'user_id' => $user_id,
            'codes' => json_encode($hashed_codes),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // Store in backup_codes table
        $this->CI->db->insert('backup_codes', $backup_codes_data);
        
        return $codes;
    }
    
    /**
     * Verify backup code
     */
    public function verify_backup_code($user_id, $code) {
        $backup_codes = $this->CI->db->where('user_id', $user_id)
                                    ->get('backup_codes')
                                    ->row_array();
        
        if (!$backup_codes) {
            return false;
        }
        
        $stored_codes = json_decode($backup_codes['codes'], true);
        
        foreach ($stored_codes as $index => $hashed_code) {
            if (password_verify($code, $hashed_code)) {
                // Remove used backup code
                unset($stored_codes[$index]);
                $this->CI->db->where('id', $backup_codes['id'])
                             ->update('backup_codes', ['codes' => json_encode(array_values($stored_codes))]);
                return true;
            }
        }
        
        return false;
    }
}
