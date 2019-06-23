<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Log extends CI_Controller {
    protected $data = array();
    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));

        $this->load->model('admin/notification_model', 'notif');

        $this->menu['notif'] = $this->notif->newNotif();
        $this->load->library('form_validation');
        $this->load->model('admin/log_model', 'logs');
    }

    public function index()
    {
        $this->all();
    }

    public function all(){

        $log = $this->logs->getLogs()->result();
        $this->data['log'] = $log;
        if(empty($log)){
            $this->data['message'] = 'Aucun log pour le moment';
            $this->render('admin/log/list', 'Liste des Logs');
        }else{
            for($i = 1; $i <= count($log); $i++){
                $tmp = $this->logs->userName($log[$i-1]->author);
                $userName[$i-1] = $tmp->firstname .' '. $tmp->lastname;
            }
            $this->data['log'] = $log;
            $this->data['author'] = $userName;
            $this->render('admin/log/list', 'Liste des Logs');
        }
    }

    public function save(){

        $this->form_validation->set_rules('action', '"Action"', 'trim|required|alpha_numeric_spaces|encode_php_tags');
        $this->form_validation->set_rules('motivation', '"Motivation"', 'trim|required|min_length[15]|max_length[512]|alpha_numeric_spaces|encode_php_tags');
        if($this->form_validation->run()){
            if($this->logs->save(array('motivation'=>$this->input->post('motivation'), 'author'=>session_data('id'), 'date'=>date("Y-m-d H:i:s"), 'action'=>$this->input->post('action')))){
                $this->data['message'] = 'L\'inscription a été bloquée';
                $this->render('registration/registrationList', 'Liste des inscriptions');
            }
        }else{
            $this->render('registration/log', 'Sauvegarder le log');
        }
    }

    private function render($view, $titre = NULL)
    {
        $this->load->view('admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('admin/menu');
        $this->load->view($view, $this->data);
        $this->load->view('admin/footerAdmin');
    }
}
