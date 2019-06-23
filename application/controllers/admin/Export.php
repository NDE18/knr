<?php

/**
 * Created by PhpStorm.
 * User: Harrys Crosswell
 * Date: 31/08/2017
 * Time: 07:02
 */
class Export extends  CI_Controller
{
    protected  $data, $menu;

    function __construct()
    {
        parent::__construct();
        
        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));
        $this->load->model('admin/examination_model', 'examination');
        $this->load->model('admin/log_model', 'logM');
        $this->load->model('admin/notification_model', 'notification');
        $this->load->model('admin/lesson_model', 'lesson');
        $this->load->model('admin/document_model', 'document');
        $this->load->model('admin/student_model', 'student');
        $this->load->model('admin/promotion_model', 'promotion');
        //$this->load->model('admin/export_model', 'export');
        $this->load->helper('general_helper');
        $this->load->helper('phpexcel_helper');
    }

    public function menu()
    {
        $this->render('admin/export/menu', 'Menu d\'exportation');
    }

    public function index()
    {
        $this->promotion();
    }

    public function promotion($promo=false, $students=false, $export=false)
    {
        if (!$promo)
        {
            $this->data['promotions']=$this->promotion->get($promo, '2');
            //var_dump($student, $this->data['promotion']); die();
            $this->render('admin/export/promotion-list', 'Liste des vagues');
        }
        else if (count($this->promotion->get($promo))>0){
            $pr=$this->promotion->get($promo);
            if ($students=='students')
            {
                if ($export==false)
                {
                    $this->data['promo']=$pr;
                    $this->data['students']=$this->promotion->getStudents($pr->id, '2');
                    //var_dump($this->data['students']); die();
                    $this->render('admin/export/student-list', 'Liste des apprenants');
                } else if ($export=='certificate') {
                    //echo "good"; die();
                    $crt=new stdClass();
                    $crt->promotion=$promo;
                    $certs=array();
                    $std=$this->promotion->getStudents($pr->id, '2');
                    if ($std!=null)
                        foreach ($std as $st)
                            array_push($certs, $this->getCertificate($st->id, $pr->id));
                    $crt->certs=$certs;
                    export($crt, $export);

                } else if ($export=='attestation') {
                    $attest=new stdClass();
                    $attest->promotion=$promo;
                    $atts=array();
                    $std=$this->promotion->getStudents($pr->id, '2');
                    if ($std!=null)
                        foreach ($std as $st)
                            array_push($atts, $this->getCertificate($st->id, $pr->id));
                    $attest->atts=$atts;
                    $stat=export($attest, $export);
                    if ($stat)
                    {
                        $this->logM->save(array(
                            "motivation"=>"",
                            "author"=>session_data('id'),
                            "date"=>moment()->format("Y-m-d H:i:s"),
                            "action"=>"Export des données pour les attestations de la vague $promo"
                        ));
                    }
                } else if ($export=='report') {
                    $rps=new stdClass();
                    $rps->promotion=$promo;
                    $reports=array();
                    $std=$this->promotion->getStudents($pr->id, '2');
                    if ($std!=null)
                        foreach ($std as $st)
                            array_push($reports, $this->getReport($st->id, $pr->id));
                    $rps->reports=$reports;
                    $stat=export($rps, $export);
                    if ($stat)
                    {
                        if ($stat)
                        {
                            $this->logM->save(array(
                                "motivation"=>"",
                                "author"=>session_data('id'),
                                "date"=>moment()->format("Y-m-d H:i:s"),
                                "action"=>"Export des données pour les relevés de note de la vague $promo"
                            ));
                        }
                    }
                } else show_404();
            }else show_404();
        } else show_404();

            /*{
        } else show_404();*/
    }

    private function render($view, $titre = NULL)
    {
        $this->load->view('admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('admin/menu', $this->menu);
        $this->load->view($view, $this->data);
        $this->load->view('admin/footerAdmin');
    }

     private function getCertificate($student, $promotion)
    {

        $this->load->model('admin/registration_model', 'registration');
        //var_dump($student, $promotion); die();
        $promotion=intval($promotion);
        $student=intval($student);
        $std=$this->promotion->getStudent($promotion, $student, '2');
        $pr=$this->promotion->get($promotion);
        $reg=$this->registration->getCode($student,$promotion)[0];
        //var_dump($reg); die();
        $certificant=new stdClass();
        $certificant->lastname=$std->lastname;
        $certificant->firstname=$std->firstname;
        $certificant->birth_date=$std->birth_date;
        $certificant->birth_place=$std->birth_place;
        $certificant->number_id=$std->number_id;
        $certificant->sexe=$std->sexe;
        $certificant->mention=$pr->label;
        $certificant->codeMention=$reg->code;

        $certificant->duration=$reg->duration;


        return $certificant;
    }

    private function getReport($student, $promotion)
    {
        $promotion=intval($promotion);
        $student=intval($student);
        //var_dump($student, $promotion); die();
        $this->load->model('admin/registration_model', 'registration');

        $std=$this->promotion->getStudent($promotion, $student, '2');
        $pr=$this->promotion->get(intval($promotion));
        $reg=$this->registration->get($student, $promotion);
        $notes=$this->getResults($promotion, $student);
        $report=new stdClass();
        $report->reg_code=$reg->code;
        $report->lastname=$std->lastname;
        $report->firstname=$std->firstname;
        $report->birth_date=$std->birth_date;
        $report->birth_place=$std->birth_place;
        $report->number_id=$std->number_id;
        $report->mention=$pr->label;
        $report->codeMention=$pr->lCode;
        $report->year=intval(date('Y', strtotime($reg->registration_date)));
        $report->marks=$notes->notes;
        $report->final=$notes->final;
        $report->dec=$notes->dec;
        $report->app=$notes->app;
        return $report;
    }

    private function getResults($promo, $student)
    {
        $stdMark = new stdClass();
        $id = intval((int)$student);
        $notes = $this->examination->getMarks($id, $promo);
        $stdMark->notes=array();
        $final = 0;
        $percent=0;
        if ($notes!=null) {
            foreach ($notes as $note) {
                $mark = new stdClass();
                if ($note != null) {
                    $mark->value = castNumberId($note->value, 2, 2);
                } else
                    $mark->value = '';
                $mark->evaluation = $note->evaluation;
                $mark->label = $note->label;
                $mark->code = $note->code;
                $mark->percent = $note->percent;
                array_push($stdMark->notes, $mark);
                $percent += $note->percent;
                $final += floatval(($note->value * $note->percent) / 100);
            }
            $percent=$percent/100;
            $final=$final/$percent;
        }
        $stdMark->final = castNumberId($final, 2, 2);
        $stdMark->dec = $this->decision($final);
        $stdMark->app = $this->appreciate($final);
        //$stdMark->note->evaluation= $note!=null? $note->evaluation: '';
        return $stdMark;
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



}