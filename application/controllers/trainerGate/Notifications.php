<?php

/**
 * Created by PhpStorm.
 * User: Harrys Crosswell
 * Date: 17/08/2017
 * Time: 13:55
 */
class Notifications extends  MY_Controller
{

    function __construct()
    {
        parent::__construct();

        if(in_array(TRAINER, (array)session_data('roles'))){
            set_session_data(array('role'=>TRAINER));
        };

        protected_session(array('', 'account/login'), array(TRAINER));

        $this->load->model('backfront/notifications_model', 'notif');
        $this->load->model('backfront/promotion_model', 'promotion');
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
        }
        else {
            $this->data['notif'] = $this->notif->getNotification();
        }

        $this->renderGate('notification/received-list', 'Mes notifications');
    }

    public function formNotifAdd(){
        $this->data['vague'] = $this->registration->getVagues();
        if ($this->input->post('send')) {
            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('pub_vague', '"Vague"', 'trim|required|max_length[15]|encode_php_tags');
            $this->form_validation->set_rules('destination', '"Destinataire"', 'trim|required|min_length[2]|max_length[512]|encode_php_tags');
            if($this->form_validation->run()){
                //var_dump($this->input->post());
                $field = array('sender'=>session_data('id'),
                    'content'=>$this->input->post('destination'),
                    'target'=>STUDENT,
                    'promotion'=>$this->input->post('pub_vague'));
                //var_dump($field); die(0);
                //$res = $this->notif->publish($field); //var_dump($res); die(0);
                if($this->notif->publish($field)){
                    $code = $this->promotion->getPromotionById($this->input->post('pub_vague'));
                    $code = (empty($code) ? ' ' : $code[0]->code);
                    $this->data['message'] = 'Votre message a bien été envoyé a la vague :'.$code;
                    $this->renderGate('trainer/form-model-add', 'Envoyer un message');
                }
            }else{
                $this->renderGate('trainer/form-model-add', 'Envoyer un message');
            }
        }else{
            $this->renderGate('trainer/form-model-add', 'Envoyer un message');
        }

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