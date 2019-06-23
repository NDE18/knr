<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Message extends My_Controller
{

    protected $data, $menu;

    function __construct()
    {
        parent::__construct();

        if (session_data('role') != MODERATOR And in_array(MODERATOR, (array)session_data('roles'))) {
            set_session_data(array('role' => MODERATOR));
        };

        protected_session(array('', 'account/login'), MODERATOR);

        $this->load->model('backfront/notifications_model', 'notif');
        $this->load->model('backfront/message_model', 'mMessage');

        $this->load->library('form_validation');
    }

    public function index(){
        $this->data['messages'] = $this->mMessage->get();
        $this->renderGate('moderator/msg-list','Les messages des utilisateurs');
    }

    public function edit($id=null){
        if($id==null)
            show_404();

        $this->form_validation->set_rules('contenu', 'Contenu', 'trim|required');
        if($this->form_validation->run()) {
            $content = $this->input->post('contenu');
            $ret = $this->mMessage->updateContent($content,$id);
            if($ret)
                redirect('moderatorGate/message');
        }
        else {
            $sms = $this->mMessage->get($id);

            if(!empty($sms)){
                $sms= $sms[0];
                $this->data['sms'] = $sms;

                $this->renderGate('moderator/form-edit-sms','Modifier un message');
            }
            else{
                show_404();
            }
        }
    }

    public function add(){
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
        $this->form_validation->set_rules('target', '"Auteur"', 'trim|required|min_length[1]|max_length[512]|encode_php_tags');
        $this->form_validation->set_rules('contenu', '"contenu"', 'trim|required|min_length[2]|max_length[512]|encode_php_tags');

        if($this->form_validation->run()){
            $field = array(
                'content'=>$this->input->post('contenu'),
                'user'=>$this->input->post('target'),
                'save_date'=>moment()->format('Y-m-d H:i:s'),
                'type'=>3
            );

            if($this->mMessage->save($field)){
                $this->data['message']['class'] = 'alert-success';
                $this->data['message']['msg'] = 'Votre publication a bien été effectuer!';
            }
            else {
                $this->data['message']['class'] = 'alert-danger';
                $this->data['message']['msg'] = 'Erreur lors de la publication!';
            }
        }

        $this->renderGate('moderator/form-add-sms', 'Enregistrer un message');
    }

    public function unlock()
    {
        $this->form_validation->set_rules('id', 'type', 'trim|required');
        //var_dump($this->input->post('id'));die();
        if($this->form_validation->run())
        {
            if($this->mMessage->setState('1',$this->input->post('id')))
            {
                echo '*0*';
            }
            else {
                show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
            }
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }

    public function lock()
    {
        $this->form_validation->set_rules('id', 'type', 'trim|required');
        //var_dump($this->input->post('id'));die();
        if($this->form_validation->run())
        {
            if($this->mMessage->setState('0',$this->input->post('id')))
            {
                echo '*0*';
            }
            else {
                show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
            }
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }

}
