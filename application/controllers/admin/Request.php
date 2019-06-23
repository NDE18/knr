<?php

/**
 * Created by PhpStorm.
 * User: Harrys Crosswell
 * Date: 31/08/2017
 * Time: 07:02
 */
class Request extends  CI_Controller
{
    protected  $data, $menu;

    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));

        $this->load->model('backfront/message_model', 'mMessage');
        $this->load->model('backfront/notifications_model', 'notification');
        $this->load->model('backfront/log_model', 'logM');
        $this->load->model('backfront/user_model', 'userM');
        $this->load->model('admin/notification_model', 'notif');
        $this->menu['notif'] = $this->notif->newNotif();
        $this->load->library('form_validation');
    }

    public function index(){
        $this->data['requetes'] = $this->mMessage->getRequets();

        $this->render('admin/request/list','Liste des requêtes académiques');

    }

    public function response($id=null){
        if($id==null){
            show_404();
        }

        $request = $this->mMessage->get($id,MSG_REQUETE);
        if(!empty($request)){
            $request = $request[0];

            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('contenu', '"contenu"', 'trim|required|min_length[20]|encode_php_tags');

            if($this->form_validation->run()){
                $response = $this->input->post('contenu');

                $this->db->trans_begin();
                $ret = $this->mMessage->responseRequest($id, $response);
                if($ret){
                    $this->notification->publish(array(
                        "sender"=>1,
                        "content"=>"Votre requête <br> <b><< ".$request->subject." >></b> a été Traitée;",
                        "send_date"=>moment()->format('Y-m-d H:i:s'),
                        "target"=>$request->userId,
                        "promotion"=>-1,
                        "url"=>base_url('requetes')
                    ));
                    $this->logM->save(array(
                        "motivation" => "",
                        "author" => session_data('id'),
                        "date" => moment()->format('Y-m-d H:i:s'),
                        "action" => "Traitement de la requête  <b><< ".$this->input->post('sujet')." >></b>"
                    ));

                    if($this->db->trans_status()){
                        $this->db->trans_commit();
                        set_flash_data(array('success',"Le traitement a été effectuée avec succès"));
                        redirect("admin/request");
                    }
                    else{
                        set_flash_data(array('error',"Le traitement n'a pas été enregistrée. Réessayer ultérieurement."));
                        redirect("studentGate#request");
                    }
                }
                else{

                }
            }

            $this->data['request'] = $request;

            $this->render('admin/request/respond-form',"Traitement de requêtes académiques");


        }
        else{
            show_404();
        }
    }

    private function render($view, $titre = NULL)
    {
        $this->load->view('admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('admin/menu', $this->menu);
        $this->load->view($view, $this->data);
        $this->load->view('admin/footerAdmin');
    }


}