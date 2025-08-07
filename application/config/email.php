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
$config['smtp_host'] = 'smtp.gmail.com';
$config['smtp_port'] = 465;
$config['smtp_user'] = 'grldnferreras@gmail.com';
$config['smtp_pass'] = 'ucek fffw ccfe siny';
$config['smtp_crypto'] = 'ssl';
$config['smtp_timeout'] = 30;
$config['mailtype'] = 'html';
$config['charset'] = 'utf-8';
$config['newline'] = "\r\n";
$config['wordwrap'] = TRUE;
$config['mailpath'] = '/usr/sbin/sendmail';
$config['crlf'] = "\r\n";
$config['bcc_batch_mode'] = false;
$config['bcc_batch_size'] = 200; 