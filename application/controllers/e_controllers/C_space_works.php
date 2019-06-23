<?php

class C_space_works extends CI_Controller
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('e_models/CaseLearners');
		$this->load->model('e_models/M_list_training');
		$this->load->helper('array');
		$this->load->helper('string');
		$this->load->model('e_models/M_verify');
		$this->load->model('e_models/M_Recapitulative_Training');
	}

	public function doExercises($id_chap, $name_mod, $training, $title_chap, $id_training, $id_mod){
		$listIsEx['list_exos'] = $this->CaseLearners->listIsExChap($id_chap);
		$listIsEx['id_chap'] = $id_chap;
		$listIsEx['id_mod'] = $id_mod;
		$listIsEx['name_mod'] = $name_mod;
		$listIsEx['training'] = $training;
		$listIsEx['id_training'] = $id_training;
		$listIsEx['title_chap'] = $title_chap;
        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
        $this->load->view('e_views/V_do_exercices', $listIsEx);
	}
	public function readChapter($id_chap, $name_mod, $training, $title_chap, $id_training, $id_mod){
		$ContentChap['content'] = $this->CaseLearners->ContentChap($id_chap);
		$ContentChap['id_chap'] = $id_chap;
		$ContentChap['id_mod'] = $id_mod;
		$ContentChap['name_mod'] = $name_mod;
		$ContentChap['training'] = $training;
		$ContentChap['id_training'] = $id_training;
		$ContentChap['title_chap'] = $title_chap;
        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
        $this->load->view('e_views/V_content_chapter', $ContentChap);
	}
	public function seeSolution($id_exo, $id_mod = NULL, $id_training = NULL, $id_chap = NULL, $point_if_felt=NULL){
		$back_link = $id_exo.'/'.$id_mod.'/'.$id_training.'/'.$id_chap.'/'.$point_if_felt;
		if (sizeof($this->CaseLearners->IsNote($id_exo)) == 0) {
	        $id_learner = session_data('id');
	        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
	        $Training['list_training'] = $this->M_list_training->listTraining();
	        $this->load->view('e_views/head_learner');
	        $this->load->view('e_views/V_take_courses', $Training);
			$message['msg'] = '
				Vous devez d\'abord traiter l\'exercice !!!	
				<span class="input-group-btn">
					<a href="'.base_url().'e_controllers/C_space_works/work/'.$back_link.'"><button class="btn btn-info" type="button">OK</button></a>
				</span>';
        	$this->load->view('e_views/erroe_exo', $message);
		}
		else{
			$listIsQsts['list_questions'] = $this->CaseLearners->listIsQsts($id_exo);
			$listIsQsts['id_exo'] = $id_exo;

			$listIsQsts['id_training'] = $id_training;

			$listIsQsts['id_mod'] = $id_mod;
			$listIsQsts['id_chap'] = $id_chap;

	        $id_learner = session_data('id');
	        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
	        $Training['list_training'] = $this->M_list_training->listTraining();
	        $this->load->view('e_views/head_learner');
	        $this->load->view('e_views/V_take_courses', $Training);
	        $this->load->view('e_views/V_see_answers', $listIsQsts);
		}		
	}
	public function work($id_exo, $id_mod, $id_chap, $point_if_felt, $id_training){
		$listIsQsts['list_questions'] = $this->CaseLearners->listIsQsts($id_exo);
		$listIsQsts['id_mod'] = $id_mod;
		$listIsQsts['id_chap'] = $id_chap;
		$listIsQsts['id_exo'] = $id_exo;
		$listIsQsts['id_training'] = $id_training;
		$listIsQsts['point_if_felt'] = $point_if_felt;
		$listIsQsts['id_exo'] = $id_exo;
        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);	
        $this->load->view('e_views/V_give_answers', $listIsQsts);	
	}
	public function readingCourses($id_mod, $name_mod, $training, $id_training=null){
		$list_chapters['list_chapter'] = $this->CaseLearners->listIsCoursesMod($id_mod);
		$list_chapters['id_mod'] = $id_mod;
		$list_chapters['name_mod'] = $name_mod;
		$list_chapters['training'] = $training;
		$list_chapters['id_training'] = $id_training;
        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
        $this->load->view('e_views/V_read_chapter', $list_chapters);
	}
	public function referenceCourses($id_mod, $name_mod, $training, $id_training=null){
		$list_chapters['list_chapter'] = $this->CaseLearners->listIsCoursesMod($id_mod);
		$list_chapters['id_mod'] = $id_mod;
		$list_chapters['id_training'] = $id_training;
		$list_chapters['name_mod'] = $name_mod;
		$list_chapters['training'] = $training;
		$list_chapters['id_training'] = $id_training;
        $id_learner = session_data('id');
        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $list_chapters['HisRef'] = $this->M_list_training->selectHisRef($id_mod);
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
        $this->load->view('e_views/V_get_ref', $list_chapters);	
	}
	public function sendWork($id_exo, $point_if_felt, $nb_qst, $id_training, $id_mod, $id_chap){

		$message['point_if_felt'] = $point_if_felt;
		$message['id_training'] = $id_training;
		$message['id_chap'] = $id_chap;
		$message['id_mod'] = $id_mod;
		
		if (sizeof($this->CaseLearners->IsNote($id_exo)) != 0) {
	        $id_learner = session_data('id');
	        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
	        $Training['list_training'] = $this->M_list_training->listTraining();
	        $this->load->view('e_views/head_learner');
	        $this->load->view('e_views/V_take_courses', $Training);
			$message['msg'] = 'Vous avez déjà traiter l\'exercice !!!<br>';
			$message['id_exo'] = $id_exo ;
        	$this->load->view('e_views/success_exo', $message);
		}
		else{
			$id_learner = session_data('id');

			$IsAnswer = array();
			$all_question = array();
			$all_question = $this->CaseLearners->listIsQsts($id_exo);

			$j = 0;
			$note = 0.00;
			for ($i = 0 ; $i < $nb_qst; $i++){
				$IsAnswer[$i] = $this->input->post('answer'.$i);
				foreach ($all_question as $key) {
					if ($key['answer'] == $IsAnswer[$i]) {
						$note += $key['point'];
						$j++;
					}
				}
			}
			$note -= (sizeof($all_question) - $j)*$point_if_felt;
			$this->CaseLearners->sendIsWork($id_exo, $id_learner, $note);
	        $message['msg'] = 'Travail envoyer !!! (Bien vouloir patienter pour les 24 heures à suivre avant de disposer de la correction si elle n\'est pas immédiate.)';
	        $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
	        $Training['list_training'] = $this->M_list_training->listTraining();
	        $this->load->view('e_views/head_learner');
	        $this->load->view('e_views/V_take_courses', $Training);
	        $this->load->view('e_views/send_success', $message);
        }	
	}
	public function selectTheme($id_theme, $id_lesson, $id_learner){
		$id_learner = session_data('id');
		$Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);

        if ($this->CaseLearners->choiseTheme($id_theme, $id_learner, $id_lesson) == 0) {
        	$Training['msg'] = '';
        	$this->load->view('e_views/e_AvailibilityTheme', $Training);
        }
        else{
	        $Training['msg'] = '';
	        $this->load->view('e_views/e_AvailibilityTheme', $Training);
    	}
	}
	public function lepTheme($id_theme){
		$id_learner = session_data('id');
		$Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $Training['msg'] = '';
        $this->load->view('e_views/V_take_courses', $Training);
        $this->CaseLearners->abandonTheme($id_theme, $id_learner);
        $this->load->view('e_views/e_AvailibilityTheme', $Training);		
	}
	public function seeDetails($id_theme, $name_trainer, $name_theme, $id_lesson){
		$id_learner = session_data('id');
		$Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
        $Training['details'] = $this->CaseLearners->detailTheme($id_theme);
        $Training['name_trainer'] = $name_trainer;
        $Training['name_theme'] = $name_theme;
        $Training['id_theme'] = $id_theme;
        $Training['id_lesson'] = $id_lesson;
        $this->load->view('e_views/e_DetailsTheme', $Training);
	}
	public function lastTest(){
		$id_learner = session_data('id');
		$Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $Training['listHisTest'] = $this->CaseLearners->listHisTest($id_learner);
        $this->load->view('e_views/head_learner');
        $this->load->view('e_views/V_take_courses', $Training);
        $this->load->view('e_views/V_list_test', $Training);
	}
	public function ResultExam(){
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
      	$this->load->view('e_views/head_learner');
      	$this->load->view('e_views/V_take_courses', $Training);
      	$this->load->view('e_views/V_Result_Exam', $Training);		
	}
	public function preparetionExamen($id_test, $label_type=null, $id_type_test=null){
		$id_learner = session_data('id');
		$Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
        $Training['list_training'] = $this->M_list_training->listTraining();
        $this->load->view('e_views/head_learner');
        $training_select['id'] = $id_test;
        if ($label_type == null or $label_type == 'Test') {
    		$this->load->view('e_views/Prerequises_test', $training_select);
        }
        else{
        	$training_select['label_type'] = $label_type;
        	$training_select['id_type_test'] = $id_type_test;
        	$this->load->view('e_views/V_last_test', $training_select);
        }
	}
}

?>