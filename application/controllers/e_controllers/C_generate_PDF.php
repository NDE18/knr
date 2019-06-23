<?php

  class C_generate_PDF extends CI_Controller{	
	function __construct(){
	  parent::__construct();
	  $this->load->model('e_models/M_generate_PDF');
	  $this->load->library('e_libraries/Pdf');
	}
	public function example(){
		$filename = 'ulriche';
        $this->pdf->setPaper('A4', 'landscape')->filename($filename)->zone('e_views')->render_view('example')->generate();
	}
	public function index_PDF($document_name, $name_view){
      $this->pdf->setPaper('A4', 'landscape')->filename($document_name)->zone('e_views')->render_view($name_view)->generate();
	}
  }

?>
