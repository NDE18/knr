<?php

/**
 * Created by PhpStorm.
 * User: Harrys Crosswell
 * Date: 31/08/2017
 * Time: 07:02
 */
class Request extends  CI_Controller
{
    protected  $data, $menu;

    function __construct()
    {
        parent::__construct();
        $this->load->model('public/lesson_model', 'mLesson');
        $this->load->model('public/general_model', 'mGeneral');
        $this->load->model('backfront/message_model', 'mMessage');
        $this->load->model('backfront/notifications_model', 'notification');
        $this->load->model('backfront/log_model', 'logM');
        $this->load->model('backfront/user_model', 'userM');
        updateVisit();
        $this->data['allReg'] = $this->mGeneral->getAllInscription();
        $this->data['allLes'] = $this->mGeneral->getAllLesson();
        $this->data['allMem'] = $this->mGeneral->getAllMember();
        $this->data['visits'] = $this->mGeneral->getAllVisits();
        $this->data['visitors'] = $this->mGeneral->getAllVisitors();
        $this->data['cLesson'] = $this->mLesson->getByType('cours')->result();
        $this->data['fLesson'] = $this->mLesson->getByType('filière')->result();
        $this->data['pLesson'] = $this->mLesson->getByType('promotion')->result();
        $this->load->library('form_validation');
    }

    public function index(){
        $this->data['requetes'] = $this->mMessage->getRequets();
        $this->meta = array(
            "description"=>"Liste des requêtes académiques traitées par le centre",
            "url"=>base_url("requetes"),
            "image"=>img_url('logo/logo.png')
        );

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Liste des requêtes académiques traitées"=>"#"
        );

        $this->render('Liste des requêtes traitées par le centre','request-list');

    }


    private function render($titre=NULL,$view = NULL)
    {
        $notif = array();
        if(session_data('connect')){
            $notif = $this->userM->getUserNotif();
        }
        $this->load->view('public/header', array('titre'=>$titre,"meta"=>$this->meta,'notif'=>$notif));
        if($view!=null)
        {
            $this->data['view'] = $view;
        }
        else
        {
            $this->data['view'] = "list";
        }
        $this->load->view("public/request-page-model", $this->data);
        $this->load->view('public/footer');
    }

}