<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Email Configuration
|--------------------------------------------------------------------------
|
| This file contains the email configuration settings for the SCMS system.
| It uses Gmail SMTP for sending email notifications.
|
*/

$config['protocol'] = 'smtp';
$config['smtp_host'] = getenv('SMTP_HOST') ? getenv('SMTP_HOST') : 'smtp.gmail.com';
$config['smtp_port'] = getenv('SMTP_PORT') ? getenv('SMTP_PORT') : 465;
$config['smtp_user'] = getenv('SMTP_USER') ? getenv('SMTP_USER') : 'scmswebsitee@gmail.com';
$config['smtp_pass'] = getenv('SMTP_PASS') ? getenv('SMTP_PASS') : 'zhrk blgg sukj wbbs';
$config['smtp_crypto'] = getenv('SMTP_CRYPTO') ? getenv('SMTP_CRYPTO') : 'ssl';
$config['smtp_timeout'] = 30;
$config['mailtype'] = 'html';
$config['charset'] = 'utf-8';
$config['newline'] = "\r\n";
$config['wordwrap'] = TRUE;
$config['mailpath'] = '/usr/sbin/sendmail';
$config['crlf'] = "\r\n";
$config['bcc_batch_mode'] = false;
$config['bcc_batch_size'] = 200; 