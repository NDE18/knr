<?php

/**
 * Created by PhpStorm.
 * User: Harrys Crosswell
 * Date: 17/08/2017
 * Time: 13:02
 */
class Barbillard extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');
        $this->load->model('public/lesson_model', 'mLesson');
        $this->load->model('public/general_model', 'mGeneral');
        $this->load->model('public/Forum_model', 'mForum');
        updateVisit();

        $this->load->model('public/Barbillard_model', 'barbillard');
        $this->load->model('backfront/user_model', 'userM');
        $this->load->model('backfront/notifications_model', 'notification');
        $this->load->model('backfront/log_model', 'logM');


        $this->data['allReg'] = $this->mGeneral->getAllInscription();
        $this->data['allLes'] = $this->mGeneral->getAllLesson();
        $this->data['allMem'] = $this->mGeneral->getAllMember();

        $this->data['visits'] = $this->mGeneral->getAllVisits();
        $this->data['visitors'] = $this->mGeneral->getAllVisitors();
        $this->data['cLesson'] = $this->mLesson->getByType('cours')->result();
        $this->data['fLesson'] = $this->mLesson->getByType('filière')->result();
        $this->data['pLesson'] = $this->mLesson->getByType('promotion')->result();
    }

    public function index(){
        $this->meta = array(
            "description"=>"Liste des éléments du barbillard de MULTISOFT ACADEMY",
            "url"=>base_url('barbillard'),
            "image"=>img_url('barbillard.png')
        );

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Le Barbillard de MULTISOFT ACADEMY"=>"",
        );

        $this->render('Le Barbillard de MULTISOFT');
    }

    public function apprenants(){
        $this->data['students'] = $this->barbillard->getStudentList();
        $this->meta = array(
            "description"=>"La liste des apprenants inscrits à MULTISOFT ACADEMY",
            "url"=>base_url('barbillard/apprenants'),
            "image"=>img_url('barbillard.png')
        );

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Barbillard" => base_url('barbillard'),
            "La liste des apprenants inscrits"=>"",
        );

        $this->render("La liste des apprenants inscrits","apprenants");
    }
    public function evaluations(){
        $this->data['evaluations'] = $this->barbillard->getEvaluationList();
        $this->meta = array(
            "description"=>"La liste des évaluations des différents enseignements dispensés à MULTISOFT ACADEMY",
            "url"=>base_url('barbillard/evaluation'),
            "image"=>img_url('logo/logo.png')
        );

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Barbillard" => base_url('barbillard'),
            "La liste des évaluations"=>"",
        );

        $this->render("La liste des évaluations des différents enseignements dispensés à MULTISOFT ACADEMY","evaluations");
    }

    private function render($titre = NULL,$view=NULL)
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
            $this->data['view'] = "index";
        }
        $this->load->view("public/barbillard-page-model", $this->data);
        $this->load->view('public/footer');
    }
}