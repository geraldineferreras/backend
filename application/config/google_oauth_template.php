<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google OAuth Configuration Template for SCMS
 * Copy this file to google_oauth.php and fill in your actual values
 * DO NOT commit the actual google_oauth.php file with real credentials
 */

$config['google_oauth'] = array(
    'client_id' => 'YOUR_GOOGLE_CLIENT_ID_HERE',
    'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET_HERE',
    'project_id' => 'YOUR_GOOGLE_PROJECT_ID_HERE',
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token',
    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
    'javascript_origins' => array(
        'http://localhost:3000',
        'http://localhost'
    ),
    'redirect_uris' => array(
        'http://localhost:3000/auth/callback',
        'http://localhost/auth/callback'
    )
);

// JWT Configuration
$config['jwt'] = array(
    'secret_key' => 'YOUR_JWT_SECRET_KEY_HERE',
    'expiration_time' => 86400, // 24 hours
    'algorithm' => 'HS256'
);

// CORS Configuration
$config['cors'] = array(
    'allowed_origins' => array(
        'http://localhost:3000',
        'http://localhost'
    ),
    'allowed_methods' => array('GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'),
    'allowed_headers' => array('Content-Type', 'Authorization', 'X-Requested-With')
);
