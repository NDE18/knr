<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Enseignements extends CI_Controller
{

    protected $data, $menu;

    function __construct()
    {
        parent::__construct();

        $this->load->model('backfront/user_model', 'userM');
        $this->load->model('public/lesson_model', 'mLesson');
        $this->load->model('public/general_model', 'mGeneral');
        updateVisit();
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
            "description"=>"Les enseignements dispensés au Centre de Formation Professionnelle MULTISOFT ACADEMY",
            "url"=>base_url('enseignements'),
            "image"=>img_url('logo/logo.png')
        );

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Les enseignements"=>"",
        );



        //$this->data['allLesson'] = $this->mLesson->getAll()->result();

        $this->render("Les enseignements");


    }

    public function view($singleLesson){
        $data = explode('--',$singleLesson,2);
        $codeLesson = $data[1];
        $plinkLesson = $data[0];

        $lesson = $this->mLesson->get($codeLesson)->result();
        if($lesson){
            $lesson = $lesson[0];
            if(permalink($lesson->label) != $plinkLesson){
                show_404();
            }
            else{
                $this->data ['breadcrumb'] = array(
                    "Accueil" => base_url(),
                    "Les enseignements"=>base_url('enseignements'),
                    mb_strtoupper($lesson->label)=>"",
                );
                $this->data['lesson'] = $lesson;
                 $this->data['evaluations'] = $this->mLesson->getEvaluations($codeLesson);
                $this->meta = array(
                    "description"=>excerpt($lesson->syllabus,150),
                    "url"=>base_url('enseignements').'/'.permalink($lesson->label).'--'.permalink($lesson->code),
                    "image"=>img_url('logo/logo.png')
                );

                $this->render(mb_strtoupper($lesson->label),"single");
            }
        }
        else
            show_404();
    }

    public function category($cat){
        if(!in_array($cat,array('cours','filiere','promotion')))
            show_404();


        $this->data['allLesson'] = $this->mLesson->getByType($cat)->result();

        if($cat=="cours")
            $texte = "Formations Accélérées ";
        elseif($cat=="filiere")
            $texte = "Formations Longues";
        else
            $texte = "Formations Promotionnelles";

        $this->meta = array(
            "description"=>"$texte au Centre de Formation Professionnelle MULTISOFT ACADEMY",
            "url"=>base_url("enseignements/$cat"),
            "image"=>img_url('logo/logo.png')
        );

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Les enseignements"=>base_url('enseignements'),
            $texte=>""
        );

        $this->render($texte,"category");




    }

    /**
     * @param null $lesson
     */
    public function register($lesson=null){
        $this->load->library('form_validation');

        if($lesson!=null) {
            $data = explode('--', $lesson, 2);
            $codeLesson = $data[1];
            $plinkLesson = $data[0];
            $lesson = $this->mLesson->get($codeLesson)->result();
            if ($lesson) {
                $lesson = $lesson[0];
                if(permalink($lesson->label) != $plinkLesson){
                    show_404();
                }

                if(isset($_POST) and !empty($_POST))
                {
                    $this->load->model('admin/registration_model', 'registration');
                    $this->load->model('admin/log_model', 'logM');
                    $this->load->model('admin/notification_model', 'notification');
                    $this->load->model('admin/trainer_model', 'trainer');

                    $post = new stdClass();
                    $post->id=session_data('id');
                    $post->lesson=$lesson->id;
                    $post->installment=0;

                    $post->amount=$lesson->fees;
                    $post->lCode=$lesson->code;

                    $post->slice=0;
                    $post->promotion = null;
                    $resp ='';
                    $reCaptcha = new ReCaptcha("6LfFLjAUAAAAADxozug2fW-P8kPn_ixJ0MV1IXRE");
                    if ($_POST["g-recaptcha-response"]) {
			    $resp = $reCaptcha->verifyResponse(
			        $_SERVER["REMOTE_ADDR"],
			        $_POST["g-recaptcha-response"]
			    );
			}	
			
		    
                    $lessonUser = $this->trainer->getLessonSlip($post->id);
                    $trouve=false;
                    foreach($lessonUser as $l){
                        if($l->lId == $lesson->id && $l->locked=='0')
                            $trouve = true;
                    }
		    if ($resp != null && $resp->success) {
			    if($trouve){
                        $this->data['status']=false;
                        $this->data['message'] = "<b>Demande refusée:</b> Vous ne pouvez pas vous inscrire à cet enseignement. car vous y êtes formateur";
                    }
                    elseif(session_data_isset('new') and session_data('new')){
                        $this->data['status']=false;
                        $this->data['message'] = "<b>Demande refusée:</b> Vous devez <a class='btn btn-outline-danger' href='".base_url('account/completeAccount')."'>complèter votre compte </a> utilisateur pour poursuivre l'enregistrement";
                    }
                    elseif ($this->registration->isRegistered($post->id, $post->lesson)){
                        $this->data['status']=false;
                        $this->data['message'] = '<b>Demande refusée:</b> Vous avez déjà une inscription en attente ou en cours pour cet enseignement.';
                    }
                    else{
                        $this->db->trans_begin();
                        $registrationBean = $this->registration->saveFrontEndRegistration($post);
                        if(!empty($registrationBean)){
                            $registrationBean = $registrationBean[0];
                            $student=$this->userM->getUser(intval($post->id))[0];

                            $this->logM->save(array(
                                "motivation" => "",
                                "author" => session_data('id'),
                                "date" => moment()->format('Y-m-d H:i:s'),
                                "action" => "Inscription de ".($student->sexe==0?'Mme ':'M.').mb_strtoupper($student->lastname).ucwords(mb_strtolower(' '.$student->firstname))." à l'enseignement ".mb_strtoupper($lesson->label)."."
                            ));

                            $this->notification->publish(array(
                                "sender"=>1,
                                "content"=>"Votre inscription <b>N° $registrationBean->code </b>a été enregistrée. Passez au Centre de Formation pour la valider en payant au moins une tranche des frais de formation avant le $registrationBean->dead_line",
                                "send_date"=>moment()->format('Y-m-d H:i:s'),
                                "target"=>$student->id,
                                "promotion"=>-1,
                                "url"=>""
                            ));

                            if ($this->db->trans_status()==TRUE)
                            {
                                $sent=sendMail(array(
                                    "user"=>$student,
                                    "title"=>"Confirmation d'inscription",
                                    "message"=>($student->sexe==0?'Mme <b>':'M. <b>').mb_strtoupper($student->lastname).ucwords(mb_strtolower(' '.$student->firstname)).",</b><br>Votre inscription &agrave; l'enseignement ".mb_strtoupper($lesson->label)." a bien &eacute;t&eacute; re&ccedil;ue. Passez au centre de formation pour verser une tranche des frais de formation avant le $registrationBean->dead_line."
                                ));
                                if (is_bool($sent) and $sent)
                                {
                                    $this->db->trans_commit();
                                    updateVisit();
                                    $this->data['allReg'] = $this->mGeneral->getAllInscription();
                                    $this->data['status']=true;
                                    $this->data['message'] = "<b>Inscription réussie:</b> Vérifier votre <a class='btn btn-outline-success' href='".base_url('memberGate/')."'> compte membre</a> pour les détails de votre inscription.";
                                } else
                                {
                                	var_dump($sent);
                                    $this->db->trans_rollback();
                                    $this->data['status']=false;
                                    $this->data['message'] = '<b>Echec d\'enregistrement:</b> Veuillez vérifier votre connexion internet.';
                                }
                            } else
                            {
                                $this->db->trans_rollback();
                                $this->data['status']=false;
                                $this->data['message'] = "<b>Echec d\'enregistrement:</b> L'enregistrement est temporairement indisponible reessayer ultérieurement SVP!";
                            }

                        }
                        else{
                            $this->data['status']=false;
                            $this->data['message'] = "<b>Echec d'enregistrement:</b> L'enregistrement est temporairement indisponible reessayer ultérieurement SVP!" ;
                        }
                    }
		    }
		    else{
		        $this->data['status']=false;
                        $this->data['message'] = "<b>Demande refusée:</b> vous ne pouvez pas effectuer cette action";
		    }
                    
                }

                $this->data['lesson'] = $lesson;
                $this->meta = array(
                    "description"=>"Formulaire d'inscription à l'enseignement ".mb_strtoupper($lesson->label),
                    "url"=>base_url("enseignements/register"."/".permalink($lesson->label).'--'.permalink($lesson->code)),
                    "image"=>img_url('logo/logo.png')
                );

                $this->data ['breadcrumb'] = array(
                    "Accueil" => base_url(),
                    "Les enseignements"=>base_url('enseignements'),
                    "Inscription à l'enseignement ".mb_strtoupper($lesson->label)=>""
                );
                $this->render("Demande d'inscription à l'enseignement $lesson->label","register-form");

            } else
                show_404();
        }
        else{
            redirect('enseignements');
        }




    }

    private function render($titre=NULL,$view = NULL)
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
            $this->data['view'] = "list";
        }
        $this->load->view("public/lesson-page-model", $this->data);
        $this->load->view('public/footer');
    }


}