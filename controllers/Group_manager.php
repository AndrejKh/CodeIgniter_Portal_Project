<?php

/**
 * \brief Like `explode()`, but return an empty list if the given string is empty.
 *
 * Isn't this how anyone would expect their explode() / split() to
 * behave?
 *
 * Parameters are the same as for explodeProperly().
 */
function explodeProperly($delim, $str) {
    return (!isset($str) || !strlen($str)) ? array() : explode($delim, $str);
}

/**
 * \brief Group_Manager controller.
 *
 * A light wrapper for UU group management rules.
 */
class Group_Manager extends MY_Controller {

    protected function _getGroupData() {
        if ($this->rodsuser->getUserInfo()['type'] === 'rodsadmin') {
            $result = $this->api->call('group_data');
        } else {
            $result = $this->api->call('group_data_filtered',
                                       ['user_name' => $this->rodsuser->getUserInfo()['name'],
                                        'zone_name' => $this->rodsuser->getUserInfo()['zone']]);
        }
        return $result->data;
    }

    protected function _getCategories() {

        return $this->api->call('group_categories')->data;
    }

    protected function _getSubcategories($category) {

        return $this->api->call('group_subcategories', ['category' => $category])->data;
    }

    protected function _findUsers($query) {
        $result = $this->api->call('group_search_users',
                                      ['pattern' => $query]);
        return $result->data;
    }

    protected function _getGroupHierarchy() {
        $groups = $this->_getGroupData();
        $hierarchy = array();

        foreach ($groups as $group) {
            $group = (array) $group;
            // Check YoDa (sub)category
            if (   isset($group['category'])    && strlen($group['category'])
                && isset($group['subcategory']) && strlen($group['subcategory'])) {

                // Group members
                $members = array();

                // Normal users
                foreach($group['members'] as $member) {
                    $members[$member] = array('access' => 'normal');
                }

                //Managers
                foreach($group['managers'] as $member) {
                    $members[$member] = array('access' => 'manager');
                }

                // Read users
                foreach($group['read'] as $member) {
                    $members[$member] = array('access' => 'reader');
                }

                $hierarchy[$group['category']][$group['subcategory']][$group['name']] = array(
                    'description'         => ((isset($group['description'])         && strlen($group['description']))
                                               ? $group['description']         : ''),
                    'data_classification' => ((isset($group['data_classification']) && strlen($group['data_classification']))
                                               ? $group['data_classification'] : null),
                    'members'             => $members,
                );
            }
        }

        return $hierarchy;
    }

    public function getCategories() {
        $query = $this->input->get('query');

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(
            // WTF PHP: json_encode randomly turns an array into a { "1": ... } object.
                array_values(
                    array_filter($this->_getCategories(), function($val) use($query) {
                        return !(isset($query) && strlen($query) && strstr($val, $query) === FALSE);
                    })
                )
            ));
    }

    public function getSubcategories() {
        $categories = $this->_getCategories();
        $category   = $this->input->get('category');
        $query      = $this->input->get('query');

        if (in_array($category, $categories)) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(
                    array_values(
                        array_filter($this->_getSubcategories($category), function($val) use($query) {
                            return !(isset($query) && strlen($query) && strstr($val, $query) === FALSE);
                        })
                    )
                ));
        } else {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(array()));
        }
    }

    public function getUsers() {
        $query = $this->input->get('query');

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(
                array_values($this->_findUsers($query))
            ));
    }

    public function groupCreate() {
        $result = $this->api->call('group_create',
                                   ['groupName'             => $this->input->post('group_name'),
                                    'category'              => $this->input->post('group_category'),
                                    'subcategory'           => $this->input->post('group_subcategory'),
                                    'description'           => $this->input->post('group_description'),
                                    'dataClassification'    =>  in_array('group_data_classification', array_keys($this->input->post()))
                                                                ? $this->input->post('group_data_classification')
                                                                : '', // Only research and intake groups will have a data classification parameter.
                                   ]);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => (int)$result->status,
                'message' =>      $result->status_info,
            )));
    }

    public function groupUpdate() {
        $toSet = array();
        foreach (array('description', 'data_classification', 'category', 'subcategory') as $property) {
            if (in_array('group_'.$property, array_keys($this->input->post())))
                $toSet[$property] = $this->input->post('group_'.$property);
        }

        $result = array();

        foreach ($toSet as $property => $value) {
            $result = $this->api->call('group_update',
                                       ['groupName'     => $this->input->post('group_name'),
                                        'propertyName'  => $property,
                                        'propertyValue' => $value]);

            if ($result->status > 0)
                break;
        }

        if (!count($toSet)) {
            $status         = 0;
            $status_info    = '';
        } else {
            $status         = $result->status;
            $status_info    = $result->status_info;
        }

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => (int)$status,
                'message' =>      $status_info,
            )));
    }

    public function groupDelete() {
        $result = $this->api->call('group_delete',
                                  ['groupName' => $this->input->post('group_name')]);
                                  
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => (int)$result->status,
                'message' =>      $result->status_info,
            )));
    }

    public function userCreate() {
        $result = $this->api->call('group_user_add',
                                   ['username'  => $this->input->post('user_name')
                                   ,'groupName' => $this->input->post('group_name')]);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => (int)$result->status,
                'message' =>      $result->status_info,
            )));
    }

    public function userUpdate() {
        $result = $this->api->call('group_user_update_role',
                                   ['username'  => $this->input->post('user_name')
                                   ,'groupName' => $this->input->post('group_name')
                                   ,'newRole'   => $this->input->post('new_role')]);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => (int)$result->status,
                'message' =>      $result->status_info,
            )));
    }

    public function userDelete() {
        $result = $this->api->call('group_remove_user_from_group',
                                   ['username'  =>$this->input->post('user_name')
                                   ,'groupName' => $this->input->post('group_name')]);

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => (int)$result->status,
                'message' =>      $result->status_info,
            )));
    }

    public function index() {
        $viewParams = array(
            'styleIncludes' => array(
                'lib/select2/select2.css',
                'lib/select2/select2-bootstrap.min.css',
                'css/group-manager.css'
            ),
            'scriptIncludes' => array(
                'lib/select2/select2.min.js',
                'js/group-manager.js'
            ),
            'activeModule'   => 'group-manager',
            'groupHierarchy'  => $this->_getGroupHierarchy(),
            'userType'        => $this->rodsuser->getUserInfo()['type'],
            'userZone'        => $this->rodsuser->getUserInfo()['zone'],
        );

        loadView('group-manager_index', $viewParams);
    }

    public function __construct() {
        parent::__construct();

        $this->load->library('api');
    }
}
