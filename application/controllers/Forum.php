<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Forum extends CI_Controller {

    protected $data, $menu;

    function __construct()
    {
        parent::__construct();
        $this->load->model('auth/auth_model', 'authM');
        $this->load->model('public/lesson_model', 'mLesson');
        $this->load->model('public/general_model', 'mGeneral');
        $this->load->model('public/Forum_model', 'mForum');
        updateVisit();

        $this->load->model('backfront/user_model', 'userM');
        
        $this->load->model('backfront/notifications_model', 'notification');
        $this->load->model('backfront/log_model', 'logM');
        $this->load->library('form_validation');
        $this->data['allReg'] = $this->mGeneral->getAllInscription();
        $this->data['allLes'] = $this->mGeneral->getAllLesson();
        $this->data['allMem'] = $this->mGeneral->getAllMember();

        $this->data['visits'] = $this->mGeneral->getAllVisits();
        $this->data['visitors'] = $this->mGeneral->getAllVisitors();
        $this->data['cLesson'] = $this->mLesson->getByType('cours')->result();
        $this->data['fLesson'] = $this->mLesson->getByType('filière')->result();
        $this->data['pLesson'] = $this->mLesson->getByType('promotion')->result();

    }

    public function index()
    {
        $this->meta = array(
            "description"=>"Toutes les discussions des inscrits à la plateforme MULTISOFT ACADEMY",
            "url"=>base_url('forum'),
            "image"=>img_url('logo/logo.png')
        );

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Liste des Forums"=>"",
        );


        $this->data['forums'] = $this->mForum->getForumAndCat()->result();

        $this->render("Liste des Forums");

    }

    public function categorie($permalink=null){
        if($permalink==null){
            show_404();
        }
        $catBean = $this->mForum->getCategoryInfo($permalink)->result();
        if(empty($catBean))
            msa_error("Désolé! Aucune catégorie de forum sous le nom <b>'$permalink'</b>");

        $catBean = $catBean[0];

        $this->form_validation->set_rules('titre', '"titre"', 'trim|required|min_length[9]|max_length[255]|encode_php_tags');
        $this->form_validation->set_rules('contenu', '"Contenu"', 'trim|required');

        if($this->form_validation->run())
        {
            $post = array(
                'title'=>$this->input->post('titre'),
                'content'=>$this->input->post('contenu'),
                'post_date'=>moment()->format('Y-m-d H:i:s'),
                'user'=>session_data('id'),
                'visible'=>'1',
                'category'=>$catBean->id,
            );

            $this->db->trans_begin();
            $ret = $this->mForum->addPost($post);

            if($ret)
            {
                $ret = $this->mForum->updPostNbr($catBean->id);
                $lastPost = $this->mForum->getLastPost()[0];
                //var_dump($lastPost);die();
                $this->notification->publish(array(
                    "sender"=>1,
                    "content"=>"Nouveau sujet << $lastPost->title >> ajouté dans le forum  par ".session_data('lastname')." dans la catégorie <b><< $catBean->name >></b>; peut-être voudriez-vous l'aider.",
                    "send_date"=>moment()->format('Y-m-d H:i:s'),
                    "target"=>1,
                    "promotion"=>0,
                    "url"=>base_url('forum/sujet/'.permalink($lastPost->title).'--'.$lastPost->id)
                ));
                $this->logM->save(array(
                    "motivation" => "",
                    "author" => session_data('id'),
                    "date" => moment()->format('Y-m-d H:i:s'),
                    "action" => "Ajout d'un sujet dans le forum << $lastPost->title >> dans la catégorie <b><< $catBean->name >></b>"
                ));
                if($ret)
                {
                    $this->db->trans_commit();
                    redirect('forum/categorie'.'/'.permalink($catBean->name));
                }
                else{
                    $this->db->trans_rollback();
                    msa_error("Désolé! Impossible d'enregistrer votre sujet pour l'instant. réessayer ultérieurement");
                }
            }
        }


        $this->meta = array(
            "description"=>"Liste des sujets dans la catégorie $catBean->name",
            "url"=>base_url("forum/categorie/$permalink"),
            "image"=>img_url('logo/logo.png')
        );

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Forum"=>base_url('forum'),
            "Catégorie : ".$catBean->name=>"",
        );

        $this->data['category'] = $catBean;
        $this->data['posts'] = $this->mForum->getPostsOfCat($catBean->id)->result();

        $this->render("Les sujets de la catégorie ".$catBean->name,'post-list');
    }

    public function solved($postId=null){
        if(session_data_isset('connect') and session_data('connect')){
            if($postId==null)
                show_404();

            $data = explode('--',$postId);
            if(count($data)!=2){
                show_404();
            }
            $postLabel = $data[0];
            if($postLabel!='post'){
                show_404();
            }
            $postId = intval($data[1]);
            $postBean = $this->mForum->getPostInfo($postId)->result();
            if(empty($postBean))
                show_404();

            $postBean = $postBean[0];
            if($postBean->userId != session_data('id'))
                msa_error('Désolé! vous ne pouvez pas modifier ce sujet.');

            if($this->mForum->solve($postId))
                redirect("forum/sujet".'/'.permalink($postBean->title).'--'.$postBean->id);
        }
        else{
            redirect('account/login');
        }


    }


    public function sujet($permalink=null){
        if($permalink==null){
            show_404();
        }
        $data = explode('--',$permalink);
        if(count($data)!=2){
            show_404();
        }
        $postId = intval($data[1]);
        $postName = $data[0];

        if(!is_integer($postId))
            show_404();


        $postBean = $this->mForum->getPostInfo($postId)->result();
        if(empty($postBean))
            msa_error("Désolé! Aucun sujet de forum sous le nom <b>'$postName'</b>");

        $postBean = $postBean[0];

        $this->form_validation->set_rules('contenu', '"Contenu"', 'trim|required');

        if($this->form_validation->run())
        {
            $post = array(
                'content'=>$this->input->post('contenu'),
                'post_date'=>moment()->format('Y-m-d H:i:s'),
                'user'=>session_data('id'),
                'visible'=>'1',
                'post'=>$postBean->id,
            );

           $this->db->trans_begin();
            $ret = $this->mForum->addComment($post);

            if($ret)
            {
                $ret = $this->mForum->updCommentNbr($postBean->id);
                $lastComment = $this->mForum->getLastComment()[0];
                //var_dump($lastPost);die();
                $this->notification->publish(array(
                    "sender"=>1,
                    "content"=>"Une réponse a été ajouté par ".session_data('lastname')." dans le sujet intitulé <b><< $postBean->title >></b>; Cliquez-ici pour la consulter.",
                    "send_date"=>moment()->format('Y-m-d H:i:s'),
                    "target"=>1,
                    "promotion"=>0,
                    "url"=>base_url('forum/sujet/'.permalink($postBean->title).'--'.$postBean->id.'#'.$lastComment->id)
                ));
                $this->logM->save(array(
                    "motivation" => "",
                    "author" => session_data('id'),
                    "date" => moment()->format('Y-m-d H:i:s'),
                    "action" => "Ajout d'une réponse au sujet << $postBean->title >> dans le forum."
                ));
                if($ret)
                {
                    $this->db->trans_commit();
                    redirect('forum/sujet'.'/'.permalink($postBean->title).'--'.$postBean->id);
                }
                else{
                    $this->db->trans_rollback();
                    msa_error("Désolé! Impossible d'enregistrer votre réponse pour l'instant. réessayer ultérieurement");
                }
            }
        }


        $this->meta = array(
            "description"=>"Liste des commentaires du post  $postBean->title",
            "url"=>base_url("forum/sujet/".permalink($postBean->title)."--".$postBean->id),
            "image"=>img_url('logo/logo.png')
        );

        $this->data ['breadcrumb'] = array(
            "Accueil" => base_url(),
            "Forum"=>base_url('forum'),
            "Sujet : ".$postBean->title=>"",
        );

        $this->data['post'] = $postBean;
        $this->data['comments'] = $this->mForum->getCommentsOfPost($postBean->id)->result();

        $this->render("Sujet : ".$postBean->title,'comment-list');
    }

    private function render($titre = NULL,$view=NULL)
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
            $this->data['view'] = "forum-list";
        }
        $this->load->view("public/forum-page-model", $this->data);
        $this->load->view('public/footer');
    }
}
