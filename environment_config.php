<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Google OAuth Configuration for SCMS
 * Copy these values to your environment or config files
 */

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '915997325303-6h2v8ctgegd6d6ft51vdmf4o1ir1lmah.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: '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');

// JWT Configuration
define('JWT_SECRET_KEY', getenv('JWT_SECRET_KEY') ?: 'scms-jwt-secret-key-2024-change-in-production');
define('JWT_EXPIRATION_TIME', getenv('JWT_EXPIRATION_TIME') ?: 86400); // 24 hours

// CORS Configuration
define('ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost');

// API Configuration
define('API_BASE_URL', 'http://localhost/scms_new_backup/index.php/api');
