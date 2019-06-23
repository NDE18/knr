<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Search extends CI_Controller
{

    protected $data, $menu;

    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));
        $this->load->model('admin/search_model', 'm_search');
    }

    public function index()
    {
        if(isset($_GET['query'])){
            $this->data['query'] = $_GET['query'];
            $this->data['result']['document'] = $this->m_search->document($_GET['query']);
            $this->data['result']['lesson'] = $this->m_search->lesson($_GET['query']);
            $this->data['result']['promotion'] = $this->m_search->promotion($_GET['query']);
            $this->data['result']['registration'] = $this->m_search->registration($_GET['query']);
            $this->data['result']['user'] = $this->m_search->user($_GET['query']);

            $this->render('admin/search/result', 'Résultat de la recherche');
        }
    }

    private function render($view, $titre = NULL)
    {
        $this->load->model('admin/notification_model', 'notif');

        $this->menu['notif'] = $this->notif->newNotif();
        $this->load->view('admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('admin/menu', $this->menu);
        $this->load->view($view, $this->data);
        $this->load->view('admin/footerAdmin');
    }
}
