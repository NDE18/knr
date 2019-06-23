<?php

/**
 * Created by PhpStorm.
 * User: Harrys Crosswell
 * Date: 17/08/2017
 * Time: 13:02
 */
class Examination extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        $this->load->model('backfront/examination_model', 'examination');
        $this->load->model('backfront/promotion_model', 'promotion');
        $this->load->model('backfront/lesson_model', 'lesson');
        $this->load->model('backfront/lessonA_model', 'lessonA');

        if(in_array(TRAINER, (array)session_data('roles'))){
            set_session_data(array('role'=>TRAINER));
        };
        protected_session(array('','account/login'),array(TRAINER));

        $this->data['trainerProfil'] = $this->lesson->getTrainerLesson(session_data('id'));
        if(empty($this->data['trainerProfil'])){
            $this->data['trainerProfil'] = null;
        }
    }

    public function index()
    {
        $this->results();
    }

    public function planning($route="")
    {
        if ($route=="")
        {
            redirect('trainerGate/examination/planning/all');
        } else if ($route=='all')
        {
            $this->data['plannings']=$this->examination->plannings();
            $this->renderGate('planning-list', 'Liste des plannings d\'examen');
        } else show_404();
    }

    public function results($promo="", $exa="", $print="")
    {
        if ($promo=="") {
            //$this->data['promotions']=$this->promotion->all();
            $promotions=$this->promotion->getAllocatedPromo(session_data('id'));
            $prs=array();
            if($promotions!=null){
                foreach ($promotions as $pr)
                {
                    $partial=new stdClass();
                    $partial->promotion=$pr;
                    $partial->results=$this->examination->getResults($pr->id);
                    //var_dump($partial->results);
                    if(count($partial->results)==count($this->examination->getPEvals($pr->id)) and count($partial->results)>0)
                    {
                        $all=new stdClass();
                        $all->label='Toutes les évaluations';
                        $all->code='all';
                        array_push($partial->results, $all);
                    }
                    array_push($prs, $partial);
                }
            }
            //var_dump($prs); die();
            $this->data['promotions']=$prs;
            $this->renderGate('trainer/select-promotion-result', 'Choix de la vague');
        } else if ($this->promotion->get($promo)!=null) {
            if ($exa=="all")
            {
            	$p = $this->promotion->getInfo($promo);
            	$this->data['session']=$this->examination->getMarkPublishDate($p->promotion->id);
                if ($print=="") {
                    $this->data['promotion']=$this->promotion->getInfo($promo);
                    $e=$this->data['evals']=$this->examination->getEvaluations($promo);
                    
                    $this->data['marks']=$this->getResults($promo);
                    $this->data['all']=true;
                    $this->renderGate('trainer/results', 'Résultats d\'examens de la vague '.$this->data['promotion']->promotion->code);
                } else if ($print=="print") {
                    $this->data['promotion']=$this->promotion->getInfo($promo);
                    $this->data['evals']=$this->examination->getEvaluations($promo);
                    $this->data['marks']=$this->getResults($promo);
                    $this->data['all']=true;
                    $this->printResult($promo);
                } else show_404();
            } else if ($this->examination->evaExist($promo, $exa))
            {
                if ($print=="") {
                    $this->data['all']=false;
                    $this->data['promotion']=$this->promotion->getInfo($promo);
                    $lesson = $this->data['promotion']->lesson;
                    $e=$this->data['evals']=$this->examination->getEvaluation($promo, $this->examination->getExaId($lesson->id,$exa));
                    
                    $this->data['marks']=$this->getResults($promo, $this->examination->getExaId($lesson->id,$exa));
                    $this->renderGate('trainer/results', 'Résultats d\'examens de la vague '.$this->data['promotion']->promotion->code);
                } else if ($print=="print") {
                    $this->data['all']=false;
                    $this->data['promotion']=$this->promotion->getInfo($promo);
                    $lesson = $this->data['promotion']->lesson;
                    $this->data['evals']=$this->examination->getEvaluation($promo, $this->examination->getExaId($lesson->id,$exa));
                    $this->data['marks']=$this->getResults($promo, $this->examination->getExaId($lesson->id,$exa));
                    $this->printResult($promo);
                } else show_404();
            } else show_404();
        } else  show_404();
    }

    public function promotions($promo='', $ev='')
    {
        if ($promo=='')
        {
            $this->data['promotions']=$this->promotion->getAllocatedPromo(session_data('id'), '1');
            $this->renderGate('trainer/select-promotion', 'Choix de la vague');
        } else if (!empty($this->promotion->get($promo))) {
            $pr=$this->promotion->getInfo($promo);
            $promotion=$pr->promotion;
            $lesson=$pr->lesson;
            if($ev=='') {
                $this->data['evaluations'] = $this->examination->getAll($promotion->lesson);
                $this->data['promo'] = $promo;
                $this->renderGate('trainer/select-evaluation', 'Choix de l\'évaluation');
            } else
            {
                $evaluation=$this->data['evaluations'] = $this->examination->getAll($promotion->lesson);
                if ($ev!='all')
                {
                    $found=0;
                    $pub=false;
                    $eval="";

                    foreach ($evaluation as $eva)
                    {
                        if ($this->permalink($eva->code)==$ev){
                            $found++;

                            $eval=$eva;
                            if ($this->examination->published($eva->id,$promotion->id))
                                $pub=true;
                        }
                    }
                    if ($found==1)
                    {
                        $this->data['eval'] = $ev;
                        $this->data['all']=false;
                        $this->data['pub']=$pub;
                        $marked=$this->examination->isMarked($eval->id, $promotion->id);
                        $this->data['marked']=$marked;
                        if ($marked==true) $m=$this->data['marks']=$this->getResults($promotion->code, intval($eval->id));
                        //var_dump($m);die();
                        $this->data['promo']=$promotion;
                        $this->data['mode']=$marked==true?'update':'add';
                        $this->data['lesson']=$lesson;
                        $this->data['students']=$this->promotion->getStudents($promotion->id);
                        $this->data['evaluation']=$eval;
                        $this->renderGate('trainer/form-marks', 'Enregistrement des notes');
                    } else show_404();
                } else if($ev=='all')
                {
                    $this->data['eval'] = $ev;
                    $this->data['all']=true;
                    $this->data['promo']=$promotion;
                    $this->data['lesson']=$lesson;
                    $this->data['students']=$this->promotion->getStudents($promotion->id);
                    $evaluations= $this->examination->getAll($promotion->lesson);
                    $marked=0;
                    $pub=false;
                    foreach ($evaluations as $eval) {
                        if ($this->examination->isMarked($eval->id, $promotion->id))
                            $marked++;
                        if ($this->examination->published($eval->id,$promotion->id))
                            $pub=true;
                    }
                    $this->data['pub']=$pub;
                    $this->data['marked']=($marked>0?true:false);
                    $this->data['mode']=($marked>0?'update':'add');
                    if ($marked>0)
                        $this->data['marks']=$this->getResults($promotion->code);
                    $this->data['evaluations'] = $evaluations;
                    $this->renderGate('trainer/form-marks', 'Enregistrement des notes');
                } else show_404();
            }
        } else show_404();
    }

    public function save()
    {
        //var_dump($_POST); die();
        $promo=intval($_POST['promo']);
        $marks=json_decode($_POST['all']);
        //var_dump($marks,$_POST); die();
        $mode=$_POST['mode'];
        //var_dump($marks, $mode); die();
        $evals=$this->permalink($_POST['evals']);
        //var_dump($evals); die();
        if ($mode=='add') {
            $status = $this->examination->save($promo, $marks);
            if ($status) {
                $promotion = $this->promotion->get($promo);
                /*$promoNbrEvals=count($this->examination->getDistinctEvaluations($promotion->id));
                $nbrEvals=count($this->examination->get($promotion->id));
                if ($promoNbrEvals==$nbrEvals)
                    $this->examination->setReady($promotion->id);*/
                $this->logM->save(array(
                    "motivation" => "",
                    "author" => session_data('id'),
                    "date" => date('Y-m-d H:i:s'),
                    "action" => "Enregistrement des notes pour la vague " . $promotion->code . "."
                ));
                $this->notification->publish(array(
                    "sender" => session_data('id'),
                    "content" => "Des résultats d'evaluations de la vague " . $promotion->code . " ont été enregistrés.",
                    "send_date" => date('Y-m-d H:i:s'),
                    "target" => ADMIN,
                    "promotion" => "",
                    "url" => ""
                ));
                redirect('trainerGate/examination/results/' . $promotion->code .'/'. $evals);
            }
        } else if ($mode=='update')
        {
        	
            $status = $this->examination->update($promo, $marks);
            
            if ($status) {
                $promotion = $this->promotion->get($promo);
                /*$promoNbrEvals=count($this->examination->getDistinctEvaluations($promotion->id));
                $nbrEvals=count($this->examination->get($promotion->id));
                //var_dump($promoNbrEvals, $nbrEvals); die();
                if ($promoNbrEvals==$nbrEvals)
                    $this->examination->setReady($promotion->id);*/
                $this->logM->save(array(
                    "motivation" => "",
                    "author" => session_data('id'),
                    "date" => date('Y-m-d H:i:s'),
                    "action" => "Modification des notes pour la vague " . $promotion->code . "."
                ));
                $this->notification->publish(array(
                    "sender" => session_data('id'),
                    "content" => "Des résultats d'evaluations de la vague " . $promotion->code . " ont été modifiés.",
                    "send_date" => date('Y-m-d H:i:s'),
                    "target" => ADMIN,
                    "promotion" => "",
                    "url" => ""
                ));
                redirect('trainerGate/examination/results/' . $promotion->code .'/'. $evals);
            }
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
                if ($notes!=null and !empty($notes))
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
                if($percent!=0)
	                $final=$final/$percent;
                $stdMark->final = castNumberId($final, 2, 2);
                $stdMark->dec = $this->decision($final);
                $stdMark->app = $this->appreciate($final);
                array_push($result, $stdMark);
            }
            //echo "<pre>";var_dump($result);echo "</pre>";
            return $result;
        } else {
            $promotion = $this->promotion->getInfo($promo);
            $students = $this->promotion->getStudents($promotion->promotion->id);
            $result = array();
            $nbr = 0;
            foreach ($students as $std) {
                $stdMark = new stdClass();
                $stdMark->number = ++$nbr;
                $stdMark->id = $std->id;
                $stdMark->number_id = $std->number_id;
                $stdMark->names = mb_strtoupper($std->lastname) . " " . ucwords($std->firstname);
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

                $stdMark->note->evaluation= $evaluation;
                array_push($result, $stdMark);
            }
            return $result;
        }
    }

    private function printResult($promotion)
    {
        $this->load->helper("html2pdf_helper");
        $content = $this->load->view('backfront/trainer/result-print', $this->data, true);
        //echo $content; die();
        try{
            $pdf = new HTML2PDF('L', 'A4', 'fr');
            $pdf->pdf->setDisplayMode('fullpage');
            $pdf->writeHTML($content);
            ob_end_clean();
            if ($this->data['all'])
                $pdf->Output('Results-Promotion-'.$promotion.'-Final-Results.pdf');
            else
                $pdf->Output('Results-Promotion-'.$promotion.'-'.$this->permalink($this->data['evals'][0]->label).'.pdf');
        }catch (HTML2PDF_exception $e){
            die($e);
        }
    }
}