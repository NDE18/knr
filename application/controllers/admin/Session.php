<?php
/**
 * Created by PhpStorm.
 * User: Harrys Crosswell
 * Date: 18/07/2017
 * Time: 10:36
 */

//namespace availability;
define('MAX', 7);
define('MIN', 1);
define('MVAGUE', 6);
define('MJOUR', 6);
define('MPLAGE', 3);
define('PROG', 3);


class Session extends CI_Controller
{
    protected $data, $menu;

    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));


        $this->load->model('admin/availability_model');
        $this->load->model('admin/session_model', 'sessionm');
        $this->load->model('admin/notification_model', 'notif');

        $this->menu['notif'] = $this->notif->newNotif();
    }

    private function render($view, $titre = NULL)
    {
        $this->load->view('admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('admin/menu', $this->menu);
        $this->load->view($view, $this->data);
        $this->load->view('admin/footerAdmin');
    }

    private function assignTimetable($Dispo=array(), $vagues, $Planning, $plages, $MVAGUE, $prog)
    {
    	//var_dump($Dispo);die();
        $tour=0;
        while($this->allProgrammed($vagues,$MVAGUE) != 1 and $tour < $prog){
            $curPlage = 0; $curJour = 0;
            $maxD = 0; $posMaxD = 0;
	    $max = (count($vagues) > MVAGUE ? MVAGUE :count($vagues));
            for($k=0;$k<$max;$k++){
                $vg = $vagues[$k];
                $posMaxD = 0;
                //La position nombre maximum d'apprenants dans la colonne de la promotion j
                do{
                    $posMaxD = 0;
                    $maxD = $Dispo[$posMaxD][$k];

                    for($i=0;$i<$plages*MJOUR;$i++){
                        if($Dispo[$i][$k] > $maxD){
                            $maxD = $Dispo[$i][$k];
                            $posMaxD = $i;
                        }
                    }
                    //echo $k."-".$posMaxD."<br>";
                    $Dispo[$posMaxD][$k] = -1;
                    $curPlage = ($posMaxD)%$plages; //Plage du jour
                    $curJour = ($posMaxD)/$plages; //Jour
                }while($Planning[$curPlage][$curJour]!=-1);
                //if($k==5) die();

        
                if($vagues[$k] > 0){
                    if(!$this->isAlreadyInDay($vg,$curJour,$plages,$Planning)){
                        $Planning[$curPlage][$curJour] = $vg[0];
                        $Dispo[$posMaxD][$k] = -1;
                        $vg[1]--;
                    } else{
                        $Dispo[$posMaxD][$k] = -1;
                    }
                }
            }

            $tour++;
        }
        return $Planning;
    }

    private function subjects($table) {
        foreach ($table as $subject) {
            $id   = $subject[0];
            $name = $subject[1];
            print "<tr><td class=\"dark\"><div id=\"$id\" class=\"redips-drag redips-clone $id\">$name</div><input id=\"b_$id\" class=\"$id\" type=\"button\" value=\"\" onclick=\"redips.report('$id')\" title=\"Show only $name\"/></td></tr>\n";
        }
    }

    private function filter($table=array())
    {
        $filtered=array();
        foreach ($table as $time){
           $proms = explode('#',$time->promotion);
            array_pop($proms);
            array_shift($proms);
            foreach($proms as $prom){
                if (!in_array($prom, $filtered)) {
                    array_push($filtered, $prom);
                }
            }
        }
        return $filtered;
    }

    public function showTimetable()
    {
        if (isset($_POST['start'])) {
            $nbrprog = $_POST['prog'];

            $start=date('Y-m-d', strtotime($_POST['start']));

            $av = new Availability_model();


            $semaine = $this->getWeek($start);
            //var_dump($semaine);die();

            $nbrPromo = $av->getPromotionsNumber(); //Nombre de promotions
            $max = ($nbrPromo > MVAGUE ? MVAGUE : $nbrPromo); //Le max est le nombre de promotions de la BD s'il est inférieur ou égal à MVAGUE (10)
            $periodNbr = $av->getPeriodNumber(); //On récupère le nombre de périodes
            $nbrTime=$this->sessionm->findTimetable($semaine['debut']);
            if ($nbrTime>0)
            {
                //echo $_POST['start']; die();
                $this->data['message']="Un emploi du temps à cette date a déjà été généré. Voulez-vous le supprimer d'abord?";
                $this->render('admin/timetable/generate', 'Générer un emploi du temps');
            }else if ($nbrprog * $max > $periodNbr * MJOUR) {
                $this->data['erreur'] = "Vous ne pouvez faire que " . intval(floor(($periodNbr * MJOUR) / $max)) . " programmation(s) pour l'instant.";
                $this->render('admin/timetable/generate', 'Générer un emploi du temps');
            } else {


                $i = 0;
                $promos = $av->getPromotions(); //On récupère les identifiants des promotions
                $promotions = array(); //On crée une matrice Promotions

                /*******Promotions va prendre autant de fois que possible les valeurs de promotions
                 * et mettre le nombre de programmations à 3 : Promotions=tableau({Id_Promotion, nbre_prog})
                 */
                $k = 0;
                foreach ($promos as $promo) {
                    $promotions[$k][0] = $promo->id;
                    $promotions[$k][1] = $nbrprog;
                    $promotions[$k][2] = $av->getCode($promo->id);
                    $promotions[$k][3] = $av->getLesson($promo->id);
                    $promotions[$k][4] = "";
                    $k++;
                }
                /****Fin***/

                $pr_matrix = array(); //On crée un matrice pr_matrix
                /*******pr_matrix est la matrice qui a en ligne les promotions et en colonne
                 * les différents apprenants d'une promotion : $pr_matrix=tableau({promotion=>{app1, app2,..., appn}})
                 */
                for ($l = 0; $l < $k; $l++) {
                    $users = $av->getUsers($promotions[$l][0]);
                    $pr_matrix[$l] = array();
                    foreach ($users as $user) {
                        if (property_exists($user, 'user') == true) {
                            array_push($pr_matrix[$l], $user->user);
                        }
                    }
                }
                /*********Fin********/


                $av_matrix = array(); //On crée une matrice av_matrix
                $day = 0; //On crée une variable day qui contiendra le jour en cour
                $period = 0; //On crée une variable period qui contiendra la période en cour
                /*********av_matrix est une matrice 3D qui contient en ligne les jours de la semaine, en colone les promotions
                 * et en profondeur le nombre d'apprenants disponibles pour une promotion à une plage donnée du jour
                 */
                while ($i < $periodNbr * 6) {
                    $j = 0;

                    if ($i % 6 == 0) {
                        $period = 1;
                        $day++;
                    } else {
                        $period++;
                    }

                    while ($j < $max) {
                        $avs = 0;
                        if (isset($pr_matrix[$j]))
                            foreach ($pr_matrix[$j] as $pr) {
                                $date = explode("/", $semaine['debut']);
                                $time = strtotime($date[2] . '-' . $date[1] . '-' . $date[0]);
                                $sd = date('Y-m-d', $time);
                                $avs += $av->getAvailability($pr, $day, $period, $sd);
                            }
                        $av_matrix[$i][$j] = $avs;
                        //$av_matrix[$i][$promotions[$j][0]] = $avs;
                        $j++;
                    }
                    $i++;
                }
                /********Fin*******/

                /*var_dump($av_matrix);
                die();*/

                $timetable = array();
                $lessons = array();
                $periods = array();

                $finalTimetable = array();
                for ($i = 0; $i < $periodNbr; $i++)
                    for ($j = 0; $j < MJOUR; $j++)
                        $timetable[$i][$j] = -1;

                $timetable = $this->assignTimetable($av_matrix, $promotions, $timetable, $periodNbr, $max, $nbrprog);

                $line = 0;
                foreach ($timetable as $key => $value) {
                    $period = $av->getPeriod($key + 1);
                    array_push($periods, $period);
                    $line++;
                    $col = 0;
                    foreach ($timetable[$key] as $ind => $val) {
                        $col++;
                        if ($val < 0) {
                            $finalTimetable[$line][$col] = array();
                            array_push($finalTimetable[$line][$col], "Libre", 0, $val);
                        } else {
                            //echo "<td align='center'><b>Vague " . $av->getCode($val) . "</b> <br>(<em>" . $av->getLesson($val) . "</em>)<br><br>";
                            $finalTimetable[$line][$col] = array();
                            array_push($finalTimetable[$line][$col], "Vague " . $av->getCode($val) . "</b> <br>(<em>" . $av->getLesson($val) . "</em>)", 0, $val);
                            array_push($lessons, array($av->getlessonId($val), $av->getLesson($val), $val));

                        }
                    }
                    //echo "</tr>";
                }

                foreach ($finalTimetable as $line)
                    foreach ($line as $col) {
                        if (isset($col[2])) {
                            $g = 0;
                            for ($z = 0; $z < $k && $g != 1; $z++) {
                                if ($promotions[$z][0] == $col[2]) {
                                    $promotions[$z][1]--;
                                    $g = 1;
                                }
                            }
                        }
                    }

                $this->data['lessons'] = $lessons;
                //$this->data['promote']=$promote;
                $this->data['periods'] = $periods;
                $this->data['promotions'] = $promotions;
                $this->data['week'] = $semaine;
                //$this->data['subjects']=$distinctSubjects;//
                $this->data['timetable'] = $finalTimetable;
                $this->render('admin/timetable/timetablePreview', 'Emploi du temps');
            }
        }
        else
        {
            $this->data['status'] = false;
            $this->render('admin/timetable/timetablePreview', 'Emploi du temps');
        }
    }

    public function allProgrammed(array $v=array(), $max){
        $i=0;
        while($i<$max && $v[$i][1]== 0){
            $i++;
        }

        if($i<$max){
            return 0;
        }
        return 1;
    }

    public function isAlreadyInDay($vague, $jour, $MAXPLAGE, $Planning){
        $i=0;

        while($i<$MAXPLAGE && $Planning[$i][$jour]!=$vague[0]){
            $i++;
        }
        if($i<$MAXPLAGE){
            return 1;
        }
        return 0;
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

    public function saveTimetable()
    {
        if (isset($_POST))
        {
            $program=$_POST['program'];
            $timetable=json_decode($program);
            //var_dump($timetable); die();
            $distinctPromo=$this->filter($timetable);
            //var_dump($timetable);
            $date = explode("/", $_POST['start_date']);
            $time = strtotime($date[2].'-'.$date[1].'-'.$date[0]);
            $this->sessionm->saveTimetable($timetable, $_POST['start_date'], $_POST['end_date'], $distinctPromo);

            $date = explode("/", $_POST['start_date']);
            $time = strtotime($date[2].'-'.$date[1].'-'.$date[0]);
            $sd=date('Y-m-d', $time);
            //var_dump($sd); die();
            $this->timetable($sd);

        }
    }

    public function timetable($timetableStartDate)
    {
        $table=$this->sessionm->getTimetable($timetableStartDate);
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
            $this->data['week']=$this->getWeek($timetableStartDate);
            $this->data['status']=true;
        } else
        {
            $this->data['status']=false;
        }
        $this->render('admin/timetable/timetable', 'Emploi du temps');
    }

    public function generateTimetable()
    {
        protected_session(array('','admin/auth'),ADMIN);
        $this->render('admin/timetable/generate', 'Générer un emploi de temps');
    }

    public function timetableList()
    {
        $timetables=$this->sessionm->timetableList();
        $week=array();
        foreach ($timetables as $tb)
            array_push($week, $this->getWeek($tb->start_date));
        $this->data['timetables']=$week;
        $this->data['today']=$this->getWeek(date('Y-m-d'));
        $this->render('admin/timetable/timetableList', 'Liste des emplois du temps');
    }

    public function timetableDelete($date)
    {
        $this->sessionm->timetableDelete($date);
        $this->timetableList();
    }

    public function printTimeTable($timetableStartDate)
    {
        $this->load->helper("html2pdf_helper");

        $table=$this->sessionm->getTimetable($timetableStartDate);
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

            $content = $this->load->view('admin/timetable/printTimetable', $this->data, true);

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