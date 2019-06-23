<?php
define('MAX', 7);
define('MIN', 1);
define('MVAGUE', 10);
define('MJOUR', 6);
define('MPLAGE', 5);
define('PROG', 3);

class Examination extends CI_Controller
{
    protected  $data, $menu;

    function __construct()
    {
        parent::__construct();

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));
        $this->load->model('admin/examination_model', 'examination');
        $this->load->model('admin/promotion_model', 'promotion');
        $this->load->model('admin/log_model', 'logM');
        $this->load->model('admin/notification_model', 'notification');
        $this->load->model('admin/lesson_model', 'lesson');
        $this->load->model('admin/document_model', 'document');
        $this->load->helper('general_helper');
        $this->load->model('admin/notification_model', 'notif');

        $this->menu['notif'] = $this->notif->newNotif();
    }

    public function index()
    {
        $this->results();
    }

    public function planning($route="", $week="")
    {
        //var_dump(getWeek(date('Y-m-d'), 'Y-m-d')->end); die();
        $this->load->model('admin/session_model', 'session');
        if (in_array($route, array('generate', 'all', '')))
        {
            if ($route=="")
            {
                redirect('admin/examination/planning/all');
            } else if ($route=='generate') {
                if ($week == "") {
                    if (isset($_POST) and !empty($_POST)) {
                        $dt=explode('/', $_POST['start']);
                        $dat=$dt[2].'-'.$dt[1].'-'.$dt[0];
                        $date=$this->getWeek(date('Y-m-d', strtotime($dat)));
                        //var_dump($date); die();
                        $post=array(
                            "document"=>intval($_POST['prog']),
                            "week"=>$dat
                        );
                        //var_dump($post); die();
                        if ($this->examination->generate($post))
                        {

                            $this->db->trans_start();
                            $this->logM->save(array(
                                "motivation"=>"",
                                "author"=>session_data('id'),
                                "date"=>moment()->format('Y-m-d H:i:s'),
                                "action"=>"Publication du planning d'examen de la semaine du ".$date['debut']."."
                            ));

                            $this->notification->publish(array(
                                "sender"=>session_data('id'),
                                "content"=>"Le planning d'examen de la semaine du lundi ".$date['debut']." au samedi ".$date['fin']." est disponible.",
                                "send_date"=>moment()->format('Y-m-d H:i:s'),
                                "target"=>TRAINER,
                                "promotion"=>"",
                                "url"=>"trainerGate/examination/plannings"
                            ));

                            $this->notification->publish(array(
                                "sender"=>session_data('id'),
                                "content"=>"Le planning d'examen de la semaine du lundi ".$date['debut']." au samedi ".$date['fin']." est disponible.",
                                "send_date"=>moment()->format('Y-m-d H:i:s'),
                                "target"=>ADMIN,
                                "promotion"=>"",
                                "url"=>"admin/examination/planning/all"
                            ));

                            $promotions=$this->promotion->get(false, '1');
                            foreach ($promotions as $promo)
                            {
                                $this->notification->publish(array(
                                    "sender"=>session_data('id'),
                                    "content"=>"Le planning d'examen de la semaine du lundi ".$date['debut']." au samedi ".$date['fin']." est disponible.",
                                    "send_date"=>moment()->format('Y-m-d H:i:s'),
                                    "target"=>STUDENT,
                                    "promotion"=>$promo->id,
                                    "url"=>"studentGate/examination/plannings"
                                ));
                            }
                            $this->db->trans_complete();
                            set_flash_data(array("success", "Le planning d'examen de la semaine du ".$date['debut']." au ".$date['fin']." a bien été enregistré." ));
                            redirect('admin/examination/planning/all');
                        } else
                        {
                            set_flash_data(array("error", "La publication du planning d'examen de la semaine du ".$date['debut']." au ".$date['fin']." a échoué." ));
                            redirect('admin/examination/planning/all');
                        }
                    } else
                    {
                        $this->data['docs']=$this->document->getAll(false, 'post_date', 'DESC');
                        $this->render('admin/examination/generate', 'Générer le planning d\'un examen');
                    }
                } else {
                    show_404();
                }
            } else
            {
                $this->data['plannings']=$this->examination->plannings();
                $this->render('admin/examination/planning-list', 'Liste des plannings d\'examen');
            }
        } else show_404();
    }

    public function publish($promotion=null, $eval=null)
    {
        $promo=$this->promotion->get($promotion);
        if ($promo!=null)
        {
            if($eval!=null){
                if ($this->examination->evaExist($promotion, $eval)){
                    if ($this->examination->publish($promo->id, $eval))
                    {
                        //$this->db->trans_start();
                        $this->logM->save(array(
                            "motivation"=>"",
                            "author"=>session_data('id'),
                            "date"=>date('Y-m-d H:i:s'),
                            "action"=>"Publication des résultats de la vague ".$promotion
                        ));

                        $this->notification->publish(array(
                            "sender"=>session_data('id'),
                            "content"=>"Les résultats de la vague $promotion sont disponibles.",
                            "send_date"=>date('Y-m-d H:i:s'),
                            "target"=>ADMIN,
                            "promotion"=>"",
                            "url"=>"admin/examination/results/$promotion"
                        ));

                        $this->notification->publish(array(
                            "sender"=>session_data('id'),
                            "content"=>"Les résultats de la vague $promotion sont disponibles.",
                            "send_date"=>date('Y-m-d H:i:s'),
                            "target"=>MANAGER,
                            "promotion"=>"",
                            "url"=>"admin/examination/results/$promotion"
                        ));

                        $this->notification->publish(array(
                            "sender"=>session_data('id'),
                            "content"=>"Les résultats de la vague $promotion sont disponibles.",
                            "send_date"=>date('Y-m-d H:i:s'),
                            "target"=>TRAINER,
                            "promotion"=>"",
                            "url"=>"trainerGate/examination/results/$promotion"
                        ));

                        $this->notification->publish(array(
                            "sender"=>session_data('id'),
                            "content"=>"Les résultats de la vague $promotion sont disponibles.",
                            "send_date"=>date('Y-m-d H:i:s'),
                            "target"=>STUDENT,
                            "promotion"=>$promo->id,
                            "url"=>"studentGate/examination/results/$promotion"
                        ));

                        set_flash_data(array("success", "Les résultats de la vague $promotion ont bien été publiés."));
                        redirect('admin/examination/results');
                    } else
                    {
                        set_flash_data(array("error", "Les résultats de la vague $promotion n'ont pas été publiés."));
                        redirect('admin/examination/results');
                    }
                } else show_404();
            }
            else{
                if ($this->examination->publish($promo->id))
                {
                    //$this->db->trans_start();
                    $this->logM->save(array(
                        "motivation"=>"",
                        "author"=>session_data('id'),
                        "date"=>date('Y-m-d H:i:s'),
                        "action"=>"Publication des résultats de la vague ".$promotion
                    ));

                    $this->notification->publish(array(
                        "sender"=>session_data('id'),
                        "content"=>"Les résultats de la vague $promotion sont disponibles.",
                        "send_date"=>date('Y-m-d H:i:s'),
                        "target"=>ADMIN,
                        "promotion"=>"",
                        "url"=>"admin/examination/results/$promotion"
                    ));

                    $this->notification->publish(array(
                        "sender"=>session_data('id'),
                        "content"=>"Les résultats de la vague $promotion sont disponibles.",
                        "send_date"=>date('Y-m-d H:i:s'),
                        "target"=>MANAGER,
                        "promotion"=>"",
                        "url"=>"admin/examination/results/$promotion"
                    ));

                    $this->notification->publish(array(
                        "sender"=>session_data('id'),
                        "content"=>"Les résultats de la vague $promotion sont disponibles.",
                        "send_date"=>date('Y-m-d H:i:s'),
                        "target"=>TRAINER,
                        "promotion"=>"",
                        "url"=>"trainerGate/examination/results/$promotion"
                    ));

                    $this->notification->publish(array(
                        "sender"=>session_data('id'),
                        "content"=>"Les résultats de la vague $promotion sont disponibles.",
                        "send_date"=>date('Y-m-d H:i:s'),
                        "target"=>STUDENT,
                        "promotion"=>$promo->id,
                        "url"=>"studentGate/examination/results/$promotion"
                    ));

                    set_flash_data(array("success", "Les résultats de la vague $promotion ont bien été publiés."));
                    redirect('admin/examination/results');
                } else
                {
                    set_flash_data(array("error", "Les résultats de la vague $promotion n'ont pas été publiés."));
                    redirect('admin/examination/results');
                }
            }

        } else show_404();
    }

    public function results($promo="", $exa="", $print="")
    {
        if ($promo=="") {
            $promotion=$this->examination->getPFromEvals();
            
            $res=array();
            if ($promotion!=null)
                foreach ($promotion as $pr)
                {
                    $partial=new stdClass();
                    $partial->promotion=$pr;
                    $partial->results=$this->examination->getResults($pr->id);
                    //var_dump($partial->results);
                    $partial->allMarked=false;
                    if (count($partial->results)==count($this->examination->getPEvals($pr->id)) and count($partial->results)>0)
                    {
                        $partial->allMarked=true;
                    }
                    //ar_dump($partial->results);
                    $pub=count($this->examination->publishedEvals($pr->id));
                    //var_dump($pub);
                    $partial->allPub=false;
                    if (count($partial->results)==$pub and count($partial->results)>0)
                    {
                        $partial->allPub=true;
                    }
                    array_push($res, $partial);
                }
            //die();
            $this->data['results']=$res;
            //var_dump($res); die();
            $this->render('admin/examination/list-', 'Choix de la vague');
        } else if ($this->promotion->get($promo)!=null) {
            if ($exa=="all") {
            	$p = $this->promotion->getInfo($promo);
                if ($print == "") {
                    $this->data['all']=true;
                    $this->data['promotion'] = $this->promotion->getInfo($promo);
                    $ev=$this->data['evals'] = $this->examination->getEvaluations($promo);
                    $ii=$this->data['marks'] = $this->getResults($promo);
                    /*echo "<pre>";
                    var_dump($ev,$ii);die;
			echo "</pre>";*/

                    $this->data['session']=$this->examination->getMarkPublishDate($p->promotion->id);
                    $this->render('admin/examination/results', 'Résultats d\'examens de la vague ' . $this->data['promotion']->promotion->code);
                } else if ($print == "print") {
                    $this->data['all']=true;
                    $this->data['promotion'] = $this->promotion->getInfo($promo);
                    $this->data['evals'] = $this->examination->getEvaluations($promo);
                    $this->data['marks'] = $this->getResults($promo);
                    $this->data['session']=$this->examination->getMarkPublishDate($p->promotion->id);
                    $this->printResult($promo);
                } else show_404();
            } else if ($this->examination->evaExist($promo, $exa))
            {
                //var_dump($promo); die();
                if ($print=="") {
                    $this->data['all']=false;
                    $this->data['promotion']=$this->promotion->getInfo($promo);
                    $lesson = $this->data['promotion']->lesson;
                    //var_dump($exa,$promo,$lesson->id,$this->examination->getExaId($lesson->id,$exa) );
                    $eva=$this->examination->getEvaluation($promo, $this->examination->getExaId($lesson->id, $exa));
                    //var_dump($eva); die();
                    $this->data['evals']=$eva;
                    $this->data['marks']=$this->getResults($promo, $this->examination->getExaId($lesson->id, $exa));
                    //var_dump($this->data['marks']); die();
                    $this->render('admin/examination/results', 'Résultats d\'examens de la vague '.$this->data['promotion']->promotion->code);
                } else if ($print=="print") {
                    $this->data['all']=false;
                    $this->data['promotion']=$this->promotion->getInfo($promo);
                    $lesson = $this->data['promotion']->lesson;
                    $this->data['evals']=$this->examination->getEvaluation($promo, $this->examination->getExaId($lesson->id, $exa));
                    $this->data['marks']=$this->getResults($promo, $this->examination->getExaId($lesson->id, $exa));
                    $this->printResult($promo);
                } else show_404();
            } else show_404();
        } else show_404();
    }

    public function decision($note)
    {
        return $note<12?'R':'A';
    }

    public function appreciate($note)
    {
        if ($note==0) return 'Null';
        else if ($note<7) return 'Faible';
        else if ($note<10) return 'Insuffisant';
        else if ($note<12) return 'Passable';
        else if ($note<14) return 'Assez bien';
        else if ($note<16) return 'Bien';
        else if ($note<18) return 'Très bien';
        else return 'Excellent';
    }

    private function getResults($promo, $evaluation=0)
    {
        if ($evaluation==0) {
            $promotion = $this->promotion->getInfo($promo);
            $students = $this->promotion->getStudents($promotion->promotion->id);
            //var_dump($students);die();
            $result = array();
            $nbr = 0;
            foreach ($students as $std) {
                $stdMark = new stdClass();
                $stdMark->number = ++$nbr;
                $stdMark->id = $std->id;
                $stdMark->number_id = $std->number_id;
                $stdMark->names = mb_strtoupper($std->lastname) . " " . ucwords($std->firstname);
                $stdMark->notes = array();
                $id = intval((int)$std->id);
                $notes = $this->examination->getMarks($id, $promo);
                $final = 0;
                $percent=0;
                if ($notes!=null)
                    foreach ($notes as $note) {
                        $mark=new stdClass();
                        if ($note!=null)
                        {
                            $mark->value= castNumberId($note->value, 2, 2);
                        } else
                            $mark->value= '';
                        $mark->evaluation=$note->evaluation;
                        array_push($stdMark->notes, $mark);
                        $percent+=$note->percent;
                        $final += floatval(($note->value * $note->percent) / 100);
                    }
                $percent=$percent/100;
                $final=$final/$percent;
                $stdMark->final = castNumberId($final, 2, 2);
                $stdMark->dec = $this->decision($final);
                $stdMark->app = $this->appreciate($final);
                array_push($result, $stdMark);
            }
            return $result;
        } else {
            $promotion = $this->promotion->getInfo($promo);
            $students = $this->promotion->getStudents($promotion->promotion->id);
            //var_dump($students);die();
            $result = array();
            $nbr = 0;
            foreach ($students as $std) {
                $stdMark = new stdClass();
                $stdMark->number = ++$nbr;
                $stdMark->id = $std->id;
                $stdMark->number_id = $std->number_id;
                $stdMark->names = mb_strtoupper($std->lastname) . " " . ucwords($std->firstname);
                $stdMark->notes = array();
                $id = intval((int)$std->id);
                $note = $this->examination->getMark($id, $promo, $evaluation);
                $stdMark->note=new stdClass();
                if ($note!=null)
                {
                    $stdMark->note->value = castNumberId($note->value, 2, 2);
                    $stdMark->app = $this->appreciate(floatval($stdMark->note->value));
                    $stdMark->dec = $this->decision(floatval($stdMark->note->value));
                } else
                {
                    $stdMark->note->value = '';
                    $stdMark->app='Aucune';
                    $stdMark->dec='Aucune';
                }

                $stdMark->note->evaluation= $note!=null? $note->evaluation: '';
                array_push($result, $stdMark);
            }
            return $result;
        }
    }

    private function printResult($promotion)
    {
        $this->load->helper("html2pdf_helper");
        $content = $this->load->view('admin/examination/result-print', $this->data, true);
        //echo $content; die();
        try{
            $pdf = new HTML2PDF('L', 'A4', 'fr');
            $pdf->pdf->setDisplayMode('fullpage');
            $pdf->writeHTML($content);
            ob_end_clean();
            if ($this->data['all'])
                $pdf->Output('Results-Promotion-'.$promotion.'-Final-Results.pdf');
            else
                $pdf->Output('Results-Promotion-'.$promotion.'-'.permalink($this->data['evals'][0]->label).'.pdf');
        }catch (HTML2PDF_exception $e){
            die($e);
        }
    }

    public function getWeek($dayDate)
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

    private function render($view, $titre = NULL)
    {
        $this->load->view('admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('admin/menu', $this->menu);
        $this->load->view($view, $this->data);
        $this->load->view('admin/footerAdmin');
    }
}

