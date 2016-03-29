<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
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
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

$route['default_controller'] = 'group-manager';

$route['group-manager'] 				= "group-manager/group_manager";

// NOTE: 'group-manager' in the route name is the module name.
//       The controller name is omitted from the path.
$route['group-manager/get-categories']    = "group-manager/group_manager/getCategories";
$route['group-manager/get-subcategories'] = "group-manager/group_manager/getSubcategories";
$route['group-manager/get-users']         = "group-manager/group_manager/getUsers";
$route['group-manager/group-create']      = "group-manager/group_manager/groupCreate";
$route['group-manager/group-update']      = "group-manager/group_manager/groupUpdate";
$route['group-manager/group-delete']      = "group-manager/group_manager/groupDelete";
$route['group-manager/user-create']       = "group-manager/group_manager/userCreate";
$route['group-manager/user-update']       = "group-manager/group_manager/userUpdate";
$route['group-manager/user-delete']       = "group-manager/group_manager/userDelete";

/* End of file routes.php */
/* Location: ./application/config/routes.php */
