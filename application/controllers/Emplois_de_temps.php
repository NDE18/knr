<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Emplois_de_temps extends CI_Controller
{

    protected $data, $menu;

    function __construct()
    {
        parent::__construct();

        $this->load->model('backfront/user_model', 'userM');
        $this->load->model('backfront/timetable_model', 'timetable');
        $this->load->model('backfront/availability_model');
        $this->load->model('public/lesson_model', 'mLesson');

        $this->data['cLesson'] = $this->mLesson->getByType('cours')->result();
        $this->data['fLesson'] = $this->mLesson->getByType('filière')->result();
        $this->data['pLesson'] = $this->mLesson->getByType('promotion')->result();
        $this->load->model('public/events_model', 'mEvent');
        $this->data['lastEvent'] = $this->mEvent->get(null,0,3);

        $this->load->model('public/flash_model', 'mFlash');
        $this->data['infosFlash'] = $this->mFlash->get(null,0,3);
        updateVisit();
    }

    public function index(){
        $timetables=$this->timetable->all();
        $week=array();
        foreach ($timetables as $tb)
            array_push($week, $this->getWeek($tb->start_date));
        $this->data['timetables']=$week;
        $this->data['today']=$this->getWeek(date('Y-m-d'));

        $this->meta = array(
            "description"=>"Consultez et téléchargez les emplois de temps des cours à MULTISOFT ACADEMY",
            "url"=>base_url('emploi-de-temps'),
//            "image"=>img_url('logo/logo.png')
            "image"=>img_url('edt.png')
        );

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Tous les emplois de temps"=>"",
        );

        $this->render('Les emplois de temps à MULTISOFT');
    }

    /**
     * @param $dayDate
     * @return array
     */
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

    public function planning($timetableStartDate=null, $print=false)
    {
    	if($timetableStartDate ==null) 
    		redirect("emplois-de-temps");
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
                msa_error("Aucun emploi de temps disponible pour cette semaine");
            }
            $this->meta = array(
                "description"=>"Consultez et télécharger l'emploi de temps de la semaine du $timetableStartDate",
                "url"=>base_url('emploi-de-temps/planning'.'/'.$timetableStartDate),
                //            "image"=>img_url('logo/logo.png')
            "image"=>img_url('edt.png')
            );

            $this->data ['breadcrumb'] = array(
                "Accueil" => base_url(),
                "Tous les emplois de temps"=>base_url('emplois-de-temps'),
                "Semaine du $timetableStartDate"=>"",
            );
            $this->render("Emploi de temps de la semaine du $timetableStartDate", 'single');
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

            $content = $this->load->view('public/timetable/timetable-pdf', $this->data, true);

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
        $this->load->view("public/timetable-page-model", $this->data);
        $this->load->view('public/footer');
    }
}