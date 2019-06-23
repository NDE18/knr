<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pages extends CI_Controller
{

    protected $data, $menu;

    function __construct()
    {

        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');
        $this->load->model('public/general_model', 'mGeneral');
        $this->load->model('public/lesson_model', 'mLesson');

        $this->data['cLesson'] = $this->mLesson->getByType('cours')->result();
        $this->data['fLesson'] = $this->mLesson->getByType('filière')->result();
        $this->data['pLesson'] = $this->mLesson->getByType('promotion')->result();
        $this->load->model('public/events_model', 'mEvent');
        $this->data['lastEvent'] = $this->mEvent->get(null,0,3);

        $this->load->model('backfront/user_model', 'userM');
        $this->load->model('public/flash_model', 'mFlash');
        $this->data['infosFlash'] = $this->mFlash->get(null,0,3);
        updateVisit();

    }

    public function render($page=NULL)
    {
        $titre = get_static_page($page)['title'];

        if($titre!=null){

            $this->load->model('public/trainer_model', 'mTrainer');


            $this->data['trainers'] = $this->mTrainer->getAll()->result();


            $this->data ['breadcrumb'] = array(
                "Accueil" => base_url(),
                $titre =>"#",
            );
            $this->meta = array(
                "description"=>"Centre de Formation Professionnelle MULTISOFT ACADEMY",
                "url"=>base_url($page),
                "image"=>img_url('logo/logo.png')
            );
            $notif = array();
            if(session_data('connect')){
                $notif = $this->userM->getUserNotif();
            }
            $this->load->view('public/header', array('titre'=>$titre,"meta"=>$this->meta,'notif'=>$notif));
            if($page!=null)
            {
                if(! file_exists(APPPATH.'views/public/pages/'.$page.'.php')){
                    // Whoops, we don't have a page for that!
                    show_404();
                }
                else{
                    $this->data['view'] = "pages/".$page;
                    $this->load->view("public/static-page-model", $this->data);
                }
            }
            else
            {
                $this->load->view("public/homepage", $this->data);
            }

            $this->load->view('public/footer');
        }
        else
            show_404();


    }


}