<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Moderator extends Ci_Controller
{
    protected $data, $menu;
    public function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));


        $this->load->library('form_validation');
        $this->load->helper('general_helper');
        $this->load->model('admin/moderator_model', 'moderator');
        $this->load->model('admin/notification_model', 'notif');
        $this->load->model('admin/log_model', 'logM');

        $this->menu['notif'] = $this->notif->newNotif();
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

    public function index()
    {
        $this->all();
    }

    public function formAdd()
    {
        protected_session(array('','admin/auth'),array(ADMIN));

        $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
        $this->form_validation->set_rules('lastName', '"Nom"', 'trim|required|min_length[2]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('firstName', '"Prénom"', 'trim|min_length[2]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('birthDate', '"Date de naissance"', 'required');
        $this->form_validation->set_rules('birthPlace', '"Lieu de naissance"', 'trim|required|min_length[2]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('nationality', '"Pays d\'origine"', 'required|encode_php_tags');
        $this->form_validation->set_rules('address', '"Adresse"', 'trim|required|min_length[2]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('phone', '"Téléphone"', 'trim|required|min_length[9]|max_length[16]|encode_php_tags');
        $this->form_validation->set_rules('mail', '"E-mail"', 'trim|required|min_length[9]|max_length[128]|encode_php_tags|valid_email|is_unique[user.mail]');
        $this->form_validation->set_rules('genre', '"Genre"', 'trim|required|encode_php_tags');

        if($this->form_validation->run())
        {
            $post = array(
                'firstname'=>$this->input->post('firstName'),
                'lastname'=>$this->input->post('lastName'),
                'birth_date'=>$this->input->post('birthDate'),
                'birth_place'=>$this->input->post('birthPlace'),
                'nationality'=>$this->input->post('nationality'),
                'address'=>$this->input->post('address'),
                'phone'=>$this->input->post('phone'),
                'mail'=>$this->input->post('mail'),
                'question'=>'none',
                'answer'=>'none',
                'login'=>$this->input->post('mail'),
                'pwd'=>randomPassword(14),
                'register_date'=>'',
                'state'=>'0',
                'number_id'=>'',
                'last_connexion'=>'',
                'sexe'=>($this->input->post('genre') == '1') ? 1 : 0
            );
            $postOk = $this->moderator->save($post);
            if(is_bool($postOk) And $postOk){
                $this->logM->save(array(
                    "motivation"=>"",
                    "author"=>session_data('id'),
                    "date"=>moment()->format('Y-m-d H:i:s'),
                    "action"=>"Enregistrement de ".mb_strtoupper($post['lastname'])." ".ucwords($post['lastname'])." à la plateforme."
                ));

                $this->logM->save(array(
                    "motivation"=>"",
                    "author"=>session_data('id'),
                    "date"=>moment()->format('Y-m-d H:i:s'),
                    "action"=>"Ajout de ".mb_strtoupper($post['lastname'])." ".ucwords($post['lastname'])." en tant que modérateur."
                ));

                set_flash_data(array("success","Enregistrement réussi!"));
                redirect("admin/moderator");
            }
            else
            {
                $this->data['message'] = 'Echec d\'enregistrement: '.$postOk;
                $this->render('admin/moderator/form-add', 'Enregistrer un modérateur');
            }
        } else
        {
            $this->render('admin/moderator/form-add', 'Enregistrer un modérateur');
        }
    }

    public function all()
    {
        $query = $this->moderator->getAll()->result();
        $this->data['query'] = $query;
        $this->render('admin/moderator/list', 'Liste des modérateurs');
    }

    public function addModerator()
    {
        $this->render('admin/moderator/addModerator', 'Ajout d\'un modérateur');
    }

    public function regModerator()
    {
        $this->form_validation->set_error_delimiters('<br><p class="form_erreur text-danger small" style="display: block; clear: both">', '<p>');
        $this->form_validation->set_rules('id', '"Utilisateur"', 'trim|required', array('required'=>"Veuillez choisir un utilisateur."));

        if($this->form_validation->run()) {
            $post_array = array(
                'id'=>$this->input->post('id')
            );

            $postOk = $this->moderator->regModerator($post_array);

            if(is_bool($postOk) And $postOk)
            {
                set_flash_data(array("success",'L\'ajout s\'est bien déroulé'));
                redirect("admin/moderator");
            }
            else
            {
                $this->data['status']=false;
                $this->data['message'] = '<b>Echec :</b> '.$postOk;
                $this->data['users']=$this->moderator->getNotModerators("lastname");
                $this->render('admin/moderator/regModerator', 'Ajout d\'un modérateur');
            }
        } else
        {
            $this->data['users']=$this->moderator->getNotModerators("lastname");
            $this->render('admin/moderator/regModerator', 'Ajout d\'un modérateur');
        }
    }

    public function motivation($mode = false, $idUr = false){
        if(($mode=="shelve" or $mode == "unshelve") and $idUr){
            $moderator = $this->moderator->getModerator(false, $idUr);
            if(is_array($moderator)){
                $this->data['name'] = $moderator[0]->firstname.' '.$moderator[0]->lastname;
                $this->data['mode'] = $mode;
                $this->data['idUr'] = $idUr;
                $this->render('admin/moderator/motivation', 'Entrez le motif');
            }else{
                redirect('admin/moderator');
            }

        }
        elseif($mode == false or $idUr == false){
            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('motivation', '"Motivation"', 'trim|required|min_length[3]|max_length[512]|encode_php_tags');
            if($this->form_validation->run()){
                if($this->input->post('mode') == 'shelve'){
                    $motivation = $this->input->post('motivation');
                    $this->shelve($this->input->post('idUr'), $motivation);
                }elseif($this->input->post('mode') == 'unshelve'){
                    $motivation = $this->input->post('motivation');
                    $this->unshelve($this->input->post('idUr'), $motivation);
                }else{
                    redirect('admin/moderator');
                }
            }else{
                $moderator = $this->moderator->getModerator(false, $this->input->post('idUr')); //var_dump($moderator); die(0);
                if(is_array($moderator)){
                    $this->data['name'] = $moderator[0]->firstname.' '.$moderator[0]->lastname;
                    $this->data['mode'] = $mode;
                    $this->data['idUr'] = $idUr;
                    $this->render('admin/moderator/motivation', 'Entrez le motif');
                }else{
                    redirect('admin/moderator');
                }
            }
        }else{
            redirect('admin/moderator');
        }


    }

    public function log($id=false){
        $this->load->model('admin/moderator_model', 'moderator');
        $log = $this->moderator->log($id);
        $this->data['log'] = $log;
        $this->render('admin/moderator/log', 'Liste des logs');
    }

    public function shelve($idUr=false, $motivation = false){
        if($idUr){
            $moderator = $this->moderator->getModerator(false, $idUr);
            if(is_array($moderator)){
                $shelve = $this->moderator->shelve($idUr);
                if($shelve){
                    $log = $this->logM->save(array('motivation'=>$motivation, 'author'=>session_data('id'), 'date'=>date('Y-m-d'), 'action'=>'Suspension de '.$moderator[0]->firstname.' '.$moderator[0]->lastname));
                    if($log){
                        set_flash_data(array("success",'Le modérateur '.$moderator[0]->lastname.' '.$moderator[0]->firstname.' viens d\'être suspendu. Motif:<< '.$this->input->post('motivation').'>>'));
                        redirect("admin/moderator");
                    }
                }else{
                    redirect('admin/moderator');
                }
            }else{
                redirect('admin/moderator');
            }

        }else{
            redirect('admin/moderator');
        }
    }

    public function unshelve($idUr=false, $motivation = false){
        //var_dump($idUr); die(0);
        if($idUr){
            $this->load->model('admin/lesson_model', 'lesson');
            $moderator = $this->moderator->getModerator(false, $idUr);
            if(is_array($moderator)){
                $shelve = $this->moderator->unshelve($idUr);
                if($shelve){
                    $log = $this->logM->save(array('motivation'=>$motivation, 'author'=>session_data('id'), 'date'=>date('Y-m-d'), 'action'=>'Mise en service de '.$moderator[0]->firstname.' '.$moderator[0]->lastname));
                    if($log){
                        set_flash_data(array("success",'Le modérateur '.$moderator[0]->lastname.' '.$moderator[0]->firstname.' a été remis en service.'));
                        redirect("admin/moderator");
                    }
                }else{
                    redirect("admin/moderator");
                }
            }else{
                redirect("admin/moderator");
            }

        }else{
            redirect("admin/moderator");
        }
    }
}