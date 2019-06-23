<?php
defined('BASEPATH') OR exit('No direct script access allowed');

  class C_Recapitulative_Training extends CI_Controller
  {
  	function __construct(){
  	  parent::__construct();

      $this->load->model('e_models/CaseLearners');
      $this->load->model('e_models/M_list_training');
      $this->load->model('e_models/M_verify');
      $this->load->model('e_models/M_Recapitulative_Training');
      $this->load->helper('text');

      $id_learner = session_data('id');
      $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
      $Training['list_training'] = $this->M_list_training->listTraining();
      $list_is_wave = $this->M_verify->HisInfoWave($id_learner);
      $All_WV = array();
      $i = 0;
      foreach ($list_is_wave as $key) {
        $All_WV[$i++] = $this->M_verify->selectIsWave($key['id_wv']);
      }
      $Training['list_is_wave'] = $All_WV;
      $Training['Recapitulative'] = $this->M_Recapitulative_Training->Recapitulative();
      $Training['RecapNoteExam'] = $this->M_Recapitulative_Training->RecapNoteExam($All_WV);
      $this->load->view('e_views/head_learner');
      $this->load->view('e_views/V_take_courses', $Training);
      $this->load->view('e_views/V_Recapitulative_Training', $Training);
  	}
    function index($page = 1){
    }
  }

?>