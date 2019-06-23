<?php

/**
 * 
 */
class Modify_lesson extends CI_Controller
{
	
	public function __construct()
	{
		parent::__construct();
		$this->load->model('auth/auth_model', 'authM');

        if(!session_data('connect'))
            $this->authM->auth(false,get_cookie('multisoft'));


        $this->load->helper('html');
        $this->load->helper('text');

	}

	private function render($view, $titre = NULL, array $data)
    {
        $this->load->view('e_views/e_admin/headerAdmin', array('titre'=>$titre));
        $this->load->view('e_views/e_admin/menu');
        $this->load->view($view, $data);
        $this->load->view('e_views/e_admin/footerAdmin');
    }

	public function index(){
		$this->load->library('table');

		$this->db->select('*');
		$this->db->from('lesson');
		$query = $this->db->get();

		$lesson = $query;

		//$_SESSION['data'] = $data;

		$this->render('e_views/e_admin/modify_lesson', 'Manage lesson' , array('lesson' => $lesson) );

		// $this->load->view('modify_lesson',array('lesson' => $lesson));
	}

	public function details($id){

		$this->db->select('*');
		$this->db->from('lesson');
		$this->db->where('id='.$id);
		$query = $this->db->get();

		$lesson = $query->row();
		$_SESSION['lesson'] = $lesson;

		$this->db->from('e_chapter');
		$this->db->where('id_lesson',$id);
		$query = $this->db->get();

		$chapter = $query;
		$_SESSION['chapter'] = $chapter->result_object();

		//$_SESSION['data'] = $data;

		$this->render('e_views/e_admin/details', 'Lesson details' , array('lesson' => $lesson , 'chapter' => $chapter) );

		// $this->load->view('details',array('lesson' => $lesson , 'chapter' => $chapter));
	}

	public function add_chapter(){ 
		if (isset($_POST['save'])) {
			$this->form_validation->set_rules('num_chap','Chapter\'Number' , 'required|greater_than_equal_to[0]|callback_chap_check');
			$this->form_validation->set_rules('title_chap','Chapter\'Name' , 'required|min_length[5]' );
			$this->form_validation->set_rules('content','Chapter\'Content' ,'required|min_length[20]' );
			if ($this->form_validation->run() == TRUE) {
				$data = array(
						'id_lesson' => $_POST['id_lesson'],
						'content' => $_POST['content'],
						'title_chap' => $_POST['title_chap'],
						'num_chap' => $_POST['num_chap'],
						'date' => date('Y-m-d h:i:s'),
						'last_modify' => date('Y-m-d h:i:s'),
						'id_user' => session_data('id')
					);
				if($this->db->insert('e_chapter', $data)){
					$this->session->set_flashdata('info', '<span class="glyphicon glyphicon-saved"></span> Chapter successfully saved to the lesson');
					redirect('e_controllers/e_admin/modify_lesson/details/'.$_POST['id_lesson'],'refresh');
				}else{
					echo "mauvais";
				}
			}else{
				$this->db->from('lesson');
				$this->db->where('id',$_POST['id_lesson']);
				$query = $this->db->get();
				$lesson = $query->row();

				$this->db->from('e_chapter');
				$this->db->where('id_lesson',$_POST['id_lesson']);
				$chapter = $this->db->get();
				// $chapter = $_SESSION['chapter'];
				// $lesson = $_SESSION['lesson'];

				$this->render('e_views/e_admin/details', 'Add chapter->Lesson' , array('lesson' => $lesson , 'chapter' => $chapter) );
				
				// $this->load->view('details',array('lesson' => $lesson , 'chapter' => $chapter));
			}
		}
	}

	public function chap_check($str)
        {
        	$this->db->select('num_chap');
        	$this->db->from('e_chapter');
        	$this->db->where('id_lesson' , $_POST['id_lesson']);
        	$query = $this->db->get();
        	$str =''.$str.'';
        	$num_chap = array();
        	foreach ($query->result() as $key ) {
        		$num_chap[] = $key->num_chap;
        	}
                if (in_array($str, $num_chap))
                {
                        $this->form_validation->set_message('chap_check', 'This Chapter number are already used...Choose another!!');
                        return FALSE;
                }
                else
                {
                        return TRUE;
                }
        }

	public function modify(){
		if (isset($_POST['modify'])) {
			$this->form_validation->set_rules('num_chap','Chapter\'Number','required|greater_than_equal_to[0]');
			$this->form_validation->set_rules('title_chap','Chapter\'Name','required|min_length[5]');
			$this->form_validation->set_rules('content','Chapter\'Content','required|min_length[20]');
			if ($this->form_validation->run() == TRUE) {
				$data = array(
						'id_lesson' => $_POST['id_lesson'],
						'content' => $_POST['content'],
						'title_chap' => $_POST['title_chap'],
						'num_chap' => $_POST['num_chap'],
						'last_modify' => date('Y-m-d h:i:s')
					);
				$this->db->where('id_chap' , $_POST['id_chap']);
				if( $this->db->update('e_chapter', $data) ) {
					$this->session->set_flashdata('info', '<span class="glyphicon glyphicon-ok" ></span> Chapter successfully UPDATE/MODIFY to this lesson');
					redirect('e_controllers/e_admin/modify_lesson/details/'.$_POST['id_lesson'] , 'refresh');
				}
			}else{
				$this->details($_POST['id_lesson']);
			}
		}

	}

	public function delete($id_lesson , $id_chap){
			
		$this->db->where('id_chap' , $id_chap);
		if($this->db->delete('e_chapter')){
			$this->session->set_flashdata('info', 'Chapter successfully DELETE to this lesson');
			redirect('e_controllers/e_admin/modify_lesson/details/'.$id_lesson );
		}
	}

	public function activate($id,$id_chap,$status){
		$this->db->update('e_chapter' , array('status' => $status , 'last_modify' => date('Y-m-d h:i:s') ) , 'id_chap ='.$id_chap );
		redirect('e_controllers/e_admin/modify_lesson/details/'.$id);
	}


	
}