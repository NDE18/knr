<?php

/**
 * Created by PhpStorm.
 * User: Harrys Crosswell
 * Date: 17/08/2017
 * Time: 13:02
 */
class Newsletter extends CI_Controller
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');
        $this->load->model('public/lesson_model', 'mLesson');
        $this->load->model('public/general_model', 'mGeneral');
        $this->load->model('public/Forum_model', 'mForum');
        updateVisit();

        $this->load->model('public/Newsletter_model', 'newsletter');
        $this->load->model('backfront/user_model', 'userM');
        $this->load->model('backfront/notifications_model', 'notification');
        $this->load->model('backfront/log_model', 'logM');
        $this->load->library('form_validation');

        $this->load->helper('email_helper');
        $this->data['allReg'] = $this->mGeneral->getAllInscription();
        $this->data['allLes'] = $this->mGeneral->getAllLesson();
        $this->data['allMem'] = $this->mGeneral->getAllMember();

        $this->data['visits'] = $this->mGeneral->getAllVisits();
        $this->data['visitors'] = $this->mGeneral->getAllVisitors();
        $this->data['cLesson'] = $this->mLesson->getByType('cours')->result();
        $this->data['fLesson'] = $this->mLesson->getByType('filière')->result();
        $this->data['pLesson'] = $this->mLesson->getByType('promotion')->result();
    }

    public function index(){
        $this->meta = array(
            "description"=>"Les nouvelles, les emplois de temps, les programmes et résultats d'examen, les évènements et bien d'autres. Inscrivez-vous pour ne manquer aucune de ces informations",
            "url"=>base_url('newsletter'),
            "image"=>img_url('newsletter.jpg')
        );

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Inscription à la Newsletter"=>"",
        );

        $this->render('Inscription à la newsletter de MULTISOFT');

    }

    public function check_mail(){
        $email = $this->input->post('email');

        $post = array(
            'email'=>$email
        );

        $saveOk = $this->newsletter->addUser($post);
        if(is_bool($saveOk) and $saveOk){
            echo 1;
        }
        else{
            echo 'Echec d\'enregistrement: '.$saveOk;

        }
    }

    public function sendnewsletter()
    {
        if(!isset($_POST['email']))
            return;
        $email = $this->input->post('email');
        $title = "Inscription à la Newsletter de MULTISOFT ACADEMY";
        $content = "Bienvenue, vous recevez ce mail car vous avez demandé à suivre la newsletter du <b>Centre de Formation Professionnelle MULTISOFT ACADEMY</b>.";

        $data = array(
            'email'=>$email,
            'subject'=>$title,
            'contenu'=>$this->load->view('letter-model',array('email'=>$email,'title'=>$title,'message'=>$content),true)
        );
        var_dump(sendNewLetter($data));
    }

    public function signout(){
        if(!isset($_GET['ref']) or empty($_GET['ref']))
            redirect();

        $this->meta = array(
            "description"=>"Désabonnement de la newsletter de MULTISOFT ACADEMY",
            "url"=>base_url('newsletter/signout'),
            "image"=>img_url('newsletter.jpg')
        );

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Newsletter"=>base_url('newsletter'),
            "Désabonnement de la Newsletter"=>"",
        );

        $email = $_GET['ref'];
        if($this->newsletter->check_mail($email)){
            $this->newsletter->signoutUser($email);
            $this->data['state'] = true;
            $this->data['message'] = "Vous avez a été correctement supprimé de notre boite aux lettres";
        }
        else{
            $this->data['state'] = false;
            $this->data['message'] = "Cette adresse n'est pas dans notre boite aux lettres";
        }
        $this->render('Désabonnement de la newsletter de MULTISOFT',"signout");
    }

    private function render($titre = NULL,$view=NULL)
    {
        $notif = array();
        if(session_data('connect')){
            $notif = $this->userM->getUserNotif();
        }
        $this->load->view('public/header', array('titre'=>$titre,"meta"=>$this->meta,'notif'=>$notif));

        if($view!=null)
        {
            $this->data['view'] = $view;
        }
        else
        {
            $this->data['view'] = "index";
        }
        $this->load->view("public/newsletter-page-model", $this->data);
        $this->load->view('public/footer');
    }
}