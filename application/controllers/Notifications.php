<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notifications extends MY_Controller {

    protected $data, $menu;

    function __construct()
    {
        parent::__construct();

        /*if(in_array(STUDENT, (array)session_data('roles'))){
            set_session_data(array('role'=>STUDENT));
        };

        protected_session(array('', 'account/login'), array(STUDENT));*/

        $this->load->model('backfront/notifications_model', 'notif');
        $this->load->model('backfront/user_model', 'mUser');
    }

    public function notifView()
    {
        if($this->input->post('mode') == 'js' And $this->notif->notificationView($this->input->post('id'))){
            echo '*0*';
        }else{
            echo '*1*';
        }

    }

    public function androNotif($matricule) ///pour l'application android
    {

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-headers: Content-Type,x-prototype-version,x-requested-with');
        $user = $this->notif->getNotification($matricule);
        echo json_encode($user);
    }
}
