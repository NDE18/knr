<?php

class C_list_operator extends CI_Controller
{
	
	function __construct(){
		parent::__construct();
		$this->load->model('e_models/M_list_training');
	}
	public function displayAllOperator(){
		$list_operator['rslt_list'] = $this->M_list_training->listOperator();
		//$this->load->view('public/V_list_operator', $list_operator);
		$this->load->view('public/e_header', $list_operator);
	}
}
?>