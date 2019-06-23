<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

//Config uploads images
$config['images'] = array(
    'upload_path' => './assets/uploads/images',
    'allowed_types' => 'jpg|jpeg|png',
    'max_size' => 1024,
    'max_width' => 1024,
    'max_height' => 1024
);

//Config uploads avatars
$config['avatars'] = array(
    'upload_path'   => './assets/uploads/images/avatars',
    'allowed_types' => 'jpg|jpeg|png',
    'overwrite' => true,
    'max_size'  => 1024*3
);

//Config uploads photos de l'utilisateur
$config['photos'] = array(
    'upload_path'   => './assets/uploads/images/photos',
    'allowed_types' => 'jpg|jpeg|png',
    'overwrite' => true,
    'max_size'  => 1024*3
);

//Config uploads photo du slides
$config['slides'] = array(
    'upload_path'   => './assets/uploads/images',
    'allowed_types' => 'jpg|jpeg|png',
    'max_size'  => 1024*3
);

//Config uploads documents
$config['documents'] = array(
    'upload_path' => './assets/uploads/documents',
    'allowed_types' => 'pdf|doc|docx',
    'max_size' => 1024
);