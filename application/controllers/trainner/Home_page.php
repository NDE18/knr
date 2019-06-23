<?php
	/**
	 * 
	 */
	class Home_page extends CI_Controller
	{
		
		public function nbre_cours()
		{
		$this->load->model('Courses');
		$lists['var'] =$this->Courses->nbr_cours();
		$this->load->view('trainner/index', $lists);
		}
	
	}
  ?>