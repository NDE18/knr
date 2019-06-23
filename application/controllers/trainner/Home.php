<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home  extends Ci_Controller
{
    protected $data, $menu;

    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        $this->load->library('form_validation');
        $this->load->model('trainner/admin_model', 'admin');
        $this->load->model('trainner/lesson_model', 'lesson');
        $this->load->helper('general_helper');
        $this->load->model('trainner/notification_model', 'notif');
        $this->load->model('trainner/registration_model', 'registration');

        $this->load->model('Courses');

    //  APPRENANT  //

        $this->load->model('e_models/CaseLearners');
        $this->load->model('e_models/M_list_training');
        $this->load->model('e_models/M_verify');

        $id_learner = session_data('id');

        if (session_data('role') == 2 and $this->M_verify->AlertExam($id_learner)!=null) {
            
            $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
            $Training['list_training'] = $this->M_list_training->listTraining();
            $Training['test'] = $this->M_verify->AlertExam();
            $this->load->view('e_views/head_learner');
            $this->load->view('e_views/V_take_courses', $Training);
            $this->load->view('e_views/AlertExam', $Training);
        }
        elseif ($this->M_verify->AlertExam($id_learner)==null) {
            if (sizeof($this->CaseLearners->HisTraining($id_learner))!=0) {
                $Training['lesson_user'] = $this->CaseLearners->HisTraining($id_learner);
                $Training['list_training'] = $this->M_list_training->listTraining();
                $this->load->view('e_views/head_learner');
                $this->load->view('e_views/V_take_courses', $Training);
                $this->load->view('e_views/list_his_lesson');
            }
            else{
                $this->load->view('public/e_header');
              $message['message'] = 'Vous avez déjà fait une inscription pour cette formation. <br>';
                $this->load->view('public/V_begining', $message);
            }
        }

    //  APPRENANT  //

        else{
            if(!session_data('connect'))
                $this->authM->auth(false,get_cookie('multisoft'));
            protected_session(array('','trainner/auth'),array(TRAINER));
        }
    }
    
    private function render($view, $titre = NULL)
    {
        $listes['list'] =$this->Courses->notif();
        $listes['liste'] =$this->Courses->wave();
        $this->load->view('trainner/menu',$listes);
        $this->load->view('trainner/headerAdmin', array('titre'=>$titre));
        $this->load->view($view, $this->data);
        $this->load->view('trainner/footerAdmin');
    }

    public function index()
    {   
        if (session_data('role') != 2) {
            $this->load->view('trainner/headerAdmin');
            $listes['list'] =$this->Courses->notif();
            $listes['liste'] =$this->Courses->wave();
            $this->load->view('trainner/menu',$listes);
            $this->load->view('trainner/index', $listes);
            $this->load->view('trainner/footerAdmin');
        }
    }
    public function profile(){

        $id = session_data("id");
        $user = $this->admin->getAdmin($id)->result();
        
        if($_FILES AND !(empty($_FILES))){

            $this->load->config('uploads', TRUE);
            $config = $this->config->item('photos', 'uploads');
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
                    if($this->admin->savePhoto($path, $id)){
                        $this->data['imgName'] = $path;
                        $this->data['message'] = 'La Photo a été modifié';
                    }
                }
            }
        }
        
        $user= $user[0];
        $dateCon = date_create($user->last_connexion);
        $dateReg = date_create($user->register_date);
        $dateBirth = date_create($user->birth_date);
       
        $this->data['user'] = $user;
        $this->data['dateCon'] = $dateCon;
        $this->data['dateReg'] = $dateReg;
        $this->data['dateBirth'] = $dateBirth;
        $this->render("trainner/home/profile", "Profil du formateur");
        
    }

}