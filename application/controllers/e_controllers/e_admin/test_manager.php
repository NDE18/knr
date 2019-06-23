<?php


/**
 * 
 */
class Test_manager extends CI_Controller
{
	
	public function __construct()
	{
		parent::__construct();
		$this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));

		$this->load->model('e_models/e_admin/test');
		$this->load->model('e_models/e_admin/wave');
		$this->load->model('e_models/e_admin/exercise');

		$this->load->helper('text');
		$this->load->helper('html');

		$this->test->update_test_all();
	}

	private function render($view, $titre = NULL, array $data)
    {
        $this->load->view('e_views/e_admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('e_views/e_admin/menu');
        $this->load->view($view, $data);
        $this->load->view('e_views/e_admin/footerAdmin');
    }

	public function index() {
		$list_test = array();
		$details = array();
		$list_wave = $this->wave->get_wave_all();
		$list_type_test = $this->db->get('e_type_test')->result();

		if (isset($_POST['search_test'])) {

			$id_wave = $_POST['id_wave'];
			$id_type_test = $_POST['id_type_test'];

			if ($id_wave=='all' && $id_type_test=='all' ) {
				$list_test = $this->test->get_test_all();
			}elseif ( $id_wave != 'all' && $id_type_test != 'all' ) {
				$list_test = $this->test->get_test_wave_type($id_wave , $id_type_test);
			}elseif( $id_wave != 'all' && $id_type_test == 'all' ){
				$list_test = $this->test->get_test_wave($id_wave);
			}elseif( $id_wave == 'all' && $id_type_test != 'all' ){
				$list_test = $this->test->get_test_type($id_type_test);
			}

		}else{
			$list_test = $this->test->get_test_all();
		}

		if ( count($list_test) != 0 ) {
			foreach ($list_test as $data) {
				$details[$data['id_test']] = $this->test->get_details($data['id_test']);
			}
		}

		$this->render('e_views/e_admin/test', 'Manage tests' , array( 'list_test' => $list_test , 'details'=>$details , 'list_type_test'=>$list_type_test , 'list_wave' => $list_wave) );

		// $this->load->view('test', array( 'list_test' => $list_test , 'details'=>$details , 'list_type_test'=>$list_type_test , 'list_wave' => $list_wave) );

	}

	public function build_test(){
		$list_lesson = $this->db->select('id , label')->from('lesson')->get()->result_array();
		$list_type_test = $this->db->get('e_type_test')->result();

		if (isset($_POST['search_exercise'])) {
			$id_lesson = $_POST['id_lesson'];
			if ($id_lesson != 'all') {
				if ( $id_lesson == null ) {
					$list_wave = $this->wave->get_wave_all();
				}else{
					$list_wave = $this->wave->get_wave_lesson($id_lesson); 
				}
				$query = $this->exercise->get_exercise_lesson($id_lesson);
			}else{
				$query = $this->exercise->get_all_exercise();
				$list_wave = $this->wave->get_wave_all();
			}
		}elseif (isset($_POST['build_test'])) {
			if (isset($_POST['exercise'])) {
				$this->test->build_test();
				redirect('e_controllers/e_admin/test_manager' );
			}else{
				$this->session->set_flashdata('build_test_info', '<div class="alert alert-danger">You most select an exercise</div>');
				redirect('e_controllers/e_admin/test_manager/build_test' );
			}
			
		}else{
			$query = $this->exercise->get_all_exercise();
			$list_wave = $this->wave->get_wave_all();
		}

		$this->render('e_views/e_admin/build_test', 'Test->Build' , array('exercise' => $query , 'list_lesson'=>$list_lesson , 'list_type_test'=>$list_type_test , 'list_wave'=>$list_wave) );

		// $this->load->view('build_test' , array('exercise' => $query , 'list_lesson'=>$list_lesson , 'list_type_test'=>$list_type_test , 'list_wave'=>$list_wave) );
	}

	public function program($id_test){
				
		$test = $this->test->get_test($id_test);

		$details = $this->test->get_details($test['id_test']);
		
		$list_wave = $this->wave->get_wave_lesson($details['wave_id_lesson']);

		if (isset($_POST['program'])) {
			$this->test->program();
			redirect('e_controllers/e_admin/test_manager');
		}

		$this->render('e_views/e_admin/program_test', 'Test->Progam' , array('test'=>$test , 'details'=>$details , 'list_wave'=>$list_wave) );

		// $this->load->view('program_test' , array('test'=>$test , 'details'=>$details , 'list_wave'=>$list_wave));
	}

	public function confirm_program($id_test){
		$this->test->confirm_program($id_test);
		
		redirect('e_controllers/e_admin/test_manager');
	}

	public function view_content($id_test) {
		$query = $this->exercise->get_exercise_test($id_test);

		$this->render('e_views/e_admin/exercise_manager', 'Test->Exercises' , array('exercise' => $query , 'list_lesson'=>false) );

		// $this->load->view('exercise_manager' , array('exercise' => $query , 'list_lesson'=>false) );
	}

	public function re_use($id_test){
		$this->test->re_use($id_test);
		redirect('e_controllers/e_admin/test_manager' );
	}

	public function delete_test($id_test){
		$this->test->delete_test($id_test);
		redirect('e_controllers/e_admin/test_manager');
	}

	public function change_test_date(){
		if($this->test->change_test_date()) {
			redirect('e_controllers/e_admin/test_manager');
		}
		redirect('e_controllers/e_admin/test_manager');
	}

	public function cancel_test($id_test){
		if( $this->test->cancel_test($id_test) ) {
			redirect('e_controllers/e_admin/test_manager');
		}

	}

	public function test1() {
		$_SESSION['test'] = array();
		if (isset($_POST['cancel'])) {
			unset($_SESSION['test']);
		}
		if(isset($_POST['previous'])){
			
		}
		if(isset($_POST['next'])){
			$this->test->insert_entry();
			$result = $this->test->get_last_ten_entries();
			echo $result[3]->id_user;
			$_SESSION['test']['id_wave'] = $_POST['id_wave'];
			$_SESSION['test']['id_type_test'] = $_POST['id_type_test'];
			$this->load->view('add_test_2');
		}
	}
}