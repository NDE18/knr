<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Harrys Crosswell
 * Date: 14/07/2017
 * Time: 17:20
 */
class Promotion extends CI_Controller
{
    private $data, $menu;

    public function __construct()
    {
        parent::__construct();


        $this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));
        protected_session(array('','admin/auth'),array(ADMIN,MANAGER));

        $this->load->model('admin/notification_model', 'notif');
        $this->menu['notif'] = $this->notif->newNotif();
        $this->load->model('admin/promotion_model', 'promotion');
        $this->load->model('admin/student_model', 'student');
        $this->load->helper('html2pdf_helper');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->all();
    }

    public function all()
    {
        $this->data['allPromo']=$this->promotion->getList();
        $this->render('admin/promotion/list', 'Liste des vagues');
    }

    public function printStudents($id, $list="")
    {
        if ($list=="") {
            $this->data['allPromoUsers'] = $this->promotion->printStudent($id);
            $this->render('admin/promotion/studentList', 'Vague ' . $this->data['allPromoUsers'][0]['code']);
        }
    }

    public function changePromo($pcode="", $uid="")
    {
        if (!empty($_POST))
        {
                $postOk=$this->promotion->changePromo($_POST['code'], $_POST['newPromo'], $_POST['id']);
                if(is_bool($postOk) and $postOk)
                {
                    redirect('promotion/printStudents/'.$_POST['newPromo']);
                } else {
                    echo "Echec";
                }
        } else
        {
            $this->data['promoInfo']=$this->promotion->getPromoInfo($pcode);
            $val = new Student_model();
            $this->data['user']=$val->getUser($uid);
            $this->data['pcode']=$pcode;
            $this->data['uid']=$uid;
            $this->render('admin/promotion/changePromo', 'Changement de vague');
        }
    }

    public function lock($pid)
    {
        $postOk=$this->promotion->lock($pid);
        if($postOk!=null)
        {
            if(is_integer($postOk))
            {
                $this->data['status']=$postOk;
                if ($postOk==1)
                {
                    set_flash_data(array("success","<b>Succès ! </b> La vague a été lancée. "));
                } else if ($postOk==3)
                {
                    set_flash_data(array("success","<b>Succès ! </b> La vague a été suspendue. "));
                } else
                {
                    set_flash_data(array("success","<b>Succès ! </b> La vague a été relancée. "));
                }
            } else
            {
                $this->data['status']=-1;
                $this->data['message']="<b>Echec : </b>".$postOk;
            }
        } else
        {
            set_flash_data(array("error","L'opération n'a pu se faire."));
        }
        redirect("admin/promotion");
    }

    public function endPromo($pid)
    {
        $post=$this->promotion->endPromo($pid);
        if(is_bool($post) and $post)
        {
            set_flash_data(array("success","<b>Succès ! </b> La vague a été achevée. "));
            redirect("admin/promotion/");
        } else
        {
            $this->data['status']=-1;
            $this->data['message']=$post;
            $this->showlist();
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