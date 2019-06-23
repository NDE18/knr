<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Trainer extends CI_Controller
{
    protected $data, $menu;
    public function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));

        $this->load->library('form_validation');
        $this->load->model('admin/trainer_model', 'trainer');
        $this->load->model('admin/log_model', 'logM');
        $this->load->helper('general_helper');
    }

    public function index()
    {
        $this->all();
    }

    public function formAdd()
    {
        if(session_data('role')!=ADMIN)
            msa_error("Dédolé! Vous n'avez pas les droits suffisants pour effectuer cette action. ");
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
                'question'=>'none',
                'answer'=>'none',
                'login'=>$this->input->post('mail'),
                'pwd'=>randomPassword(14),
                'register_date'=>'',
                'state'=>'0',
                'number_id'=>'',
                'sexe'=>($this->input->post('genre') == '1') ? 1 : 0
            );
            $postOk = $this->trainer->save($post);
            if(is_bool($postOk) And $postOk)
            {
                set_flash_data(array("success","Le formateur a bien été enregistré."));
                redirect("admin/trainer");
            }
            else
            {
                $this->data['status']=false;
                $this->data['message'] = '<b>Echec d\'enregistrement:</b> '.$postOk;
                $this->render('admin/trainer/form-add', 'Enregistrer un formateur');
            }
        }
        else
        {
            $this->render('admin/trainer/form-add', 'Enregistrer un formateur');
        }
    }

    public function all()
    {
        $query = $this->trainer->getAll()->result();
        $this->data['query']=$query;
        $this->render('admin/trainer/list', 'Liste des formateurs');
    }

    public function allocation($id=null)
    {
        $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
        $this->form_validation->set_rules('lesson', '"Enseignement"', 'trim|required');
        $this->form_validation->set_rules('id', '"Formateur"', 'trim|required');
        $this->form_validation->set_rules('allDate', '"Date d\'allocation"', 'required');

        if($this->form_validation->run()) {
            $this->data['id']=$this->input->post('id');
            $post_array = array(
                'user'=>$this->input->post('id'),
                'lesson'=>$this->input->post('lesson'),
                'start_date'=>date('Y-m-d', strtotime($this->input->post('allDate'))),
            );

            $postOk = $this->trainer->allocation($post_array);

            if(is_bool($postOk) And $postOk)
            {
                set_flash_data(array("success","L 'allocation s 'est bien déroulée"));
                redirect("admin/trainer");
            }
            else
            {
                $this->data['status']=false;
                $this->data['message'] = '<b>Echec d\'allocation:</b> '.$postOk;
                $this->load->model('admin/lesson_model', 'lesson');
                $this->data['allLess']=$this->lesson->retrieveLessons();
                if($this->data['id']!=null)
                    $this->data['user']=$this->trainer->getUser($this->data['id'])->result()[0];
                else
                    $this->data['users']=$this->trainer->getAll("lastname")->result();
                $this->render('admin/trainer/allocation', 'Allocation à un enseignement');
            }
        }
        else
        {
            $this->data['id']=$id;
            $this->load->model('admin/lesson_model', 'lesson');
            $this->data['allLess']=$this->lesson->retrieveLessons();
            if(!empty($id))
                $this->data['user']=$this->trainer->getUser($this->data['id'])->result()[0];
            else
                $this->data['users']=$this->trainer->getUsers("lastname")->result();
            $this->render('admin/trainer/allocation', 'Allocation à un enseignement');
        }
    }

    public function addTrainer()
    {
        $this->render('admin/trainer/addTrainer', 'Ajout d\'un formateur');
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

    public function profile($id = false, $print = false){
        if(isset($id) and $print != 'print'){

            if($_FILES and !empty($_FILES)){
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
                    }
                    else
                    {
                        if($this->trainer->savePhoto('assets/uploads' . explode('assets/uploads', $this->upload->data()['full_path'])[1], $id)){
                            $this->data['imgName'] = $this->upload->data()['file_name'];
                            $this->data['message'] = 'La Photo a été modifié';
                        }
                    }
                }
            }

            $trainer = $this->trainer->getTrainer($id)->result()[0];
            $dateCon = date_create($trainer->last_connexion);
            $dateReg = date_create($trainer->register_date);
            $dateBirth = date_create($trainer->birth_date);

            $this->data['trainer'] = $trainer;
            $this->data['dateCon'] = $dateCon;
            $this->data['dateReg'] = $dateReg;
            $this->data['dateBirth'] = $dateBirth;

            $lesson = $this->trainer->getLessonSlip($id);
            if(!empty($lesson))
                $this->data['lesson'] = $lesson;

            $this->render("admin/trainer/profile", "Profil Professionnel de ".strtoupper($trainer->lastname).' '.ucfirst($trainer->firstname));

        }elseif(isset($id) and isset($print) and $print == 'print'){
            $this->load->helper("html2pdf_helper");
            $trainer = $this->trainer->getTrainer($id)->result()[0];
            $this->data['student'] = $trainer;
            $dateCon = date_create($trainer->last_connexion);
            $dateReg = date_create($trainer->register_date);
            $dateBirth = date_format($dateReg, 'd').'/'.date_format($dateReg, 'm').'/'.date_format($dateReg, 'Y');
            $dateReg = 'Année :'.date_format($dateReg, 'Y').'   Mois :'.date_format($dateReg, 'm').'   Jour :'.date_format($dateReg, 'd');
            $dateCon = 'Année :'.date_format($dateCon, 'Y').'   Mois :'.date_format($dateCon, 'm').'   Jour :'.date_format($dateCon, 'd');
            $this->data['dateCon'] = $dateCon; $this->data['dateReg'] = $dateReg; $this->data['dateBirth'] = $dateBirth;

            $lesson = $this->trainer->getLessonSlip($id);
            $profil = '';

            if(!empty($lesson))
                $this->data['lesson'] = $lesson;

            $content = $this->load->view('admin/trainer/pdf-profil', $this->data, TRUE);

            try{
                $pdf = new HTML2PDF('P', 'A4', 'fr');
                $pdf->pdf->setDisplayMode('fullpage');
                $pdf->writeHTML($content);
                ob_end_clean();
                $pdf->Output('Profil-'.$trainer->number_id.'.pdf');
            }catch (HTML2PDF_exception $e){
                die($e);
            }
        }
    }

    public function log($id=false){
        $this->load->model('admin/trainer_model', 'trainer');
        $log = $this->trainer->log($id);
        $this->data['log'] = $log;
        $this->render('admin/trainer/log', 'Liste des logs');
    }

    public function lessons($idF=false){
        if($idF){
            $user= $this->trainer->getTrainer($idF)->result();
            if(!empty($user)){
                $user = $user[0];
                $lessonDispense = $this->trainer->getLessonSlip($idF);
                if(!empty($lessonDispense)){
                    $this->data['trainer'] = $user;
                    $this->data['lessonDispense'] = $lessonDispense;
                    $this->render('admin/trainer/lessons-list', 'Liste des enseignements dipensés par '.$user->firstname.' '.$user->lastname);
                }else{
                    set_flash_data(array("success",'Aucune lesson pour ce formateur '));
                    redirect("admin/trainer");
                }
            }
        }else{
            redirect("admin/trainer");
        }
    }

    public function shelve($idLa=false){
        if($idLa){
            $this->load->model('admin/lesson_model', 'lesson');
            $trainer = $this->trainer->getTrainer(false, $idLa);
            $lesson = $this->lesson->getLessonByLessonAll($idLa);
            if(is_array($trainer) and is_array($lesson)){
                $shelve = $this->trainer->shelve($idLa);
                if($shelve){
                    $this->load->model('admin/log_model', 'logs');
                    $log = $this->logs->save(array('motivation'=>'Suspention d\'allocation, ID = '.$idLa, 'author'=>session_data('id'), 'date'=>date('Y-m-d'), 'action'=>'suspension de '.$trainer[0]->firstname.' '.$trainer[0]->lastname.' pour l\'enseignement '.$lesson[0]->label.' code : '.$lesson[0]->code));
                    if($log){
                        set_flash_data(array("success",'Le formateur '.$trainer[0]->lastname.' '.$trainer[0]->firstname.' a été suspendu pour l\'enseignement '.$lesson[0]->label.' code : '.$lesson[0]->code));
                        redirect("admin/trainer/lessons/".$trainer[0]->id);
                    }
                }else{
                    $this->lessons($trainer[0]->id);
                }
            }else{
                $this->lessons(false);
            }
        }else{
            $this->lessons(false);
        }
    }

    public function unshelve($idLa=false){
        if($idLa){
            $this->load->model('admin/lesson_model', 'lesson');
            $trainer = $this->trainer->getTrainer(false, $idLa); //var_dump($trainer); die(0);
            $lesson = $this->lesson->getLessonByLessonAll($idLa); //var_dump($lesson); die(0);
            if(is_array($trainer) and is_array($lesson)){
                $shelve = $this->trainer->unshelve($idLa); //var_dump($shelve); die(0);
                if($shelve){
                    $this->load->model('admin/log_model', 'logs');
                    $log = $this->logs->save(array('motivation'=>'Allocation, ID = '.$idLa, 'author'=>session_data('id'), 'date'=>date('Y-m-d'), 'action'=>'Mise en service de '.$trainer[0]->firstname.' '.$trainer[0]->lastname.' pour l\'enseignemet'.$lesson[0]->label.' code : '.$lesson[0]->code));
                    if($log){
                        set_flash_data(array("success",'Le formateur '.$trainer[0]->lastname.' '.$trainer[0]->firstname.' a été alloué pour l\'enseignement '.$lesson[0]->label.' code : '.$lesson[0]->code));
                        redirect("admin/trainer/lessons/".$trainer[0]->id);
                    }
                }else{
                    $this->lessons($trainer[0]->id);
                }
            }else{
                $this->lessons(false);
            }
        }else{
            $this->lessons(false);
        }
    }

    public function motivation($mode = false, $idUr = false){
        if(($mode=="shelve" or $mode == "unshelve") and $idUr){
            $trainer = $this->trainer->getTrainerF(false, $idUr);
            if(is_array($trainer)){
                $this->data['name'] = $trainer[0]->firstname.' '.$trainer[0]->lastname;
                $this->data['mode'] = $mode;
                $this->data['idUr'] = $idUr;
                //$this->data['mode'] = $mode;
                $this->render('admin/trainer/motivation', 'Enregistrer la motivation');
            }else{
                redirect('admin/trainer/all');
            }
        }
        elseif($mode == false or $idUr == false){
            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('motivation', '"Motivation"', 'trim|required|min_length[3]|max_length[512]|alpha_numeric_spaces|encode_php_tags');

            if($this->form_validation->run()){

                if($this->input->post('mode') == 'shelve'){

                    $motivation = $this->input->post('motivation');
                    $this->shelveF($this->input->post('idUr'), $motivation);
                }elseif($this->input->post('mode') == 'unshelve'){
                    $motivation = $this->input->post('motivation');
                    $this->unshelveF($this->input->post('idUr'), $motivation);
                }else{
                    redirect('admin/trainer');
                }
            }else{
                $trainer = $this->trainer->getTrainerF(false, $this->input->post('idUr')); //var_dump($trainer); die(0);
                if(is_array($trainer)){
                    $this->data['name'] = $trainer[0]->firstname.' '.$trainer[0]->lastname;
                    $this->data['mode'] = $mode;
                    $this->data['idUr'] = $idUr;
                    $this->render('admin/trainer/motivation', 'Enregistrer la motivation');
                }else{
                    redirect('admin/trainer');
                }
            }
        }else{
            redirect('admin/trainer');
        }
    }

    public function shelveF($idUr=false, $motivation = false){
        if($idUr){
            $trainer = $this->trainer->getTrainerF(false, $idUr);

            if(is_array($trainer)){
                $shelve = $this->trainer->shelveF($idUr);
                if($shelve){
                    $this->load->model('admin/log_model', 'logs');
                    $log = $this->logs->save(array('motivation'=>$motivation, 'author'=>session_data('id'), 'date'=>date('Y-m-d'), 'action'=>'suspension de '.$trainer[0]->firstname.' '.$trainer[0]->lastname));
                    if($log){
                        //--------------
                        set_flash_data(array("success","Le gérant '.$trainer[0]->lastname.' '.$trainer[0]->firstname.' viens d\'être suspendu. Motif: << '.$this->input->post('motivation'").' >>');
                        redirect("admin/trainer");
                    }
                }else{
                    redirect('admin/trainer/all');
                }
            }else{
                redirect('admin/trainer/all');
            }

        }else{
            redirect('admin/trainer/all');
        }
    }

    public function unshelveF($idUr=false, $motivation = false){
        //var_dump($idUr); die(0);
        if($idUr){
            $this->load->model('admin/lesson_model', 'lesson');
            $trainer = $this->trainer->getTrainerF(false, $idUr);
            if(is_array($trainer)){
                $shelve = $this->trainer->unshelveF($idUr);
                if($shelve){
                    $this->load->model('admin/log_model', 'logs');
                    $log = $this->logs->save(array('motivation'=>$motivation, 'author'=>session_data('id'), 'date'=>date('Y-m-d'), 'action'=>'Mise en service de '.$trainer[0]->firstname.' '.$trainer[0]->lastname));
                    if($log){
                        set_flash_data(array("success","Le gérant '.$trainer[0]->lastname.' '.$trainer[0]->firstname.' viens d\'être réactivé pour '.$this->input->post('motivation'"));
                        redirect("admin/trainer");
                    }
                }else{
                    redirect('admin/trainer/all');
                }
            }else{
                redirect('admin/trainer/all');
            }
        }else{
            redirect('admin/trainer/all');
        }
    }

    public function allLessonSlip(){
       $this->load->model('admin/availability_model');
        $lessonDispense = $this->trainer->lessonSlip();
        $trainerLessonsDispense = array();

        $av = new Availability_model();

        foreach($lessonDispense as $l){
            $proms = explode('#',$l->promotion);
            array_pop($proms);
            array_shift($proms);
            $obj = new stdClass();
            $obj = $l;
            $obj->label='';
            foreach($proms as $prom){
                $obj->label .= $av->getLesson($prom)."<br> ";
            }

            $trainerLessonsDispense[] = $obj;
        }

        if(empty($lessonDispense)){
            $this->render('admin/trainer/LessonSlip-list', 'Fiche de suivis des formateurs');
        }else{
            $this->data['lessonDispense'] = $trainerLessonsDispense;
            $this->render('admin/trainer/LessonSlip-list', 'Fiche de suivis des formateurs');
        }
    }

    public function lessonSlip($code=null){
        if($code==null){
            set_flash_data(array('error',"Aucun code de session entré"));
        }
        else{
        }
    }
    
    public function edit($sess=null)
    {
        $this->load->model('admin/session_model', 'session');
        $this->load->model('admin/period_model', 'period');
        $this->load->model('admin/promotion_model', 'promotion');
        //var_dump($_POST); die();
        if ($sess==null)
        {
            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('content', '"Contenu"', 'trim|required|min_length[2]|encode_php_tags');

            if($this->form_validation->run())
            {
                //var_dump($_POST); die();
                $trainer=intval($_POST['trainer']);
                $session=intval($_POST['session']);
                $content=$_POST['content'];
                $sess=$this->session->get($session);
                $this->trainer->update($session, $content);
                $this->session->update($trainer, $session);
                $this->logM->save(array(
                    "motivation"=>"",
                    "author"=>session_data('id'),
                    "date"=>moment()->format('Y-m-d H:i:s'),
                    "action"=>"Modification de la fiche de suivi N° $sess->code."
                ));
                set_flash_data(array('success', "La fiche de suivi N° $sess->code a bien été modifiée."));
                redirect('admin/trainer/allLessonSlip');
            } else
            {
                redirect("admin/trainer/edit/$session");
            }
        } else
        {
            $session=$this->session->get(intval($sess));

            $proms = explode('#',$session->promotion);
            array_pop($proms);
            array_shift($proms);
            $av = new Availability_model();

            $lesson = "";
            foreach($proms as $prom){
                $lesson .= $av->getLesson($prom)."<br>----<br>";
            }
            /*echo "<pre>";
            var_dump($lesson);
            echo "</pre>";
            die();*/
            //var_dump($promotion); die();
            $day="";
            switch ($session->day)
            {
                case 1: $day="Lundi"; break;
                case 2: $day="Mardi"; break;
                case 3: $day="Mercredi"; break;
                case 4: $day="Jeudi"; break;
                case 5: $day="Vendredi"; break;
                case 6: $day="Samedi"; break;
                default: $day="Aucun"; break;
            }
            $p=$this->period->get($session->period);
            $period=$day.", $session->start_date, de $p->start H:00 à $p->end H:00";
            $this->data['trainers']=$this->trainer->getAll()->result();
            //var_dump($this->data['trainers']); die();
            $this->data['lesson']=$lesson;
            $this->data['period']=$period;
            $this->data['session']=intval($sess);
            $this->render('admin/trainer/lesson-slip-edit', 'Editer une fiche de suivi');
        }
    }





}