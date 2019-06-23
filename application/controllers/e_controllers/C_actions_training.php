<?php

class C_actions_training extends CI_Controller
{
	
	function __construct(){
		parent::__construct();
		$this->load->model('e_models/M_list_training');
		$this->load->model('e_models/M_all_actions_training');
	}
	public function updateTraining($id_choice){
		$lesson = $this->M_list_training->listTraining($id_choice);
		if(count($lesson)==1){
			$this->load->view('e_views/Update_training', $lesson[0]);
		}
	}
	public function statueTraining($id_choice){
		$all_lesson = $this->M_list_training->listTraining();

		foreach ($all_lesson as $key) {

			if ($id_choice = $key['id']) {
				$state = $this->M_all_actions_training->state_training($id_choice); 	
			}
		}

		$list_training['rslt_list'] = $this->M_list_training->listTraining();
		$this->load->view('e_views/V_add_training', $list_training);
	}
	public function deleteTraining($id){

	}
}
?>