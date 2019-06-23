<?php
defined('BASEPATH') OR exit('No direct script access allowed');

  class C_home_page extends CI_Controller
  {
    protected $data = array();

  	function __construct(){
  	  parent::__construct();

      $this->load->model('e_models/M_verify');
      
      $this->load->model('auth/auth_model', 'authM');
      $this->load->model('e_models/CaseLearners');
  	  $this->load->model('e_models/M_list_training');

      $this->load->model('e_models/M_confirm_valid_registration');

      $this->load->library('form_validation');
  	}
  public function index()
  {
    if (session_data('role') == 3){
      $this->C_space_trainer();
    }
    else
      $this->C_home_page();
  }  
  public function C_home_page(){    
    $list_operator['rslt_list'] = $this->M_list_training->listOperator();
    $this->load->view('public/e_header', $list_operator);
  }
	public function list_lesson(){
		$lesson['list_training'] = $this->M_list_training->listTraining();
		$this->load->view('public/e_list_training', $lesson);
	}
	public function list_lesson2(){
		$lesson['list_training'] = $this->M_list_training->listTraining();
		$this->load->view('public/e_list_training2', $lesson);
	}
  public function list_lesson3(){
    $lesson['list_training'] = $this->M_list_training->listTraining();
    $this->load->view('public/e_list_training3', $lesson);
  }
  public function choiceTraining($id_training){
    $this->M_verify->verify();
    $id = session_data('id');
    $isRegister = $this->M_list_training->IsWave($id);
    $M = 0; $N=0;
    foreach ($isRegister as $key) {
      $ThisWv = $this->db->select('*')->from('e_wave')->where('id_wave', $key['id_wv'])->where('id_lesson', $id_training)->get()->result_array();
      if (sizeof($ThisWv)!=0) {
        $N++;
      }
    }
    if ($N!=0) {
      $message['message'] = '<h2 style="color: orange; margin-right: 103px">Vous suivez déjà cette formation.</h2> <br>';
      $this->load->view('public/V_begining', $message);
    }
    else {
      if (sizeof($this->M_confirm_valid_registration->isRegister($id_training, $id)) != 0) {
      $message['message'] = 'Vous avez déjà fait une demande pour cette formation. <br><a href="'.base_url().'e_controllers/c_confirm_valid_registration/modifyChoise/'.$id_training.'"><br><button type="button" class="btn btn-warning">Rectifier un détail de cette demande ?</button></a>';
      $this->load->view('public/V_begining', $message);  
      }
      else{
        $training_select['choice'] = $this->M_list_training->listTraining();
        $training_select['id'] = $id_training;
        $training_select['list_Id_module'] = $this->M_list_training->listDistinctModule($id_training);
        $training_select['list_module'] = $this->M_list_training->listModule();
        $training_select['list_slices'] = $this->M_list_training->isSlices($id_training);
        $training_select['list_operator'] = $this->M_list_training->listOperator();
        $this->load->view('public/e_top_menu');
        $this->load->view('public/e_left_menu');
        $this->load->view('e_views/V_confirm_valid_registration', $training_select);
      }
    }
  }
  public function testTraining($id){
    $this->load->view('public/e_top_menu');
    $this->M_verify->verify();
    $training_select['id'] = $id;
    $this->load->view('e_views/Prerequises_test', $training_select);
  }
  public function C_space_trainer(){
    $this->load->view('e_views/V_space_trainer');
  }
  public function details($id, $actual_page){
    $this->load->view('public/e_top_menu');
    $this->load->view('public/e_left_menu');
    $training_select['choice'] = $this->M_list_training->listTraining();
    $training_select['id'] = $id;
    $training_select['preceding_page'] = $actual_page ;
    $this->load->view('e_views/e_details_training', $training_select);
  }
  public function correctionTest($id_lesson){

    $id = session_data('id');
    $point = 0.00;
    $TotalPoints = 0.00;
    $note = htmlspecialchars($this->input->post('note'));
    $M = 0;
    $all_qsts = array();

    $this->load->view('public/e_top_menu');
    $this->load->view('public/e_left_menu');
    $query = $this->db->select('*')
            ->from('e_exercise', 'e_test', 'e_question', 'e_answer')
            ->join('e_build','e_build.id_ex = e_exercise.id_exercise')
            ->join('e_test','e_test.id_test = e_build.id_test')
            ->where('e_exercise.id_lesson='.$id_lesson)
            ->get()->result_array();
    foreach ($query as $key2) {
      $all_qsts[$M++] = $this->db->select('*')->from('e_question')->where('id_exercise', $key2['id_exercise'])->get()->result_array();
      $id_compo = array();
      $M =0;
      $compo = $this->db->where('id_test', $key2['id_test'])->get('e_composition')->result_array();
      foreach ($compo as $MAKA) {
        $id_compo[$M++] = $MAKA['id_compo'];
      }
    }

    foreach ($all_qsts as $key5) {
      foreach ($key5 as $key17) {
        $TotalPoints += $key17['point'];
        if ($this->input->post('proposition'.$key17['answer']) == $key17['answer']) {
          $point += $key17['point'];
        }
        else{
          $point_if_felt = $this->db->select('point_if_felt')->from('e_exercise')->where('id_exercise', $key17['id_exercise'])->get()->result_array();
          foreach ($point_if_felt as $key13) {
            $point -= $key13['point_if_felt'];
          }
        }
      }
    }
    if ($point < 0) {
      $point = 0;
    }
    $IsTest['point'] = $point;
    $IsTest['TotalPoints'] = $TotalPoints;
    $IsTest['date_compo'] = moment()->format(NO_TZ_MYSQL);
    if ($point < ($TotalPoints*20)/100) {
      $mention = 'Nulle';
    }
    elseif ($point >= ($TotalPoints*20)/100 and $point < ($TotalPoints*30)/100) {
      $mention = 'Médiocre';
    }
    elseif ($point >= ($TotalPoints*30)/100 and $point < ($TotalPoints*40)/100) {
      $mention = 'Insuffisant';
    }
    elseif ($point >= ($TotalPoints*40)/100 and $point < ($TotalPoints*50)/100) {
      $mention = 'Passable';
    }
    elseif ($point >= ($TotalPoints*50)/100 and $point < ($TotalPoints*60)/100) {
      $mention = 'Assez Bien';
    }
    elseif ($point >= ($TotalPoints*60)/100 and $point < ($TotalPoints*70)/100) {
      $mention = 'Bien';
    }
    elseif ($point >= ($TotalPoints*70)/100 and $point < ($TotalPoints*80)/100) {
      $mention = 'Très Bien';
    }
    elseif ($point >= ($TotalPoints*80)/100 and $point < ($TotalPoints*90)/100) {
      $mention = 'Très Bien (Avec Félicitation)';
    }
    elseif ( $point >= ($TotalPoints*90)/100 and $point < ($TotalPoints*95)/100) {
      $mention = 'Excellente';
    }
    else {
      $mention = 'Parfaite';
    }
    $IsTest['mention'] = $mention;

    if ($this->M_verify->registerTest($mention, $note, $point, $IsTest['date_compo'], $id_compo[0], $id) == 0) { 
      $lastnote = $this->db->where('id_compo', $id_compo[0])->where('id_user', $id)->get('e_statement')->result_array();
      $data = array('appression' => $mention, 'content' =>$note, 'note' =>$point, 'registration_date' =>$IsTest['date_compo'], 'id_compo' =>$id_compo[0], 'id_user' => $id);
      $newnote = $this->db->update('e_statement', $data);
      foreach ($lastnote as $kem) {
        $note = $kem['note'];
        $lastdate = $kem['registration_date'];
        $lastapp = $kem['appression'];
        if ($note < 0) {
          $note = 0;
        }

        $IsTest['msg'] = 'Appreciation actuelle : '.$IsTest['mention'].'<br><hr>Dernière note : '.$note.'/'.$TotalPoints.'<br><hr>
                       <a class="glyphicon glyphicon-queen" style="width: 260px; float: right; margin-right: 100px;" href="'.base_url().'e_controllers/C_home_page/solutionTest/'.$id_lesson.'">
              <button type="button" class="btn btn-primary" aria-label="Left Aligno" style="border-radius: 0px;">
                <span class="glyphicon glyphicon-queen" aria-hidden="true">
                  Solution ?
                </span>
              </button>
            </a>';
      }
    }
    else{
      echo '<br><hr><a class="glyphicon glyphicon-queen" style="width: 260px;" href="'.base_url().'e_controllers/C_home_page/solutionTest/'.$id_lesson.'">
                        <button type="button" class="btn btn-primary" aria-label="Left Align">
                          <span class="glyphicon glyphicon-queen" aria-hidden="true">
                            Solution ?
                          </span>
                        </button></a>';
    }
    $this->load->view('e_views/V_result_e_test', $IsTest);
  }
  public function solutionTest($id_lesson){
    $IsTest['id'] = $id_lesson;
    $this->load->view('public/e_top_menu');
    $this->load->view('public/e_left_menu');
    $this->load->view('e_views/V_solution_test', $IsTest);
  }
}

?>