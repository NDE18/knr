<?php


/**
 * 
 */
			
class Wave_manager extends CI_Controller
{
	
	public function __construct() 
	{
		parent::__construct();
		$this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
		
			$this->load->model('e_models/e_admin/paid');
			
			$this->load->model('e_models/e_admin/wave');

			$this->load->helper('html');
			$this->load->helper('form');
			$this->load->helper('text');
			$this->load->helper('date');

			$this->load->library('form_validation');
	}

	private function render($view, $titre = NULL, array $data)
    {
        $this->load->view('e_views/e_admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('e_views/e_admin/menu');
        $this->load->view($view, $data);
        $this->load->view('e_views/e_admin/footerAdmin');
    }

	public function index(){

		$list_lesson = $this->db->select('id , label')->from('lesson')->get()->result_array();

		if (isset($_POST['search_wave'])) {
			$list = $this->wave->get_specific_wave($_POST['id_lesson'] , $_POST['type_wave'] , $_POST['status']);
		}else{
			$list = $this->wave->get_wave_all();
		}
		
		$this->render('e_views/e_admin/wave_manager' , 'Gerer Vagues' , array('list'=>$list , 'list_lesson'=>$list_lesson ));

		// $this->load->view('wave_manager',array('list'=>$this->wave->get_wave_all() ) );
		// $this->load->view('wave_manager');
	}

	public function activate($id_wave,$status){
		$this->wave->update_status($id_wave,$status);
		redirect('e_controllers/e_admin/wave_manager');
	}

	public function insert($id_paid ) //data from e_paid table
	{
		$inscription = $this->paid->get($id_paid);
		$details = $this->paid->get_details($id_paid);
		$list_lesson = $this->db->select('id , label')->from('lesson')->get()->result_array();

		$list_user_role = $this->wave->list_user_role('3');
		$list_formator = array();
		foreach ($list_user_role as $user_role) {
			$list_formator[$user_role->id] = character_limiter($user_role->firstname, 10).'  '.character_limiter($user_role->lastname , 10);
		}
		
		if (isset($_POST['search'])) {
			$list_wave = $this->wave->listByLesson_type( $_POST['lesson'] , $_POST['formation_type'] );
		}elseif( isset($_POST['best']) || true ){
			$_POST['lesson'] = $inscription['id_lesson'];
			$_POST['formation_type'] = $inscription['formation_type'];
			$list_wave = $this->wave->listByLesson_type( $inscription['id_lesson'] , $inscription['formation_type'] );
		}

		$this->render('e_views/e_admin/insertion' , 'Vagues->insertion' , array( 'inscription' => $inscription , 'list_lesson' => $list_lesson , 'list_formator'=>$list_formator , 'details' => $details , 'list_wave' => $list_wave ) );

		// $this->load->view('insertion' , array( 'inscription' => $inscription , 'list_lesson' => $list_lesson , 'list_user'=>$list_user , 'details' => $details , 'list_wave' => $list_wave ) );
	}

	public function create_wave($id_paid){
		if (isset($_POST['create_wave'])) {
			$paid = $this->paid->get($id_paid);
			$code = $this->db->get_where('lesson' , 'id='.$paid['id_lesson'])->row()->code;

			$id_wave = $this->wave->create_wave($code);

			$this->wave->insert_delay($id_wave);

			$this->add_student($id_paid , $id_wave);

            $this->paid->accept($id_paid , $id_wave);
			
		}
	}

	public function add_student($id_paid,$id_wave){
		$paid = $this->paid->get($id_paid);
		// if ( $paid['id_wave'] == null ) {
		// 	$this->wave->add_student($id_paid , $id_wave);
		// }elseif ( $paid['id_wave'] != null and $paid['id_wave'] == $id_wave) {
		// 	exit();
		// }
			$this->wave->add_student($id_paid , $id_wave);
		
            $this->paid->accept($id_paid , $id_wave);
            
		redirect('e_controllers/e_admin/inscription/index' , 'refresh');
	}

	public function delete($id_wave){
		$this->wave->delete($id_wave);
		redirect('e_controllers/e_admin/wave_manager' , 'refresh');
	}

	public function overview($id_wave){
		$wave = $this->wave->get($id_wave);

		$details = $this->wave->get_details($id_wave);

		$delays = $this->wave->get_wave_delay($id_wave);

		$list_user_role = $this->wave->list_user_role('3');
		$list_formator = array();
		foreach ($list_user_role as $user_role) {
			$list_formator[$user_role->id] = character_limiter($user_role->firstname, 10).'  '.character_limiter($user_role->lastname , 10);
		}

		$this->render('e_views/e_admin/wave_overview' , 'Wave->Overview' , array( 'wave'=>$wave , 'details'=>$details , 'delays'=>$delays , 'list_formator'=>$list_formator ) );

	}

	public function wave_learner_control($id_content , $id_user , $status){
		$content = $this->db->get_where('e_content' , arraY('id_cnt'=>$id_content) )->row();
		if ($content) {
			
			if($content->id_wv != null and in_array($status , array('1','0','-1')) ){ // Verifie la valeur de $status
				if ($content->id_user == $id_user ) { //Verifie s'il s'agit du bon $user
					if ( $content->status != $id_user ) {
						$this->db->update( 'e_content' , array('status'=>$status) , 'id_cnt='.$content->id_cnt );
					}
				}

				redirect('e_controllers/e_admin/wave_manager/overview/'.$content->id_wv , 'refresh');
			}		
		}		

		redirect('e_controllers/e_admin/wave_manager' , 'refresh');
	}

	public function modify_delay(){
		$this->wave->modify_delay();

		$this->wave->update_content($_POST['id_wave']);

		redirect('e_controllers/e_admin/wave_manager/overview/'.$_POST['id_wave'] , 'refresh');
	}
	
	public function modify_wave_formator(){
		$data = array();
		$data['id_user'] = $_POST['id_user'];
		$this->wave->update_wave( $_POST['id_wave'] , $data );

		redirect('e_controllers/e_admin/wave_manager/overview/'.$_POST['id_wave'] , 'refresh');
	}

	public function modify_date_end(){
		$data = array();
		$data['date_end'] = $_POST['date_end'];
		$this->wave->update_wave( $_POST['id_wave'] , $data );

		redirect('e_controllers/e_admin/wave_manager/overview/'.$_POST['id_wave'] , 'refresh');
	}

	public function list_paid($id_cnt , $id_user){
		$this->load->helper("html2pdf_helper");

		$content_msacad = $this->db->get_where( 'e_content' , array('id_cnt'=>$id_cnt) )->row();
		$list_paid_user_wave = $this->paid->list_paid_user_wave( $content_msacad->id_user , $content_msacad->id_wv);

		$details_wave = $this->wave->get_details($content_msacad->id_wv);
		$learner = $this->db->get_where('user' , array('id' => $content_msacad->id_user) )->row();

		if($id_user == $content_msacad->id_user){
			$content = $this->load->view('e_views/public/list_paid_user_wave', array( 'learner' => $learner , 'details_wave' => $details_wave , 'list_paid_user_wave'=>$list_paid_user_wave ) , TRUE );
	        try{
	    
	            $pdf = new HTML2PDF('P', 'A4', 'fr' );
	            $pdf->pdf->setDisplayMode('fullpage');
	   			$pdf->setDefaultFont('Arial');
	            $pdf->writeHTML($content);
	            ob_get_clean();
	            $pdf->Output($learner->firstname.'_'.$details_wave['lesson']->code.'_listOfPay.pdf');
	        }catch (HTML2PDF_exception $e){
	            die($e);
	        }
		}else{
			redirect('e_controllers/e_admin/wave_manager/overview/'.$content_msacad->id_wv , 'refresh');
		}


        

	}

}