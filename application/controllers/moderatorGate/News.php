<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class News extends MY_Controller {

    protected $data, $menu;

    function __construct()
    {
        parent::__construct();

        if(session_data('role')!=MODERATOR And in_array(MODERATOR, (array)session_data('roles'))){
            set_session_data(array('role'=>MODERATOR));
        };

        protected_session(array('','account/login'), MODERATOR);

        $this->load->model('backfront/news_model', 'newsM');

        $this->load->library('form_validation');
    }


    public function index()
    {
        $this->data['news'] = $this->newsM->getAllNews();
        $this->renderGate('moderator/news-list', 'Liste des nouvelles');
    }

    public function active()
    {
        $this->form_validation->set_rules('aid', '', 'trim|is_numeric');
        $this->form_validation->set_rules('bid', '', 'trim|is_numeric');
        $this->form_validation->set_rules('d_show', '', 'trim|is_numeric');
        $this->form_validation->set_rules('show', '', 'trim|is_numeric');

        if($this->form_validation->run())
        {
            if($this->input->post('aid')) {
                if(!$this->newsM->updateState((int)$this->input->post('aid'))) {
                    set_flash_data(array('error', 'Une erreur est survenue lors de l\'activation!'));
                }
            }elseif($this->input->post('bid')) {
                if (!$this->newsM->updateState((int)$this->input->post('bid'), false)) {
                    set_flash_data(array('error', 'Une erreur est survenue lors du blocage!'));
                }
            }elseif($this->input->post('d_show')){
                if (!$this->newsM->updateShowSlider((int)$this->input->post('d_show'), false)) {
                    set_flash_data(array('error', 'Une erreur est survenue lors du blocage pour le slider!'));
                }
            }elseif($this->input->post('show')){
                if (!$this->newsM->updateShowSlider((int)$this->input->post('show'))) {
                    set_flash_data(array('error', 'Une erreur est survenue lors de l\'activation pour le slider!'));
                }
            }
            redirect('moderatorGate/news');
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }

    public function formAdd()
    {
        $this->form_validation->set_rules('titre', 'titre', 'trim|min_length[4]');
        $this->form_validation->set_rules('contenu', 'contenu', 'trim|min_length[4]');

        if($this->form_validation->run()) {
            $data['thumbnail'] = '';
            $data['content'] = '';
            $data['title'] = '';
            $data['show_in_slider'] = ($this->input->post('show_in_slide'))?1:0;
            if($this->input->post('titre'))
                $data['title'] = $this->input->post('titre');

            if ($this->input->post('text'))
                $data['content'] = $this->input->post('text');

            if(!isset($_FILES['image']) And !isset($data['content'])) {
                $this->data['message']['class'] = 'alert-danger';
                $this->data['message']['msg'] = "Au moins un des champs ('image' et 'contenu') doit être rempli";
                set_flash_data(array('error', $this->data['message']['msg']));
            }
            else {
                if (isset($_FILES['image']) And $_FILES['image']['error']!=4) {
                    $this->load->config('uploads', TRUE);
                    $config = $this->config->item('slides', 'uploads');
                    $config['file_name'] = permalink('img nouvelle 0');
                    $config['upload_path'] .= '/news';
                    $this->load->library('upload', $config);

                    if ($this->upload->do_upload('image')) {
                        $data['thumbnail'] = 'assets/uploads' . explode('assets/uploads', $this->upload->data()['full_path'])[1];
                    } else {
                        $this->data['message']['class'] = 'alert-danger';
                        $this->data['message']['msg'] = $this->upload->display_errors();
                        set_flash_data(array('error', $this->data['message']['msg']));
                    }
                }

                if(!isset($this->data['message'])) {
                    if ($this->newsM->setNews($data)) {
                        $this->data['message']['class'] = 'alert-success';
                        $this->data['message']['msg'] = 'Enregistrement de la nouvelle réussi';
                        set_flash_data(array('success', $this->data['message']['msg']));
                        redirect(strtolower(role_tostring(session_data('role'), 'en')) . 'Gate/news');
                    } else {
                        $this->data['message']['class'] = 'alert-danger';
                        $this->data['message']['msg'] = 'Erreur lors de l\'enregistrement de la nouvelle';
                        set_flash_data(array('error', $this->data['message']['msg']));
                    }
                }
            }
        }

        $this->renderGate('moderator/form-add-news', 'Ajouter des nouvelles');
    }

    public function formEdit()
    {
        if(is_numeric($this->uri->rsegment(3)) And count($news = $this->newsM->getNews($this->uri->rsegment(3))) == 1) {
            $news = $news[0];
            $this->form_validation->set_rules('titre', 'titre', 'trim|min_length[4]|max_length[255]');
            $this->form_validation->set_rules('text', 'text', 'trim|min_length[4]');

            if ($this->form_validation->run()) {
                $data['thumbnail'] = ((int)$this->input->post('has_change'))?'':$news->thumbnail;
                $data['content'] = '';
                $data['title'] = '';
                $data['show_in_slider'] = 0;
                if ($this->input->post('titre'))
                    $data['title'] = $this->input->post('titre');

                if ($this->input->post('text'))
                    $data['content'] = $this->input->post('text');

                if (!isset($_FILES['image']) And !isset($data['content'])) {
                    $this->data['message']['class'] = 'alert-danger';
                    $this->data['message']['msg'] = "Au moins un des champs ('image' et 'contenu') doit être rempli";
                    set_flash_data(array('error', $this->data['message']['msg']));
                } else {
                    if (isset($_FILES['image']) And $_FILES['image']['error']!=4) {
                        $this->load->config('uploads', TRUE);
                        $config = $this->config->item('slides', 'uploads');

                        if($news->thumbnail) {
                            $config['overwrite'] = true;
                            $config['file_name'] = explode('.', array_reverse(explode('/', $news->thumbnail))[0])[0];
                        }
                        else {
                            $config['file_name'] = permalink('img nouvelle 0');
                        }
                        //var_dump($config,$_FILES['image']); die();

                        $config['upload_path'] .= '/news';
                        $this->load->library('upload', $config);

                        if ($this->upload->do_upload('image')) {
                            $data['thumbnail'] = 'assets/uploads' . explode('assets/uploads', $this->upload->data()['full_path'])[1];
                        } else {
                            $this->data['message']['class'] = 'alert-danger';
                            $this->data['message']['msg'] = $this->upload->display_errors();
                            set_flash_data(array('error', $this->data['message']['msg']));
                        }
                    }

                    if (!isset($this->data['message'])) {
                        $data['show_in_slider'] = ($this->input->post('show_in_slide'))?1:0;
                        if ($this->newsM->updateNews($data, $this->uri->rsegment(3))) {
                            $this->data['message']['class'] = 'alert-success';
                            $this->data['message']['msg'] = 'Modification de la nouvelle réussi';
                            set_flash_data(array('success', $this->data['message']['msg']));
                            redirect(strtolower(role_tostring(session_data('role'), 'en')) . 'Gate/news');
                        }
                        else {
                            $this->data['message']['class'] = 'alert-danger';
                            $this->data['message']['msg'] = 'Erreur lors de l\'enregistrement de la nouvelle';
                            set_flash_data(array('error', $this->data['message']['msg']));
                        }
                    }
                }
            }

            $_POST['titre'] = $news->title;
            $_POST['image'] = $news->thumbnail;
            $_POST['text']  = $news->content;
            $this->renderGate('moderator/form-edit-news', 'Modification des nouvelles');
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }
}
