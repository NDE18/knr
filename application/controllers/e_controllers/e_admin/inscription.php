<?php


/**
 * Authentification
 */
class Inscription extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));

		$this->load->model('e_models/e_admin/paid');

		$this->load->helper('html');
		$this->load->helper('form');

	}

	private function render($view, $titre = NULL, array $data)
    {
        $this->load->view('e_views/e_admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('e_views/e_admin/menu');
        $this->load->view($view, $data);
        $this->load->view('e_views/e_admin/footerAdmin');
    }
	
	public function index() {
		$details = array();
		if (isset($_POST['search_inscription'])) {
			$method = 'get_inscription_'.$_POST['validation_state'];
			$list = $this->paid->$method();
		}else {
			$list = $this->paid->get_inscription_wait();
		}

		if ( count($list) != 0 ) {
			foreach ($list as $data) {
				$details[$data['id_paid']] = $this->paid->get_details($data['id_paid']);
			}
		}

		$this->render('e_views/e_admin/inscription', 'gerer inscription' , array('list' => $list , 'details' => $details ) );
		
		// $this->load->view('e_views/e_admin/inscription' , array('list' => $list , 'details' => $details ) );
	}

	public function reject($id_paid){
		$this->paid->reject($id_paid);
		// NOTIFICATION
		redirect('e_controllers/e_admin/inscription','refresh');
	}

	public function insert($id_paid){
		redirect('e_controllers/e_admin/wave_manager/insert/'.$id_paid , 'refresh');
	}

	public function add_student($id_paid , $id_wave){
		redirect('e_controllers/e_admin/wave_manager/add_student/'.$id_paid.'/'.$id_wave , 'refresh');
	}

	
}