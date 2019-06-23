<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class C_confirm_valid_registration extends CI_Controller
{
  function __construct(){
      parent::__construct();
      $this->load->library('form_validation');
      $this->load->model('e_models/CaseLearners');
      $this->load->model('e_models/M_list_training');
      $this->load->model('e_models/M_confirm_valid_registration');
      $this->load->helper('security');
  }

  public function registration(){
    $this->form_validation->set_rules('kind', 'occupation', 'required');
    $this->form_validation->set_rules('place_kind', 'Lieu de l\'occupation', 'trim|required|xss_clean');
    $this->form_validation->set_rules('reference', 'La reférence de payement', 'trim|required|xss_clean');
    $this->form_validation->set_rules('operator', 'Id de L\'opérateur utilisé', 'required|integer');
    $this->form_validation->set_rules('num_slice', 'Numéro du montant versé', 'required');
    $this->form_validation->set_rules('total_amount', 'Cout total de la formation', 'required');
    $this->form_validation->set_rules('mtn1', 'En une seule tranche de la formation', 'required');
    $this->form_validation->set_rules('id_training', 'id de la formation', 'required');


    if($this->form_validation->run())
    {
      $id = session_data('id');
      $training_select = $this->input->post('training_select');
      $kind = $this->input->post('kind');
      $place_kind = $this->input->post('place_kind');
      $operator = $this->input->post('operator');
      $type_formation = $this->input->post('type_formation');
      $num_slice = $this->input->post('num_slice');
      $mtn1 = $this->input->post('mtn1');
      $mtn21 = $this->input->post('mtn21');
      $mtn31 = $this->input->post('mtn31');
      $mtn22 = $this->input->post('mtn22');
      $mtn32 = $this->input->post('mtn32');
      $mtn33 = $this->input->post('mtn33');
      $total_amount = $this->input->post('total_amount');
      $reference = $this->input->post('reference');
      $id_training = $this->input->post('id_training');
      $formation_type = $this->input->post('formation_type');


      if ($formation_type == 'cours') {
        $formation_type = 'crt';
      }
      elseif ($formation_type == 'filière') {
        $formation_type = 'lng';
      }
      else{
        $formation_type = 'prm';
      }

      if ($num_slice == 0) {
        $message['message'] = 'veuillez selectionner une tranche payée pour continuer.';
        $this->load->view('public/V_begining', $message);
      }

      elseif($num_slice == 1) { $remaining = 0; $total_slice = 1; $next = 0;}

      elseif ($num_slice == 2) { $remaining = ($total_amount - $mtn21) ; $total_slice = 2; $num_slice = 1; $next = $mtn22;}

      else { $remaining = ($total_amount - $mtn31); $total_slice = 3; $num_slice = 1; $next = $mtn32 ;}

      $payment = array('id_user' => $id, 'id_op' => $operator, 'num_slice' => $num_slice, 'remaining_amount' => $remaining, 'reference' => $reference, 'kind' => $kind, 'place_kind' => $place_kind , 'total_slice' => $total_slice, 'id_training' => $id_training, 'total_slice' => $total_slice, 'next_amount' => $next);

      if (sizeof($this->M_confirm_valid_registration->getPreviewChoise($id_training)) != 0) {
        if ($this->M_confirm_valid_registration->updateElearner($payment)) {
          $message['message'] = '<label style="margin-top: 50px;">Nous prendrons en compte vos modifications <br> (veuillez contacter un des numéros de transaction pour plus de détails)</label>';
          $this->load->view('public/V_begining', $message);           
        }
      }
      else{
        if ($this->M_confirm_valid_registration->addElearner($payment)) {
          $message['message'] = '<label class="h4">Prennez patience pour un délais maximum d\'un jour ou, veuillez contacter le numéro de transaction que vous avez selectionner.</label>';
          $this->load->view('public/V_begining', $message); 
        }
        else{
        $message['message'] = '<label class="h4">Echec de l\'inscription !!!</label>';
        $this->load->view('public/V_begining', $message);
        }
      }
    }
    else{
      $id_training = $this->input->post('id_training');
      $this->load->view('public/e_top_menu');
      $this->load->view('public/e_left_menu');  
      $e['error'] = 'Veuillez bien remplir tous les champs s\'il vous plait !<br><a href="'.base_url().'e_controllers/C_home_page/choiceTraining/'.$id_training.'"><button type="button" class="btn btn-info">OK</button></a>';
      $this->load->view('e_views/e_error_cvr', $e);
    }
  }

  public function details($id){
    $this->load->view('public/e_top_menu');
    $this->load->view('public/e_left_menu');
    $training_select['choice'] = $this->M_list_training->listTraining();
    $training_select['id'] = $id;
    $this->load->view('e_views/e_details_training', $training_select);
  }
  public function finalizeRegulation($id_learner, $reference=null){
    $fees = $this->db->where('id_user', $id_learner)->get('e_paid')->result_array();
    $i = 0;
    $all= array();
    $Training['list_operator'] = $this->M_list_training->listOperator();
    foreach ($fees as $key305) {
      if ($key305['remaining_amount'] == 0 and $key305['validation_state'] == 1) {
        $all[$i++] = 1;
      }
    }
    if (sizeof($all) == sizeof($fees)) {
      $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
      $Training['list_training'] = $this->M_list_training->listTraining();
      $this->load->view('e_views/head_learner');
      $this->load->view('e_views/V_take_courses', $Training);
      $message['msg'] = '<div class="panel panel-default" style="width: 82%;float: right; height: 404px;">
          <div class="panel-body"><a href="'.base_url().'e_controllers/C_take_courses'.'"
            <button type="button" class="btn btn-primary" style="margin-left: 44px; margin-right: 88px; color: #2059f6;height: 324px; background-color: #fedb95";>
              <span class="glyphicon glyphicon-queen" aria-hidden="true">
                Vous n\'avez aucune redevance en vers notre structure,<br> nous vous remercions pour votre confiance en vous souhaitant un parcours sans faute(s) chez nous.
              </span>
              <img src="'.base_url().'assets/img/logo/logo-sm.png'.'" alt="MULTISOFT ACADEMY" class="rounded float-left" style="width: 103px; height: 103px;margin-left: 391px; margin-top: 22px">
            </button></a>
          </div>
        </div>';
      $this->load->view('e_views/V_no_remaining', $message);
    }
    else{
      $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
      $Training['list_training'] = $this->M_list_training->listTraining();
      $this->load->view('e_views/head_learner');
      $this->load->view('e_views/V_take_courses', $Training);
      $Regulation = array();
      $Regulation['msg'] = "";
      $Regulation['IsRegulation'] = $this->M_confirm_valid_registration->IsRegulation($id_learner);

      if (sizeof($this->input->post('reference')) != null) {
        $reference = htmlspecialchars($this->input->post('reference'));
        $operator = $this->input->post('operator');
        $formation_type = $this->input->post('formation_type');
        $total_slice = $this->input->post('total_slice');
        $num_slice = $this->input->post('num_slice');
        $next = $this->input->post('next_amount');
        $id_lesson = $this->input->post('id_lesson');
        $new_remaining = $this->input->post('new_remaining');
        if ($reference != null or $reference = '') {
          $ref = $reference;
          $f_t = $formation_type;
          $t_s = $total_slice;
          $n_s = $num_slice;
          $nxt = $next;
          $n_r = $new_remaining;
          $idt = $id_lesson;
          if ($this->M_confirm_valid_registration->updatePayment($operator, $ref, $f_t, $t_s, $n_s, $nxt, $n_r, $idt)==0) {
            $Regulation['msg'] = 'Dernier payement en attente de vadilation';
            $this->load->view('e_views/V_regulation', $Regulation);
          }
          else{
            $this->M_confirm_valid_registration->updatePayment($operator, $ref, $f_t, $t_s, $n_s, $nxt, $n_r, $idt);
            $this->load->view('e_views/V_regulation', $Regulation);
          }
        }
      }
      else{
        $Regulation['msg'] = "";
        $this->load->view('e_views/V_regulation', $Regulation);
      }
    }
  }
  public function modifyChoise($id_training){
    $training_select['choice'] = $this->M_list_training->listTraining();
    $training_select['id'] = $id_training;
    $training_select['list_Id_module'] = $this->M_list_training->listDistinctModule($id_training);
    $training_select['list_module'] = $this->M_list_training->listModule();
    $training_select['list_slices'] = $this->M_list_training->isSlices($id_training);
    $training_select['list_operator'] = $this->M_list_training->listOperator();
    $this->load->view('public/e_top_menu');
    $this->load->view('e_views/V_confirm_valid_registration', $training_select);
  }
}
?>