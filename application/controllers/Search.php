<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends CI_Controller
{

    protected $data, $menu;


    function __construct()
    {
        parent::__construct();
        $this->load->model('backfront/user_model', 'userM');
        $this->load->model('public/lesson_model', 'mLesson');
        $this->load->model('public/general_model', 'mGeneral');
        updateVisit();
        $this->data['allReg'] = $this->mGeneral->getAllInscription();
        $this->data['allLes'] = $this->mGeneral->getAllLesson();
        $this->data['allMem'] = $this->mGeneral->getAllMember();
        $this->data['visits'] = $this->mGeneral->getAllVisits();
        $this->data['visitors'] = $this->mGeneral->getAllVisitors();
        $this->data['cLesson'] = $this->mLesson->getByType('cours')->result();
        $this->data['fLesson'] = $this->mLesson->getByType('filière')->result();
        $this->data['pLesson'] = $this->mLesson->getByType('promotion')->result();
        $this->load->model('admin/search_model', 'm_search');
        $this->load->model('backfront/user_model', 'userM');
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
            $this->data['view'] = "result";
        }

        $this->load->view("public/search-page-model", $this->data);
        $this->load->view('public/footer');
    }

    public function index()
    {
        if(isset($_GET['key'])){

            $key = $_GET['key'];
            $this->data ['breadcrumb'] = array(
                "Accueil" => base_url(),
                "Résultat de la recherche"=>base_url("search"),
                $_GET['key'] =>"#",
            );
            $this->meta = array(
                "description"=>"Résultats de la recherche de '$key'",
                "url"=>base_url("search?key=$key"),
                "image"=>img_url('logo/logo.png')
            );

            $this->data['query'] = $_GET['key'];
            $this->data['result']['lessons'] = $this->m_search->lesson($_GET['key']);
            $this->data['result']['news'] = $this->m_search->news($_GET['key']);
            $this->data['result']['events'] = $this->m_search->events($_GET['key']);
            $this->data['result']['posts'] = $this->m_search->posts($_GET['key']);
            $this->render('Résultats de la recherche de "'.$_GET['key'].'"');

        }
        else{
            $this->data ['breadcrumb'] = array(
                "Accueil" => base_url(),
                "Recherche"=>""
            );
            $this->meta = array(
                "description"=>"Recherchez dans le site",
                "url"=>base_url("search"),
                "image"=>img_url('logo/logo.png')
            );
            $this->render('Recherche',"form");
        }
    }
}