<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends MY_Controller {

    protected $data, $menu;

    function __construct()
    {
        parent::__construct();

        if(session_data('role')!=MODERATOR And in_array(MODERATOR, (array)session_data('roles'))){
            set_session_data(array('role'=>MODERATOR));
        };

        protected_session(array('','account/login'), MODERATOR);

        $this->load->model('backfront/notifications_model', 'notif');

        $this->load->library('form_validation');
    }


    public function index()
    {
        if(isset($_GET['new']) And $_GET['new'] == 0) {
            $this->data['notif'] = $this->userM->getUserNotif();
            if(count($this->data['notif']) > 0) {
                $data = array();
                foreach ($this->data['notif'] as $id) {
                    $data[count($data)] = $id->id;
                }
                $this->notif->notificationView($data);
            }
            $this->data['pageTitre'] = 'LISTE DES Nouvelles NOTIFICATIONS';
        }
        else {
            $this->data['notif'] = $this->notif->getNotification();
        }

        $this->renderGate('notification/received-list', 'Mes notifications');
    }

    public function formNotifAdd(){
        $users = $this->userM->getAllUsers();

        foreach ($users as $index => $user) {
            $newsU = new stdClass();
            foreach ($user as $key => $item) {
                if(in_array($key, array('id', 'firstname', 'lastname', 'number_id'))) {
                    $newsU->{$key} = $item;
                }
            }
            if($newsU)
                $users[$index] = (array)$newsU;
        }

        $this->data['users'] = $users;

        $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
        $this->form_validation->set_rules('target', '"Publié à"', 'trim|required|min_length[1]|max_length[512]|encode_php_tags');
        $this->form_validation->set_rules('publication', '"Publication"', 'trim|required|min_length[2]|max_length[512]|encode_php_tags');

        if($this->form_validation->run()){
            $field = array('sender'=>session_data('id'),
                'content'=>$this->input->post('publication'),
                'target'=>$this->input->post('target'),
                'promotion'=>-1
            );

            if($this->notif->publish($field)){
                $this->data['message']['class'] = 'alert-success';
                $this->data['message']['msg'] = 'Votre publication a bien été effectuer!';
            }
            else {
                $this->data['message']['class'] = 'alert-danger';
                $this->data['message']['msg'] = 'Erruer lors de la publication!';
            }
        }

        $this->renderGate('moderator/form-publish-notif', 'Publier une notification');
    }

    public function notifView()
    {
        if($this->input->post('mode') == 'js') {
            if ($this->notif->notificationView($this->input->post('id'))) {
                echo '*0*';
            } else {
                echo '*1*';
            }
        }else{
            msa_error("Vous n'avez pas le droit d'accéder cette page.");
        }

    }
}
