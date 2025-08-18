<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Configuration Template for SCMS
 * Copy this file and fill in your actual values
 */

// Google OAuth Configuration
$config['google_oauth'] = array(
    'client_id' => getenv('GOOGLE_CLIENT_ID') ?: 'your_google_client_id_here',
    'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: 'your_google_client_secret_here',
    'project_id' => getenv('GOOGLE_PROJECT_ID') ?: 'your_google_project_id_here',
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
    'secret_key' => getenv('JWT_SECRET_KEY') ?: 'your_jwt_secret_key_here',
    'expiration_time' => getenv('JWT_EXPIRATION_TIME') ?: 86400,
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
