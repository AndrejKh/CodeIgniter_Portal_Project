<?php

class Intake extends CI_Controller {

	public function index()
	{
		$this->load->view('common-start', [
			 'style_includes' => ['intake.css'],
			'script_includes' => ['intake.js'],
		]);
		$this->load->view('common-header', [
			'active_module' => 'intake'
		]);
		$this->load->view('intake_index');
		$this->load->view('common-footer');
		$this->load->view('common-end');
	}
}
