<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Event extends MY_Controller {

    protected $data, $menu;


    function __construct()
    {
        parent::__construct();

        if(session_data('role')!=MODERATOR And in_array(MODERATOR, (array)session_data('roles'))){
            set_session_data(array('role'=>MODERATOR));
        };

        protected_session(array('','account/login'), MODERATOR);

        $this->load->model('backfront/events_model', 'eventsM');

        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->data['events'] = $this->eventsM->getAllEvents();
        $this->renderGate('moderator/event-list', 'Liste des évènements');
    }

    public function formAdd()
    {
        $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
        $this->form_validation->set_rules('start_date', 'date de début', 'trim|required|regex_match[/^[\d]{1,2}\/[\d]{1,2}\/[\d]{4} [\d]{1,2}:[\d]{1,2}/]');
        $this->form_validation->set_rules('end_date', 'date de fin', 'trim|regex_match[/^[\d]{1,2}\/[\d]{1,2}\/[\d]{4} [\d]{1,2}:[\d]{1,2}/]');
        $this->form_validation->set_rules('text', 'Contenu', 'trim|required|min_length[3]');
        $this->form_validation->set_rules('title', 'Titre', 'trim|required|min_length[3]|encode_php_tags');

        if($this->form_validation->run()) {
            $data['start_date'] = $this->date_FrToEn($this->input->post('start_date'));

            if($this->input->post('end_date'))
                $data['end_date'] = $this->date_FrToEn($this->input->post('end_date'));

            $data['content'] = $this->input->post('text');
            $data['title'] = $this->input->post('title');

            if(!($data['start_date'] = moment($data['start_date']))) {
                $n = $this->count($this->data['message']);
                $this->data['message'][$n]['class'] = 'alert-danger';
                $this->data['message'][$n]['msg'] = 'La date de début est incorrect.';
            }

            if(isset($data['end_date']) And !($data['end_date'] = moment($data['end_date']))) {
                $n = $this->count($this->data['message']);
                $this->data['message'][$n]['class'] = 'alert-danger';
                $this->data['message'][$n]['msg'] = 'La date de fin est incorrect.';
            }

            if(!isset($this->data['message'])) {
                if($data['start_date']->fromNow()->getSeconds() > 0) {
                    $n = $this->count($this->data['message']);
                    $this->data['message'][$n]['class'] = 'alert-danger';
                    $this->data['message'][$n]['msg'] = 'La date de début doit être supérieur à aujourd\'huit.';
                }

                if(isset($data['end_date'])) {
                    if ($data['end_date']->fromNow()->getSeconds() > 0) {
                        $n = $this->count($this->data['message']);
                        $this->data['message'][$n]['class'] = 'alert-danger';
                        $this->data['message'][$n]['msg'] = 'La date de fin doit être supérieur à aujourd\'huit.';
                    }

                    if ($data['end_date']->from($data['start_date'])->getSeconds() >= 0) {
                        $n = $this->count($this->data['message']);
                        $this->data['message'][$n]['class'] = 'alert-danger';
                        $this->data['message'][$n]['msg'] = 'La date de fin doit être strictement supérieur à la date de début.';
                    }
                }

                if(!isset($this->data['message'])) {
                    $data['start_date'] = $data['start_date']->format(NO_TZ_MYSQL);

                    if(isset($data['end_date']))
                        $data['end_date'] = $data['end_date']->format(NO_TZ_MYSQL);

                    if ($this->eventsM->setEvents($data)) {
                        set_flash_data(array('success', ucfirst('évènement ajouté!')));
                        redirect('moderatorGate/event');
                    }
                    else {
                        $n = $this->count($this->data['message']);
                        $this->data['message'][$n]['class'] = 'alert-danger';
                        $this->data['message'][$n]['msg'] = 'Une erreur est survénue lors de l\'enregistrement.';
                    }
                }
            }
            else {
                $n = $this->count($this->data['message']);
                $this->data['message'][$n]['class'] = 'alert-info';
                $this->data['message'][$n]['msg'] = 'Exemple de date correcte: '.moment()->format('d/m/Y H:i:s');
            }
        }

        $this->renderGate('moderator/form-add-events', 'Ajouter des évènements');
    }

    public function formEdit()
    {
        if(is_numeric($this->uri->rsegment(3)) And count($events = $this->eventsM->getEvents($this->uri->rsegment(3)))==1) {
            $this->form_validation->set_error_delimiters('<p class="form_erreur text-danger small">', '<p>');
            $this->form_validation->set_rules('start_date', 'date de début', 'trim|required|regex_match[/^[\d]{1,2}\/[\d]{1,2}\/[\d]{4} [\d]{1,2}:[\d]{1,2}/]');
            $this->form_validation->set_rules('end_date', 'date de fin', 'trim|regex_match[/^[\d]{1,2}\/[\d]{1,2}\/[\d]{4} [\d]{1,2}:[\d]{1,2}/]');
            $this->form_validation->set_rules('text', 'Contenu', 'trim|required|min_length[3]');
            $this->form_validation->set_rules('title', 'Titre', 'trim|required|min_length[3]|encode_php_tags');

            $events = $events[0];
            $post = array('title'=>$events->title, 'text'=>$events->content, 'start_date'=>moment($events->start_date)->format(NO_TZ_FR), 'end_date'=>($events->end_date And moment($events->end_date))?moment($events->end_date)->format(NO_TZ_FR):'');

            if(moment($events->start_date)->fromNow()->getSeconds()>=0) {
                set_flash_data(array('error', 'Impossible de modifier un évènement déjà passé!'));
                redirect('moderatorGate/event');
            }elseif ($this->form_validation->run()) {
                $data['end_date'] = ($this->input->post('end_date'))?$this->date_FrToEn($this->input->post('end_date')):false;
                $data['content'] = $this->input->post('text');
                $data['title'] = $this->input->post('title');
                $data['start_date'] = $this->date_FrToEn($this->input->post('start_date'));

                if (!($data['start_date'] = moment($data['start_date']))) {
                    $n = $this->count($this->data['message']);
                    $this->data['message'][$n]['class'] = 'alert-danger';
                    $this->data['message'][$n]['msg'] = 'La date de début est incorrect.';
                }

                if (isset($data['end_date']) And $data['end_date'] And !($data['end_date'] = moment($data['end_date']))) {
                    $n = $this->count($this->data['message']);
                    $this->data['message'][$n]['class'] = 'alert-danger';
                    $this->data['message'][$n]['msg'] = 'La date de fin est incorrect.';
                }

                if (!isset($this->data['message'])) {
                    if ($data['start_date']->fromNow()->getSeconds() > 0) {
                        $n = $this->count($this->data['message']);
                        $this->data['message'][$n]['class'] = 'alert-danger';
                        $this->data['message'][$n]['msg'] = 'La date de début doit être supérieur à aujourd\'huit.';
                    }

                    if (isset($data['end_date']) And $data['end_date']) {
                        if ($data['end_date']->fromNow()->getSeconds() > 0) {
                            $n = $this->count($this->data['message']);
                            $this->data['message'][$n]['class'] = 'alert-danger';
                            $this->data['message'][$n]['msg'] = 'La date de fin doit être supérieur à aujourd\'huit.';
                        }

                        if ($data['end_date']->from($data['start_date'])->getSeconds() >= 0) {
                            $n = $this->count($this->data['message']);
                            $this->data['message'][$n]['class'] = 'alert-danger';
                            $this->data['message'][$n]['msg'] = 'La date de fin doit être strictement supérieur à la date de début.';
                        }
                    }

                    if (!isset($this->data['message'])) {
                        $data['start_date'] = $data['start_date']->format(NO_TZ_MYSQL);

                        if (isset($data['end_date']) And $data['end_date'])
                            $data['end_date'] = $data['end_date']->format(NO_TZ_MYSQL);

                        if($post['title'] == $data['title'] And $post['text'] == $data['content'] And $this->date_FrToEn($post['start_date']) == $data['start_date'] And ((!$post['end_date'] And !isset($data['end_date'])) Or $this->date_FrToEn($post['end_date'])==$this->date_FrToEn($data['end_date'])))
                            redirect('moderatorGate/event');

                        if ($this->eventsM->updateEvents($data, $events->id)) {
                            set_flash_data(array('success', ucfirst('évènement modifié!')));
                            redirect('moderatorGate/event');
                        }
                        else {
                            $n = $this->count($this->data['message']);
                            $this->data['message'][$n]['class'] = 'alert-danger';
                            $this->data['message'][$n]['msg'] = 'Une erreur est survénue lors de la modification.';
                        }
                    }
                } else {
                    $n = $this->count($this->data['message']);
                    $this->data['message'][$n]['class'] = 'alert-info';
                    $this->data['message'][$n]['msg'] = 'Exemple de date correcte: ' . moment()->format(NO_TZ_FR);
                }
            }


            $_POST = array('title'=>$events->title, 'text'=>$events->content, 'start_date'=>moment($events->start_date)->format(NO_TZ_FR_SECS), 'end_date'=>($events->end_date And moment($events->end_date))?moment($events->end_date)->format(NO_TZ_FR_SECS):'');
            $this->renderGate('moderator/form-edit-events', 'Modifier un évènement');
        }
        else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }

    public function active()
    {
        $this->form_validation->set_rules('aid', '', 'trim|is_numeric');
        $this->form_validation->set_rules('bid', '', 'trim|is_numeric');

        if ($this->form_validation->run()) {
            if ($this->input->post('aid')) {
                if (!$this->eventsM->updateState((int)$this->input->post('aid'))) {
                    set_flash_data(array('error', 'Une erreur est survenue lors de l\'activation!'));
                }else{
                    set_flash_data(array('success', 'l\'activation éffectué'));
                }
            } elseif ($this->input->post('bid')) {
                if (!$this->eventsM->updateState((int)$this->input->post('bid'), false)) {
                    set_flash_data(array('error', 'Une erreur est survenue lors du blocage!'));
                }else{
                    set_flash_data(array('success', 'Blocage éffectué'));
                }
            }
            redirect('moderatorGate/event');
        } else {
            show_error('La page demandé n\'existe pas!', 404, "Oops, Erreur 404");
        }
    }

    private function date_FrToEn($date)
    {
        if(!$date) return '';
        $date = explode(' ', $date);
        $time = explode(':', $date[1]);
        $date = explode('/', $date[0]);
        foreach ($time as $index => $item) {
            $time[$index] = str_pad($item, 2, '0', STR_PAD_LEFT);
        }

        foreach ($date as $index => $item) {
            $date[$index] = str_pad($item, 2, '0', STR_PAD_LEFT);
        }

        return implode(' ', array(implode('-', array_reverse($date)), implode(':', $time)));
    }
    
    private function count($array = array())
    {
        return (isset($array) And is_array($array))? count($array) : 0;
    }   
}
