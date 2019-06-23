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
        $this->load->model('backfront/registration_model', 'registration');
        $this->load->model('backfront/lessonA_model', 'lessonA');
        $this->load->helper('general_helper');

        if(in_array(STUDENT, (array)session_data('roles'))){
            set_session_data(array('role'=>STUDENT));
        };

        protected_session(array('','account/login'),array(STUDENT));

        $this->data['userProfil'] = $this->userM->getUser((int)session_data('id'));
        $this->data['acadProfil'] = $this->registration->getLesson(session_data('id'));
        if(empty($this->data['acadProfil'])){
            $this->data['acadProfil'] = null;
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
            redirect('studentGate/examination/planning/all');
        } else if ($route=='all')
        {
            $this->data['plannings']=$this->examination->plannings();
            $this->renderGate('planning-list', 'Liste des plannings d\'examen');
        } else show_404();
    }


    public function results($promo="", $exa="", $print="")
    {
        //echo "Essai"; die();
        if ($promo=="") {
            $promotions=$this->promotion->getStudentPromotions(session_data('id'));
            //$this->vardump($promotions);
            //var_dump($promotions);
            $prs=array();
            if ($promotions!=null)
                foreach ($promotions as $pr)
                {
                    $partial=new stdClass();
                    $partial->promotion=$pr;

                    //$partial->results=$this->examination->getResults($pr->id)[0];
                    $partial->results=$this->examination->getResults($pr->id,1);

                    //var_dump(($partial->results), count($this->examination->getPEvals($pr->id)));die();
                    if (count($partial->results)==count($this->examination->getPEvals($pr->id)) and count($partial->results)>0)
                    {
                        $all=new stdClass();
                        $all->label='Toutes les évaluations';
                        $all->code='all';
                        array_push($partial->results, $all);
                    }
                    array_push($prs, $partial);
                }
            $this->data['promotions']=$prs;
            $this->renderGate('student/select-promotion-result', 'Choix de la vague');
        } else if ($this->promotion->get($promo)!=null) {
            if ($exa=="all")
            {
                $this->data['marks']=$this->getResults($promo)[0];
                $this->data['session']=$this->examination->getMarkPublishDate($p->promotion->id);
                if ($this->getResults($promo)[1]){
                    if ($print=="") {
                        $this->data['promotion']=$this->promotion->getInfo($promo);
                        $this->data['evals']=$this->examination->getEvaluations($promo);
                        $this->data['all']=true;
                        $this->renderGate('student/results', 'Résultats d\'examens de la vague '.$this->data['promotion']->promotion->code);
                    } else if ($print=="print") {
                        $this->data['promotion']=$this->promotion->getInfo($promo);
                        $this->data['evals']=$this->examination->getEvaluations($promo);
                        $this->data['all']=true;
                        $this->printResult($promo);
                    } else show_404();
                } else show_404();
            } else if ($this->examination->evaExist($promo, $exa))
            {
                $this->data['promotion']=$this->promotion->getInfo($promo);
                $lesson = $this->data['promotion']->lesson;
                if ($this->examination->published($this->examination->getExaId($lesson->id,$exa),$this->data['promotion']->promotion->id))
                {
                    if ($print=="") {
                        $this->data['all']=false;
                        $this->data['evals']=$this->examination->getEvaluation($promo, $this->examination->getExaId($lesson->id,$exa));
                        $this->data['marks']=$this->getResults($promo, $this->examination->getExaId($lesson->id,$exa));
                        $this->renderGate('student/results', 'Résultats d\'examens de la vague '.$this->data['promotion']->promotion->code);
                    } else if ($print=="print") {
                        $this->data['all']=false;
                        $this->data['promotion']=$this->promotion->getInfo($promo);

                        $lesson = $this->data['promotion']->lesson;
                        $this->data['evals']=$this->examination->getEvaluation($promo, $this->examination->getExaId($lesson->id,$exa));
                        $this->data['marks']=$this->getResults($promo, $this->examination->getExaId($lesson->id,$exa));
                        $this->printResult($promo);
                    } else show_404();
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
            $result = array();
            $Evals=$this->examination->getPEvals($promo);
            $published=true;
            foreach($Evals as $evl)
            {
                if (!$this->examination->published($evl->id,$promotion->promotion->id))
                    $published=false;
            }
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
                $toCount=$notes;
                $final = 0;
                $percent=0;
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
            $fResult=array($result, $published);
            return $fResult;
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
                    $stdMark->note->value = '';
                $stdMark->note->evaluation= $note!=null? $note->evaluation: '';
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
            $pdf->Output('Results-Promotion-'.$promotion.'.pdf');
        }catch (HTML2PDF_exception $e){
            die($e);
        }
    }
}

