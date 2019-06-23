<?php

/**
 * Authentification
 */

class Generate extends CI_Controller
{

	public function __construct()
	{ 
		parent::__construct();
		$this->load->model('auth/auth_model', 'authM');
/*
        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));*/

		$this->load->model('e_models/e_admin/paid');
		$this->load->model('e_models/e_admin/wave');

		$this->load->helper('html');
		$this->load->helper('form');
		$this->load->helper('text');
		$this->load->helper('url');

	}

	private function render($view, $titre = NULL, array $data)
    {
        $this->load->view('e_views/e_admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('e_views/e_admin/menu');
        $this->load->view($view, $data);
        $this->load->view('e_views/e_admin/footerAdmin');
    }
	
	public function index(){

		// $this->render('e_views/public/exemple00', 'Download' , array() );
		// $list_lesson = $this->db->get('lesson')->result();
		// $this->bill_payment('4');
		$this->attestation(78 , 15);
	}

	public function list_learner(){

		$list_product = $this->db->get_where('e_product' , array('admit') )->result();
		$list_admit = array();
		foreach ($list_product as $product) {
			$statement = $this->db->get_where('e_statement' , array('id_state' => $product->id_state) )->row();
			$user = $this->db->get_where('user', array('id' => $statement->id_user) )->row();
			$composition = $this->db->get_where('e_composition', array('id_compo' => $statement->id_compo) )->row();
			$wave = $this->db->get_where('e_wave', array('id_wave' => $composition->id_wave) )->row();
			$list_admit[] = array('product'=>$product , 'user'=>$user , 'wave' => $wave);
		}

		$this->render('e_views/e_admin/list_admit' , 'Attestation list des admis' , array( 'list_admit' => $list_admit ) );
	}

	public function bill_payment($id_paid){

		$inscription = $this->paid->get($id_paid);
		$details = $this->paid->get_details($id_paid);

		$this->load->view('e_views/public/exemple00' , array( 'inscription' => $inscription , 'details' => $details) );
	}

	public function attestation($id_user,$id_wave){
		$this->load->helper("html2pdf_helper");

        $details = $this->wave->get_details($id_wave);
		$learner = $this->db->get_where('user' , array('id' => $id_user) )->row();
		$wave = $this->db->get_where('e_wave' , array('id_wave' => $id_wave))->row();


            $content = $this->load->view('e_views/public/attestation', array( 'learner' => $learner , 'details' => $details) , TRUE );
            try{
    
                $pdf = new HTML2PDF('P', 'A4', 'fr' );
                $pdf->pdf->setDisplayMode('fullpage');
    			$pdf->setDefaultFont('Arial');
                $pdf->writeHTML($content);
                ob_get_clean();
                $pdf->Output($learner->number_id.'_'.$wave->code_wave.'.pdf');
            }catch (HTML2PDF_exception $e){
                die($e);
            }
    }

    public function do_upload($id_prd)
        {
                $config['upload_path']          = './assets/uploads/e_documents/Certificats/';
                $config['allowed_types']        = 'pdf|PDF';
                $config['max_size']             = 1000;

                $this->load->library('upload', $config);

                if ( ! $this->upload->do_upload('userfile'))
                {
                        echo "faute";
                }
                else
                {
                        $data = array('upload_data' => $this->upload->data());


                        $this->db->update('e_product' , array('status'=>'1') , 'id_prd='.$id_prd );

                        $this->load->view('e_views/e_admin/upload_success', $data);

                }


        }
	
	 public function printQuitus($id_paid){
        $this->load->helper("html2pdf_helper");

        $inscription = $this->paid->get($id_paid);
		$details = $this->paid->get_details($id_paid);


            $content = $this->load->view('e_views/public/bill_payment', array( 'inscription' => $inscription , 'details' => $details) , TRUE );
            try{
    
                $pdf = new HTML2PDF('P', 'A4', 'fr' );
                $pdf->pdf->setDisplayMode('fullpage');
    			$pdf->setDefaultFont('Arial');
                $pdf->writeHTML($content);
                ob_get_clean();
                $pdf->Output('Quitus--.pdf');
            }catch (HTML2PDF_exception $e){
                die($e);
            }       
    }
	
}