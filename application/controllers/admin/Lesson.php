<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Lesson extends CI_Controller {

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
        $this->load->model('admin/lesson_model', 'lesson');
    }

    public function index(){
        $this->all();
    }

    public function formAdd()
    {
        if(session_data('role')!=ADMIN)
            msa_error("Dédolé! Vous n'avez pas les droits suffisants pour effectuer cette action. ");
        $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
        $this->form_validation->set_rules('nom', '"Nom"', 'trim|required|min_length[4]|max_length[255]|encode_php_tags');
        $this->form_validation->set_rules('code', '"Code"', 'trim|required|min_length[4]|max_length[6]|alpha_dash|encode_php_tags');
        $this->form_validation->set_rules('dure', '"Durée"', 'trim|required|is_natural');
        $this->form_validation->set_rules('fees', '"Frais"', 'trim|required|is_natural');
        $this->form_validation->set_rules('top', '"Top"', 'trim|required|is_natural');

        if($this->form_validation->run())
        {
            $post = array(
                'label'=>$this->input->post('nom'),
                'code'=>$this->input->post('code'),
                'duration'=>$this->input->post('dure'),
                'fees'=>$this->input->post('fees'),
                'type'=>$this->input->post('type'),
                'top'=>$this->input->post('top'),
                'syllabus'=>$this->input->post('syllabus'),
                'state'=> 1
            );
            $post = $this->lesson->save($post);
            if(is_bool($post)){
                redirect('admin/lesson');
            }else
            {
                $this->data['info'] = array(
                    'class'=>is_string($post)? 'alert alert-danger' : 'alert alert-info',
                    'sms'=>is_string($post)? $post : 'Echec d\'enregistrement'
                );
                $this->form_validation->set_value('code', 'Ce code existe déjà');
                $this->render('admin/lesson/form-add', 'Enregistrer un enseignement');
            }
        }
        else
        {
            $this->render('admin/lesson/form-add', 'Enregistrer un enseignement');
        }

    }

    public function all()
    {
        $this->data['query'] = $this->lesson->getAll();
        $this->render('admin/lesson/list', 'Liste des enseignements');
    }

    public function edit($id=false){
        $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
        $this->form_validation->set_rules('nom', '"Nom"', 'trim|required|min_length[4]|max_length[255]|encode_php_tags');
        $this->form_validation->set_rules('code', '"Code"', 'trim|required|min_length[4]|max_length[6]|alpha_dash|encode_php_tags');
        $this->form_validation->set_rules('dure', '"Durée"', 'trim|required|is_natural');
        $this->form_validation->set_rules('fees', '"Frais"', 'trim|required|is_natural');
        $this->form_validation->set_rules('top', '"Top"', 'trim|required|is_natural');
        //$id = $this->input->post('idft');
        if($this->form_validation->run())
        {
            $post = array(
                'label'=>$this->input->post('nom'),
                'code'=>$this->input->post('code'),
                'duration'=>$this->input->post('dure'),
                'fees'=>$this->input->post('fees'),
                'type'=>$this->input->post('type'),
                'top'=>$this->input->post('top'),
                'syllabus'=>$this->input->post('syllabus'),

            );
            $post = $this->lesson->updateTable($post, $id);
            if(is_bool($post) And $post)
            {
                set_flash_data(array("success","Enregistrement réussi!"));
                redirect("admin/lesson");
            }
            else
            {
                $this->data['message'] = 'Echec d\'enregistrement';
            }
        }
        else
        {
            if(isset($id)){
                $this->data['req'] = $this->lesson->get($id)->result();
            }else{
                redirect("admin/lesson");
            }
        }
        $this->render('admin/lesson/form-edit', "Modification de l'enseignement");

    }

    public function delete($id=false){
        if(empty($id)){
            redirect("admin/lesson");
        }else{
            if($this->lesson->delete($id)){
                redirect("admin/lesson");
            }
        }
    }

    private function render($view, $titre = NULL)
    {
        $this->load->view('admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('admin/menu', $this->menu);
        $this->load->view($view, $this->data);
        $this->load->view('admin/footerAdmin');
    }
}
