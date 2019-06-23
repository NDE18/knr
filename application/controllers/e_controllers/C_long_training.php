<?php

class C_long_training extends CI_Controller
{
	
	function __construct(){
		parent::__construct();
		$this->load->model('e_models/M_list_training');
	}
	public function index(){
		$list_training['training'] = $this->M_list_training->listTraining();
		$this->load->view('public/e_list_training', $list_training);
	}
}

?>