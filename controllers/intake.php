<?php

class Intake extends CI_Controller {

	public function index()
	{
		$this->load->view('common-start', array(
			 'style_includes' => array('intake.css'),
			'script_includes' => array('intake.js'),
			'active_module'   => 'intake',
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
