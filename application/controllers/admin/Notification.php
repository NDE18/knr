<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notification extends CI_Controller {

    protected $data, $menu;

    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));


        $this->load->model('admin/notification_model', 'notif');

        $this->menu['notif'] = $this->notif->newNotif();
    }

    public function index()
    {
        if(isset($_GET['new']) And $_GET['new'] == 0) {
            $this->data['notif'] = $this->notif->newNotif();
            if(count($this->data['notif']) > 0) {
                $data = array();
                foreach ($this->data['notif'] as $id) {
                    $data[count($data)] = $id->id;
                }
                $this->notif->notificationView($data);
            }
        }
        else {
            $this->data['notif'] = $this->notif->getMyNotif();
        }
        $this->render('admin/notification/received-list', 'Mes notifications');
    }

    public function allModel(){
        $this->data['notif'] = $this->notif->getAllModel();
        $this->render('admin/notification/model-list', 'Liste des models de notification');
    }

    public function formAdd()
    {
        if(session_data('role') == ADMIN){
            if ($this->input->post('send')) {
                if ($this->notif->saveModel('content', $this->input->post('my_notif')))
                    set_flash_data(array('success', 'Votre notification a bien été enregistre!'));
                else
                    set_flash_data(array('error', 'Votre notification n\'a pas été enregistre!'));
            }
            $this->render('admin/notification/form-model-add', 'Enregistrer un model de notification');
        }else{
            msa_error("Dédolé! Vous n'avez pas les droits suffisants pour effectuer cette action. ");
        }
    }

    public function modify($id)
    {
        if(session_data('role') == ADMIN){
            if ($id And is_numeric($id)) {
                if ($this->input->post('send')) {
                    if ($this->notif->updateModel($id, $this->input->post('my_notif'))) {
                        set_flash_data(array('success', 'Votre notification a bien été modifiée!'));
                        redirect('admin/notification/');
                    } else {
                        set_flash_data(array('error', 'Votre notification n\'a pas été modifiée!'));
                    }
                }

                $this->data['model'] = $this->notif->getOneModel($id);
                $this->render('admin/notification/form-model-add', 'Modifier des notifications');
            } else {
                redirect("admin/notification/allModel");
            }
        }else{
            msa_error("Dédolé! Vous n'avez pas les droits suffisants pour effectuer cette action. ");
        }
    }

    public function modalPublishContent()
    {
        if($this->input->post('mode')=='js')
        {
            $this->load->model('admin/document_model', 'document');

            $this->data['notif'] = $this->input->post('notif');
            $this->data['roles'] = $this->document->getRoles();
            $this->data['vague'] = $this->document->getVagues();

            $this->load->view('admin/notification/modalPublishContent', $this->data);
        }
        else
        {
            show_404();
        }
    }

    public function publish()
    {
        if(is_numeric($this->input->post('pub_role'))) {
            $data = array(
                'sender' => session_data('id'),
                'content' => $this->notif->getOneModel($this->input->post('notif'))->content,
                'target' => $this->input->post('pub_role')
            );

            if ($data['target'] == 2) {
                $data['promotion'] = $this->input->post('pub_vague');
            }

            if($this->notif->publish($data)){
                set_flash_data(array('success', 'Votre publication a bien été éffectué'));
            }else{
                set_flash_data(array('error', 'Votre publication n\'a pas été éffectué'));
            }
            redirect('admin/notification/allModel');
        }
        msa_error('Oops');
    }

    public function notifView()
    {
        if($this->input->post('mode') == 'js' And $this->notif->notificationView($this->input->post('id'))){
            echo '*0*';
        }else{
            echo '*1*';
        }

    }

    private function render($view, $titre = NULL)
    {
        $this->menu['notif'] = $this->notif->newNotif();
        $this->load->view('admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('admin/menu', $this->menu);
        $this->load->view($view, $this->data);
        $this->load->view('admin/footerAdmin');
    }
}
