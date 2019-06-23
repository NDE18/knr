<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Errors extends CI_Controller{

    protected $data = array();

   function __construct()
   {
       parent::__construct();
   }

    public function run()
    {
        var_dump($this->uri->segment(3));
    }
}