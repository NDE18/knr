<?php

class Timetable extends MY_Controller
{

    function __construct()
    {
        parent::__construct();

        if(in_array(STUDENT, (array)session_data('roles'))){
            set_session_data(array('role'=>STUDENT));
        };
        protected_session(array('','account/login'),array(STUDENT));

        $this->load->model('backfront/timetable_model', 'timetable');
        $this->load->model('backfront/registration_model', 'registration');
        $this->load->model('backfront/availability_model');
        $this->load->model('backfront/lesson_model', 'lesson');

        $this->data['userProfil'] = $this->userM->getUser((int)session_data('id'));
        $this->data['acadProfil'] = $this->registration->getLesson(session_data('id'));
        if(empty($this->data['acadProfil'])){
            $this->data['acadProfil'] = null;
        }
    }

    public function index()
    {
        $this->all();
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

    private function checkAvailability($user = false){
        $start_date = $this->getWeek(date_add(date_create(date('Y-m-d')),date_interval_create_from_date_string("7 days"))->format('Y-m-d'))['debut'];
        //var_dump($start_date);die();
        $start_date = explode('/', $start_date);

        $start_date = $start_date[2].'/'.$start_date[1].'/'.$start_date[0];
        $verif = $this->timetable->checkAvailability($user, $start_date);
        if(is_array($verif) and !empty($verif)){
            return false;
        }else{
            return true;
        }
    }

    public function availability()
    {
        $user = session_data('id');
        $period = $this->timetable->getPeriod();
        $verif  = $this->checkAvailability($user);
        if($verif == true){
            if(empty($period)){
                set_flash_data(array('warning', 'Cette page est momentanément indisponible!'));
                redirect('studentGate/timetable');
            }else{
                if($this->input->post('send')) {
                    for ($i = 0; $i < count($period); $i++){
                        for ($j = 0; $j < 6; $j++) {
                            $key = $i.'_'.$j.'_'.$period[$i]->id;
                            $keys = $key;
                            $key = explode('_', $key);
                            switch($key[1]){
                                case '0': $day = 'lundi'; break;
                                case '1': $day = 'mardi'; break;
                                case '2': $day = 'mercredi'; break;
                                case '3': $day = 'jeudi'; break;
                                case '4': $day = 'vendredi'; break;
                                case '5': $day = 'samedi'; break;
                                default : $day = '-1';
                            }
                            if($this->input->post($keys) == 'on'){
                                //$value = 'on';
                                $available =  1;
                                $start_date = $this->getWeek(date_add(date_create(date('Y-m-d')),date_interval_create_from_date_string("7 days"))->format('Y-m-d'))['debut'];
                                $start_date = explode('/', $start_date);
                                $start_date = $start_date[2].'/'.$start_date[1].'/'.$start_date[0];
                                $field = array('user'=>$user,
                                    'day'=>$day,
                                    'available'=>(string)$available,
                                    'start_date'=>$start_date,
                                    'period'=>$key[2]);
                                $this->timetable->saveAvailability($field);

                            }else{
                                //$value = '';
                                $available = 0;
                                $start_date = $this->getWeek(date_add(date_create(date('Y-m-d')),date_interval_create_from_date_string("7 days"))->format('Y-m-d'))['debut'];
                                $start_date = explode('/', $start_date);
                                $start_date = $start_date[2].'/'.$start_date[1].'/'.$start_date[0];
                                $field = array('user'=>$user,
                                    'day'=>$day,
                                    'available'=>(string)$available,
                                    'start_date'=>$start_date,
                                    'period'=>$key[2]);
                                $this->timetable->saveAvailability($field);
                            }
                        }//$i++;
                    }
                    set_flash_data(array('success', 'Votre disponibilité de la semaine a bien été soumise'));
                    $this->data['period'] = $period;
                    redirect('studentGate/timetable/availability');
                }else{
                    $this->data['period'] = $period;
                    $this->renderGate('student/avaibility-form', 'Soumettez votre disponibilté');
                }
            }
        }else{
            $this->data['message'] = "Désolé vous ne pouvez soumettre votre disponibilité qu'une fois par semaine. Par contre vous avez la possibilité de <a class='btn btn-primary' href='".base_url('studentGate/timetable/updateAvailability')."'>la modifier</a>.";
            $this->data['period'] = $period;
            $this->renderGate('student/avaibility-form', 'Soumettre une disponibilté');
        }
    }

    public function updateAvailability(){
        $user = session_data('id');
        $period = $this->timetable->getPeriod(); //var_dump($period); die();

        if($this->input->post('send')){
            $verif  = $this->checkAvailability($user);
            if($verif == true){
                //set_flash_data(array('info', 'Vous avez déja eu a soumettre votre disponibilité de la semaine, vous avez la possibilité de la modifier si besoin'));
                redirect('studentGate/student/availability');
            }else {
                $start_date = $this->getWeek(date_add(date_create(date('Y-m-d')),date_interval_create_from_date_string("7 days"))->format('Y-m-d'))['debut'];
                $start_date = explode('/', $start_date);
                $start_date = $start_date[2] . '/' . $start_date[1] . '/' . $start_date[0];


                $availability = $this->timetable->getAvailability($user,$start_date); $k = 0; //var_dump($availability); die(0);
                for ($i = 0; $i < count($period); $i++) {
                    for ($j = 0; $j < 6; $j++) {
                        $key = $i . '_' . $j . '_' . $period[$i]->id;
                        $keys = $key;

                        $key = explode('_', $key);
                        switch ($key[1]) {
                            case '0':
                                $day = 'lundi';
                                break;
                            case '1':
                                $day = 'mardi';
                                break;
                            case '2':
                                $day = 'mercredi';
                                break;
                            case '3':
                                $day = 'jeudi';
                                break;
                            case '4':
                                $day = 'vendredi';
                                break;
                            case '5':
                                $day = 'samedi';
                                break;
                            default :
                                $day = '-1';
                        }
                        $k++;
                        $available = ($this->input->post($keys) == 'on') ? 1 : 0;
                        $field = array('user' => $user,
                            'day' => $day,
                            'available' => (string)$available,
                            'start_date' => $start_date,
                            'period' => $key[2]);
                        $this->timetable->updateAvailability($field, $availability[$k-1]->id);

                    }
                }
                redirect('studentGate/timetable/updateAvailability');

            }
        }
        else {
            $period = $this->timetable->getPeriod();
            if (empty($period)) {
                $this->data['message'] = 'Impossible de soumettre les disponibilité, Veuillez contacter le directeur SVP!!!';
                $this->renderGate('student/avaibility-form', 'Modifiez votre disponibilité');
            } else {
                $start_date = $this->getWeek(date_add(date_create(date('Y-m-d')),date_interval_create_from_date_string("7 days"))->format('Y-m-d'))['debut'];
                $start_date = explode('/', $start_date);
                $start_date = $start_date[2] . '/' . $start_date[1] . '/' . $start_date[0];
                $availability = $this->timetable->getAvailability($user,$start_date);
                if (empty($availability)) {
                    $this->data['message'] = 'Désolé, vous ne pouvez modifier de disponibilité sans les avoir soumis';
                    $this->renderGate('student/avaibility-form', 'Modifiez votre disponibilité');
                } else {
                    $this->data['availability'] = $availability;
                    $this->data['period'] = $period;
                    $this->renderGate('student/avaibility-form', 'Modifiez votre disponibilité');
                }
            }
        }

    }

    public function all()
    {
        $timetables=$this->timetable->all();
        $week=array();
        foreach ($timetables as $tb)
            array_push($week, $this->getWeek($tb->start_date));
        $this->data['timetables']=$week;
        $this->data['today']=$this->getWeek(date('Y-m-d'));
        $this->renderGate('timetables', 'Emplois du temps');
    }

    public function planning($timetableStartDate, $print=false)
    {
        if ($print==false or $print!="print")
        {
            $table=$this->timetable->getTimetable($timetableStartDate);
            if($table!=null)
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
                $this->data['status']=false;
            }
            $this->renderGate('timetable', 'Emploi du temps');
        } else {
            $this->printTimeTable($timetableStartDate);
        }

    }

    private function printTimeTable($timetableStartDate)
    {
        $this->load->helper("html2pdf_helper");
        $table=$this->timetable->getTimetable($timetableStartDate);
        $promotion = array();
        if($table!=null)
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
                                    if(!in_array($av->getCode($prom), $promotion))
                                    {
                                        $promotion[count($promotion)] = $av->getCode($prom);
                                    }
                                    $contentTb.='<br><b>'.$av->getCode($prom).'</b><br> (<em>'.mb_strtoupper($av->getLesson($prom)).'</em>)<br>----';
                                }

                                $timetable[$period->start.':00 - '.$period->end.':00'][$i]['content'] = $contentTb;

                                $found=1;
                            }
                        } else
                        {
                        }
                    }
                    if ($found==0)
                    {
                        $timetable[$period->start.':00 - '.$period->end.':00'][$i]=false;
                    }
                }
            }
            $this->data['promotion'] = '';
            $i = 0; $end = count($promotion);
            foreach ($promotion as $item) {
                $i++;
                $this->data['promotion'] .= ($i == $end)? mb_strtoupper($item) : mb_strtoupper($item).' - ';
            }
            $this->data['timetable']=$timetable;
            $this->data['week']=$this->getWeek($timetableStartDate);

            $content = $this->load->view('backfront/timetable-print', $this->data, true);

            try{
                $pdf = new HTML2PDF('L', 'A4', 'fr');
                $pdf->pdf->setDisplayMode('fullpage');
                $pdf->writeHTML($content);
                ob_end_clean();
                $pdf->Output('Timetable'.$timetableStartDate.'.pdf');
            }catch (HTML2PDF_exception $e){
                die($e);
            }
        } else
        {

        }
    }
}