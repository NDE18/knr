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
        $this->load->model('backfront/message_model', 'mMessage');
        $this->load->library('form_validation');
        $this->load->model('backfront/registration_model', 'registration');


        if(in_array(STUDENT, (array)session_data('roles'))){
            set_session_data(array('role'=>STUDENT));
        };
        protected_session(array('','account/login'),array(STUDENT));
        $this->data['acadProfil'] = $this->registration->getLesson(session_data('id'));
    }

    public function index()
    {
         $planning = $this->getWeek(date('Y-m-d'))['debut'];
        $planning = explode('/', $planning);
        $planning = $planning[2].'-'.$planning[1].'-'.$planning[0];
        //var_dump($planning); die(0);
        $this->data['requetes'] = $this->mMessage->getRequets(intval(session_data('id')));
        if(empty($this->data['acadProfil'])){
            $this->data['acadProfil'] = null;
            //$this->data['planning'] = $this->planning('2017-07-24');
            $this->data['planning'] = $this->planning($planning);
            $this->renderGate('student/home', 'Accueil');
        }else{
            $pending = array(); $somH = array();
            $i = 0; $code = array();
            foreach ($this->data['acadProfil'] as $lesson) {
                //var_dump($lesson);
                $pending[$i] = $this->timetable->lessonProgression($lesson->promId);
                $somH[$i] = $pending[$i]->sumDuration;
                $code[$i] = $this->promotion->getPromotionById($lesson->promId);
                $code[$i] = (empty($code[$i]) ? ' ' : $code[$i][0]->code);
                $pending[$i] = ((int)$pending[$i]->sumDuration * 100) / (int)$lesson->duration;
                $i++;
            }//die(0);
            $this->data['somH'] = $somH;
            //$this->data['code'] = $code;
            //$this->data['planning'] = $this->planning('2017-07-24');
            $this->data['planning'] = $this->planning($planning);
            $this->planning($planning);
            $this->data['pending'] = $pending;
            $this->renderGate('student/home', 'Accueil');
        }
    }

    public function profil(){
        $id = session_data("id");
        $user = array();
        $user = $this->userM->getUser((int)$id);

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
        $this->renderGate("student/profile", "Profil de l'apprenant  ".session_data('firstname').' '.session_data('lastname'));

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
        $this->form_validation->set_rules('npwd', '"Nouveau mot de passe"', 'trim|min_length[8]|max_length[128]|encode_php_tags');

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
                redirect('studentGate/home/profil');
            }
            else
            {
                $this->data['status']=false;
                $this->data['message'] = '<b>Echec de modification :</b> '.$postOk;
                //redirect('admin/home/profile/');
            }
        }
        $this->renderGate('student/form-profile-edit', 'Modifier le profil');
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
            } else
            {
                return -1;
            }
        } else {
            $this->printTimeTable($timetableStartDate);
        }
    }


}