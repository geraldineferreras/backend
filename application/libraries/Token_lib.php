<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Token_lib {
    
    private $CI;
    private $secret_key;
    private $algorithm = 'HS256';
    private $expiration_time = 3600; // 1 hour in seconds
    
    public function __construct() {
        $this->CI =& get_instance();
        $this->secret_key = 'your_super_secret_key_here_change_this_in_production_2024';
    }
    
    /**
     * Generate JWT token
     * @param array $payload
     * @return string
     */
    public function generate_token($payload) {
        $header = [
            'typ' => 'JWT',
            'alg' => $this->algorithm
        ];
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $this->expiration_time;
        $payload['nbf'] = time();
        
        $header_encoded = $this->base64url_encode(json_encode($header));
        $payload_encoded = $this->base64url_encode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $header_encoded . "." . $payload_encoded, $this->secret_key, true);
        $signature_encoded = $this->base64url_encode($signature);
        
        return $header_encoded . "." . $payload_encoded . "." . $signature_encoded;
    }
    
    /**
     * Validate JWT token
     * @param string $token
     * @return array|false
     */
    public function validate_token($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return false;
        }
        
        $header = json_decode($this->base64url_decode($parts[0]), true);
        $payload = json_decode($this->base64url_decode($parts[1]), true);
        $signature = $this->base64url_decode($parts[2]);
        
        if (!$header || !$payload) {
            return false;
        }
        
        // Check if token is expired
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }
        
        // Check if token is not yet valid
        if (isset($payload['nbf']) && $payload['nbf'] > time()) {
            return false;
        }
        
        // Verify signature
        $expected_signature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $this->secret_key, true);
        
        if (!hash_equals($signature, $expected_signature)) {
            return false;
        }
        
        return $payload;
    }
    
    /**
     * Refresh JWT token
     * @param string $token
     * @return string|false
     */
    public function refresh_token($token) {
        $payload = $this->validate_token($token);
        
        if (!$payload) {
            return false;
        }
        
        // Remove timestamp fields
        unset($payload['iat']);
        unset($payload['exp']);
        unset($payload['nbf']);
        
        return $this->generate_token($payload);
    }
    
    /**
     * Get token from Authorization header
     * @return string|false
     */
    public function get_token_from_header() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $auth_header = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
                return $matches[1];
            }
        }
        
        return false;
    }
    
    /**
     * Base64URL encode
     * @param string $data
     * @return string
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64URL decode
     * @param string $data
     * @return string
     */
    private function base64url_decode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    /**
     * Set custom expiration time
     * @param int $seconds
     */
    public function set_expiration_time($seconds) {
        $this->expiration_time = $seconds;
    }
    
    /**
     * Get current expiration time
     * @return int
     */
    public function get_expiration_time() {
        return $this->expiration_time;
    }
}