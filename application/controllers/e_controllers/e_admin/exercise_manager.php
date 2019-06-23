<?php


/**
 * 
 */
			
class Exercise_manager extends CI_Controller
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));

		$this->load->model('e_models/e_admin/test');
		$this->load->model('e_models/e_admin/question');
		$this->load->model('e_models/e_admin/exercise');

		$this->load->helper('html');
		$this->load->helper('text');
		$this->load->helper('form');

		// $this->load->library('session');
	}

	private function render($view, $titre = NULL, array $data)
    {
        $this->load->view('e_views/e_admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('e_views/e_admin/menu');
        $this->load->view($view, $data);
        $this->load->view('e_views/e_admin/footerAdmin');
    }
	

	public function index() {
		
		$list_lesson = $this->db->select('id , label')->from('lesson')->get()->result_array();

		if (isset($_POST['search_exercise'])) {
			$id_lesson = $_POST['id_lesson'];
			if ($id_lesson != 'all') {
				$query = $this->exercise->get_exercise_lesson($id_lesson);
			}else{
				$query = $this->exercise->get_all_exercise();
			}
		}else{
			$query = $this->exercise->get_all_exercise();
		}

		$this->render('e_views/e_admin/exercise_manager', 'Manage exercises' , array('exercise' => $query , 'list_lesson'=>$list_lesson ) );

		// $this->load->view('exercise_manager' , array('exercise' => $query , 'list_lesson'=>$list_lesson ) );
	}

	public function add() {
		
		if (isset($_POST['add_ex'])) 
		{
			unset($_POST['add_ex']);
			$tet = new $this->exercise;
			$tet->hydrate($_POST);
			$_SESSION['exercise'] = $tet->get();
			unset($_POST);

			$this->render('e_views/e_admin/add_exercise_2', 'Add exercise' , array() );

			// $this->load->view('add_exercise_2');
		}
		elseif (isset($_POST['add_ex_2'])) 
		{
			$this->form_validation->set_rules('question', 'Question', 'required|min_length[4]');
			$this->form_validation->set_rules('answer', 'Answer', 'required|min_length[4]');
			
			if ($_SESSION['exercise']['ex_type'] == 'QRU') {
				$this->form_validation->set_rules('prop1', 'Proposition 1', 'required|min_length[4]');
				$this->form_validation->set_rules('prop2', 'Proposition 2', 'required|min_length[4]');
				$this->form_validation->set_rules('prop3', 'Proposition 3', 'required|min_length[4]');
			}
			
			if ($this->form_validation->run() == TRUE) {			

				if(!isset($_SESSION['question']))
				{
					$_SESSION['question'] = array();
				}
				$quest = $this->question;
				$quest->hydrate($_POST);
				$_SESSION['question'][] = $quest->get();
				$_SESSION['exercise']['number_question'] = count($_SESSION['question']);
				$ex_point = 0;

				for ($i=0; $i < $_SESSION['exercise']['number_question'] ; $i++)
				{ 
					$ex_point += $_SESSION['question'][$i]['point'];
				}

				$_SESSION['exercise']['ex_point'] = $ex_point;
				unset($_POST);

				$this->render('e_views/e_admin/add_exercise_2', 'Add exercise' , array() );

				// $this->load->view('add_exercise_2');
				//exit();


			}else{

				$this->render('e_views/e_admin/add_exercise_2', 'Add exercise' , array() );

				// $this->load->view('add_exercise_2');
			}

		}
		elseif (isset($_POST['add_ex_3'])) 
		{	
			$this->render('e_views/e_admin/add_exercise_2', 'Add exercise' , array() );

			// $this->load->view('add_exercise_2');
		}
		elseif (isset($_POST['end']))
		{
			if ($_SESSION['exercise']['number_question'] == 0) {
				$this->session->set_flashdata('error', '<span class="glyphicon glyphicon-exclamation-sign" aria-hidden="true"></span> Sorry, you have first to add less than one question...');
				
				$this->render('e_views/e_admin/add_exercise_2', 'Add exercise' , array() );
				
				// $this->load->view('add_exercise_2');
			}else{
				$this->session->set_flashdata('info', '<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> Overview all questions you have enterred, and click to confirm...<br>
					<span class="glyphicon glyphicon-cog" aria-hidden="true"></span> You can also modify it after in the database...');
				
				$this->render('e_views/e_admin/add_exercise_overview', 'Add exercise' , array() );
				
				// $this->load->view('add_exercise_overview');
			}
		}
		elseif (isset($_POST['confirm'])) 
		{
			$_SESSION['exercise']['date'] = date('Y-m-d h:i:s');
			$_SESSION['exercise']['date_modify'] = date('Y-m-d h:i:s');
			$this->db->insert('e_exercise', $_SESSION['exercise']);
			
			$id_exercise = $this->db->insert_id();

			foreach ($_SESSION['question'] as $question ) {
				$question['id_exercise'] = $id_exercise;
				$this->db->insert('e_question', $question);
			}

			$this->session->set_flashdata('info','<span class="glyphicon glyphicon-info-sign" aria-hidden="true"></span> Exrcise "<em>'.$_SESSION['exercise']['ex_label'].'</em>" are successfully added...');
			unset($_SESSION['chapter']); //// Have to examinate it again...
			redirect('e_controllers/e_admin/exercise_manager');
		}
		elseif (isset($_POST['previous'])) 
		{	
			echo "previous";
			
			$this->render('e_views/e_admin/add_exercise', 'Add exercise' , array() );
			
			// $this->load->view('add_exercise');
		}
		elseif (isset($_POST['add_question'])) 
		{
			$this->form_validation->set_rules('question', 'Question', 'required|min_length[4]');
			$this->form_validation->set_rules('answer', 'Answer', 'required|min_length[4]');
			
			if ($_POST['type_question'] == 'QRU') {
				$this->form_validation->set_rules('prop1', 'Proposition 1', 'required|min_length[4]');
				$this->form_validation->set_rules('prop2', 'Proposition 2', 'required|min_length[4]');
				$this->form_validation->set_rules('prop3', 'Proposition 3', 'required|min_length[4]');
			}
			
			if ($this->form_validation->run() == TRUE) {			

				$this->question->insert_entry();
				$this->exercise->update_point_question($_POST['id_exercise']);
				redirect('e_controllers/e_admin/exercise_manager/modify/' . $_POST['id_exercise']);
				// $this->modify($_POST['id_exercise']);

			}else{
				$this->modify($_POST['id_exercise']);
				// redirect('exercise_manager/modify/' . $_POST['id_exercise']);
			}

		}
		else
		{
			$query = $this->db->get('lesson');
			$lesson = array();
			$chapter = array();
			$chapter[0] = array(null=>'Choose chapter');
			foreach ($query->result() as $row) {
	           $lesson[$row->id] = $row->label;
	           $this->db->select('*');
	           $this->db->from('e_chapter');
	           $this->db->where('id_lesson',$row->id);
	           $query2 = $this->db->get();
	           $chapter[$row->label] = array();
	           foreach ($query2->result() as $row2) {
	           		$chapter[$row->label][$row2->id_chap] = $row2->title_chap;
	           }
	         }
			unset ($_SESSION['exercise']);
			unset ($_SESSION['question']);
			unset ($_SESSION['chapter']);
			$_SESSION['chapter'] = $chapter; //not need yet to passes it in the view...
			
			$this->render('e_views/e_admin/add_exercise', 'Add exercise' , array( 'lesson' => $lesson ) );
			
			// $this->load->view('add_exercise', array('lesson' => $lesson ) );
		}
	}

	public function overview($id){
		$exercise = $this->exercise->get_exercise($id);
		$question_list = $this->question->get_exercise_question($id);
		
		$this->render('e_views/e_admin/exercise_overview', 'Overview exercise' , array('exercise'=>$exercise , 'question_list'=>$question_list ) );
		
		// $this->load->view('exercise_overview',array('exercise'=>$exercise , 'question_list'=>$question_list ));
	}

	public function modify($id){
		$exercise = $this->exercise->get_exercise($id);
		$question_list = $this->question->get_exercise_question($id);

		$query = $this->db->get('lesson');
		$chapter = array();
		$chapter[0] = array(null=>'Choose chapter');
		foreach ($query->result() as $row) {
	        $this->db->from('e_chapter');
	        $this->db->where('id_lesson',$row->id);
	        $query2 = $this->db->get();
	        $chapter[$row->label] = array();
	        foreach ($query2->result() as $row2) {
         		$chapter[$row->label][$row2->id_chap] = $row2->title_chap;
	        }
	    }

		$this->render('e_views/e_admin/exercise_modify', 'Modify exercise' , array('exercise'=>$exercise , 'question_list'=>$question_list , 'chapter'=>$chapter ) );

		// $this->load->view('exercise_modify',array('exercise'=>$exercise , 'question_list'=>$question_list , 'chapter'=>$chapter ));
	}

	public function modify_exercise(){
		$id_exercise = $_POST['id_exercise'];
		if (isset($_POST['update_exercise'])) {
			unset ($_POST['update_exercise']);
			// $this->exercise->get_exercise($_POST['id_exercise']);
			// $this->exercise->ex_label = $_POST['ex_label'];
			// $this->exercise->id_chap = $_POST['id_chap'];
			$_POST['date_modify'] = date('Y-m-d h:i:s');
			if ($_POST['id_chap'] == '') {
				$_POST['id_chap'] = null;
			}
			if($this->db->update('e_exercise' , $_POST , 'id_exercise = '.$_POST['id_exercise']  )){
				$this->exercise->update_point_question($_POST['id_exercise']);
				// $this->session->set_flashdata('update_exercise_info','Successfully update this exercise...');
			}
		}

		$this->exercise->update_point_question($id_exercise);

		$this->modify($id_exercise);

		// redirect('exercise_manager/modify/'.$_POST['id_exercise']);
	}

	public function modify_question(){
		if (isset($_POST['update_question'])) {
			$this->form_validation->set_rules('question'.$_POST['id_question'], 'Question', 'required|min_length[4]');
			$this->form_validation->set_rules('answer'.$_POST['id_question'], 'Answer', 'required|min_length[4]');
			
			if ($_POST['type_question'] == 'QRU') {
				$this->form_validation->set_rules('prop1'.$_POST['id_question'], 'Proposition 1', 'required|min_length[4]');
				$this->form_validation->set_rules('prop2'.$_POST['id_question'], 'Proposition 2', 'required|min_length[4]');
				$this->form_validation->set_rules('prop3'.$_POST['id_question'], 'Proposition 3', 'required|min_length[4]');
			}
			
			if ($this->form_validation->run() == TRUE) {
			
				unset ($_POST['update_question']);
				
				if( $this->question->modify_question() ) {
					$this->session->set_flashdata('update_question_info'.$_POST['id_question'],'Successfully update this question');
					$this->exercise->update_point_question($_POST['id_exercise']);

					redirect('e_controllers/e_admin/exercise_manager/modify/' . $_POST['id_exercise']);
				}
			}else{
				$this->session->set_flashdata('update_question_info'.$_POST['id_question'],'Some error are occured...!!');
				$this->modify($_POST['id_exercise']);
			}

		}
	}

	public function delete_exercise($id){
		$this->db->where('id_exercise', $id);
		if ($this->db->delete(array('e_exercise' , 'e_question' ) ) == false)
		{
			$this->session->set_flashdata('delete_exercise_info','No able to delete the exercise...');
		}else{
			$this->session->set_flashdata('delete_exercise_info','Successfully delete the exercise...');
		}
		redirect('e_controllers/e_admin/exercise_manager');
	}

	public function copy_exercise($id_exercise){
		$this->exercise->copy_exercise($id_exercise);
		redirect('e_controllers/e_admin/exercise_manager' , 'refresh');
	}

	public function delete_question($id_exercise , $id_question){
		$this->db->where('id_question' , $id_question);
		if ($this->db->delete( 'e_question' ) == false)
		{
			$this->session->set_flashdata('delete_question_info','No able to delete the question...');
		}
		else
		{
			$this->session->set_flashdata('delete_question_info','Successfully delete the the question...');
		}

		$this->exercise->update_point_question($id_exercise);

		redirect('e_controllers/e_admin/exercise_manager/modify/'.$id_exercise);

	}

	public function activate($id , $status){
		$this->db->update('e_exercise' , array('status' => $status) , 'id_exercise ='.$id );
		$this->exercise->update_point_question($id);
		redirect('e_controllers/e_admin/exercise_manager');
	}

	public function get_exercise_details($id_exercise){
		$this->exercise->get_exercise_details($id_exercise);
	}


}