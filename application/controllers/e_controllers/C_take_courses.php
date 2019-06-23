<?php

class C_take_courses extends CI_Controller
{	
    function __construct(){

        parent::__construct();

        $this->load->model('e_models/M_verify');

        $this->load->model('auth/auth_model', 'authM');

        $this->load->model('e_models/M_upload');

        $this->load->library('form_validation');
        $this->load->model('e_models/CaseLearners');
        $this->load->model('e_models/M_list_training');
        $this->load->model('e_models/M_verify');
        $this->load->helper(array('form', 'url', 'text', 'html'));
        $this->load->model('Courses');

    }

    public function index(){     
        $id_learner = session_data('id');
    	$this->M_verify->verify_e();

        //A DONNER A SIMO
/*        
        $all_Questions = $this->db->get('e_question')->result_array();
        foreach ($all_Questions as $N) {
            if (sizeof($this->db->where('id_qst', $N['id_question'])->get('e_answer')->result_array())!=0 and sizeof($this->db->where('id_qst', $N['id_question'])->get('e_question')->result_array())==0) {
                $this->db->delete('*')->from('e_answer')->where('id_qst')->get();
            }

            if (sizeof($this->db->where('id_qst', $N['id_question'])->where('proposition', $N['answer'])->get('e_answer')->result_array())==0) {
                $arr0 = array('id_qst' => $N['id_question'], 'proposition' => $N['answer']);
                $this->db->insert('e_answer', $arr0);
            }else{
                $arr0 = array('id_qst' => $N['id_question'], 'proposition' => $N['answer']);
                $this->db->update('e_answer', $arr0);
            }

            if ($N['prop1']!=null and sizeof($this->db->where('id_qst', $N['id_question'])->where('proposition', $N['prop1'])->get('e_answer')->result_array())==0) {
                $arr1 = array('id_qst' => $N['id_question'], 'proposition' => $N['prop1']);
                $this->db->insert('e_answer', $arr1);
            }
            else{
                $arr1 = array('id_qst' => $N['id_question'], 'proposition' => $N['prop1']);
                $this->db->update('e_answer', $arr1);
            }

            if ($N['prop2']!=null and sizeof($this->db->where('id_qst', $N['id_question'])->where('proposition', $N['prop2'])->get('e_answer')->result_array())==0) {
                $arr2 = array('id_qst' => $N['id_question'], 'proposition' => $N['prop2']);
                $this->db->insert('e_answer', $arr2);
            }
            else{
                $arr2 = array('id_qst' => $N['id_question'], 'proposition' => $N['prop2']);
                $this->db->update('e_answer', $arr2);
            }

            if ($N['prop3']!=null and sizeof($this->db->where('id_qst', $N['id_question'])->where('proposition', $N['prop3'])->get('e_answer')->result_array())==0) {
                $arr3 = array('id_qst' => $N['id_question'], 'proposition' => $N['prop3']);
                $this->db->insert('e_answer', $arr3);
            }
            else{
                $arr3 = array('id_qst' => $N['id_question'], 'proposition' => $N['prop3']);
                $this->db->update('e_answer', $arr3);
            }
        }*/

        // EXAMS
        if ($this->M_verify->AlertExam($id_learner)!=null) {
            $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
            $Training['list_training'] = $this->M_list_training->listTraining();
            $Training['test'] = $this->M_verify->AlertExam();
            $this->load->view('e_views/head_learner');
            $this->load->view('e_views/V_take_courses', $Training);
            $this->load->view('e_views/AlertExam', $Training);
        }
        else{
            $this->takeCourses();
            $all_His_Lesson = $this->db->where('id_user', session_data('id'))->where('status=1')->get('e_content')->result_array();
            $data = array();
            foreach ($all_His_Lesson as $key) {
                $id_lesson = $this->db->where('id_wave', $key['id_wv'])->get('e_wave')->row()->id_lesson;
                $thisTr = $this->db->select('*')->from('e_training')->where('id_user', session_data('id'))->where('id_lesson', $id_lesson)->get()->result_array();
                if (sizeof($thisTr)==0) {
                    $data = array('id_user' => session_data('id'), 'id_lesson' => $id_lesson);
                    $this->db->insert('e_training', $data);
                }
            }
            $all_NO_Lesson = $this->db->where('id_user', session_data('id'))->where('status=-1')->get('e_content')->result_array();
            foreach ($all_NO_Lesson as $key) {
                $id_lesson = $this->db->where('id_wave', $key['id_wv'])->get('e_wave')->row()->id_lesson;
                $thisTr = $this->db->select('*')->from('e_training')->where('id_user', session_data('id'))->where('id_lesson', $id_lesson)->get()->result_array();
                if (sizeof($thisTr)>0) {
                    $data = array('id_user' => session_data('id'), 'id_lesson' => $id_lesson);
                    $this->db->delete('e_training', $data);
                }
            }
        }
    }
	public function takeCourses(){
        $id_learner = session_data('id');
        if (sizeof($this->CaseLearners->HisTraining($id_learner))!=0) {
            $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
            $Training['list_training'] = $this->M_list_training->listTraining();
            $this->load->view('e_views/head_learner');
            $this->load->view('e_views/V_take_courses', $Training);
            $this->load->view('e_views/list_his_lesson');
        }
        else{
            
        }
	}
    public function reviewCourse($id_training, $training){
        $id_learner = session_data('id');
        $Training['id_training'] = $id_training;
        $Training['training'] = $training;
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $Training['lesson_user2'] = $this->CaseLearners->HisTrainingMod($id_training);
        $Training['list_module'] = $this->M_list_training->listModule();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
        $this->load->view('e_views/V_review_mod', $Training);          
    }
    public function sendReport(){
        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
        $this->load->view('e_views/e_send_report', $Training);
    }

    public function do_upload() {

        $name_user = session_data('lastname');
        $code_training = $this->input->post('code_training');
        $id_select = $this->M_list_training->searchId($code_training);

        foreach ($id_select as $key) {
            $id_training = $key['id'];
        }
        $config['file_name'] = $this->input->post('new_name');
        $config['file_name'] = $code_training.'_'.$name_user.'_'.$config['file_name'];

        $config['upload_path']   = './assets/uploads/e_documents/Reports/';
        $config['allowed_types'] = 'PDF|pdf|docx';
        $config['max_size']      = 2002;
         
        $this->load->library('upload', $config);

        if ( !$this->upload->do_upload('report_file')) {
            $error = array('error' => $this->upload->display_errors());

            $id_learner = session_data('id');
            $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
            $Training['list_training'] = $this->M_list_training->listTraining();
            $Training['msg'] = "Echec de l'envois : Vérifier sa taille ( inférieur à 1,2 Mo et de type .pdf, .docx ), et assurez-vous qu'il à été bien sélectionné puis renvoyer à nouvelle.";
            $this->load->view('e_views/head_learner');
            $this->load->view('e_views/V_take_courses', $Training);
            
            $this->load->view('e_views/Send_error', $error);
        }
        else {
            $path_complete_report = $config['upload_path'].$config['file_name'];

            if ($this->M_upload->sendReportPath($path_complete_report, $id_training, $config['file_name'])) {
            }
            
            $id_learner = session_data('id');
            $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
            $Training['list_training'] = $this->M_list_training->listTraining();
            $Training['msg'] = 'Envois réussis de votre rapport au responsable de la formation de :  '.$code_training;
            $this->load->view('e_views/head_learner');
            $this->load->view('e_views/V_take_courses', $Training);
            $this->load->view('e_views/Send_success');
        }
    }
    public function makeRequest(){

        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
//requtes faites
        $Training['allIsRequests'] = $this->CaseLearners->listRequest($id_learner);
        $this->load->view('e_views/V_request', $Training);
    }
    public function listRequest(){
        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
        $Training['allIsRequests'] = $this->CaseLearners->listRequest($id_learner);
        $this->load->view('e_views/V_request_list', $Training);      
    }
    public function learnerCoupons(){
        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);

        $Training['amountPosted'] = $this->CaseLearners->amountPosted($id_learner);
        $this->load->view('e_views/V_list_coupons', $Training);      
    }
    public function deleteAllRequest(){
        $id_user =session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_user);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
        $this->db->where('id_user', $id_user);
        $this->db->delete('e_request');
        $message['msg'] = 'Liste vidée avec success';
        $this->load->view('e_views/Send_success', $message);
    }
    public function sendRequest($id_rqst=null, $id_trainingN=null){
        if ($id_rqst!=null and $id_trainingN!=null) {
            $id_learner = session_data('id');
            $date_rqst = moment()->format(NO_TZ_MYSQL);
            $id_training = $id_trainingN;
            $this->CaseLearners->resndRequest($id_rqst, $date_rqst, $id_training);
            $this->load->view('e_views/head_learner');
            $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
            $Training['list_training'] = $this->M_list_training->listTraining();
            $this->load->view('e_views/V_take_courses', $Training);
            $message['msg'] = 'Requête envoyée (Vous serez notifier dans les plus brefs délais)';
            $this->load->view('e_views/Send_success', $message);            
        }
        else{
            $id_learner = session_data('id');
            $id_training = $this->input->post('id_training');
            $object = htmlentities($this->input->post('object'));
            $justification = htmlentities($this->input->post('justification'));

            $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
            $Training['list_training'] = $this->M_list_training->listTraining();

            $date_rqst = moment()->format(NO_TZ_MYSQL);
            $this->CaseLearners->Request($id_learner, $id_training, $object, $justification, $date_rqst);

            $this->load->view('e_views/head_learner');
            $this->load->view('e_views/V_take_courses', $Training);
            $message['msg'] = 'Requete envoyée (Nous vous fairons dans les plus brefs délais)';
            $this->load->view('e_views/Send_success', $message);

        }
    }
    public function removeRqst($id_rqst){
        $this->db->delete('e_request', array('id_rqst' => $id_rqst));
        $message['msg'] = 'Requete retirer.'; 
        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);

        $this->load->view('e_views/Remove_success', $message);
    }
    public function beginClass($training, $id_training){
/*
    foreach ($isWave as $key) {
        if ($key['status_classe'] == 1) {
            if ($key['status'] == 1) {
                $code_wave = $key['code_wave'].date('y').'MS';
                $id_wave = $key['id_wave'];
            }
            else{
                echo '
                <div class="alert alert-danger" role="alert" style="text-align: center;margin-top: 202px; margin-left: -80px;">Cette classe a fini les cours (Veuillez consulter le planing des examens ou contacter l\'admininstration plus de détails" ) !
                    <span class="input-group-btn">
                        <a href="'.base_url().'/e_controllers/c_take_courses'.'"><button class="btn btn-info" type="button">OK</button></a>
                    </span>
                </div>';
            }
        }
        else{
            echo '
            <div class="alert alert-danger" role="alert" style="text-align: center;margin-top: 202px; margin-left: -80px;">Cette classe est momentanément fermée !<br>
                <span class="input-group-btn">
                    <a href="'.base_url().'/e_controllers/c_take_courses'.'"><button class="btn btn-info" type="button">OK</button></a>
                </span>
            </div>';
        }
    }*/

        $id_learner = session_data('id');


        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        //$this->load->view('e_views/V_take_courses', $Training);
        $isWave = $this->db->select('id_wave')->from('e_wave')->where('id_lesson', $id_training)->get()->row()->id_wave;
        $ListWave['isWave'] = $this->M_verify->selectIsWave($isWave);
        $ListWave['wave_user'] = $this->CaseLearners->HisWaveUser($id_learner);
        $ListWave['id_wave'] = $isWave;
        $ListWave['message']=$this->Courses->sms($isWave);
        $ListWave['lists'] =$this->Courses->name();
        $ListWave['list']=$this->Courses->stud($isWave);
        $ListWave['lister']=$this->Courses->lesson($isWave);

        $this->load->view('e_views/V_begining', $ListWave);
    }
    public function submitAvailability(){
        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);

        $jour_dispo = '';
        $test = 0;
        for ($i=1; $i <= 10; $i++) { 
            for ($j=1; $j <= 6; $j++) {
                if($this->input->post('heure-jour['.$i.']['.$j.']')=='on'){
                    $test++;
                    $jour_dispo .= '('.$i.','.$j.')';
                }
            }
        }
        if ($test!=0) {
            if ($test >= 13) {
                $this->CaseLearners->sendIsDispo($id_learner, $jour_dispo);
                $message['msg'] = 'Nous prendrons en compte votre disponibilité .';
                $this->load->view('e_views/V_being_send', $message);
            }
            else{//e_controllers/c_take_courses/submitAvailability
                $message['msg'] = 'Echec de la soumission : Veuillez cocher au moins 13 cases !!!';
                $this->load->view('e_views/V_no_send', $message);
            }
        }
        else{
            $this->load->view('e_views/e_submit_availability');
        }
    }
    public function seeTheme($id_learner){
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);

        $Training['msg'] = '';
        $this->load->view('e_views/e_AvailibilityTheme', $Training);
    }
    public function generalPlaning(){
        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);

        $allTraining = $this->M_list_training->listTraining();
        $IsTraining = $this->M_list_training->IsTraining($id_learner);
        $IsWave = $this->M_list_training->IsWave($id_learner);
        foreach ($allTraining as $key) {
            foreach ($IsTraining as $key1) {
                if ($key1['id_lesson'] == $key['id']) {
                    $IsModule = $this->M_list_training->IsModule($key1['id_lesson']);
                    foreach ($IsModule as $key2) {
                        $All_id_Manager = $this->M_list_training->All_id_Manager($key2['id_mod']);
                        foreach ($All_id_Manager as $key3) {
                            $AllManager = $this->M_list_training->AllManager($key3['id_user']);
                        }
                    }
                }
            }           
        }
        $Training['IsWave'] = $IsWave;
        $Training['list_training'] = $allTraining;
        $Training['lesson_user'] = $IsTraining;
        $Training['IsModule'] = $IsModule;
        $Training['AllManager'] = $AllManager;
        $this->load->view('e_views/e_all_planing', $Training);
    }
    public function appreciation(){
        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
        $this->load->view('e_views/V_appreciation', $Training);
    }
    public function sendApp($oui=null){
        $appreciation = htmlentities($this->input->post('appreciation'));
        
        $id_lesson = $this->input->post('id_lesson');
        $id_learner = session_data('id');
        if ($oui != null) {
            $id_learner = session_data('id');
            $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
            $Training['list_training'] = $this->M_list_training->listTraining();
            $this->load->view('e_views/head_learner');
            $this->load->view('e_views/V_take_courses', $Training);
            $this->M_upload->sendApp($id_lesson, $appreciation, $id_learner);
            $message['msg'] = '<label style="margin-top: -300px;">Nous vous remercions pour votre avis.</label><br><a href="'.base_url().'e_controllers/C_take_courses/appreciation'.'"><button style="margin-top: -100px;" type="submit" class="btn btn-success" name="send">Nouvelle soumission ?</button></a><br>';
            $this->load->view('e_views/V_no_send', $message); 
        }
        if ($appreciation == '' or $id_lesson == '0') {
            $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
            $Training['list_training'] = $this->M_list_training->listTraining();
            $this->load->view('e_views/head_learner');
            $this->load->view('e_views/V_take_courses', $Training);
            $message['msg'] = '<br><br><label style="margin-top: -170px;margin-left: 35px;">Choisissez une formation et rédigez un avis personnel <br> au sujet de celle-ci à son responsable ou cloisir "OK" pour abondonner</label><br><br><br><a href="'.base_url().'e_controllers/C_take_courses/appreciation'.'" class="btn btn-success glyphicon glyphicon-circle-arrow-left">Retour</a><br><br>';
            $this->load->view('e_views/V_no_send', $message);
            
        }
        else{
            if (sizeof($this->db->select('*')->from('e_training')->where('id_lesson', $id_lesson)->where('id_user', $id_learner)->get()->result_array()) == 0) {
            $id_learner = session_data('id');
            $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
            $Training['list_training'] = $this->M_list_training->listTraining();
            $this->load->view('e_views/head_learner');
            $this->load->view('e_views/V_take_courses', $Training);
            $this->M_upload->sendApp($id_lesson, $appreciation, $id_learner);
            $message['msg'] = '<label style="margin-top: -300px;">Nous vous remercions pour votre avis.</label><br><a href="'.base_url().'e_controllers/C_take_courses/appreciation'.'"><button style="margin-top: -100px;" type="submit" class="btn btn-success" name="send">Nouvelle soumission ?</button></a><br>';
            $this->load->view('e_views/V_no_send', $message);
            }
            else{
            $id_learner = session_data('id');
            $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
            $Training['list_training'] = $this->M_list_training->listTraining();
            $this->load->view('e_views/head_learner');
            $this->load->view('e_views/V_take_courses', $Training);
            $this->M_upload->sendApp($id_lesson, $appreciation, $id_learner);
            $message['msg'] = '<label class="h4">Vous avez déjà soumis un avis par rapport à cette formation</label><br><br><a style="background-color: black;border-radius: 0px;" class="h5 btn btn-success" href="'.base_url().'e_controllers/C_take_courses/updateApp/'.$id_lesson.'/'.$appreciation.'/'.$id_learner.'">Remplacer le dernier avis</a><br><br><br>';
            $this->load->view('e_views/V_no_send', $message);
            }
        }
    }

    public function updateApp($id_lesson, $appreciation, $id_learner){
        $this->M_upload->sendApp($id_lesson, $appreciation, $id_learner);

        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
        $this->M_upload->sendApp($id_lesson, $appreciation, $id_learner);
        $message['msg'] = '<label style="margin-top: -170px;">Avis remplacé</label><br>';
        $this->load->view('e_views/Send_success', $message);
    }
}
?>