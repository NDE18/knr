<?php

class Home extends MY_Controller
{

    function __construct()
    {

        parent::__construct();
        $this->load->model('backfront/timetable_model', 'timetable');
        $this->load->model('backfront/notifications_model', 'notif');
        $this->load->model('backfront/lesson_model', 'lesson');
        $this->load->model('backfront/registration_model', 'registration');
        $this->load->model('backfront/timetable_model', 'timetable');
        $this->load->model('backfront/availability_model', 'availability');
        $this->load->model('backfront/promotion_model', 'promotion');
        $this->load->library('form_validation');


        if(in_array(TRAINER, (array)session_data('roles'))){
            set_session_data(array('role'=>TRAINER));
        };
        protected_session(array('','account/login'),array(TRAINER));

        $hourDispense = $this->userM->hourDispense(session_data('id'));
        $hourDispense = empty($hourDispense) ? 0 : $hourDispense[0]->duration;
        $this->data['hourDispense'] = $hourDispense;
    }

    public function index()
    {
        $planning = $this->getWeek(date('Y-m-d'))['debut'];
        $planning = explode('/', $planning);
        $planning = $planning[2].'-'.$planning[1].'-'.$planning[0];
        $this->data['planning'] = $this->planning($planning);

        $this->renderGate('trainer/home', 'Accueil');
    }

    private function getWeek($dayDate)
    {
        $date = explode("-", $dayDate);

        $time = strtotime($date[0].'-'.$date[1].'-'.$date[2]);

        $day = date("w", "$time");
        $jourdeb=0;
        $jourfin=0;

        switch ($day) {
            case "0":
                $jourdeb = mktime(0,0,0,$date[1],$date[2]-6,$date[0]);
                $jourfin = mktime(0,0,0,$date[1],$date[2],$date[0]);
                break;

            case "1":
                $jourdeb = mktime(0,0,0,$date[1],$date[2],$date[0]);
                $jourfin = mktime(0,0,0,$date[1],$date[2]+6,$date[0]);
                break;

            case "2":
                $jourdeb = mktime(0,0,0,$date[1],$date[2]-1,$date[0]);
                $jourfin = mktime(0,0,0,$date[1],$date[2]+5,$date[0]);
                break;

            case "3":
                $jourdeb = mktime(0,0,0,$date[1],$date[2]-2,$date[0]);
                $jourfin = mktime(0,0,0,$date[1],$date[2]+4,$date[0]);
                break;

            case "4":
                $jourdeb = mktime(0,0,0,$date[1],$date[2]-3,$date[0]);
                $jourfin = mktime(0,0,0,$date[1],$date[2]+3,$date[0]);
                break;

            case "5":
                $jourdeb = mktime(0,0,0,$date[1],$date[2]-4,$date[0]);
                $jourfin = mktime(0,0,0,$date[1],$date[2]+2,$date[0]);
                break;

            case "6":
                $jourdeb = mktime(0,0,0,$date[1],$date[2]-5,$date[0]);
                $jourfin = mktime(0,0,0,$date[1],$date[2]+1,$date[0]);
                break;
        }
        $date=date_create(date('d-m-Y', $jourfin));
        date_sub($date,date_interval_create_from_date_string("1 days"));

        $week=array('debut'=>date('d/m/Y',$jourdeb), 'fin'=>date_format($date, 'd/m/Y'));
        return $week;
    }

    public function profil(){
        $id = session_data("id");
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
        $this->data['dateCon'] = $dateCon;
        $this->data['dateReg'] = $dateReg;
        $this->data['dateBirth'] = $dateBirth;
        $this->renderGate("trainer/profile", "Profil du formateur  ".session_data('firstname').' '.session_data('lastname'));

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
        $this->form_validation->set_rules('level', '"Niveau"', 'trim|required|encode_php_tags');
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
                redirect('trainerGate/home/profil');
            }
            else
            {
                $this->data['status']=false;
                $this->data['message'] = '<b>Echec de modification :</b> '.$postOk;
                //redirect('admin/home/profile/');
            }
        }
        $this->renderGate('trainer/form-profile-edit', 'Modifier le profil');
    }

    public function formNotifAdd(){
        $this->data['vague'] = $this->registration->getVagues();
        if ($this->input->post('send')) {
            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('pub_vague', '"Vague"', 'trim|required|max_length[15]|encode_php_tags');
            $this->form_validation->set_rules('destination', '"Destinataire"', 'trim|required|min_length[2]|max_length[512]|encode_php_tags');
            if($this->form_validation->run()){
                //var_dump($this->input->post());
                $field = array('sender'=>session_data('id'),
                    'content'=>$this->input->post('destination'),
                    'target'=>STUDENT,
                    'promotion'=>$this->input->post('pub_vague'));
                //var_dump($field); die(0);
                //$res = $this->notif->publish($field); //var_dump($res); die(0);
                if($this->notif->publish($field)){
                    $code = $this->promotion->getPromotionById($this->input->post('pub_vague'));
                    $code = (empty($code) ? ' ' : $code[0]->code);
                    $this->data['message'] = 'Votre message a bien été envoyé à la vague :'.$code;
                    $this->renderGate('trainer/form-model-add', 'Enregistrer un model de notification');
                }
            }else{
                $this->renderGate('trainer/form-model-add', 'Envoyer un message');
            }
        }else{
            $this->renderGate('trainer/form-model-add', 'Envoyer un message');
        }

    }

    public function giveLessonDispense($mode=false){
        if($mode and $mode == 'slip'){
            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('lessonInfo', '"Contenu"', 'trim|required|encode_php_tags');
            if($this->form_validation->run()) {
                //var_dump($this->input->post()); die(0);
                $resu =  $this->timetable->updateSession($this->input->post('codeSession'), $this->input->post('lessonInfo'));
                //var_dump($resu); die(0);
                if($resu == true){
                    $this->data['message'] = 'Merci d\'avoir notifié votre présence';
                    $this->renderGate('trainer/lessonDispense', 'Indication du cours dispensé');
                }else{
                    $sessions = $this->timetable->selectSessions($this->input->post('codeSession'), $this->getWeek(date('Y-m-d'))['debut']);
                    if(empty($sessions)){
                        var_dump($sessions);
                    }else{
                        $lesson = $this->timetable->getLessonByPromotion($sessions[0]->promotion);
                        if($lesson == null){
                            $this->data['message'] = 'Désolé, cette session est incohérente, veuillez contacter l\'administrateur si le problème persiste';
                            $this->renderGate('trainer/lessonDispense', 'Indication du cours dispensé');
                        }else{
                            $this->data['message'] = 'Désolé, cette session est bloquée, veuillez contacter l\'administrateur si le problème persiste';
                            $this->renderGate('trainer/lessonDispense', 'Indication du cours dispensé');
                        }
                    }
                }
            }else{
                $sessions = $this->timetable->selectSessions($this->input->post('codeSession'), $this->getWeek(date('Y-m-d'))['debut']);
                //var_dump($sessions);
                if(empty($sessions)){
                    var_dump($sessions);
                }else{
                    $lesson = $this->timetable->getLessonByPromotion($sessions[0]->promotion);
                    if($lesson == null){
                        $this->data['message'] = 'Désolé, cette sesion est incohérente, veuillez contacter l\'administrateur si le problème persiste';
                        $this->renderGate('trainer/lessonDispense', 'Indication du cours dispensé');
                    }else{

                        $this->data['codeSession'] = $this->input->post('codecodeSession');
                        $this->data['lesson'] = $lesson;
                        $this->renderGate('trainer/lessonDispense', 'Indication du cours dispensé');
                    }
                }
            }

        }
        else{
            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('code', '"Code"', 'trim|required|encode_php_tags');
            //var_dump($this->input->post()); die(0);

            if($this->form_validation->run()){
                $sessions = $this->timetable->selectSessions($this->input->post('code'), $this->getWeek(date('Y-m-d'))['debut']);
                //$this->vardump($sessions); die(0);
                if($sessions == null){
                    $this->data['message'] = 'Désolé, la session N°'.$this->input->post('code').' n\'est pas accessible';
                    $this->renderGate('trainer/lessonDispense', 'Indication du cours dispensé');
                }else{
                    $lessons = $this->timetable->getLessonByPromotion($sessions[0]->promotion);
                    if($lessons == null){
                        $this->data['message'] = 'Désolé, la session demandée semble incohérente.';
                        $this->renderGate('trainer/lessonDispense', 'Indication du cours dispensé');
                    }else{
                        $this->data['codeSession'] = $this->input->post('code');
                        $this->data['lesson'] = $lessons;
                        $this->renderGate('trainer/lessonDispense', 'Indication du cours dispensé');
                    }
                }
            }else{
                $this->renderGate('trainer/lessonDispense', 'Indication du cours dispensé');
            }
        }

    }

    public function planning($timetableStartDate, $print=false)
    {
        if ($print==false or $print!="print")
        {
            $table=$this->timetable->getTimetable($timetableStartDate);

            if($table)
            {
                $av = new Availability_model();
                $timetable=array();
                $periods=$av->getPeriods();
                foreach ($periods as $period)
                {
                    $timetable[$period->start.':00 - '.$period->end.':00']=array();
                    for ($i=1; $i<=6; $i++)
                    {
                        $found=0;
                        foreach ($table as $tb)
                        {
                            if ($tb->day==$i)
                            {
                                if($tb->period==$period->id)
                                {
                                    $proms = explode('#',$tb->promotion);
                                    array_pop($proms);
                                    array_shift($proms);
                                    $contentTb = (count($proms)>1)?"Vagues<br>":"Vague<br>";
                                    foreach($proms as $prom){
                                        $contentTb.='<br><b>'.$av->getCode($prom).'</b><br> (<em>'.mb_strtoupper($av->getLesson($prom)).'</em>)<br>----';
                                    }
                                    $timetable[$period->start.':00 - '.$period->end.':00'][$i]=$contentTb;

                                    $found=1;
                                }
                            } else
                            {
                            }
                        }
                        if ($found==0)
                        {
                            $timetable[$period->start.':00 - '.$period->end.':00'][$i]="Libre";
                        }
                    }
                }
                $this->data['timetable']=$timetable;
                $this->data['timetableStartDate']=$timetableStartDate;
                $this->data['forID']=$timetableStartDate;
                $this->data['week']=$this->getWeek($timetableStartDate);
                $this->data['status']=true;
                return $this->data;
            } else
            {
                return -1;
                //return 'Aucun emploi de temps disponible pour cette semaine';
                //msa_error("Aucun emploi de temps disponible pour cette semaine");
            }

        } else {
            $this->printTimeTable($timetableStartDate);
        }
    }

    public function lessonSlip(){
        $lessonSlip = $this->userM->lessonSlip();
        foreach($lessonSlip as $ls){
            $ls->label ="";
            $proms = explode('#',$ls->promotion);
            array_pop($proms);
            array_shift($proms);
            foreach($proms as $prom)
            {
                $ls->label .= $this->promotion->get((int)$prom)->label." - ";
            }
            
        }
        //var_dump($lessonSlip);
        if(empty($lessonSlip)){
            $this->renderGate('trainer/lessonSlip-list', 'Liste des fiches de suivie');
        }else{
            $this->data['lessonDispense'] = $lessonSlip;
            $this->renderGate('trainer/lessonSlip-list', 'Liste des fiches de suivies');
        }
    }
    
     public function lessons(){
        $lessons = $this->userM->lessons();
        $this->data['lessonDispense'] = $lessons;
        $this->renderGate('trainer/lessonList',"Liste de mes enseignements");
    }


}