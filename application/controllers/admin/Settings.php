<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends CI_Controller {
    protected $data = array();
    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN));


        $this->load->model('admin/notification_model', 'notif');

        $this->menu['notif'] = $this->notif->newNotif();
        $this->load->library('form_validation');
        $this->load->model('admin/settings_model', 'settings');
    }

    public function index()
    {
        $this->edit();
    }

    public function edit(){
        $this->form_validation->set_rules('payDL', 'date limite de payement', 'trim|required|numeric');
        $this->form_validation->set_rules('lessonSDL', 'date limite de fiche de suivi', 'trim|required|is_natural');
        $this->form_validation->set_rules('regI', 'Tranche d\'enregistrement', 'trim|required|is_natural_no_zero');
        $this->form_validation->set_rules('minML', 'duree minimale d\'une filiere', 'trim|required|is_natural');
        $this->form_validation->set_rules('minLL', 'duree minimale d\'une lesson', 'trim|required|is_natural');
        $this->form_validation->set_rules('maxAB', 'nombre dabsence maximal', 'trim|required|is_natural');
        if($this->form_validation->run()) {
            $post = array(
                'pay_dead_line'=>$this->input->post('payDL'),
                'lesson_slip_dead_line'=>$this->input->post('lessonSDL'),
                'reg_instalment'=>$this->input->post('regI'),
                'min_mention_last'=>$this->input->post('minML'),
                'min_lesson_last'=>$this->input->post('minLL'),
                'max_absence_nbr'=>$this->input->post('maxAB'),
            );
            $post = $this->settings->save($post);
            if(is_bool($post) and $post)
            {
                set_flash_data(array('success',"Modification réussie!"));
            }else{

            }
            $this->data['settings']=$this->settings->getSettings();
            $this->render('admin/settings/form-edit', 'Modifier les paramètres');
        }else{
            $this->data['settings']=$this->settings->getSettings();
            $this->render('admin/settings/form-edit', 'Modifier les paramètres');
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
