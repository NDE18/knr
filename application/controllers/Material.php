<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Material extends Ci_Controller
{
    protected $data, $menu;
    public function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!protected_session(false, false, true) And !($this->authM->auth(false, get_cookie('multisoft'))))
        {
            protected_session('auth');
        }
        $this->load->library('form_validation');
        $this->load->model('admin/material_model', 'material');
    }

    public function index()
    {
        $this->lyst();
    }

    private function render($view, $titre = NULL)
    {
        $this->load->model('admin/notification_model', 'notif');

        $this->menu['notif'] = $this->notif->newNotif();
        $this->load->view('admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('admin/nemu', $this->menu);
        $this->load->view($view, $this->data);
        $this->load->view('admin/footerAdmin');
    }

    public function save($id = false, $mode=false)
    {
        if(isset($id) and !is_bool($id)){
            $this->data['query'] = $this->material->selectAll($id)->result();
            if(count($this->data['query']) == 1) { // soit cest un ajout(ajout du stock), soit cest un retrait
                //var_dump($this->data['query']); die(0);

                $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
                //$this->form_validation->set_rules('nom', '"Nom"', 'trim|required|min_length[1]|max_length[64]|alpha_dash|encode_php_tags');
                //$this->form_validation->set_rules('type', '"Type"', 'trim|required|min_length[4]|max_length[64]|alpha_dash|encode_php_tags');
                //$this->form_validation->set_rules('emballage', '"Emballage"', 'trim|required|min_length[4]|max_length[64]|alpha_dash|encode_php_tags');
                $this->form_validation->set_rules('transaction', '"Transaction"', 'trim|required|min_length[5]|max_length[7]|alpha_dash|encode_php_tags');
                $this->form_validation->set_rules('quantity', '"Quantité"', 'trim|required|is_natural_no_zero|encode_php_tags');

                if($this->form_validation->run())
                {
                    $post = array(
                        'name'=>$this->data['query'][0]->name,       //strtolower($this->input->post('nom')),
                        'type'=>$this->data['query'][0]->type,      //strtolower($this->input->post('type')) ,
                        'packaging'=>$this->data['query'][0]->packaging,  //strtolower($this->input->post('emballage')),
                        'qty'=>0,
                    );


                    $matId = $this->material->saveM($post, strtolower($this->input->post('transaction'))); $date = date('Y-m-d');

                    //var_dump($matId); die(0);
                    if(is_int($matId)){ //le materiel existe deja
                        //var_dump("retrait"); die(0);
                        $userId = session_data('id');
                        $transact = array(
                            'type'=>strtolower($this->input->post('transaction')),
                            'qty'=>intval($this->input->post('quantity')),
                            'date'=>$date,
                            'user'=>$userId,
                            'material'=>$matId,
                        );
                        //var_dump($transact);
                        //Dans le cas ou le meteriel existe deja on verifie le type de transaction
                        if($transact['type'] == "retrait"){
                            $currentStock = $this->material->currentStock($matId);
                            if($transact['qty'] > $currentStock){
                                $this->data['message'] = 'La quantité de retrait est superieur à celle en stocks!';
                                //$this->form_validation->set_value('name', 'Ce matériel existe déjà');
                                $this->data['query'] = $this->material->selectAll($id)->result();
                                if(count($this->data['query']) == 1) {
                                    //var_dump($this->data['query']->result());
                                    $this->data['req'] = $this->data['query'];
                                    $this->render('admin/material/removeMaterial', 'Retirer un materiel');
                                }
                            }elseif($transact['qty'] <= $currentStock){
                                //var_dump($currentStock-$transact['qty']); die(0);
                                $transact = $this->material->saveT($transact, $currentStock-$transact['qty'], $matId);
                                if(is_bool($transact)){
                                    $this->data['message'] = 'Retrait effectué avec succès!';
                                    //$this->form_validation->set_value('name', 'Ce matériel existe déjà');
                                    $this->data['query'] = $this->material->selectAll($id)->result();
                                    if(count($this->data['query']) == 1) {
                                        //var_dump($this->data['query']->result());
                                        $this->data['req'] = $this->data['query'];
                                        $this->render('admin/material/removeMaterial', 'Retirer un materiel');
                                    }
                                }else{
                                    $this->data['message'] = 'Echec de l\'enregistrement de la $transaction';
                                    $this->render('admin/material/removeMaterial', 'Enregistrer un materiel');
                                }
                            }
                        }elseif($transact['type'] == "ajout"){
                            $userId = session_data('id');
                            //var_dump($matId); die(0);
                            $transact = array(
                                'type'=>$this->input->post('transaction'),
                                'qty'=>intval($this->input->post('quantity')),
                                'date'=>$date,
                                'user'=>$userId,
                                'material'=>$matId,
                            );
                            $currentStock = $this->material->lastQtyMaterial($matId) + $transact['qty'];
                            $transact = $this->material->saveT($transact, $currentStock, $matId);
                            if(is_bool($transact)){
                                $this->data['message'] = 'L\' ajout a été bien effectué!';

                                $this->data['query'] = $this->material->selectAll($id)->result();
                                if(count($this->data['query']) == 1) {
                                    //var_dump($this->data['query']->result());
                                    $this->data['req'] = $this->data['query'];
                                    $this->render('admin/material/addMaterial', 'Ajouter un materiel');
                                }
                            }else{
                                $this->data['message'] = 'Echec de l\'enregistrement de la $transaction';
                                $this->render('admin/material/saveModify', 'Enregistrer un materiel');
                                //var_dump($transact); die(0);
                            }
                        }
                    }elseif(is_object($matId)){ //le materiel nexiste pas
                        $userId = session_data('id');
                        //var_dump($matId); die(0);
                        $transact = array(
                            'type'=>$this->input->post('transaction'),
                            'qty'=>$this->input->post('quantity'),
                            'date'=>$date,
                            'user'=>$userId,
                            'material'=>$matId->maxid,
                        );

                        $transact = $this->material->saveT($transact, $transact['qty'], $matId->maxid);
                        if(is_bool($transact)){
                            $this->data['message'] = 'Enregistré avec succès!';
                            $this->render('admin/material/save', 'Enregistrer un materiel');

                        }else{
                            $this->data['message'] = 'Echec de l\'enregistrement de la $transaction';
                            $this->render('admin/material/save', 'Enregistrer un materiel');
                            //var_dump($transact); die(0);
                        }
                    }elseif($matId == false){
                        $this->data['message'] = 'Echec de l\'enregistrement du materiel: Impossible d\'ajouter ce materiel car il est inexistant!';
                        $this->render('admin/material/save', 'Enregistrer un materiel');
                        $this->load->view('admin/headerAdmin');
                        $this->load->view('admin/nemu');
                        $this->load->view('admin/material/save', $this->data);
                        $this->load->view('admin/footerAdmin');
                    }
                }else{
                    if(strtolower($this->input->post('transaction')) == 'retrait'){
                        $this->data['query'] = $this->material->selectAll($id)->result();
                        if(count($this->data['query']) == 1) {
                            //var_dump($this->data['query']->result());
                            $this->data['req'] = $this->data['query'];
                            $this->render('admin/material/removeMaterial', 'Retirer un materiel');
                        }
                    }
                    elseif(strtolower($this->input->post('transaction')) == 'ajout'){
                        $this->data['query'] = $this->material->selectAll($id)->result();
                        if(count($this->data['query']) == 1) {
                            //var_dump($this->data['query']->result());
                            $this->data['req'] = $this->data['query'];
                            $this->render('admin/material/addMaterial', 'Ajouter un materiel');
                        }
                    }

                }

            }
        }
        elseif(is_bool($id)){// il s'agit de l'ajout dun nouveau materiel
            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('nom', '"Nom"', 'trim|required|min_length[1]|max_length[64]|alpha_numeric_spaces|encode_php_tags');
            $this->form_validation->set_rules('type', '"Type"', 'trim|required|min_length[4]|max_length[64]|alpha_dash|encode_php_tags');
            $this->form_validation->set_rules('emballage', '"Emballage"', 'trim|required|min_length[4]|max_length[64]|alpha_dash|encode_php_tags');
            $this->form_validation->set_rules('transaction', '"Transaction"', 'trim|required|min_length[5]|max_length[7]|alpha_dash|encode_php_tags');
            $this->form_validation->set_rules('quantity', '"Quantité"', 'trim|required|is_natural_no_zero|encode_php_tags');

            if($this->form_validation->run())
            {
                $post = array(
                    'name'=>strtolower($this->input->post("nom")),       //strtolower($this->input->post('nom')),
                    'type'=>strtolower($this->input->post("type")), //$this->data['query'][0]->type,      //strtolower($this->input->post('type')) ,
                    'packaging'=>strtolower($this->input->post("emballage")), //$this->data['query'][0]->packaging,  //strtolower($this->input->post('emballage')),
                    'qty'=>0,
                );


                $matId = $this->material->saveM($post, strtolower($this->input->post('transaction'))); $date = date('Y-m-d');

                //var_dump($matId); die(0);
                /*if(is_int($matId)){
                    //var_dump("retrait"); die(0);
                    $userId = 1;
                    $transact = array(
                        'type'=>strtolower($this->input->post('transaction')),
                        'qty'=>intval($this->input->post('quantity')),
                        'date'=>$date,
                        'user'=>$userId,
                        'material'=>$matId,
                    );
                    //var_dump($transact);
                    //Dans le cas ou le meteriel existe deja on verifie le type de transaction
                    if($transact['type'] == "retrait"){
                        $currentStock = $this->material->currentStock($matId);
                        if($transact['qty'] > $currentStock){
                            $this->data['message'] = 'La quantité de retrait est superieur à celle en stocks!';
                            //$this->form_validation->set_value('name', 'Ce matériel existe déjà');

                            $this->load->view('admin/headerAdmin');
                            $this->load->view('admin/nemu');
                            $this->load->view('admin/material/save', $this->data);
                            $this->load->view('admin/footerAdmin');
                        }elseif($transact['qty'] <= $currentStock){
                            //var_dump($currentStock-$transact['qty']); die(0);
                            $transact = $this->material->saveT($transact, $currentStock-$transact['qty'], $matId);
                            if(is_bool($transact)){
                                $this->data['message'] = 'Enregistré avec succès!';
                                //$this->form_validation->set_value('name', 'Ce matériel existe déjà');
                                $this->load->view('admin/headerAdmin');
                                $this->load->view('admin/nemu');
                                $this->load->view('admin/material/save', $this->data);
                                $this->load->view('admin/footerAdmin');
                            }else{
                                $this->data['message'] = 'Echec de l\'enregistrement de la $transaction';
                                $this->load->view('admin/headerAdmin');
                                $this->load->view('admin/nemu');
                                $this->load->view('admin/material/save', $this->data);
                                $this->load->view('admin/footerAdmin');
                            }
                        }
                    }else{
                        $userId = 1;
                        //var_dump($matId); die(0);
                        $transact = array(
                            'type'=>$this->input->post('transaction'),
                            'qty'=>intval($this->input->post('quantity')),
                            'date'=>$date,
                            'user'=>$userId,
                            'material'=>$matId,
                        );
                        $currentStock = $this->material->lastQtyMaterial($matId) + $transact['qty'];
                        $transact = $this->material->saveT($transact, $currentStock, $matId);
                        if(is_bool($transact)){
                            $this->data['message'] = 'Enregistré avec succès!';
                            $this->load->view('admin/headerAdmin');
                            $this->load->view('admin/nemu');
                            $this->load->view('admin/material/addMaterial', $this->data);
                            $this->load->view('admin/footerAdmin');
                        }else{
                            var_dump($transact); die(0);
                        }
                    }
                }else*/
                //var_dump($matId); die(0);
                if(is_int($matId)){
                    $this->data['message'] = 'Ce matériel existe déjà, Veuillez acceder a la liste du materiel pour effectuer une entrée!';
                    $this->render('admin/material/save', 'Enregistrer un matériel');
                    /*$this->load->view('admin/headerAdmin');
                    $this->load->view('admin/nemu');
                    $this->load->view('admin/material/save', $this->data);
                    $this->load->view('admin/footerAdmin');*/
                }
                elseif(is_object($matId)){
                    $userId = session_data('id');
                    //var_dump($matId); die(0);
                    $transact = array(
                        'type'=>$this->input->post('transaction'),
                        'qty'=>$this->input->post('quantity'),
                        'date'=>$date,
                        'user'=>$userId,
                        'material'=>$matId->maxid,
                    );

                    $transact = $this->material->saveT($transact, $transact['qty'], $matId->maxid);
                    if(is_bool($transact)){
                        $this->data['message'] = 'Enregistré avec succès!';
                        $this->render('admin/material/save', 'Enregistrer un matériel');
                        /*
                        $this->load->view('admin/headerAdmin');
                        $this->load->view('admin/nemu');
                        $this->load->view('admin/material/save', $this->data);
                        $this->load->view('admin/footerAdmin');*/
                    }else{
                        var_dump($transact); die(0);
                    }
                }elseif($matId == false){
                    $this->data['message'] = 'Echec de l\'enregistrement du materiel: Impossible d\'ajouter ce materiel car il est inexistant!';
                    $this->render('admin/material/save', 'Enregistrer un matériel');
                    /*
                    $this->load->view('admin/headerAdmin');
                    $this->load->view('admin/nemu');
                    $this->load->view('admin/material/save', $this->data);
                    $this->load->view('admin/footerAdmin');*/
                }
            }
            else
            {
                $this->render('admin/material/save', 'Enregistrer un matériel');
                /*
                $this->load->view('admin/headerAdmin');
                $this->load->view('admin/nemu');
                $this->load->view('admin/material/save', $this->data);
                $this->load->view('admin/footerAdmin');*/
            }
        }

    }

    public function lyst()
    {

        $this->data['material'] = $this->material->lyst()->result();
        $this->render('admin/material/liste', 'Liste du matériel');
        /*
        $this->load->view('admin/headerAdmin');
        $this->load->view('admin/nemu');
        $this->load->view('admin/material/liste', array('material'=>$material));
        $this->load->view('admin/footerAdmin');*/
    }

    public function modify($id=false, $idT=false){
        if(isset($id)){

            $this->data['query'] = $this->material->selectAll($id)->result();

            //var_dump($this->data['query']); die(0);
            //var_dump(count($this->data['query'])); die(0);
            if(count($this->data['query']) == 1){
                //var_dump($this->data['query']->result());
                $this->data['req'] = $this->data['query'];
                $this->data['idfT'] = $idT;
                //var_dump($this->data); die(0);

                //die(0);
                $this->render('admin/material/saveModify', 'Modifier un materiel');

            }else{
                $this->data['message'] = 'Veuillez sélectionner un materiel dans la liste SVP';
                $this->render('admin/material/saveModify', 'Modifier un materiel');
            }
        }
    }

    public function update($id=false,$idT=false){
            //var_dump($this->input->post()); die(0);
            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('nom', '"Nom"', 'trim|required|min_length[1]|max_length[64]|alpha_numeric_spaces|encode_php_tags');
            $this->form_validation->set_rules('type', '"Type"', 'trim|required|min_length[4]|max_length[64]|alpha_dash|encode_php_tags');
            $this->form_validation->set_rules('emballage', '"Emballage"', 'trim|required|min_length[4]|max_length[64]|alpha_numeric_spaces|encode_php_tags');
            //$this->form_validation->set_rules('action', '"Action"', 'trim|required|alpha_numeric_spaces|encode_php_tags');
            $this->form_validation->set_rules('motivation', '"Motivation"', 'trim|required|min_length[15]|max_length[512]|alpha_numeric_spaces|encode_php_tags');

            $id = $this->input->post('idft');
            $idT = $this->input->post('idfT');
            if($this->form_validation->run())
            {

                $user = session_data('id');

                $userId = $this->material->userId($user);
                if(!is_null($userId)){
                    $post = array(
                        'name'=>$this->input->post('nom'),
                        'type'=>$this->input->post('type'),
                        'packaging'=>$this->input->post('emballage'),
                    );
                    $post = $this->material->updateMat($post, $id);

                    if(is_bool($post) And $post)
                    {

                            $post = array(
                                'motivation'=>$this->input->post('motivation'),
                                'author'=>$user,
                                'date'=>date('Y-m-d'),
                                'action'=>"",
                            );

                            $post = $this->material->saveLog($post, $id);
                            if(is_bool($post) And $post){
                                $this->lyst();
                            }else{
                                var_dump("echec denregistrement du log");
                            }


                    }
                    else
                    {
                        var_dump("echec de modification du materiel"); die(0);
                        //$this->modify($id);

                        $this->data['message'] = "echec de modification du materiel";
                        $this->render('admin/material/saveModify', 'Modifier un materiel');
                        /*
                        $this->form_validation->set_value('name', 'Ce matériel existe déjà');
                        $this->load->view('admin/headerAdmin');
                        $this->load->view('admin/nemu');
                        $this->load->view('admin/material/saveModify', $this->data);
                        $this->load->view('admin/footerAdmin');*/
                    }
                }else{
                    //var_dump("vous ne pouvez pas modifier ce materiel"); die(0);

                    $this->data['query'] = $this->material->selectAll($id, $idT)->result();

                    if(count($this->data['query']) == 1) {
                        $this->data['message'] = "vous ne pouvez pas modifier ce materiel";
                        $this->data['req'] = $this->data['query'];
                        $this->render('admin/material/save', 'Modifier un materiel');
                        /*
                        $this->load->view('admin/headerAdmin');
                        $this->load->view('admin/nemu');
                        $this->load->view('admin/material/saveModify', $this->data);
                        $this->load->view('admin/footerAdmin');*/
                    }


                }
            }
            else
            {
                //var_dump("dsfffffffffffgfdhjgyfkfyujdfh"); die(0);
                $this->modify($id, $idT);
                /*
                $this->load->view('admin/headerAdmin');
                $this->load->view('admin/nemu');
                $this->load->view('admin/material/saveModify', $this->data);
                $this->load->view('admin/footerAdmin');*/
            }
    }

    public function inventory($id=false){
        if($id == false){
            $inventory = $this->material->inventory()->result();
            $userName = array();

            for($i = 1; $i <= count($inventory); $i++){
                $tmp = $this->material->userName($inventory[$i-1]->user);
                $userName[$i-1] = $tmp->firstname .' '. $tmp->lastname;
            }
            $inventory = array("inventory"=>$inventory, "userName"=>$userName);
            $this->data['inventory'] = $inventory;
            $this->render('admin/material/inventory', 'Transaction du materiel MSA');
            /*$inventory = array("inventory"=>$inventory, "userName"=>$userName);
            $this->load->view('admin/headerAdmin');
            $this->load->view('admin/nemu');
            $this->load->view('admin/material/inventory', array('inventory'=>$inventory));
            $this->load->view('admin/footerAdmin');*/
        }
    }

    public  function materialAction($id = false, $mode = false){
        if(isset($id) and isset($mode) and !is_bool($id) and ! is_bool($mode)){
            if($mode == "add"){
                $this->data['query'] = $this->material->selectAll($id)->result();
                if(count($this->data['query']) == 1) {
                    //var_dump($this->data['query']->result());
                    $this->data['req'] = $this->data['query'];
                    $this->render('admin/material/addMaterial', 'Ajouter un materiel');
                }else{$this->lyst();}
            }elseif($mode == "remove"){
                $this->data['query'] = $this->material->selectAll($id)->result();
                if(count($this->data['query']) == 1) {
                    //var_dump($this->data['query']->result());
                    $this->data['req'] = $this->data['query'];
                    $this->render('admin/material/removeMaterial', 'Retirer un materiel');

                }else{
                    $this->lyst();
                }
            }
        }else{
            $this->lyst();
        }
    }

    public function printInventory(){
        $this->load->helper("html2pdf_helper");

        $inventory = $this->material->inventory()->result();
        if(empty($inventory)){
            $this->data['message'] = 'Aucune transaction trouvée';
            $this->lyst();
        }else{
            $userName = array();

            for($i = 1; $i <= count($inventory); $i++){
                $tmp = $this->material->userName($inventory[$i-1]->user);
                $userName[$i-1] = $tmp->firstname .' '. $tmp->lastname;
            }



            /*echo "
                    <page backtop='20mm' backleft='10mm' backright='10mm' backbottom='30mm'>

                        <table style='width: 100%; align-items: center; margin: auto'>
                            <tr>
                                <td><h1 align='center'>Inventaire du materiel MSA</h1></td>
                            </tr>
                        </table>


                    <table border='1' style='margin: auto; font-size: 11pt; border-collapse: collapse;'>
                        <thead>
                            <tr>
                                <th align=\"center\" class=\"text - center\">N&#176;</th>
                                <th align=\"center\">Nom</th>
                                <th align=\"center\">Conditionnemnt</th>
                                <th align=\"center\">Quantité</th>
                                <th align=\"center\">Type de transaction</th>
                                <th align=\"center\">Date de transaction</th>
                                <th align=\"center\">Transacteur</th>
                            </tr>
                        </thead>
                        <tbody>";
                            $k = 0;
                            for($i = 1; $i <= count($inventory['inventory']); $i++)
                            {
                                //var_dump($inventory[$i]->id);
                                echo '<tr><td class="text-center">' . ++$k . '</td>';
                                echo '<td align="center">' . $inventory['inventory'][$i-1]->name . '</td>';
                                echo '<td align="center">' . $inventory['inventory'][$i-1]->packaging . '</td>';
                                echo '<td align="center">' . $inventory['inventory'][$i-1]->qty . '</td>';
                                echo '<td align="center">' . $inventory['inventory'][$i-1]->transType . '</td>';
                                echo '<td align="center">'. $inventory['inventory'][$i-1]->transDate .'</td>';
                                echo '<td align="center">'. $inventory['userName'][$i-1] .'</td></tr>';
                            }
            echo "      </tbody>
                    </table>
                    </page>";*/
            $this->data['inventory'] = array("inventory"=>$inventory, "userName"=>$userName);
            $profil = $this->load->view('admin/material/printInventory', $this->data, TRUE);

            ob_start();

            echo $profil; //die(0);


            $content = ob_get_clean();

            try{
                $pdf = new HTML2PDF('P', 'A4', 'fr');
                $pdf->pdf->setDisplayMode('fullpage');
                $pdf->writeHTML($content);
                $pdf->Output('inventaire.pdf');
            }catch (HTML2PDF_exception $e){
                die($e);
            }
        }

    }

    public function printLyst(){
        $this->load->helper("html2pdf_helper");

        $material = $this->material->lyst()->result();
        if(empty($material)){
            $this->data['message'] = 'Aucun matériel trouvé';
            $this->lyst();
        }
        ob_start();
        echo "
            <style>
                table {margin: auto; margin-top: auto; font-size: 12pt; border-collapse: collapse; font-family: Helvetica;}

            </style>
            <div>
                 <h1>Liste du matériel</h1>
                <hr>
            </div>
            <table border='1'>
                    <thead>
                        <tr>
                            <th align='center' class=\"text-center\">N&#176;</th>
                            <th align='center'>Nom</th>
                            <th align='center'>Type de materiel</th>
                            <th align='center'>Conditionnement</th>
                            <th align='center'>Quantité</th>
                            <th align='center'>Plus</th>
                        </tr>
                    </thead>
                    <tbody>
        ";
                        $k = 0;
                        //var_dump($material);
                        for($i = 1; $i <= count($material); $i++)
                        {
                            //var_dump($material[$i]->id);
                            echo '<tr><td align=\'center\' class="text-center">' . ++$k . '</td>';
                            echo '<td align=\'center\'>' . $material[$i-1]->name . '</td>';
                            echo '<td align=\'center\'>' . $material[$i-1]->type . '</td>';
                            echo '<td align=\'center\'>' . $material[$i-1]->packaging . '</td>';
                            echo '<td align=\'center\'>'. $material[$i-1]->qty .'</td>';
                            echo '<td>
                                    <a href="modify/'.$material[$i-1]->id.'" data-toggle="tooltip" data-placement="top" title="modifier!" class="btn btn-primary"><i  class="fa fa-edit"></i></a>
                                    <a href="delete" data-toggle="tooltip" data-placement="top" title="modifier!" class="btn btn-primary"><i class="fa fa-trash"></i></a>
                                    <a href="'.base_url("admin/material/materialAction").'/'.$material[$i-1]->id.'/add" title="Ajouter du materiel!" class="btn btn-primary"><i class="fa fa-plus-square"></i></a>
                                    <a href="'.base_url("admin/material/materialAction").'/'.$material[$i-1]->id.'/remove" title="retirer du materiel!" class="btn btn-primary"><i class="fa fa-minus-square"></i></a>

                                  </td></tr>';
                        }
        echo "
                    </tbody>
             </table>
        ";
        $content = ob_get_clean();

        try{
            $pdf = new HTML2PDF('P', 'A4', 'fr');
            $pdf->pdf->setDisplayMode('fullpage');
            $pdf->writeHTML($content);
            $pdf->Output('liste.pdf');
        }catch (HTML2PDF_exception $e){
            die($e);
        }
    }

    public function printLysts(){
        $this->load->helper("html2pdf_helper");

        $this->data['material'] = $this->material->lyst()->result();

        if(empty($this->data['material'])){
            $this->data['message'] = 'Aucun matériel trouvé';
            $this->lyst();
        }else{
            $profil = $this->load->view('admin/material/printLyst', $this->data, TRUE);

            ob_start();
            echo $profil;

            $content = ob_get_clean();

            try{
                $pdf = new HTML2PDF('P', 'A4', 'fr');
                $pdf->pdf->setDisplayMode('fullpage');
                $pdf->writeHTML($content);
                $pdf->Output('liste.pdf');
            }catch (HTML2PDF_exception $e){
                die($e);
            }
        }


    }
}