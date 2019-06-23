<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class News extends CI_Controller {

    protected $data, $menu;


    function __construct()
    {
        parent::__construct();
        $this->load->model('public/news_model', 'mNews');
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

    public function index()
    {
        $this->all();
    }

    public function view($singleNew){
        $data = explode('--',$singleNew,2);
        $idNews = $data[1];
        $plinkNew = $data[0];

        $new = $this->mNews->get($idNews);
        if($new){
            $new = $new[0];
            if(permalink($new->title) != $plinkNew)
                show_404();
            else{
                $this->data ['breadcrumb'] = array(
                    "Accueil" => base_url(),
                    "Les nouvelles"=>base_url("nouvelles"),
                     $new->title =>"#",
                );
                $this->data['new'] = $new;
                $this->meta = array(
                    "description"=>excerpt($new->content,150),
                    "url"=>base_url('nouvelles').'/'.permalink($new->title).'--'.$new->id,
                    "image"=>($new->thumbnail!=null)?base_url($new->thumbnail ):img_url('logo/logo.png')
                );
                $this->render($new->title,"single");
            }
        }
        else{
            show_404();
        }

    }

    public function all($page=1){
        $this->data['allNews'] = $this->mNews->get(null,$page-1);
        $this->meta = array(
            "description"=>"La liste des nouvelles au centre de formation professionnelle MULTISOFT ACADEMY",
            "url"=>base_url('nouvelles'),
            "image"=>img_url('logo/logo.png')
        );

        // Mise en place de la pagination
        $this->pagination->initialize(array('base_url' => base_url() .'nouvelles/page',
            'total_rows' => $this->mNews->count(),
            'per_page' => MAX_NEWS_PER_PAGE,
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
            "Les nouvelles"=>"#"
        );
        $this->render('Les nouvelles à MULTISOFT ACADEMY');
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
        $this->load->view("public/news-page-model", $this->data);
        $this->load->view('public/footer');
    }
}
