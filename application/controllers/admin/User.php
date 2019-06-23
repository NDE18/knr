<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User extends CI_Controller
{
    protected $data, $menu;

    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN));

        $this->load->library('form_validation');
        $this->load->model('admin/user_model', 'user');
        $this->load->helper('general_helper');
        $this->load->model('admin/notification_model', 'notif');

        $this->menu['notif'] = $this->notif->newNotif();
    }

    private function render($view, $titre = NULL)
    {
        $this->load->view('admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('admin/menu', $this->menu);
        $this->load->view($view, $this->data);
        $this->load->view('admin/footerAdmin');
    }

    public function index(){
        $this->all();
    }

    public function all()
    {
        $this->data['list']=$this->user->getUsers("lastname");
        $this->render('admin/user/list', 'Liste de tous les utilisateurs');
    }

    public function lock($id=false)
    {
        if ($id)
        {
            $state=$this->user->lock($id);
            if (is_object($state))
            {
                $this->data['status']=true;
                $this->data['message']="Le compte de <b>".mb_strtoupper($state->lastname)." ".ucfirst($state->firstname)."</b> a été ".($state->state=='1'?'suspendu':'réactivé')." avec succès.";
                //redirect(base_url() . 'user/userList');
               redirect("admin/user");
            } else
            {
                $this->data['status']=false;
                $this->data['message']=$state;
                //redirect(base_url() . 'user/userList');
                redirect("admin/user");
            }
        }
    }

    public function profile($id = false, $print = false){
        if(isset($id) and $print != 'print'){
            $user = $this->user->getUser($id)->result();
            if(empty($user)){
                redirect('admin/user');
            }else{
                $user= $user[0];
                $dateCon = date_create($user->last_connexion);
                $dateReg = date_create($user->register_date);
                $dateBirth = date_create($user->birth_date);
                $this->data['user'] = $user;
                $this->data['dateCon'] = $dateCon;
                $this->data['dateReg'] = $dateReg;
                $this->data['dateBirth'] = $dateBirth;
                $this->data['roles'] = $this->user->getUserRoles($id)->result();
                $this->render("admin/user/profile", "Profil de l'utilisateur");
            }
        }
    }

}