<?php

    class Account extends MY_Controller
    {
        function __construct()
        {
            parent::__construct();

            $this->load->library('form_validation');

            $this->data['cLesson'] = $this->lesson->getByType('cours')->result();
            $this->data['fLesson'] = $this->lesson->getByType('filière')->result();
            $this->data['pLesson'] = $this->lesson->getByType('promotion')->result();

            $this->load->model('public/events_model', 'mEvent');
            $this->data['lastEvent'] = $this->mEvent->get(null,0,3);
            
             $this->load->model('public/flash_model', 'mFlash');
             $this->data['infosFlash'] = $this->mFlash->get(null,0,3);
             updateVisit();
        }

        public function index()
        {
            $this->login();
        }

        public function signup()
        {
            
            $redirectUri=isset($_GET['redirect'])?$_GET['redirect']:null;

            if(session_data_isset('sudo') and session_data('sudo')) {
                unset_session_data();
            }
            
            if(session_data('connect'))
                if($redirectUri!=null)
                    redirect($redirectUri);
                else
                    redirect();
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
            $this->form_validation->set_rules('pwd', '"Mot de passe"', 'trim|required|min_length[8]|encode_php_tags');
            $this->form_validation->set_rules('pwd2', '"Mot de passe"', 'trim|required|min_length[8]|encode_php_tags');



            if($this->form_validation->run())
            {
                //var_dump($this->input->post()); die(0);
                $post = array(
                    'firstname'=>htmlentities($this->input->post('firstName')),
                    'lastname'=>htmlentities($this->input->post('lastName')),
                    'birth_date'=>date('Y-m-d', strtotime($this->input->post('birthDate'))),
                    'birth_place'=>htmlentities($this->input->post('birthPlace')),
                    'nationality'=>$this->input->post('nationality'),
                    'address'=>htmlentities($this->input->post('address')),
                    'phone'=>htmlentities($this->input->post('phone')),
                    'mail'=>$this->input->post('mail'),
                    'question'=>'none',
                    'answer'=>'none',
                    'login'=>$this->input->post('mail'),
                    'pwd'=>htmlentities(($this->input->post('pwd'))),
                    'npwd'=>htmlentities($this->input->post('pwd2')),
                    'register_date'=>'',
                    'state'=>'0',
                    'number_id'=>'',
                    'sexe'=>($this->input->post('genre') == '1') ? 1 : 0
                );
                $resp ='';
                    $reCaptcha = new ReCaptcha("6LfFLjAUAAAAADxozug2fW-P8kPn_ixJ0MV1IXRE");
                    if ($_POST["g-recaptcha-response"]) {
			    $resp = $reCaptcha->verifyResponse(
			        $_SERVER["REMOTE_ADDR"],
			        $_POST["g-recaptcha-response"]
			    );
			}	
		if ($resp != null && $resp->success) {
			 //var_dump($post);
                    $postOk = $this->userM->signUp($post);
                    //var_dump($ret);
                    if(is_array($postOk))
                    {
                        redirect('account/signUpComplete'.'/'.$postOk['number_id']);
                    }
                    else 
                    {
                        $this->data['status']=false;
                        $this->data['message'] = '<b>Echec d\'enregistrement:</b> '.$postOk;
                    }
		}
		else{
			$this->data['status']=false;
                        $this->data['message'] = '<b>Echec d\'enregistrement:</b> Pour des raisons de sécurité nous ne pouvons traiter votre demande.';
		}
               
            }


            $this->renderFront("account/form-add","Inscription à la plateforme ".APPNAME);
            //$this->renderFront('login-form', 'Authentification', true);
        }


        public function signUpComplete($number_id=null){

            if($number_id==null)
                msa_error("Vous n'avez pas le droit d'accéder cette page.");

            if($this->userM->userExist($number_id, 'number_id')){
                $user = $this->userM->getUser($number_id);
            }else
                msa_error("Désolé! ce compte n'existe pas.");

            $this->data['user']=$user[0];

            $this->renderFront('account/signup-success','Inscription réussie !');
        }

        public function androSignin($login,$pass){

            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-headers: Content-Type,x-prototype-version,x-requested-with');
            $user = $this->userM->androAuth($login,$pass);
            echo json_encode($user);
        }


        public function login()
        {
            $redirectUri=isset($_GET['redirect'])?$_GET['redirect']:null;

            if(session_data_isset('sudo') and session_data('sudo')) {
                unset_session_data();
            }
            if(!session_data('connect'))
                $this->userM->auth(false, get_cookie('msa_user'));
            if(session_data('connect'))
                if($redirectUri!=null)
                    redirect($redirectUri);
                else
                    redirect();

            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('email', 'adresse E-mail ou Matricule', 'trim|required|min_length[1]|max_length[64]|encode_php_tags');
            $this->form_validation->set_rules('pwd', 'Mot de passe', 'trim|required|min_length[1]|max_length[255]|encode_php_tags');

            if($this->form_validation->run())
            {
                $user = array(
                    'mail'=>$this->input->post('email'),
                    'pwd'=>$this->input->post('pwd'),
                    'remember'=>($this->input->post('remember'))? true : false
                );

                $authState = $this->userM->auth($user);
                if($authState ==1)
                {
                    $this->gate_home($redirectUri);
                }
                elseif($authState == -1)
                    $this->data['error'] = 'Compte bloqué!';
                else
                    $this->data['error'] = 'Login ou Mot de passe incorrect!';
            }
            $this->renderGate('login-form', 'Authentification', true);
        }

        public function logout()
        {
            $redirectUri=isset($_GET['redirect'])?$_GET['redirect']:null;

            parent::logout(); // TODO: Change the autogenerated stub
            if($redirectUri!=null)
                redirect($redirectUri);
            else
                redirect();
        }

        public function completeAccount($number_id=null, $name=null)
        {

            isSudo();

            if(!session_data('connect'))
                $this->userM->auth(false,get_cookie('multisoft'));

            if(in_array(MEMBER, (array)session_data('roles'))){
                set_session_data(array('role'=>MEMBER));
            };

            protected_session(array('','account/login/?redirect=account/completeAccount'),array(MEMBER));


            if (isset($_POST) and !empty($_POST)) {
                $user = $this->userM->getUser(session_data('id'));
                $avatar = (isset($_FILES) and $_FILES['user_avatar']['name'] != '') ?"assets/uploads/images/". $this->upload($_FILES) : ($user->sexe == 0 ?  'assets/img/img_avatar2.png' : 'assets/img/img_avatar.png');

                $mcq = "";
                if (isset($_POST['aff'])) $mcq .= "Les affiches;";
                if (isset($_POST['pro'])) $mcq .= "Un proche;";
                if (isset($_POST['rad'])) $mcq .= "La radio;";
                if (isset($_POST['pla'])) $mcq .= "Une plaque d'information;";
                if (isset($_POST['aut'])) $mcq .= $this->input->post('oth') . ";";

                $fmcq = substr($mcq, 0, strlen($mcq) - 1);

                $post = array(
                    'id' => session_data('id'),
                    'pwd' => sha1($this->input->post('npwd')),
                    'question' => $this->input->post('question'),
                    'answer' => $this->input->post('answer'),
                    'avatar' => $avatar,
                    'mcq' => $fmcq
                );
                if ($this->userM->complete($post) == true) {
                    set_session_data(array('avatar'=>$avatar));
                    redirect();
                } else {
                    echo "La complétion a échoué.";
                }
            }
            else
            {
                if($number_id==null or $name == null){
                    redirect("account/completeAccount/".session_data("plink"));
                }

                if ($this->userM->userExist($number_id, 'number_id'))
                {
                    $user=$this->userM->getUser($number_id)[0];
                    if($user->state != "1"){
                        $this->data['user'] = $user;
                        //var_dump($name,permalink($user->lastname));
                        if(strcmp(permalink($user->firstname.' '.$user->lastname), $name)==0 and strtolower(session_data('matricule'))==strtolower($user->number_id))
                            $this->renderFront("account/account-complete","Complétion du compte utlisateur");
                        //$this->load->view('backfront/user/account-complete', $this->data);
                        else
                        {
                            msa_error("Vous n'avez pas le droit de modifier ce compte. ");
                        }
                    }
                    else
                        msa_error("Les informations de ce compte ont déjà été complétées. Pour modifier vos informations
                        <a class='btn btn-primary' href='".base_url('account/')."'>Cliquez ici</a>");
                }
                else
                {
                    msa_error("Aucun utilisateur n'est enregistré sous le matricule
                        <b>$number_id</a>");
                }


            }
        }

        public function resetPassword()
        {
            if (isset($_POST) and !empty($_POST))
            {
                if ($_POST['step']==1)
                {
                    if ($this->userM->userExist($_POST['log_in'], "mail")==1 or $this->userM->userExist($_POST['log_in'], "number_id")==1){

                        $user=$this->userM->getUser($_POST['log_in'])[0];
                        if($user->state!='-1' and $user->state!='0')
                            echo $user->number_id.'/'.$user->question;
                        else
                            echo -1;
                    } else {
                        echo -1;
                    }
                } else
                {
                    $numid=$_POST['numid'];
                    $user=$this->userM->getUser($numid)[0];
                    if ($this->userM->issetUser($user->id, $_POST['answer']))
                    {
                        $password=randomPassword(14);
                        $title="Votre nouveau mot de passe";
                        $message=($user->sexe==0?'Mme ':'M. ').mb_strtoupper($user->lastname).' '.ucwords($user->firstname).", votre nouveau mot de passe est <br><b>".$password."</b><br><br> Vous pouvez le modifier plutard sur votre profil si vous le souhaitez (recommandé).";
                        //$sent=$this->sendMail(array("user"=>$user, "title"=>$title, "message"=>$message));
                        $sent=sendMail(array("user"=>$user, "title"=>$title, "message"=>$message));
                        if (is_bool($sent) and $sent)
                        {
                            if ($this->userM->resetPassword($user->id, $password))
                            {
                                echo 1;
                            } else echo -2;
                        } else
                        {
                            echo -1;
                        }
                    } else
                    {
                        echo 0;
                    }
                }
            } else
            {
                $this->renderFront('account/forgotten-password','Récupération de mot de passe');
            }
        }

        private function upload($files)
        {
            $this->load->config('uploads', TRUE);
            $this->load->library('upload', $this->config->item('images', 'uploads'));

            $this->upload->set_max_width(2048);
            $this->upload->set_max_height(2048);
            foreach ($files as $name => $file) {
                if (!$this->upload->do_upload($name)) {
                    $this->data['message'] = $this->upload->display_errors();
                    return false;
                } else {
                    return $this->upload->data()['file_name'];
                }
            }
        }



    }