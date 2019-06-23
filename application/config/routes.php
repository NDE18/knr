<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'Frontpage';
$route['404_override'] = '';
$route['translate_uri_dashes'] = true;
$route['admin'] = "admin/home";

$route['nouvelles'] = "News/index";
$route['nouvelles/page/(:num)'] = "News/all/$1";
$route['nouvelles/page'] = "News/all/1";
$route['nouvelles/(:any)'] = "News/view/$1";

$route['evenements'] = "Events/index";
$route['evenements/page/(:num)'] = "Events/all/$1";
$route['evenements/page'] = "Events/all/1";
$route['evenements/(:any)'] = "Events/view/$1";

$route['emplois-de-temps'] = "Emplois_de_temps/index";

$route['plannings'] = "Examination/planning";

$route['search'] = "Search";

$route['resultats'] = "Examination";
$route['resultats/(:any)/(:any)'] = "Examination/results/$1/$2";
$route['resultats/(:any)/(:any)/(:any)'] = "Examination/results/$1/$2/$3";

$route['requetes'] = "request";

$route['notifications'] = "Notifications";

$route['enseignements'] = "Enseignements/index";
$route['enseignements/([a-z]+)'] = "Enseignements/category/$1";
$route['enseignements/(:any)'] = "Enseignements/view/$1";


$route['account'] = "Account/index";


$route['memberGate'] = "memberGate/Home";


$route['studentGate'] = "studentGate/Home";


$route['trainerGate'] = "trainerGate/Home";


$route['moderatorGate'] = "moderatorGate/Home";


$route['forum'] = "Forum/index";
$route['newsletter'] = "Newsletter";
$route['barbillard'] = "Barbillard";
//$route['forum/cat'] = "Forum/index";

$route['(:any)'] = 'Pages/render/$1';