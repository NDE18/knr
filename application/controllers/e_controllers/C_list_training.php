<?php

class C_list_training extends CI_Controller
{	
	function __construct()
	{
		parent::__construct();
		$this->load->model('e_models/M_list_training');
	}
	public function displayAllTraining(){
		$list_training['rslt_list'] = $this->M_list_training->listTraining();
		$this->load->view('e_views/V_list_training', $list_training);
	}
	public function listModuleTraining($id_training){
		$Module_training['his_module'] = $this->M_list_training->listDistinctModule($id_training);
		$Module_training['all_module'] = $this->M_list_training->listModule();
		$Module_training['training'] = $id_training;
		$this->load->view('public/e_top_menu');
		$this->load->view('public/e_left_menu');
		$this->load->view('e_views/e_module_training', $Module_training);
	}
}

?>