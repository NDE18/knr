<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home  extends Ci_Controller
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
        $this->load->model('admin/admin_model', 'admin');
        $this->load->model('admin/lesson_model', 'lesson');
        $this->load->helper('general_helper');
        $this->load->model('admin/notification_model', 'notif');
        $this->load->model('admin/registration_model', 'registration');


        $this->menu['notif'] = $this->notif->newNotif();
    }

    private function render($view, $titre = NULL)
    {
        $this->load->view('admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('admin/menu', $this->menu);
        $this->load->view($view, $this->data);
        $this->load->view('admin/footerAdmin');
    }

    public function index()
    {
        $this->load->model('admin/promotion_model', 'promotion');
        $this->data['notif'] = count($this->menu['notif']);
        $this->data['inscrit'] = count($this->admin->getInscrit());
        $mentions=$this->lesson->getL(false, "filière");
        $courses=$this->lesson->getL(false, "cours");
        $chart3=new stdClass();
        $chart3->opened=count($this->promotion->get());
        $chart3->suspended=count($this->promotion->get(false, '-1'));
        $chart3->finished=count($this->promotion->get(false, '2'));
        $chart3->pending=count($this->promotion->get(false, '1'));
        $this->data['ch3']=$chart3;
        $chart1=array();
        $chart5 = array();
        //var_dump($courses); die();

        if ($mentions)
            foreach ($mentions as $m)
            {
                $chart=new stdClass();
                $chart->code=$m->code;
                $chart->label=$m->label;
                $chart->reg=$this->registration->getRegNumber($m->id);
                array_push($chart1, $chart);
            }
        if ($courses)
            foreach ($courses as $c)
            {
                $ch=new stdClass();
                $ch->code=$c->code;
                $ch->label=$c->label;
                $ch->reg=$this->registration->getRegNumber($c->id);
                array_push($chart5, $ch);
            }


        $max_ch1=0;
        $max_ch2=0;
        $avg1=0;
        $avg2=0;
        if (!empty($chart1))
            foreach ($chart1 as $ch)
            {
                $avg1+=$ch->reg;
                if ($ch->reg>$max_ch1)
                    $max_ch1=$ch->reg;
            }
        if (!empty($chart5))
            foreach ($chart5 as $ch)
            {
                $avg2+=$ch->reg;
                if ($ch->reg>$max_ch2)
                    $max_ch2=$ch->reg;
            }
        $this->data['ch1']=$chart1;
        $this->data['ch5']=$chart5;
        $this->data['max_ch1']=$max_ch1;
        $this->data['max_ch2']=$max_ch2;

        $this->data['avg1']=count($chart1)>0?$avg1/count($chart1):0;
        $this->data['avg2']=count($chart5)>0?$avg2/count($chart5):0;
        //var_dump($this->data); die();
        $now=intval(date('Y'));
        $chart2=array();
        for ($i=$now; $i>$now-5; $i--)
        {
            $y=$now-(4-($now-$i));
            $tYear=new stdClass();
            $tYear->name="".$y;
            $tYear->months=array();
            for ($j=1; $j<=12; $j++)
            {
                $stat=new stdClass();
                $stat->month=$j;
                $stat->value=$this->registration->getPerDate($y, $j);
                array_push($tYear->months, $stat);
            }
            array_push($chart2, $tYear);
        }
        $this->data['ch2']=$chart2;

        /**
         *
         * Information pour la progression des enseignements
         */
        $pending = array(); $somH = array();
        $i = 0;
        $code = array();
        $this->data['acadProfil'] = $this->registration->getLesson();
        //var_dump($this->data['acadProfil']);
        foreach ($this->data['acadProfil'] as $lesson) {
            //var_dump($lesson);
            $pending[$i] = $this->promotion->lessonProgression($lesson->promId);
            $somH[$i] = $pending[$i]->sumDuration;
            $code[$i] = $this->promotion->get($lesson->promId);
            $code[$i] = (empty($code[$i]) ? ' ' : $code[$i][0]->code);
            $pending[$i] = ((int)$pending[$i]->sumDuration * 100) / (int)$lesson->duration;
            $i++;
        }//die(0);
        $this->data['somH'] = $somH;
        $this->data['pending'] = $pending;

        $this->render('admin/index', 'Acceuil');
    }

    /**
     * Afficher le profil de l'administrateur connecté et modification de l'image de profil
     */
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
        $this->render("admin/home/profile", "Profil de l'administrateur");
        
    }

    /**
     * @param null $id
     * somussion du formulaiire de modification des informations utilisateurs
     */
    public function editProfile()
    {
        $idUser = session_data("id");
        $user = $this->admin->getAdmin($idUser);
        if (!empty($user->result()[0])) {
            $this->data['user'] = $user->result()[0];
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
                'pwd'=>$this->input->post('pwd'),
                'npwd'=>$this->input->post('npwd')
            );
            $postOk = $this->admin->modify($post);
            if(is_bool($postOk) And $postOk)
            {
                $this->data['status']=true;
                set_flash_data(array('success','Profil modifié avec succès!'));
                redirect('admin/home/profile');
            }
            else
            {
                $this->data['status']=false;
                $this->data['message'] = '<b>Echec de modification :</b> '.$postOk;
                //redirect('admin/home/profile/');
            }
        }
        $this->render('admin/home/form-profile-edit', 'Modifier le profil');
    }
}