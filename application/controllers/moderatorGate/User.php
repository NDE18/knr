<?php
class User extends MY_Controller
{

    function __construct()
    {
        parent::__construct();

        if(session_data('role')!=MODERATOR And in_array(MODERATOR, (array)session_data('roles'))){
            set_session_data(array('role'=>MODERATOR));
        };
        protected_session(array('','account/login'), MODERATOR);

        $this->load->model('backfront/moderator_model', 'moderatorM');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->data['users'] = $this->moderatorM->getUser();
        $this->renderGate('moderator/member-list', 'Liste des utilisateurs');
    }

    public function newUsers()
    {
        $this->data['users'] = $this->moderatorM->getNewUser();
        $this->renderGate('moderator/member-list', 'Liste des nouveaux utilisateurs');
    }

    public function unlock()
    {
        $this->form_validation->set_rules('type', 'type', 'trim|required');
        $this->form_validation->set_rules('id', 'type', 'trim|required');
        if($this->form_validation->run())
        {
            if(strcmp(strtolower($this->input->post('type')), 'user')==0 And $this->userM->userUpdateState($this->input->post('id')))
            {
                if((bool)$this->input->post('mode') === false)
                    redirect('moderatorGate/user');
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


    public function resetPassword()
    {
        $this->form_validation->set_rules('id', 'type', 'trim|required');

        if($this->form_validation->run()) {
            $id = intval($this->input->post('id'));
            $user = $this->userM->getUser($id);
            if(!empty($user[0]))
            {
                $user = $user[0];
                $password=randomPassword(14);
                $title="Reinitialisation du mot de passe";
                $message=($user->sexe==0?'Mme ':'M. ').mb_strtoupper($user->lastname).' '.ucwords(mb_strtolower($user->firstname)).", votre nouveau mot de passe est <b>".$password."</b>.<br><br> Vous pouvez le modifier plutard sur votre profil si vous le souhaitez (recommandé).";
                $sent= sendMail(array("user"=>$user, "title"=>$title, "message"=>$message));
                if (is_bool($sent) and $sent) {
                    if ($this->userM->resetPassword($user->id, $password)) {
                        set_flash_data(array('success', 'Le mot de passe a bien été modifié!'));
                        if($this->input->post('mode') == 'js') {
                            echo '*0*';
                            return true;
                        }
                        //redirect('moderatorGate/user');
                    }
                    else {
                        set_flash_data(array('error', 'Le mot de passe n\a pas pu être modifié!'));
                        if($this->input->post('mode') == 'js') {
                            echo '*1*'.$sent;
                            return false;
                        }
                        redirect('moderatorGate/user');
                    }
                }
                else {
                    if($this->input->post('mode') == 'js') {
                        echo '*1*'.$sent;
                        return false;
                    }
                    redirect('moderatorGate/user');
                }
            }
            else {
                show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
            }
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }

    }

    public function profile($id = false, $permlink)
    {
        if (isset($id)) {
            $user = $this->userM->getUser($id);

            if (empty($user)) {
                redirect('moderatorGate/user');
            } else {
                $user = $user[0];
                if(permalink($user->firstname.' '.$user->lastname) == $permlink){

                    $this->data['user'] = $user;
                    $this->data['dateCon'] = date_create($user->last_connexion);
                    $this->data['dateReg'] = date_create($user->register_date);
                    $this->data['dateBirth'] = date_create($user->birth_date);
                    $this->renderGate("moderator/user-profile", "Profil de l'utilisateur");
                }
                else{
                    show_404();
                }

            }

        }
        else show_404();
    }
}