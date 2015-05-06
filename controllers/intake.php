<?php

class Intake extends CI_Controller {

	public function index()
	{
		// TODO: Integrate portals, remove redirect.
		redirect(base_url() . '../portal-intake');
		return;

		$this->load->view('common-start', array(
			 'styleIncludes' => array('intake.css'),
			'scriptIncludes' => array('intake.js'),
			'activeModule'   => 'intake',
			'user' => array(
				'username' => $this->rodsuser->getUsername(),
			),
		));
		$this->load->view('intake_index');
		$this->load->view('common-end');
	}

	public function __construct() {
		parent::__construct();
		$this->load->library('prods');
		$this->load->library('session');
		$this->load->model('rodsuser');

		if (
			   $this->session->userdata('username') !== false
			&& $this->session->userdata('password') !== false
		) {
			$this->rodsuser->getRodsAccount(
				$this->session->userdata('username'),
				$this->session->userdata('password')
			);
		}
		if (!$this->rodsuser->isLoggedIn())
			redirect('user/login');
	}
}
