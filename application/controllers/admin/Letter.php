<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Letter extends CI_Controller {

    protected $data, $menu;

    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN));


        $this->load->library('form_validation');
        $this->load->model('admin/adminfolder_model', 'adminfolder');
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

    public function index()
    {
        $this->recommandationLetter();
    }

    public function recommandationLetter(){
        $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
        $this->form_validation->set_rules('nomA', '"Nom complet de l\'apprenant"', 'trim|required|encode_php_tags');
        $this->form_validation->set_rules('bp', '"Boite postal"', 'trim|required');
        $this->form_validation->set_rules('destination', '"Destination"', 'required');
        $this->form_validation->set_rules('lesson', '"lesson"', 'required');


        if($this->form_validation->run()){
            if($this->input->post('lesson') == 'nothing'){
                $this->data['message'] = 'Désolé L\'apprenant '.$this->input->post('nomA').' n\'a aucune filiere ou lesson achevée';
                $this->render('admin/letter/form-recommandation', 'Lettre de recommandation');
            }else{
                $this->load->model('admin/student_model', 'student');
                $this->load->helper("html2pdf_helper");
                $student = $this->student->getUser($this->input->post('idA'));
                $dateBirth = date_create($student['birth_date']);
                $this->data['birthdate'] = date_format($dateBirth, 'd').'/'.date_format($dateBirth, 'm').'/'.date_format($dateBirth, 'Y');
                $this->data['birthplace'] = $student['birth_place'];
                $this->data['destination'] = $this->input->post('destination');
                $this->data['bp'] = $this->input->post('bp');
                $this->data['nomA'] = $this->input->post('nomA');
                $this->data['mat'] = $this->input->post('mat');
                $this->data['fil'] = $this->input->post('lesson');

                $content  = $this->load->view('admin/letter/pdf-recommandation', $this->data, TRUE);


                try{
                    $pdf = new HTML2PDF('P', 'A4', 'fr');
                    $pdf->pdf->setDisplayMode('fullpage');
                    $pdf->writeHTML($content);
                    ob_end_clean();
                    $pdf->Output('recommandationLetter'.$student['id'].'.pdf');
                }catch (HTML2PDF_exception $e){
                    die($e);
                }
            }
        }else{
            $this->render('admin/letter/form-recommandation', 'Lettre de recommandation');
        }
    }

    public function searchVal(){
        if(isset($_POST['val'])){
            $res = $this->adminfolder->searchVal($_POST['val']);

            $send = array();
            if(empty($res)){
                echo '*1*';
            }else{
                for($i = 0; $i < count($res); $i++){
                    $send[$i] = new stdClass();
                    $send[$i]->label = $res[$i]->firstname ." ". $res[$i]->lastname;
                    $send[$i]->id = $res[$i]->id;
                    $send[$i]->number_id = $res[$i]->number_id;
                }
                echo json_encode($send);

            }
        }
    }

    public  function searchLesson(){
        if(isset($_POST)){
            $lesson = $this->adminfolder->searchLesson($_POST['idA']); $res = '';
            if(empty($lesson)){
                $res = "<option value='nothing'>Aucune</option>>";
            }else{
                for($i = 0; $i < count($lesson); $i++){
                    $res .=  "<option value='".$lesson[$i]->label."'>".$lesson[$i]->label."</option>";
                }
            }
            echo $res;
        }
    }

}
