<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Frontpage extends CI_Controller {

	protected $data, $menu;

	function __construct()
	{

		parent::__construct();
		updateVisit();
		$this->load->model('public/news_model', 'mNews');
		$this->load->model('public/message_model', 'mMessages');
		$this->load->model('public/flash_model', 'mFlash');
		$this->data['infosFlash'] = $this->mFlash->get(null,0,3);
        	$this->load->model('backfront/user_model', 'userM');
	}

	public function index()
	{
		$this->load->model('public/lesson_model', 'mLesson');
		$this->load->model('public/general_model', 'mGeneral');

		$this->data['cLesson'] = $this->mLesson->getByType('cours')->result();
		$this->data['fLesson'] = $this->mLesson->getByType('filière')->result();
		$this->data['pLesson'] = $this->mLesson->getByType('promotion')->result();
		$this->data['allNews'] = $this->mNews->get(null,0,3);
		$this->data['slides'] = $this->mNews->getSlides();
		$this->data['allReg'] = $this->mGeneral->getAllInscription();
		$this->data['allLes'] = $this->mGeneral->getAllLesson();
		$this->data['allMem'] = $this->mGeneral->getAllMember();
		$this->data['visits'] = $this->mGeneral->getAllVisits();
		$this->data['visitors'] = $this->mGeneral->getAllVisitors();
		$this->data['testimonials'] = $this->mMessages->get();

		$this->load->model('public/events_model', 'mEvent');
		$this->data['lastEvent'] = $this->mEvent->get(null,0,3);
		$this->render();
	}


	private function render($view=NULL,$titre = NULL)
	{
        $notif = array();
		if(session_data('connect')){
            $notif = $this->userM->getUserNotif();
		}

        $this->load->view('public/header', array('titre'=>$titre,'notif'=>$notif));
		if($view!=null)
        {
            $this->data['view'] = $view;
            $this->load->view("public/static-page-model", $this->data);
        }
		else
        {
            $this->load->view("public/homepage", $this->data);
        }

		$this->load->view('public/footer');
	}
}
