<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Document extends CI_Controller
{
    protected $data, $menu;

    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');


        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));

        $this->load->library('form_validation');
        $this->load->model('admin/document_model', 'document');
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
        $this->all();
    }

    /**
     * Formulaire d'upload de document
     */
    public function formUpload()
    {
        if(isset($_POST['send']))
        {

            $this->data['files'] = $_FILES;
            $this->data['number'] = count($_FILES);
            $this->data['set_error'] = array();
            $this->data['set_success'] = array();
            $upload_error = '';

            $this->load->config('uploads', TRUE);
            $config = $this->config->item('documents', 'uploads');

            foreach($_FILES as $name => $file){
                $nb = count($_FILES[$name]['name']);

                if(isset($_FILES[$name]['name']))
                    $config['file_name'] = permalink(pathinfo($_FILES[$name]['name'], PATHINFO_FILENAME));

                $this->load->library('upload', $config);
                if (!$this->upload->do_upload($name)) {
                    $val = trim(($upload_error)? preg_replace('/'.str_replace('</', '<\/', $upload_error).'/', '', $this->upload->display_errors(), 1) : $this->upload->display_errors());
                    $this->data['set_error'][count($this->data['set_error'])] = 'Fichier N&#176;' . (count($this->data['set_error'])+1) . ': ' . $file['name'] . ' ' . $val;
                    $upload_error = $this->upload->display_errors();
                } else {
                    $val = array(
                        'name' => $this->upload->data()['client_name'],
                        'path' => 'assets/uploads' . explode('assets/uploads', $this->upload->data()['full_path'])[1],
                        'user' => $_SESSION['id'],
                        'code' => 'DOC' . date('dmy') . str_pad(count($this->document->getAll()), 4, '0', STR_PAD_LEFT),
                        'post_date' => moment()->format('Y-m-d H:i:s'),
                        'download_nbr' => 0,
                    );

                    if (!$this->document->save($val)) {
                        $this->data['set_error'][count($this->data['set_error'])] = 'Fichier N&#176;' . (count($this->data['set_error'])+1) . ': ' . $file['name'] . 'Echec d\'upload';
                    }
                }
            }


            if(count($this->data['set_error']) == 0){
                set_flash_data(array('success', 'Tous les fichiers ont bien été uploadés'));
                redirect('admin/document/all');
            }
        }

        $this->render('admin/document/form-upload', 'Uploader des documents');
    }

    /**
     * Liste de tous les documents uploadés
     */
    public function all()
    {
        $this->data['document'] = $this->document->getAll();

        $this->render('admin/document/list', 'Liste des documents');
    }

    /**
     * Fenetre de publication d'un document
     */

    public function modalPublishContent()
    {
        if($this->input->post('mode')=='js')
        {
            $this->data['document'] = $this->document->getAll($this->input->post('doc'));
            $this->data['roles'] = $this->document->getRoles();
            $this->data['vague'] = $this->document->getVagues();
            $this->load->view('admin/document/modalPublishContent', $this->data);
        }
        else
        {
            show_404();
        }
    }

    /**
     * Fonction de publication d'un document aux utilisateurs
     */

    public function publish()
    {
        if($this->input->post('pub_role') Or $this->input->post('pub_role') == 0) {
            $role = ['Le <b>membre</b>', 'L\'<b>apprenant</b>', 'Le <b>formateur</b>', 'Le <b>modérateur</b>', 'La <b>manageur</b>', 'L\'<b>adminstrateur</b>'];
            $doc = json_decode($this->input->post('doc'));

            $data = array(
                'sender' => session_data('id'),
                'content' => $role[session_data('role')-1].' <b>'.ucfirst(mb_strtolower(session_data('firstname'), 'UTF-8')).'</b> vous a envoyé ce document',
                'target' => $this->input->post('pub_role'),
                'url' => base_url($doc->path)
            );
            if ($data['target'] == 2) {
                $data['promotion'] = $this->input->post('pub_vague');
            }
            if($this->document->publish($data, $doc->id)){
                set_flash_data(array('success', 'Votre publication a bien été éffectué'));
            }else{
                set_flash_data(array('error', 'Votre publication n\'a pas été éffectué'));
            }
            redirect('admin/document/all');
        }
        show_404();
    }
}