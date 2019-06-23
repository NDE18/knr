<?php

/**
 * Created by PhpStorm.
 * User: Harrys Crosswell
 * Date: 17/08/2017
 * Time: 13:02
 */
class Examination extends Ci_Controller
{
    function __construct()
    {
        parent::__construct();

        $this->load->model('backfront/examination_model', 'examination');
        $this->load->model('public/promotion_model', 'promotion');
        $this->load->model('backfront/registration_model', 'registration');
        $this->load->model('backfront/lesson_model', 'lesson');
        $this->load->model('backfront/lessonA_model', 'lessonA');
        $this->load->model('public/events_model', 'mEvent');

        $this->load->helper('general_helper');

        $this->load->model('backfront/user_model', 'userM');
        $this->load->model('public/lesson_model', 'mLesson');

        $this->data['cLesson'] = $this->mLesson->getByType('cours')->result();
        $this->data['fLesson'] = $this->mLesson->getByType('filière')->result();
        $this->data['pLesson'] = $this->mLesson->getByType('promotion')->result();

        $this->data['lastEvent'] = $this->mEvent->get(null,0,3);

        $this->load->model('public/flash_model', 'mFlash');
        $this->data['infosFlash'] = $this->mFlash->get(null,0,3);


    }

    public function index()
    {
        $this->results();
    }

    public function planning($route="")
    {
        if ($route=="")
        {
            redirect('examination/planning/all');
        } else if ($route=='all')
        {
            $this->data['plannings']=$this->examination->plannings();


            $this->meta = array(
                "description"=>"Consulter et télécharger les plannings d'évaluation à MULTISOFT ACADEMY",
                "url"=>base_url('plannings'),
                "image"=>img_url('examen.jpg')
            );

            $this->data ['breadcrumb'] = array(
                "Accueil" => base_url(),
                "Tous les plannings d'évaluation"=>"",
            );
            $this->render('Liste des plannings d\'examen','planning-list');
        } else show_404();
    }

    public function results($promo="", $exa="", $print="")
    {
        
        if ($promo=="") {
        
            $promotions=$this->promotion->getEvaluatedPromotions();
            $prs=array();
            if ($promotions!=null)
            
                foreach ($promotions as $pr)
                {
                    $partial=new stdClass();
                    $partial->promotion=$pr;

                    $partial->results=$this->examination->getResults($pr->id,1);
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
            $this->meta = array(
                "description"=>"Consultez tous les Résultats aux différentes évaluations à MULTISOFT ACADEMY",
                "url"=>base_url('resultats'),
                "image"=>img_url('examen.jpg')
            );

            $this->data ['breadcrumb'] = array(
                "Accueil" => base_url(),
                "Résultats d'évaluation : Choix de la vague"=>"",
            );
           
            $this->render( 'Résultats d\'évaluation : Choix de la vague','select-promotion-result');
        }
        else if ($this->promotion->get($promo)!=null) {
            if ($exa=="all")
            {
            	$p = $this->promotion->getInfo($promo);
            	//var_dump($p);
            	//var_dump($p->promotion->id);
                $this->data['marks']=$this->getResults($promo)[0];
                $this->data['session']=$this->examination->getMarkPublishDate($p->promotion->id);
                              //  $this->data['marks']=$this->getResults($p->promotion->id)[0];
                if ($this->getResults($promo)[1]){
                    if ($print=="") {
                        $this->data['promotion']=$this->promotion->getInfo($promo);
                        $this->data['evals']=$this->examination->getEvaluations($promo);
                        $this->data['all']=true;
                        $this->meta = array(
                            "description"=>"Consultez et télécharger les Résultats d'évaluation de la vague ".$this->data['promotion']->promotion->code." à MULTISOFT ACADEMY",
                            "url"=>base_url("resultats/$promo/$exa"),
                            "image"=>img_url('examen.jpg')
                        );

                        $this->data ['breadcrumb'] = array(
                            "Accueil" => base_url(),
                            "Résultats d'évaluation "=>base_url("resultats"),
                            "Vague ".$this->data['promotion']->promotion->code=>"",
                        );
                        $this->render('Résultats d\'évaluation de la vague '.$this->data['promotion']->promotion->code,'results');
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
                        $this->meta = array(
                            "description"=>"Consulter et télécharger les Résultats d'évaluation de la vague ".$this->data['promotion']->promotion->code." à MULTISOFT ACADEMY",
                            "url"=>base_url("resultats/$promo/$exa"),
                            "image"=>img_url('examen.jpg')
                        );

                        $this->data ['breadcrumb'] = array(
                            "Accueil" => base_url(),
                            "Résultats d'évaluation "=>base_url("resultats"),
                            "Notes de ".$this->data['evals'][0]->label." (".$this->data['evals'][0]->code.") &blacktriangleright; Vague ".$this->data['promotion']->promotion->code=>"",
                        );
                        $this->render("Notes de ".$this->data['evals'][0]->label." (".$this->data['evals'][0]->code.") &blacktriangleright; Vague ".$this->data['promotion']->promotion->code,'results');
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
        $this->load->view("public/examination-page-model", $this->data);
        $this->load->view('public/footer');
    }

    public function decision($note)
    {
        return $note<12?'R':'A';
    }

    public function appreciate($note)
    {
        if ($note==0) return 'Null';
        else if ($note<7) return 'Médiocre';
        else if ($note<10) return 'Faible';
        else if ($note<12) return 'Passable';
        else if ($note<14) return 'Assez bien';
        else if ($note<17) return 'Bien';
        else if ($note<20) return 'Très bien';
        else return 'Excellent';
    }

    private function getResults($promo, $evaluation=0)
    {
        if ($evaluation==0) {
        
            $promotion = $this->promotion->getInfo($promo);
 		
            $students = $this->promotion->getStudents($promotion->promotion->id);
            $result = array();
            $p = $this->promotion->getInfo($promo);
            $Evals=$this->examination->getPEvals($p->promotion->id);
            $published=true;
            //var_dump($Evals);echo "<br>";
            //var_dump($promotion); die();
            foreach($Evals as $evl)
            {
                if (!$this->examination->published($evl->id,$p->promotion->id))
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
        $content = $this->load->view('public/examination/result-print', $this->data, true);
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