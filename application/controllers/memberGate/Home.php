<?php

class Home extends MY_Controller
{

    function __construct()
    {
        parent::__construct();

        $this->load->model('backfront/timetable_model', 'timetable');
        $this->load->model('backfront/availability_model');
        $this->load->model('backfront/lesson_model', 'lesson');
        $this->load->model('backfront/promotion_model', 'promotion');
        $this->load->library('form_validation');
        $this->load->model('backfront/registration_model', 'registration');


        if(in_array(MEMBER, (array)session_data('roles'))){
            set_session_data(array('role'=>MEMBER));
        };
        protected_session(array('','account/login'),array(MEMBER));
    }

    public function index()
    {
        $this->data['registrations'] = $this->registration->getRegistrationOfUser(session_data('id'));
        $this->renderGate('member/home', 'Accueil');
    }


    public function profil(){
        $id = session_data("id");
        $user = array();
        $user = $this->userM->getUser((int)$id);

        //var_dump($user); die();
        if($_FILES AND !(empty($_FILES))){

            $this->load->config('uploads', TRUE);
            $config = $this->config->item('avatars', 'uploads');
            $config['file_name'] = permalink('profil '.session_data('matricule'));
            $this->load->library('upload', $config);

            foreach($_FILES as $name => $file){
                if(empty($name)){
                    $this->data['message'] = 'Vous n\'avez sélectionné aucune image';
                }
                elseif(!$this->upload->do_upload($name))
                {
                    $this->data['message'] = $this->upload->display_errors();
                }
                else
                {
                    $path = 'assets/uploads' . explode('assets/uploads', $this->upload->data()['full_path'])[1];
                    if($this->userM->savePhoto($path, $id)){
                        $this->data['imgName'] = $path;
                        $this->data['message'] = 'La Photo a été modifié';
                        set_session_data(array('avatar'=>$path));
                    }
                }
            }
        }

        $user= $user[0];
        $dateCon = date_create($user->last_connexion);
        $dateReg = date_create($user->register_date);
        $dateBirth = date_create($user->birth_date);

        $this->data['user'] = $user;
        //$this->data['imgName'] = $user->avatar;
        $this->data['dateCon'] = $dateCon;
        $this->data['dateReg'] = $dateReg;
        $this->data['dateBirth'] = $dateBirth;
        $this->renderGate("member/profile", "Profil de l'apprenant  ".session_data('firstname').' '.session_data('lastname'));

    }

    public function editProfile()
    {
        $idUser = session_data("id");
        $user = $this->userM->getUser((int)$idUser); //var_dump($user); die(0);

        if (!empty($user)) {
            $this->data['user'] = $user[0];
        } else {
            $this->data['massage'] = "Cet utilisateur n'est pas reconnu.";
        }

        $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
        $this->form_validation->set_rules('lastName', '"Nom"', 'trim|required|min_length[2]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('firstName', '"Prénom"', 'trim|min_length[2]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('birthDate', '"Date de naissance"', 'required');
        $this->form_validation->set_rules('birthPlace', '"Lieu de naissance"', 'trim|required|min_length[2]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('nationality', '"Pays d\'origine"', 'required|encode_php_tags');
        $this->form_validation->set_rules('address', '"Adresse"', 'trim|required|min_length[2]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('phone', '"Téléphone"', 'trim|required|min_length[9]|max_length[16]|encode_php_tags');
        $this->form_validation->set_rules('mail', '"E-mail"', 'trim|required|min_length[9]|max_length[128]|encode_php_tags|valid_email');
        $this->form_validation->set_rules('school', '"Etablissement"', 'trim|required|min_length[3]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('fil', '"Filière"', 'trim|required|min_length[3]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('level', '"Niveau"', 'trim|required');
        $this->form_validation->set_rules('pwd', '"Mot de passe"', 'trim|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('npwd', '"Nouveau mot de passe"', 'trim|min_length[9]|max_length[128]|encode_php_tags');

        if($this->form_validation->run())
        {
            $post = array(
                'firstname'=>$this->input->post('firstName'),
                'lastname'=>$this->input->post('lastName'),
                'birth_date'=>date('Y-m-d', strtotime($this->input->post('birthDate'))),
                'birth_place'=>$this->input->post('birthPlace'),
                'nationality'=>$this->input->post('nationality'),
                'address'=>$this->input->post('address'),
                'phone'=>$this->input->post('phone'),
                'mail'=>$this->input->post('mail'),
                'school'=>$this->input->post('school'),
                'school_area'=>$this->input->post('fil'),
                'school_level'=>$this->input->post('level'),
                'pwd'=>$this->input->post('pwd'),
                'npwd'=>$this->input->post('npwd')
            );
            $postOk = $this->userM->modify($post);
            if(is_bool($postOk) And $postOk)
            {
                $this->data['status']=true;
                set_flash_data(array('success','Profil modifié avec succès!'));
                redirect('memberGate/home/profil');
            }
            else
            {
                $this->data['status']=false;
                $this->data['message'] = '<b>Echec de modification :</b> '.$postOk;
                //redirect('admin/home/profile/');
            }
        }
        $this->renderGate('member/form-profile-edit', 'Modifier le profil');
    }


}