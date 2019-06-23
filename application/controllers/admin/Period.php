<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Period extends CI_Controller {
    protected $data = array();
    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));

        $this->load->library('form_validation');
        $this->load->model('admin/period_model', 'period');

        $this->load->model('admin/notification_model', 'notif');

        $this->menu['notif'] = $this->notif->newNotif();
    }

    public function index()
    {
        $this->all();
    }

    public function formAdd(){
        $this->form_validation->set_rules('start', 'debut de la periode', 'trim|required|is_natural_no_zero');
        $this->form_validation->set_rules('end', 'fin de la periode', 'trim|required|is_natural_no_zero');

        if($this->form_validation->run()) {
            if($this->input->post('start') < $this->input->post('end')){
                $post = array(
                    'start'=>$this->input->post('start'),
                    'end'=>$this->input->post('end'),
                );
                $post = $this->period->save($post);
                if($post==true)
                    $post = 'Enregistrement avec success';
            }else{
                $post = "le debut est superirieur ou egale a la fin";
            }

        }else{
            $post = (validation_errors()) ? validation_errors() : '';
        }
        $this->data['post'] = $this->period->lyst();
        $this->render('admin/period/form-add', 'Enregistrer des periodes');
    }

    public function all()
    {
        $this->data['query'] = $this->period->lyst();
        $this->render('admin/period/list', 'Liste des périodes');
    }

    private function changePeriod($choix, $pas){
        $end = 8;
        $query = $this->period->lyst();
        if(!empty($this->period->lyst())){
            foreach($query->result() as $id){
                $start = $end;
                $end = $start + $pas;
                $post = array(
                    'start'=>$start,
                    'end'=>$end,
                );
                if(!$this->period->updateTable($post, $id->id)){
                    break;
                }
            }
            redirect("admin/period/");
        }
    }

    public function savePeriod($choix){
        if($choix == 2){
            $this->changePeriod($choix, 2);
        }elseif($choix == 3){
            $this->changePeriod($choix, 3);
        }else{
            redirect("admin/period");
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
