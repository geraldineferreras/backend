<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Environment Configuration Template for SCMS
 * Copy this file to environment_config.php and fill in your actual values
 * DO NOT commit the actual environment_config.php file with real credentials
 */

// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');

// JWT Configuration
define('JWT_SECRET_KEY', 'YOUR_JWT_SECRET_KEY_HERE');
define('JWT_EXPIRATION_TIME', 86400); // 24 hours

// CORS Configuration
define('ALLOWED_ORIGINS', 'http://localhost:3000,http://localhost');

// API Configuration
define('API_BASE_URL', 'http://localhost/scms_new_backup/index.php/api');
