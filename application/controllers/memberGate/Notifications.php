<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends MY_Controller {

    protected $data, $menu;

    function __construct()
    {
        parent::__construct();

        if(in_array(MEMBER, (array)session_data('roles'))){
            set_session_data(array('role'=>MEMBER));
        };

        protected_session(array('', 'account/login'), array(MEMBER));

        $this->load->model('backfront/notifications_model', 'notif');
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
        }
        else {
            $this->data['notif'] = $this->notif->getNotification();
        }
        $this->renderGate('notification/received-list', 'Mes notifications');
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
