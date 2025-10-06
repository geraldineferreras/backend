<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Email Configuration
|--------------------------------------------------------------------------
|
| This file contains the email configuration settings for the SCMS system.
| It uses Gmail SMTP for sending email notifications.
| Supports both local development and Railway deployment.
|
*/

// Use environment variables for Railway deployment, fallback to defaults for local
$config['protocol'] = 'smtp';
$config['smtp_host'] = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$config['smtp_port'] = getenv('SMTP_PORT') ?: 465;
$config['smtp_user'] = getenv('SMTP_USER') ?: 'scmswebsitee@gmail.com';
$config['smtp_pass'] = getenv('SMTP_PASS') ?: 'zhrk blgg sukj wbbs';
$config['smtp_crypto'] = getenv('SMTP_CRYPTO') ?: 'ssl';
$config['smtp_timeout'] = 60; // Increased timeout for Railway
$config['mailtype'] = 'html';
$config['charset'] = 'utf-8';
$config['newline'] = "\r\n";
$config['wordwrap'] = TRUE;
$config['mailpath'] = '/usr/sbin/sendmail';
$config['crlf'] = "\r\n";
$config['bcc_batch_mode'] = false;
$config['bcc_batch_size'] = 200;

// Additional Railway-optimized settings
$config['smtp_keepalive'] = FALSE;
$config['smtp_debug'] = getenv('SMTP_DEBUG') ?: FALSE;
$config['validate'] = TRUE; 