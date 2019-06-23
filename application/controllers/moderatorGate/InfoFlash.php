<?php

class InfoFlash extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        if(session_data('role')!=MODERATOR And in_array(MODERATOR, (array)session_data('roles'))){
            set_session_data(array('role'=>MODERATOR));
        };

        protected_session(array('','account/login'), MODERATOR);

        $this->load->model('backfront/flash_model', 'flashM');

        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
    }

    public function index()
    {
        $this->data['flashs'] = $this->flashM->getFlash();
        $this->renderGate('moderator/flash-info-list', 'Liste des infos flash');
    }

    public function formAdd()
    {
        $this->form_validation->set_rules('flash_content', "\"Contenue de l'info flash\"", 'trim|required|min_length[10]|encode_php_tags');

        if($this->form_validation->run())
        {
            if($this->flashM->setFlash(array('content'=>$this->input->post('flash_content'))))
            {
                set_flash_data(array('success', 'Enregistrement de l\'info réussie'));
                redirect('moderatorGate/infoFlash');
            }
        }
        $this->renderGate('moderator/form-add-flash-info', 'Enregistrer des infos flash');
    }

    public function formEdit()
    {
        if(is_numeric($this->uri->rsegment(3)) And count($flashs = $this->flashM->getFlash($this->uri->rsegment(3)))==1) {
            $this->form_validation->set_rules('flash_content', "\"Contenue de l'info flash\"", 'trim|required|min_length[10]|encode_php_tags');

            if($this->form_validation->run())
            {
                if($this->flashM->updateContent($this->input->post('flash_content'), $this->uri->rsegment(3)))
                {
                    set_flash_data(array('success', 'Modification de l\'info réussie'));
                    redirect('moderatorGate/infoFlash');
                }
            }
            $_POST['flash_content'] = $flashs[0]->content;
            $this->renderGate('moderator/form-add-flash-info', 'Modifier des infos flash');
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }

    public function updateState()
    {
        if($this->input->post('mode')=='js'){
            if($id = $this->input->post('unlock_id') And $this->flashM->updateState($id)){
                set_flash_data(array('success', 'Déblocage effectué'));
                echo "*0*Success";
                return true;
            }
            elseif($id = $this->input->post('lock_id') And $this->flashM->updateState($id, false)){
                set_flash_data(array('success', 'Blocage effectué'));
                echo "*0*Success";
                return true;
            }
            set_flash_data(array('alert', 'Une erreur uest servenue lors de l\'execution de la requete'));
            echo "*1*Erreur";
            return false;
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }
}