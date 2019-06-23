<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller
{
    protected $data = array();

    function __construct()
    {
        parent::__construct();

        $this->load->model('auth/auth_model', 'authM');


        $this->load->library('form_validation');
    }

    public function index()
    {

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('admin/home', ''),array(ADMIN,MANAGER));

        $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
        $this->form_validation->set_rules('email', 'adresse E-mail ou Matricule', 'trim|required|min_length[1]|max_length[64]|encode_php_tags');
        $this->form_validation->set_rules('pwd', 'Mots de passe', 'trim|required|min_length[1]|max_length[255]|encode_php_tags');

        if($this->form_validation->run())
        {
            if($this->authM->auth(array(
                'mail'=>$this->input->post('email'),
                'pwd'=>$this->input->post('pwd'),
                'remember'=>($this->input->post('remember'))? true : false
            )))
            {
                redirect('admin/home');
            }
            $this->data['error'] = 'Login ou Mot de passe incorrect!';
        }
        $this->render('auth/index', 'Authentification');
    }

    public function loggout()
    {
        unset_session_data();
        delete_cookie('multisoft');
        redirect('admin/auth');
    }

    private function render($view, $titre = NULL)
    {
        $this->load->view('admin/headerAdmin', array('titre'=>$titre));
        //$this->load->view('admin/menu');
        $this->load->view($view, $this->data);
        $this->load->view('admin/footerAdmin');
    }
}