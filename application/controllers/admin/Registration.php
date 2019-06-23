<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Registration extends Ci_Controller
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
        $this->load->model('admin/promotion_model', 'promotion');
        $this->load->model('admin/registration_model', 'registration');
        $this->load->model('admin/log_model', 'logM');
        $this->load->model('admin/notification_model', 'notification');

        $this->menu['notif'] = $this->notification->newNotif();
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

    public function registerToLessons($id=null)
    {
        $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
        $this->form_validation->set_rules('lesson', '"Enseignement"', 'trim|required');
        $this->form_validation->set_rules('installment', '"Avance"', 'required', array('required'=>'Vous devez verser une avance d\'au moins 45%'));

        if($this->form_validation->run()) {
            $this->load->model('admin/settings_model', 'settings');
            $this->load->model('admin/lesson_model', 'lesson');
            $this->load->model('admin/user_model', 'user');
            $this->load->model('admin/trainer_model', 'trainer');

            $lessonUser = $this->trainer->getLessonSlip($this->input->post('id'));
            $trouve=false;
            //var_dump($lessonUser);
            foreach($lessonUser as $l){
                if($l->lId == $this->input->post('lesson') && $l->locked=='0')
                    $trouve = true;
            }

            if(!$trouve){
                $this->data['id']=$this->input->post('id');
                $amount=($_POST['negociated']!=null and $_POST['negociated']!=0)?$_POST['negociated'] : $this->lesson->getL($this->input->post('lesson'))->fees;
                $percent=$this->settings->getMinRegInstallment();
                $MinInstVal = ceil(($amount * $percent) / 100);
                //var_dump($percent, $amount, $MinInstVal); die();
                $post = new stdClass();
                $post->id=$this->input->post('id');
                $post->lesson=$this->input->post('lesson');
                $post->installment=$this->input->post('installment');
                $post->amount=$amount;
                $lesson=$this->lesson->getL($post->lesson);
                //var_dump($lesson);
                $post->lCode=$lesson->code;
                $post->dead_line=null;
                $post->slice=1;
                //var_dump($MinInstVal, intval($post->installment)); die();
                if ($MinInstVal>intval($post->installment))
                {
                    $this->data['status']=false;
                    $this->data['message'] = '<b>Echec d\'enregistrement:</b> Vous devez avancer au moins '.$percent.'% des frais pour cet enseignement.';
                    $this->load->model('admin/lesson_model', 'lesson');
                    $this->load->model('admin/student_model', 'student');
                    $this->data['allLess']=$this->lesson->retrieveLessons("label");
                    $usr=$this->student->getUser($this->data['id']);
                    if($usr!=null)
                        $this->data['user']=$usr;
                    else
                        $this->data['users']=$this->student->getUsers("lastname");
                    $this->render('admin/registration/registerToLessons', 'Inscription à un enseignement');
                }
                else if ($this->registration->isRegistered($post->id, $post->lesson)){
                    $this->data['status']=false;
                    $this->data['message'] = '<b>Echec d\'enregistrement:</b> Cet utilisateur est déjà inscription en attente ou en cours pour cet enseignement.';
                    $this->load->model('admin/lesson_model', 'lesson');
                    $this->load->model('admin/student_model', 'student');
                    $this->data['allLess']=$this->lesson->retrieveLessons("label");
                    $usr=$this->student->getUser($this->data['id']);
                    if($usr!=null)
                        $this->data['user']=$usr;
                    else
                        $this->data['users']=$this->student->getUsers("lastname");
                    $this->render('admin/registration/registerToLessons', 'Inscription à un enseignement');
                }
                else
                {
                    $this->db->trans_begin();
                     $promotion=0;$promotion=0;
                    if (isset($_POST['newpromo']) or $_POST['plist']=='' or $_POST['plist']==null)
                    {
                        $promotion=$this->promotion->getOpenedPromo($post->lesson);
                        if ($promotion==null)
                            $promotion=$this->promotion->create($post->lesson);
                        else
                            $promotion=$promotion->id;
                    } else {
                        $promotion=intval($_POST['plist']);
                    }

                    /*$promotion=$this->promotion->getOpenedPromo($post->lesson);
                    if ($promotion==null)
                        $promotion=$this->promotion->create($post->lesson);
                    else
                        $promotion=$promotion->id;*/
                    $registration=$this->registration->save($post);
                    if ($this->registration->isValidated($registration)){
                        $this->data['status']=false;
                        $this->data['message'] = '<b>Echec d\'enregistrement:</b> Cette inscription a déjà été validée.';
                        $this->load->model('admin/lesson_model', 'lesson');
                        $this->load->model('admin/student_model', 'student');
                        $this->data['allLess']=$this->lesson->retrieveLessons("label");
                        $usr=$this->student->getUser($this->data['id']);
                        if($usr!=null)
                            $this->data['user']=$usr;
                        else
                            $this->data['users']=$this->student->getUsers("lastname");
                        $this->render('admin/registration/registerToLessons', 'Inscription à un enseignement');
                    }
                    else if ($this->registration->validate($registration, $promotion))
                    {
                        $nbr = $this->db->query('SELECT user, role FROM user_role WHERE user=? AND role=?', array($post->id, STUDENT))->num_rows();
                        if ($nbr == 0) //Si l'utilisateur n'a pas encore de role apprenant
                        {
                            //On ajoute l'utilisateur au rÃ´le d'apprenant
                            $this->db->query('INSERT INTO user_role(user, role)
                                  VALUES (?, ?)', array($post->id, STUDENT));
                        }
                        $student=$this->user->getUser($post->id)->result()[0];

                        $this->logM->save(array(
                            "motivation" => "",
                            "author" => session_data('id'),
                            "date" => moment()->format('Y-m-d H:i:s'),
                            "action" => "Inscription de ".($student->sexe==0?'Mme ':'M.').mb_strtoupper($student->lastname).ucwords(mb_strtolower(' '.$student->firstname))." à l'enseignement ".mb_strtoupper($lesson->label)."."
                        ));

                        $toPay="";

                        if (intval($post->installment)<$amount)
                        {
                            $toPay="Vous devez encore payer <b>".($amount-intval($post->installment))."</b> FCFA.";

                        }
                        $this->notification->publish(array(
                            "sender"=>session_data('id'),
                            "content"=>"Votre inscription a été enregistrée et validée.<br>".$toPay,
                            "send_date"=>moment()->format('Y-m-d H:i:s'),
                            "target"=>$student->id,
                            "promotion"=>-1,
                            "url"=>""
                        ));

                        if ($this->db->trans_status()==TRUE)
                        {
                            $sent=sendMail(array(
                                "user"=>$student,
                                "title"=>"Validation de votre inscription",
                                "message"=>($student->sexe==0?'Mme ':'M.').mb_strtoupper($student->lastname).ucwords(mb_strtolower(' '.$student->firstname)).",<br>Votre inscription &agrave; l'enseignement ".mb_strtoupper($lesson->label)." a bien &eacute;t&eacute; valid&eacute;e. Vous pouvez commencer &agrave; le suivre.<br>$toPay"
                            ));
                            if (is_bool($sent) and $sent)
                            {
                                $this->db->trans_commit();
                                set_flash_data(array("success", "L'inscription de ".($student->sexe==0?'Mme ':'M.').mb_strtoupper($student->lastname).ucwords(mb_strtolower(' '.$student->firstname))." a bien été enregistrée et validée."));
                                redirect('admin/registration');
                            } else
                            {
                                $this->db->trans_rollback();
                                $this->data['status']=false;
                                $this->data['message'] = '<b>Echec d\'enregistrement:</b> Veuillez vérifier votre connexion internet.';
                                $this->load->model('admin/lesson_model', 'lesson');
                                $this->load->model('admin/student_model', 'student');
                                $this->data['allLess']=$this->lesson->retrieveLessons("label");
                                $usr=$this->student->getUser($this->data['id']);
                                if($usr!=null)
                                    $this->data['user']=$usr;
                                else
                                    $this->data['users']=$this->student->getUsers("lastname");
                                $this->render('admin/registration/registerToLessons', 'Inscription à un enseignement');
                            }
                        } else
                        {
                            $this->db->trans_rollback();
                            $this->data['status']=false;
                            $this->data['message'] = '<b>Echec d\'enregistrement:</b> Une erreur interne s\'est produite. Veuillez réessayer ultérieurement.';
                            $this->load->model('admin/lesson_model', 'lesson');
                            $this->load->model('admin/student_model', 'student');
                            $this->data['allLess']=$this->lesson->retrieveLessons("label");
                            $usr=$this->student->getUser($this->data['id']);
                            if($usr!=null)
                                $this->data['user']=$usr;
                            else
                                $this->data['users']=$this->student->getUsers("lastname");
                            $this->render('admin/registration/registerToLessons', 'Inscription à un enseignement');
                        }

                    } else
                    {
                        $this->db->trans_rollback();
                        $this->data['status']=false;
                        $this->data['message'] = '<b>Echec de validation de l\'inscription:</b> Veuillez réessayer.';
                        $this->load->model('admin/lesson_model', 'lesson');
                        $this->load->model('admin/student_model', 'student');
                        $this->data['allLess']=$this->lesson->retrieveLessons("label");
                        $usr=$this->student->getUser($this->data['id']);
                        if($usr!=null)
                            $this->data['user']=$usr;
                        else
                            $this->data['users']=$this->student->getUsers("lastname");
                        $this->render('admin/registration/registerToLessons', 'Inscription à un enseignement');
                    }
                }
            }
            else{
                $this->data['status']=false;
                $this->data['message'] = '<b>Echec de validation de l\'inscription:</b> Vous ne pouvez pas inscrire cet apprenant à cet enseignement car il y est formateur.';
                $this->data['id']=($id!=null)?$id:0;
                $this->load->model('admin/lesson_model', 'lesson');
                $this->load->model('admin/student_model', 'student');
                $this->data['allLess']=$this->lesson->retrieveLessons("label");
                $usr=$this->student->getUser($this->data['id']);
                if($usr!=null)
                    $this->data['user']=$usr;
                else
                    $this->data['users']=$this->student->getUsers("lastname");
                $this->render('admin/registration/registerToLessons', 'Inscription à un enseignement');
            }




        }
        else
        {
            //echo "ndem"; die();
            $this->data['id']=($id!=null)?$id:0;

            $this->load->model('admin/lesson_model', 'lesson');
            $this->load->model('admin/student_model', 'student');
            $this->data['allLess']=$this->lesson->retrieveLessons("label");
            $usr=$this->student->getUser($this->data['id']);
            if($usr!=null)
                $this->data['user']=$usr;
            else
                $this->data['users']=$this->student->getUsers("lastname");
                
            $this->data['promotions'] = $this->promotion->getAvPromos();
            $this->render('admin/registration/registerToLessons', 'Inscription à un enseignement');
        }
    }

    public function getRegInfos()
    {
        if (isset($_POST['send_vreg'])) {
            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('code', '"Code"', 'trim|required|max_length[15]|min_length[13]|regex_match[/[A-Z]{3,5}[0-9]{6}N[0-9]{3}/]', array('regex_match' => 'Le code doit etre sous la forme CODE000000N000'));

            //var_dump('toto');die();
            if ($this->form_validation->run()) {
                $this->data['code'] = $this->input->post('code');
                $infos = $this->registration->getRegistration($this->data['code']);

                if (is_object($infos)) {
                    $this->data['infos']=$infos;
                    $this->render('admin/registration/regInfos', 'Informations sur l\'inscription');
                } else {
                    $this->data['message'] = $infos;
                    $this->render('admin/registration/validateRegistration', 'Validation des inscriptions');
                }
            } else
            {
                $this->render('admin/registration/validateRegistration', 'Validation des inscriptions');
            }
        }

        if (isset($_POST['send_rinfos']))
        {
            $this->validateRegistrations($this->input->post('code'));
        }


    }

    public function validateRegistrations($code='')
    {
        if($code=='') {
            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('code', '"Code"', 'trim|required|max_length[14]|min_length[14]|regex_match[/[A-Z]{3,5}[0-9]{6}N[0-9]{3}/]', array('regex_match' => 'Le code doit etre sous la forme CODE000000N000'));

            if ($this->form_validation->run()) {
                $reg=$this->registration->getRegistration($code);
                if (is_object($reg))
                {
                    $lesson=$this->lesson->getL($reg->lesson);
                    $student=$this->user->getUser($reg->user);
                    $promotion=$this->promotion->getOpenedPromo($reg->lesson);
                    if ($promotion==null)
                        $promotion=$this->promotion->create($reg->lesson);
                    else
                        $promotion=$promotion->id;
                    if ($this->registration->isValidated($reg->id)) {
                        $this->registration->validate($code, $promotion);
                        $nbr = $this->db->query('SELECT user, role FROM user_role WHERE user=? AND role=?', array($student->id, STUDENT))->num_rows();
                        if ($nbr == 0) //Si l'utilisateur n'a pas encore de role apprenant
                        {
                            //On ajoute l'utilisateur au rÃ´le d'apprenant
                            $this->db->query('INSERT INTO user_role(user, role)
                                  VALUES (?, ?)', array($student->id, STUDENT));
                        }
                        $this->logM->save(array(
                            "motivation" => "",
                            "author" => session_data('id'),
                            "date" => moment()->format('Y-m-d H:i:s'),
                            "action" => "Validation de l'inscription N° $code de ".($student->sexe==0?'Mme ':'M.').mb_strtoupper($student->lastname).ucwords(mb_strtolower(' '.$student->firstname))."."
                        ));

                        $this->notification->publish(array(
                            "sender"=>session_data('id'),
                            "content"=>"Votre inscription a été enregistrée et validée.",
                            "send_date"=>moment()->format('Y-m-d H:i:s'),
                            "target"=>$student->id,
                            "promotion"=>-1,
                            "url"=>""
                        ));

                        $toPay="";
                        $bill=intval($reg->amount-$reg->installment);

                        if ($bill>0)
                        {
                            $toPay="Vous devez encore payer <b>".($bill)."</b> FCFA.";
                            $this->notification->publish(array(
                                "sender"=>session_data('id'),
                                "content"=>$toPay,
                                "send_date"=>moment()->format('Y-m-d H:i:s'),
                                "target"=>$student->id,
                                "promotion"=>-1,
                                "url"=>""
                            ));
                        }

                        $sent=sendMail(array(
                            "user"=>$student,
                            "title"=>"Validation de votre inscription",
                            "message"=>($student->sexe==0?'Mme ':'M.').mb_strtoupper($student->lastname).ucwords(mb_strtolower(' '.$student->firstname)).",<br>Votre inscription &agrave; l'enseignement ".mb_strtoupper($lesson->label)." a bien &eacute;t&eacute; valid&eacute;e. Vous pouvez commencer &agrave; le suivre.<br>$toPay"
                        ));
                        if (is_bool($sent) and $sent)
                        {
                            $this->db->trans_commit();
                            set_flash_data(array("success", "L'inscription de ".($student->sexe==0?'Mme ':'M.').mb_strtoupper($student->lastname).ucwords(mb_strtolower(' '.$student->firstname))." a bien été enregistrée et validée."));
                            redirect('admin/registration');
                        } else
                        {
                            $this->data['status']=false;
                            $this->data['message'] = '<b>La validation a échoué :</b> Veuillez vérifier votre connexion à Internet.';
                            $this->render('admin/registration/validateRegistration', 'Validation des inscriptions');
                        }
                    } else {
                        $this->data['status']=false;
                        $this->data['message'] = '<b>La validation a échoué :</b> Cette inscription a déjà été validée.';
                        $this->render('admin/registration/validateRegistration', 'Validation des inscriptions');
                    }
                }
                else
                {
                    $this->data['status']=false;
                    $this->data['message'] = '<b>La validation a échoué :</b> '.$reg;
                    $this->render('admin/registration/validateRegistration', 'Validation des inscriptions');
                }
            }
            else {
                $this->render('admin/registration/validateRegistration', 'Validation des inscriptions');
            }
        }
        else
        {
            $postOk = $this->registration->validateRegistrations($code);

            if (is_bool($postOk) And $postOk) {
                set_flash_data(array("success",'L\'inscription de cet apprenant a bien été validée.'));
                redirect("admin/registration");
            } else {
                $this->data['status']=false;
                $this->data['message'] = '<b>La validation a échoué :</b> ' . $postOk;
                $this->render('admin/registration/validateRegistration', 'Validation des inscriptions');
            }
        }
    }

    public function addRegistration()
    {
        $this->render('admin/registration/registrationChoice', 'Ajouter inscription');
    }

    public function all()
    {
        $this->data['allReg'] = $this->registration->regList();
        $this->render('admin/registration/list', 'Liste des inscriptions');
    }

    public function printQuitus($regId=false){
        $this->load->helper("html2pdf_helper");

        $this->data['registration'] = $this->registration->printQuitus($regId);
        if(!empty($this->data['registration'])){
            $this->data['registration'] = $this->data['registration'][0];
            $dateBirth = date_create($this->data['registration']->birth_date);
            $this->data['dateBirth'] = $dateBirth;

            $content = $this->load->view('admin/registration/pdf-quitus', $this->data, TRUE);
            try{
                $pdf = new HTML2PDF('P', 'A4', 'fr',true,"UTF-8",array(1,1,1,1));
                $pdf->pdf->setDisplayMode('fullpage');
                $pdf->writeHTML($content,isset($_GET['vuehtml']));
                ob_end_clean();
                $pdf->Output('Quitus--'.$this->data['registration']->regCode.'.pdf');
            }catch (HTML2PDF_exception $e){
                die($e);
            }
        }else{
            redirect("admin/registration");
        }
    }

    public function printQuittanceRegistration($regId=false){
        $this->load->helper("html2pdf_helper");

        $this->data['registration'] = $this->registration->printQuitus($regId);
        if(!empty($this->data['registration'])){

            $this->data['registration'] = $this->data['registration'][0];
            $dateBirth = date_create($this->data['registration']->birth_date);
            $this->data['dateBirth'] = $dateBirth;
            $regDate = date_create($this->data['registration']->regDate);

            switch(date_format($regDate, 'm'))
            {
                case '01': $regDate = date_format($regDate, 'd').' Janvier '.date_format($regDate, 'Y'); break;
                case '02': $regDate = date_format($regDate, 'd').' Février '.date_format($regDate, 'Y'); break;
                case '03': $regDate = date_format($regDate, 'd').' Mars '.date_format($regDate, 'Y'); break;
                case '04': $regDate = date_format($regDate, 'd').' Avril '.date_format($regDate, 'Y'); break;
                case '05': $regDate = date_format($regDate, 'd').' Mai '.date_format($regDate, 'Y'); break;
                case '06': $regDate = date_format($regDate, 'd').' Juin '.date_format($regDate, 'Y'); break;
                case '07': $regDate = date_format($regDate, 'd').' Juillet '.date_format($regDate, 'Y'); break;
                case '08': $regDate = date_format($regDate, 'd').' Août '.date_format($regDate, 'Y'); break;
                case '09': $regDate = date_format($regDate, 'd').' Septembre '.date_format($regDate, 'Y'); break;
                case '10': $regDate = date_format($regDate, 'd').' Octobre '.date_format($regDate, 'Y'); break;
                case '11': $regDate = date_format($regDate, 'd').' Novembre '.date_format($regDate, 'Y'); break;
                case '12': $regDate = date_format($regDate, 'd').' Décembre '.date_format($regDate, 'Y'); break;
                default: $regDate = date_format($regDate, 'd').'/'.date_format($regDate, 'm').'/'.date_format($regDate, 'Y') ; break;
            }

            $this->data['regDate'] = $regDate;
            $content = $this->load->view('admin/registration/pdf-receipt', $this->data, TRUE);
            try{
                $pdf = new HTML2PDF('P', 'A4', 'fr',true,"UTF-8",array(1,1,1,1));
                $pdf->pdf->setDisplayMode('fullpage');
                $pdf->writeHTML($content,isset($_GET['vuehtml']));
                ob_end_clean();
                $pdf->Output('quittanceRegistration'.$regId.'.pdf');

            }catch (HTML2PDF_exception $e){
                die($e);
            }
            finally{
                redirect('admin/registration');
            }
        }else{
            redirect("admin/registration");
            //var_dump("vide");
        }


    }

    public function shelveRegistration($idReg = false){
        if(isset($idReg) and !is_bool($idReg)){

            $registration = $this->registration->regList($idReg);
            if(!empty($registration))
            {
                $this->data['idReg'] = $idReg;
                $this->render('admin/registration/log', 'Sauvegarde du motif');
            }
            else{
                set_flash_data(array("error","Cette inscription n'existe pas."));
                redirect('admin/registration');
            }
        }elseif($idReg == false){

            //$this->form_validation->set_rules('action', '"Action"', 'trim|required|alpha_numeric_spaces|encode_php_tags');
            $this->form_validation->set_rules('motivation', '"Motivation"', 'trim|required|min_length[3]|max_length[512]|encode_php_tags');
            $this->data['idReg']=$this->input->post('idReg');
            if($this->form_validation->run()){

                //var_dump($this->input->post()); die(0);
                $registration = $this->registration->regList($this->input->post('idReg'))[0];
                //var_dump($registration);die();
                if($this->registration->shelveRegistration(array('state'=>'-1'), $this->input->post('idReg'))){
                    $this->load->model('admin/log_model', 'logs');
                    if($this->logs->save(array('motivation'=>$this->input->post('motivation'), 'author'=>session_data('id'), 'date'=>moment()->format('Y-m-d H:i:s'), 'action'=>"Blocage de l'inscription N°".$registration['code']))){
                        set_flash_data(array("success",'L\'inscription a été bloquée.'));
                        redirect('admin/registration');
                    }

                }else{
                    var_dump('cette inscription n\'a pu être bloquée'); die(0);
                }
            }else{
                $this->render('admin/registration/log', 'Sauvegarder le log');
            }

        }
    }

    public function payInstallement($idR=false, $idU=false){
        if($idR and $idU){
            $this->data['user'] = $this->registration->regList($idR);
            if(empty($this->data['user'])){
                redirect("admin/registration");
            }else{
                $this->data['user'] = $this->data['user'][0];
                $this->data['tour'] = 1;
                $this->render('admin/registration/payInstallement', 'Paie d\'une tranche');
            }
        }elseif(!empty($this->input->post('idR')) and !empty($this->input->post('idU')) and $this->input->post('tour') != '1'){
            $idR = $this->input->post('idR');
            $idU = $this->input->post('idU');
            $this->data['user'] = $this->registration->regList($idR);

            if(empty($this->data['user'])){
                redirect("admin/registration");
            }else{
                $this->data['user'] = $this->data['user'][0];
                $this->data['tour'] = 1;
                $this->render('admin/registration/payInstallement', 'Paie d\'une tranche');
            }
        }
        elseif($this->input->post('tour') == '1'){
            $this->form_validation->set_rules('installemnt', '"Tranche prochaine"', 'trim|required|is_natural_no_zero|encode_php_tags');
            if($this->form_validation->run()){
                $res = $this->registration->payInstallement($this->input->post('idR'), $this->input->post('idU'), $this->input->post('installemnt'), $this->input->post('fees'));
                $regBean = $this->registration->regList($this->input->post('idR'))[0];
                if($res==true or $res == 2){
                    if($res==true){
                        $this->load->model('admin/log_model', 'logs');
                        $registration = $this->registration->regList($this->input->post('idR'))[0];
                        $this->logs->save(array('motivation'=>'Payement d\'une tranche', 'author'=>session_data('id'), 'date'=>moment()->format('Y-m-d H:i:s'), 'action'=>'payement d\'une tranche: code :'.$registration['code'].'; Apprenant : '.$registration['firstname']." ". $registration['lastname'] .'; Montant versée : '.$this->input->post('installemnt').'; Numéro de tranche :'.$registration['slice_number']));
                        //var_dump(intval($this->input->post('prom')));die();
                        if($this->input->post('mode') == '-1' or $this->input->post('mode') == '0'){//********
                            if($this->input->post('mode') == '-1' && intval($this->input->post('prom'))!=0){
                                if($regBean['fees']-$regBean['installment']==0){
                                    $postOk = $this->registration->endRegistration($registration['code']);
                                }
                                else $postOk = true;
                            }
                            else{
                                $promotion=$this->promotion->getOpenedPromo($registration['lId']);
                                if ($promotion==null)
                                    $promotion=$this->promotion->create($registration['lId']);
                                else
                                    $promotion=$promotion->id;

                                $postOk = $this->registration->validate($registration['code'],$promotion);
                                $nbr = $this->db->query('SELECT user, role FROM user_role WHERE user=? AND role=?', array($this->input->post('idU'), STUDENT))->num_rows();
                                if ($nbr == 0) //Si l'utilisateur n'a pas encore de role apprenant
                                {
                                    //On ajoute l'utilisateur au rÃ´le d'apprenant
                                    $this->db->query('INSERT INTO user_role(user, role)
                                  VALUES (?, ?)', array($this->input->post('idU'), STUDENT));
                                }
                            }

                            if (is_bool($postOk) And $postOk){
                                redirect('admin/registration/printQuittanceRegistration/'.$this->input->post('idR'));
                            } else {
                                if($this->registration->payInstallement($this->input->post('idR'), 'delete')){
                                    if($this->logs->save(array('author'=>session_data('id'), 'delete'))){
                                        $this->data['status']=false;
                                        $this->data['message'] = '<b>La validation a échoué :</b> ' . $postOk;
                                        $this->render('admin/registration/validateRegistration', 'Validation des inscriptions');
                                    }else{
                                        $this->data['status']=false;
                                        $this->data['message'] = '<b>La validation a échoué, Les journaux n\'ont pas été supprimés :</b> ' . $postOk;
                                        $this->render('admin/registration/validateRegistration', 'Validation des inscriptions');
                                    }
                                }else{
                                    $this->data['status']=false;
                                    $this->data['message'] = '<b>La validation a échoué, la tranche et les journaux n\'ont pas été supprimés :</b> ' . $postOk;
                                    $this->render('admin/registration/validateRegistration', 'Validation des inscriptions');
                                }
                            }
                        }
                        else{
                            redirect('admin/registration/printQuittanceRegistration/'.$this->input->post('idR'));
                            //$this->printQuittanceRegistration($this->input->post('idR'), $res);
                        }
                    }else{
                        redirect("admin/registration");
                    }
                }elseif($res == false){
                    $this->data['message'] = 'Veuillez réessayer SVP: Echec de l\'enregistrement de la tranche';
                    $this->payInstallement($this->input->post('idR'), $this->input->post('idU'));
                }else{
                    $this->data['message'] = 'Désolé le montant de la tranche à payer est supérieur a la somme restante attendue';
                    $this->payInstallement($this->input->post('idR'), $this->input->post('idU'));
                }
            }else{
                if(empty($this->input->post())){
                    redirect("admin/registration");
                }else{
                    $this->data['user'] = $this->registration->regList($this->input->post('idR'))[0];
                    $this->render('admin/registration/payInstallement', 'Paie d\'une tranche');
                }
            }
        }
        else{
            redirect("admin/registration");
        }

    }

    public function unShelve($idR=false, $idU=false){
        $this->data['user'] = $this->registration->regList($idR);
        if(empty($this->data['user'])){
            set_flash_data(array("error",'l\'Utilisateur n\'existe pas.'));
            redirect('admin/registration');
        }else{
            $this->data['mode'] = 1;
            $this->data['user'] = $this->data['user'][0];
            //var_dump($this->data); die(0);
            $this->render('admin/registration/payInstallement', 'Paie d\'une tranche');
        }
    }

    public function qrValidation()
    {
        $this->render('admin/registration/qrCodeValidation', 'Validation de l\'incription : code QR');
    }

    public function qrValidationModal()
    {
        //echo $_POST['code'];
        if (isset($_POST['code'])) {
            $code=$_POST['code'];
            $infos = $this->registration->getRegistration($code);
            if (is_object($infos)) {
                $result = 'Désirez-vous valider cette inscription ?<br><br><form method="post" id="qrRegForm" action="'.base_url('registration/payInstallement').'">

                    <table class="table-fill">
                        <tr>
                            <td  class="text-left th">
                                <label class=""><b>Code d\'inscription : </b> </label>
                            </td>
                            <td class="text-left">
                                <p class="form-control-static">'.$infos->reg_code.'</p>
                            </td>
                        </tr>

                        <tr>
                            <td  class="text-left th">
                                <label class=""><b>Enseignement : </b> </label>
                            </td>
                            <td class="text-left">
                                <p class="form-control-static">'.mb_strtoupper($infos->label).'</p>
                            </td>
                        </tr>

                        <tr>
                            <td class="text-left th">
                                <label class=""><b>Nom et prénoms de l\'apprenant : </b> </label>
                            </td>
                            <td class="text-left">
                                <p class="form-control-static">'.$infos->lastname.' '.$infos->firstname.'</p>
                            </td>
                        </tr>

                        <tr>
                            <td class="text-left th">
                                <label class=""><b>Date d\'inscription : </b> </label>
                            </td>
                            <td class="text-left">
                                <p class="form-control-static">'.date('d-m-Y', strtotime($infos->reg_date)).'</p>
                            </td>
                        </tr>

                        <tr>
                            <td class="text-left th">
                                <label class=""><b>&Eacute;tat de l\'inscription : </b></label>
                            </td>
                            <td class="text-left">
                                <p class="form-control-static">';
                                    switch ($infos->reg_state)
                                    {
                                        case '0': $result.="En attente"; break;
                                        case '-1': $result.="Suspendue"; break;
                                        case '1': $result.="Validée"; break;
                                        case '2': $result.="Finalisée"; break;
                                    }

                                $result.='</p>
                            </td>
                        </tr>
                    </table>';
                $result.='<input type="hidden" id="code" name="code" value="'.$infos->reg_code.'" />
                    <input type="hidden" id="idR" name="idR" value="'.$infos->regId.'" />
                    <input type="hidden" id="idU" name="idU" value="'.$infos->idU.'" />
                    <input type="hidden" name="mode" id="mat" value="'.$infos->reg_state.'" />
                    </form>';


                echo $result;
            } else
            {
                echo $infos;
            }
        }
    }

}