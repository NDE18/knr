<?php

class forums extends MY_Controller
{
    function __construct()
    {
        parent::__construct();

        if(session_data('role')!=MODERATOR And in_array(MODERATOR, (array)session_data('roles'))){
            set_session_data(array('role'=>MODERATOR));
        };

        protected_session(array('','account/login'), MODERATOR);

        $this->load->model('backfront/forum_model', 'forum');
    }

    public function index()
    {
        $this->data['forum'] = $this->forum->getAllForum();
        $this->renderGate('moderator/forum-list', 'Liste des forums');
    }

    public function single()
    {
        /**
         * $this->uri->rsegment(3) id du forum
         * $this->uri->rsegment(5) id de la categorie
         * $this->uri->rsegment(7) id du post
        */
        if(is_numeric($this->uri->rsegment(3))) {
            if(is_null($this->uri->rsegment(4))) {
                $this->data['category'] = $this->forum->getAllCategory($this->uri->rsegment(3));
                $this->renderGate('moderator/category-list', 'Liste des catégories');
            }
            elseif(strcmp(mb_strtolower($this->uri->rsegment(4)), 'category')==0 And is_numeric($this->uri->rsegment(5))) {
                if(is_null($this->uri->rsegment(6))) {
                    $this->data['post'] = $this->forum->getAllPost($this->uri->rsegment(5));
                    if(!($this->data['title'] = $this->forum->getCategory($this->uri->rsegment(5))[0]->name))
                    {
                        $this->data['title'] = 'undefine';
                    }
                    $this->renderGate('moderator/post-list', 'Liste des posts de la categorie: '.$this->data['title']);
                }
                elseif(strcmp(mb_strtolower($this->uri->rsegment(6)), 'post')==0 And is_numeric($this->uri->rsegment(7))) {
                    if(is_null($this->uri->rsegment(8))) {
                        $this->data['comment'] = $this->forum->getAllComment($this->uri->rsegment(7));
                        if(!($this->data['title'] = $this->forum->getPost($this->uri->rsegment(7))[0]->title))
                        {
                            $this->data['title'] = 'undefine';
                        }
                        $this->renderGate('moderator/comment-list', 'Liste des commentaires du post: '.$this->data['title']);
                    }
                }
            }else{
                show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
            }
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }

    public function forumFormAdd()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_error_delimiters('<p class="form_erreur w3-text-red small">', '<p>');
        $this->form_validation->set_rules('forum', 'forum', 'trim|required|min_length[3]');

        if($this->form_validation->run()){
            if(!$this->forum->forumExist($this->input->post('forum'))) {
                if ($this->forum->saveForum(array('name'=>$this->input->post('forum')))) {
                    set_flash_data(array('success', 'Le forum a bien été enregistré!'));
                    redirect('moderatorGate/forums');
                }
                else{
                    set_flash_data(array('error', 'Erreur lors de l\'enregistrement du forum <br>"<b>'.$this->input->post('forum').'</b>"!'));
                }
            }
            else{
                set_flash_data(array('error', 'Le forum "<b>'.$this->input->post('forum').'</b>" existe déjà!'));
            }
        }

        $_POST = array('title'=>'enregistrer un forum');
        $this->renderGate('moderator/form-add-forum', 'Enregistrer un forum');
    }

    public function forumFormEdit()
    {
        if(is_numeric($this->uri->rsegment(3)) And ($forum = $this->forum->getForum($this->uri->rsegment(3)))) {
            $this->load->library('form_validation');
            $this->form_validation->set_error_delimiters('<p class="form_erreur w3-text-red small">', '<p>');
            $this->form_validation->set_rules('forum', 'forum', 'trim|required|min_length[3]|');

            if($this->form_validation->run()){
                if(!(bool)count($this->forum->getForum("name = '".$this->input->post('forum')."'", true))) {
                    if ($this->forum->updateForum(array('name' => $this->input->post('forum')), $this->uri->rsegment(3))) {
                        set_flash_data(array('success', 'Le forum a bien été modifié!'));
                        if($this->input->post('mode')=='js') {
                            return true;
                        }
                        redirect('moderatorGate/forums');
                    }
                    else{
                        set_flash_data(array('error', 'Erreur lors de l\'enregistrement du forum <br>"<b>'.$this->input->post('forum').'</b>"!'));
                    }
                }
                else{
                    set_flash_data(array('error', 'Le forum <b>'.$this->input->post('forum').'</b> existe déjà!'));
                }
            }

            if($this->input->post('mode')=='js'){
                return false;
            }

            $_POST = array('forum'=>$forum[0]->name, 'title'=>"modifier un forum");
            $this->renderGate('moderator/form-add-forum', 'Modifiction du forum: '.$forum[0]->name);
        }
        else {
            if($this->input->post('mode')=='js'){
                return false;
            }
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }

    public function categoryFormAdd()
    {
        if(is_numeric($this->uri->rsegment(3))) {
            $this->load->library('form_validation');
            $this->form_validation->set_error_delimiters('<p class="form_erreur w3-text-red small">', '<p>');
            $this->form_validation->set_rules('category', 'categorie', 'trim|required|min_length[3]');
            $this->data['error'] = '';

            if ($this->form_validation->run()) {
                $category = explode(';', $this->input->post('category'));
                foreach ($category as $key=>$item) {
                    $category[$key] = trim($item);
                    if(!(bool)preg_match('/^[a-zA-Z0-9]+/', $category[$key])) {
                        $this->data['error'] .= '<p class="form_erreur w3-text-red small"> Veuiller respecter les formatage ('.$category[$key].') <p>';
                    }
                }

                if(!($this->data['error']) And $this->forum->saveCategory($category, $this->uri->rsegment(3))) {
                    set_flash_data(array('success', 'La(Les) catégorie(s) a(ont) bien été enregistré(s)!'));
                    redirect('moderatorGate/forums/single/' . $this->uri->rsegment(3));
                }
            }

            $this->renderGate('moderator/form-add-category', 'Enregistrer des categories');
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }

    public function categoryFormEdit()
    {
        if(is_numeric($this->uri->rsegment(3)) And ($category = $this->forum->getCategory($this->uri->rsegment(3)))) {
            $this->load->library('form_validation');
            $this->form_validation->set_error_delimiters('<p class="form_erreur w3-text-red small">', '<p>');
            $this->form_validation->set_rules('category', 'categorie', 'trim|required|min_length[3]|');
            $this->data['error'] = '';

            if ($this->form_validation->run()) {
                if(!(bool)count($this->forum->getCategory("permalink = '".permalink($this->input->post('forum'))."'", true))) {
                    if ($this->forum->updateCategory(array('name'=>$this->input->post('category')), $this->uri->rsegment(3))) {
                        set_flash_data(array('success', 'La(Les) catégorie(s) a(ont) bien été enregistré(s)!'));
                        if($this->input->post('mode')=='js') {
                            return true;
                        }
                        redirect('moderatorGate/forums/single/' . $category[0]->forum);
                    }
                    else{
                        set_flash_data(array('error', 'Erreur lors de l\'enregistrement de la categorie <br>"<b>'.$this->input->post('forum').'</b>"!'));
                    }
                }
                else{
                    set_flash_data(array('error', 'La categorie <b>'.$this->input->post('forum').'</b> existe déjà!'));
                }
            }

            $_POST['category'] = $category[0]->name;
            $this->renderGate('moderator/form-edit-category', 'Modifier la categorie: '.$category[0]->name);
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }

    public function delete()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('type', 'type', 'trim|required');
        $this->form_validation->set_rules('id', 'type', 'trim|required');
        if($this->form_validation->run())
        {
            if(strtolower($this->input->post('type'))=='post' And $this->forum->UpdateStatusPost($this->input->post('id'), false)) {
                echo '*0*';
            }
            elseif(strtolower($this->input->post('type'))=='comment' And $this->forum->UpdateStatusComment($this->input->post('id'), false)) {
                echo '*0*';
            }
            else {
                echo '*1*';
            }
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }

    public function activated()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules('type', 'type', 'trim|required');
        $this->form_validation->set_rules('id', 'type', 'trim|required');
        if($this->form_validation->run())
        {
            if(strtolower($this->input->post('type'))=='post' And $this->forum->UpdateStatusPost($this->input->post('id'))) {
                echo '*0*';
            }
            elseif(strtolower($this->input->post('type'))=='comment' And $this->forum->UpdateStatusComment($this->input->post('id'))) {
                echo '*0*';
            }
            else {
                echo '*1*';
            }
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }
}