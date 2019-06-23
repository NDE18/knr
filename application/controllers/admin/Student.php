<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Student extends CI_Controller
{
    protected $data, $menu;

    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));

        $this->load->library('form_validation');
        $this->load->helper('general_helper');
        $this->load->model('admin/student_model', 'student');
        $this->load->model('admin/notification_model', 'notif');
        $this->load->model('admin/log_model', 'mLog');

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

    /**
     * formulaire d'ajout d'un apprenant
     */
    public function formAdd()
    {
        $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
        $this->form_validation->set_rules('lastName', '"Nom"', 'trim|required|min_length[2]|max_length[255]|encode_php_tags');
        $this->form_validation->set_rules('firstName', '"Prénom"', 'trim|min_length[2]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('school', '"Ecole"', 'trim|min_length[2]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('schoolLevel', '"Niveau scolaire"', 'trim|min_length[1]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('schoolArea', '"Parcours scolaire"', 'trim|min_length[1]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('birthDate', '"Date de naissance"', 'required');
        $this->form_validation->set_rules('birthPlace', '"Lieu de naissance"', 'trim|required|min_length[2]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('nationality', '"Pays d\'origine"', 'required|encode_php_tags');
        $this->form_validation->set_rules('address', '"Adresse"', 'trim|min_length[2]|max_length[128]|encode_php_tags');
        $this->form_validation->set_rules('phone', '"Téléphone"', 'trim|required|min_length[9]|max_length[16]|encode_php_tags');
        $this->form_validation->set_rules('mail', '"E-mail"', 'trim|min_length[9]|max_length[128]|encode_php_tags|valid_email|is_unique[user.mail]');
        $this->form_validation->set_rules('genre', '"Genre"', 'trim|required|encode_php_tags');

        if($this->form_validation->run())
        {
            //var_dump($this->input->post()); die(0);
            $mdp = randomPassword(14);
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
                'school_level'=>$this->input->post('schoolLevel'),
                'school_area'=>$this->input->post('schoolArea'),
                'question'=>'none',
                'answer'=>'none',
                'login'=>$this->input->post('mail'),
                'pwd'=>$mdp,
                'register_date'=>'',
                'state'=>'0',
                'number_id'=>'',
                'sexe'=>($this->input->post('genre') == '1') ? 1 : 0
            );
            $this->load->database();
            $this->db->trans_begin();
            $testResult = $this->student->correctData($post);

            if(is_array($testResult)){
                $this->db->trans_commit();
                $this->data['status']=false;
                $this->data['message'] = '<b>Echec d\'enregistrement:</b> '.$testResult['msg'];
            }
            elseif($testResult)
            {
                $userId = $this->student->saveUser($post);
                if(($userId))
                {
                    $user = (object)$this->student->getUser($userId);
                    if(!empty($user)){
                        $notifState = $this->notif->publish(array(
                            'sender' => session_data('id'),
                            'content' => "Vous avez bien été inscrit",
                            'target' => array($userId),
                            'promotion'=>-1
                        ));
                        if($notifState){
                            $logState = $this->mLog->save(array(
                                'date'=>moment()->format('Y-m-d H:i:s'),
                                'author'=>session_data('id'),
                                'action'=>'Enregistrement de <b> '.$user->firstname.' '.$user->lastname.' </b>à la plateforme.'
                            ));
                            if($logState){
                                $title="Bienvenue dans la Plateforme MULTISOFT ACADEMY";
                                $message=($user->sexe==0?'Mme <b>':'M. <b>').mb_strtoupper($user->lastname).' '.ucwords($user->firstname)."</b>, Votre inscription a bien &eacute;t&eacute; enregistr&eacute;e. Veuillez utiliser les param&egrave;tres suivants pour <a href='".base_url('account/login')."'>activer votre compte</a> : <br>Votre login : <b>" . $user->mail. "</b><br>Votre mot de passe : <br><b>" . $mdp.'</b>';
                                $sent = sendMail(array('user'=>$user, 'title'=>$title, 'message'=>$message));
                                if (is_bool($sent) And $sent) {
                                    $this->db->trans_commit();
                                    redirect('admin/registration/registerToLessons/'.$userId);
                                } else {
                                    $this->db->trans_rollback();
                                    $this->data['status']=false;
                                    $this->data['message'] = '<b>Echec d\'enregistrement:</b> '."Echec d'envoi de l'e-mail. Veuillez vérifier votre connexion Internet.";
                                }

                            }
                            else{
                                $this->db->trans_rollback();
                                $this->data['status']=false;
                                $this->data['message'] = '<b>Echec d\'enregistrement: -- LOG</b> ';
                            }

                        }
                        else{
                            $this->db->trans_rollback();
                            $this->data['status']=false;
                            $this->data['message'] = '<b>Echec d\'enregistrement:</b> --notif';
                        }
                    }
                    else{
                        $this->db->trans_rollback();
                        $this->data['status']=false;
                        $this->data['message'] = '<b>Echec d\'enregistrement:--USER</b> ';
                    }
                }
                else
                {
                    $this->db->trans_rollback();
                    $this->data['status']=false;
                    $this->data['message'] = '<b>Echec d\'enregistrement:</b> ';
                }
            }

        }
        $this->render('admin/student/form-add', 'Enregistrer un apprenant');
    }

    /**
     * Liste de tous les apprenants
     */
    public function all(){
        $this->load->model('admin/lesson_model', 'lesson');

        $this->data['listL'] = $this->lesson->getAll()->result();
        $this->data['query'] = $this->student->lyst()->result();

        $this->render('admin/student/list', 'Liste des apprenants');
    }

    /**
     * @param bool|false $id - id de l'apprenant
     * @param bool|false $print - condition d'impression
     * Affichage du profil d'un apprenant avec la possibilité d'imprimer
     */
    public function profile($id = false, $print = false){
        if(isset($id) and $print != 'print'){
            $student = $this->student->getStudent($id)->result();
            if(empty($student)){
                redirect('admin/student');
            }else{
               if($_FILES AND !empty($_FILES)){
                   $this->load->config('uploads', TRUE);
                   $this->load->library('upload', $this->config->item('images', 'uploads'));
                   foreach($_FILES as $name => $file){
                       if(empty($name)){
                           $this->data['message'] = 'Vous n\'avez sélectionné aucune image';
                           $this->profile($id);
                       }
                       elseif(!$this->upload->do_upload($name))
                       {
                           $this->data['message'] = $this->upload->display_errors();
                           $this->profile($id);
                       }
                       else
                       {
                           if($this->student->savePhoto('assets/uploads' . explode('assets/uploads', $this->upload->data()['full_path'])[1], $id)){
                               $this->data['imgName'] = $this->upload->data()['file_name'];
                               $this->data['message'] = 'La Photo a été modifié';
                               //$this->profile($id);
                           }
                       }
                   }
               }
                $student = $student[0];
                $promotion = $this->student->getPromotion($id)->result();
                $dateCon = ($student->last_connexion == "0000-00-00") ? "null" : date_create($student->last_connexion);
                $dateReg = date_create($student->register_date);
                $dateBirth = date_create($student->birth_date);
                $this->data['student'] = $student;
                $this->data['dateCon'] = $dateCon;
                $this->data['dateReg'] = $dateReg;
                $this->data['dateBirth'] = $dateBirth;
                $lesson = $this->student->getLesson($id);
                if(empty($lesson)){
                    $this->render("admin/student/profile", "Profil");
                }else{
                    if(empty($promotion)){
                        $this->data['lesson'] = $lesson;
                        $this->render("admin/student/profile", "Profil");
                    }else{
                        $this->data['promotion'] = $promotion;
                        $this->data['lesson'] = $lesson;
                        $this->render("admin/student/profile", "Profil");
                    }
                }
            }
        }elseif(isset($id) and isset($print) and $print == 'print'){
            $this->load->helper("html2pdf_helper");
            $student = $this->student->getStudent($id)->result();
            if(empty($student)){
                redirect('admin/student');
            }else {
                $student = $student[0];
                $this->data['student'] = $student;
                $dateCon = ($student->last_connexion = "0000-00-00") ? "null" : date_create($student->last_connexion);
                $dateReg = date_create($student->register_date);
                $dateBirth = date_format($dateReg, 'd').'/'.date_format($dateReg, 'm').'/'.date_format($dateReg, 'Y');
                $dateReg = 'Année :'.date_format($dateReg, 'Y').'   Mois :'.date_format($dateReg, 'm').'   Jour :'.date_format($dateReg, 'd');
                $dateCon = 'Année :'.date_format($dateCon, 'Y').'   Mois :'.date_format($dateCon, 'm').'   Jour :'.date_format($dateCon, 'd');
                $this->data['dateCon'] = $dateCon;
                $this->data['dateReg'] = $dateReg;
                $this->data['dateBirth'] = $dateBirth;
                $lesson = $this->student->getLesson($id);
                $profil = '';
                if(!empty($lesson))
                    $this->data['lesson'] = $lesson;

                $content =  $this->load->view('admin/student/pdf-profile', $this->data, TRUE);

                try{
                    $pdf = new HTML2PDF('P', 'A4', 'fr');
                    $pdf->pdf->setDisplayMode('fullpage');
                    $pdf->writeHTML($content);
                    ob_end_clean();
                    $pdf->Output('Profile'.$student->number_id.'.pdf');
                }catch (HTML2PDF_exception $e){
                    die($e);
                }
            }
        }
    }

    /**
     * Génération du pdf de la liste des cartes des apprenants
     */
    public function printCards(){
        $this->load->helper("html2pdf_helper");
        if(isset($_POST['send']) and isset($_POST['lesson'])) {
            $lesson = ($_POST['lesson'] == '-1') ? false : intval($_POST['lesson']);
            $student = $this->student->lystStudentCard($lesson, 'cours')->result();
            if(empty($student)){
                set_flash_data(array('alert', 'Désolé il n\' existe aucun apprenant pour cet enseignement ou cet enseignement n\'est pas de type filière.'));
                redirect("admin/student/all");
            }else{
                $this->data['student'] = $student;

                $content =   $this->load->view('admin/student/pdf-list-card', $this->data, TRUE);

                try{
                    $pdf = new HTML2PDF('P', 'A4', 'fr',true,'UTF-8',array(1,1,1,1));
                    $pdf->pdf->setDisplayMode('fullpage');
                    $pdf->writeHTML($content);
                    ob_end_clean();
                    $pdf->Output('Cardlist.pdf');
                }catch (HTML2PDF_exception $e){
                    die($e);
                }
            }

        }elseif(isset($_POST['lesson']) and $_POST['lesson'] != -1){
            $this->load->model('admin/lesson_model', 'lesson');

            $this->data['query'] = $this->student->lystStudentCard(intval($_POST['lesson']))->result();

            echo $this->load->view('admin/student/dynamic-list', $this->data, true);

        }else{
            $this->data['query'] = $this->student->lyst()->result();
            echo $this->load->view('admin/student/dynamic-list', $this->data, true);
        }


    }

    /**
     * @param bool|false $id
     * Affichage de la liste des logs d'un apprenant
     */
    public function log($id=false){
        $log = $this->student->log($id);
        $this->data['log'] = $log;
        $this->render('admin/student/log', 'Liste des logs');
    }

}