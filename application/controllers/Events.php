<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Events extends CI_Controller
{

    protected $data, $menu;


    function __construct()
    {
        parent::__construct();

        $this->load->model('backfront/user_model', 'userM');
        $this->load->model('public/lesson_model', 'mLesson');
        $this->data['cLesson'] = $this->mLesson->getByType('cours')->result();
        $this->data['fLesson'] = $this->mLesson->getByType('filière')->result();
        $this->data['pLesson'] = $this->mLesson->getByType('promotion')->result();
        $this->load->model('public/events_model', 'mEvent');
        $this->data['lastEvent'] = $this->mEvent->get(null,0,3);

        $this->load->model('public/flash_model', 'mFlash');
        $this->data['infosFlash'] = $this->mFlash->get(null,0,3);

        updateVisit();


    }

    public function index()
    {
        $this->all();
    }

    public function view($singleEvent){
        $data = explode('--',$singleEvent,2);
        $idEvent = $data[1];
        $plinkEvent = $data[0];

        $event = $this->mEvent->get($idEvent);
        if($event){
            $event = $event[0];
            if(permalink($event->title) != $plinkEvent)
                show_404();
            else{
                $this->data ['breadcrumb'] = array(
                    "Accueil" => base_url(),
                    "Les évènements"=>base_url("evenements"),
                    $event->title =>"#",
                );
                $this->data['event'] = $event;
                $this->meta = array(
                    "description"=>excerpt($event->content,150),
                    "url"=>base_url('evenement').'/'.permalink($event->title).'--'.$event->id,
                    "image"=>img_url('logo/logo.png')
                );
                $this->render($event->title,"single");
            }
        }
        else{
            show_404();
        }

    }

    public function all($page=1){
        $this->data['agenda'] = $this->mEvent->get(null,$page-1);
        $this->meta = array(
            "description"=>"La liste des évènements au centre de formation professionnelle MULTISOFT ACADEMY",
            "url"=>base_url('evenements'),
            "image"=>img_url('logo/logo.png')
        );

        // Mise en place de la pagination
        $this->pagination->initialize(array('base_url' => base_url() .'evenements/page',
            'total_rows' => $this->mEvent->count(),
            'per_page' => MAX_EVENTS_PER_PAGE,
            'use_page_numbers' => TRUE,
            'full_tag_open' => '<ul class="pagination">',
            'full_tag_close' => '</ul>',
            'first_link' => '<<',
            'first_tag_open' => '<li class="page-item disabled">',
            'first_tag_close' => '</li>',
            'last_link' => '>>',
            'last_tag_open' => '<li class="page-item">',
            'last_tag_close' => '</li>',
            'next_link' => '>',
            'next_tag_open' => '<li class="page-item">',
            'next_tag_close' => '</li>',
            'prev_link' => '<',
            'prev_tag_open' => '<li class="page-item">',
            'prev_tag_close' => '</li>',
            'cur_tag_open' => '<li class="page-item active"><a class="page-link" href="#">',
            'cur_tag_close' => '</a></li>',
            'num_tag_open' => '<li class="page-item"> ',
            'num_tag_close' => '</li>',
            'attributes' => array('class' => 'page-link'),
        ));
        $this->data['pagination'] = $this->pagination->create_links();

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Les évènements"=>"#"
        );
        $this->render('Les évènements à MULTISOFT ACADEMY');
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
        $this->load->view("public/events-page-model", $this->data);
        $this->load->view('public/footer');
    }
}