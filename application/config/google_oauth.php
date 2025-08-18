<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google OAuth Configuration for SCMS
 * This file is automatically loaded by CodeIgniter
 */

$config['google_oauth'] = array(
    'client_id' => '915997325303-6h2v8ctgegd6d6ft51vdmf4o1ir1lmah.apps.googleusercontent.com',
    'client_secret' => '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',
    'project_id' => 'scms-469206',
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
    'secret_key' => 'scms-jwt-secret-key-2024-change-in-production',
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
