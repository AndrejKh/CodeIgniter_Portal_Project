<?php

class Home extends CI_Controller {

	public function index()
	{
		$this->load->view('common-start');
		$this->load->view('common-header');
		$this->load->view('home_index');
		$this->load->view('common-footer');
		$this->load->view('common-end');
	}
}
