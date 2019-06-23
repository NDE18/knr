<?php


/**
 * 
 */
class Composer extends CI_Controller
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
		$this->load->model('e_models/e_admin/question');

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
		$list_exercise = $this->exercise->get_exercise_test(14);
		$test = $this->test->get_test(14);

		$this->render('e_views/e_admin/composer', 'Composer test' , array( 'test'=>$test , 'list_exercise' => $list_exercise ) );

		// $this->load->view('test', array( 'list_test' => $list_test , 'details'=>$details , 'list_type_test'=>$list_type_test , 'list_wave' => $list_wave) );

	}

	public function receive(){
		$this->render('e_views/e_admin/resultats', 'Resultat test' , array( 'reponse' => $_POST ) );

	}

}