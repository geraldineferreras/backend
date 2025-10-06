<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class EmailDiagnostics extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->output->set_content_type('application/json');
	}

	// GET /api/email/diagnostics?to=someone@example.com
	public function diagnostics_get() {
		$to = $this->input->get('to');
		if (!$to) {
			return $this->respond(400, array(
				'error' => 'Missing required query parameter: to',
				'example' => '/api/email/diagnostics?to=you@example.com'
			));
		}

		$results = array(
			'environment' => array(
				'SMTP_HOST' => getenv('SMTP_HOST') ?: null,
				'SMTP_PORT' => getenv('SMTP_PORT') ?: null,
				'SMTP_USER' => getenv('SMTP_USER') ?: null,
				'SMTP_CRYPTO' => getenv('SMTP_CRYPTO') ?: null,
				'RAILWAY_ENVIRONMENT' => getenv('RAILWAY_ENVIRONMENT') ?: null
			)
		);

		// Test CodeIgniter Email library
		$ciEmail = $this->test_ci_email($to);
		$results['codeigniter_email'] = $ciEmail;

		// Test PHPMailer via helper
		$phpMailer = $this->test_phpmailer($to);
		$results['phpmailer'] = $phpMailer;

		$overallOk = ($ciEmail['success'] === true) || ($phpMailer['success'] === true);
		$status = $overallOk ? 200 : 500;
		return $this->respond($status, $results);
	}

	private function test_ci_email($to) {
		$this->load->library('email');
		// Basic message
		$subject = 'SCMS CI Email Test - ' . date('Y-m-d H:i:s');
		$message = "<p>This is a test email sent using CodeIgniter Email library.</p>";

		$from = getenv('SMTP_USER') ?: 'scmswebsitee@gmail.com';
		$this->email->from($from, 'SCMS System');
		$this->email->to($to);
		$this->email->subject($subject);
		$this->email->message($message);
		$this->email->set_mailtype('html');

		$success = $this->email->send();
		$debug = method_exists($this->email, 'print_debugger') ? $this->email->print_debugger(array('headers')) : '';

		return array(
			'success' => (bool)$success,
			'debug' => $success ? null : $debug
		);
	}

	private function test_phpmailer($to) {
		$this->load->helper('email_notification');
		$available = class_exists('PHPMailer\\PHPMailer\\PHPMailer');
		$subject = 'SCMS PHPMailer Test - ' . date('Y-m-d H:i:s');
		$message = '<p>This is a test email sent using PHPMailer.</p>';

		if (!$available) {
			return array(
				'success' => false,
				'available' => false,
				'error' => 'PHPMailer class not found. Ensure dependency is installed on Railway.'
			);
		}

		$sent = function_exists('send_email') ? send_email($to, $subject, $message) : false;
		return array(
			'success' => (bool)$sent,
			'available' => true
		);
	}

	private function respond($statusCode, $payload) {
		return $this->output
			->set_status_header($statusCode)
			->set_output(json_encode($payload));
	}
}


