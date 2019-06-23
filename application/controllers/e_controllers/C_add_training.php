<?php

class C_add_training extends CI_Controller
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('e_models/M_list_training');
		$this->load->model('e_models/M_all_actions_training');
	}

	public function displayAllTraining(){
		$list_training['rslt_list'] = $this->M_list_training->listTraining();
		$this->load->view('e_views/V_add_training', $list_training);
	}
	public function addTraining(){
		$this->load->view('e_views/Add_training');
	}
	public function addTraining1(){

		$this->M_all_actions_training->add_training();
	}
	public function img_upload_training() {
		$config['file_name'] = $this->input->post('code_training');
	 	$config['upload_path']   = './assets/img/e_img/e_img_lesson/';
	 	$config['allowed_types'] = 'gif|jpg|png';
	 	$config['max_size']      = 400;
	 	$config['max_width']     = 1024;
	 	$config['max_height']    = 768;
	 
	 	$this->load->library('upload', $config);

	 	if ( ! $this->upload->do_upload('img_training')) {
	    	$error = array('error' => $this->upload->display_errors());
	    	$this->load->view('e_views/Add_training', $error);
	    }
	 	else {
	    	$data = array('upload_data' => $this->upload->data());
	    	$this->load->view('e_views/Add_training', $data);
	 	}
	}
}

?>