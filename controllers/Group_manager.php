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
    return empty($str) ? array() : explode($delim, $str);
}

/**
 * \brief Group_Manager controller.
 *
 * A light wrapper for UU group management rules.
 */
class Group_Manager extends MY_Controller {

    protected $_groups;        /// `[ group... ]`.
    protected $_categories;    /// `[ category... ]`.
    protected $_subcategories; /// `category => [ subcategory... ]...`.

    protected function _getGroupData() {
        if (isset($this->_groups)) {
            return $this->_groups;
        }

        if ($this->rodsuser->getUserInfo()['type'] == 'rodsadmin') {
            $rulebody = <<<EORULE
rule {
        uuGetGroupData();
}
EORULE;
            $rule = new ProdsRule(
                $this->rodsuser->getRodsAccount(),
                $rulebody,
                array(),
                array(
                    'ruleExecOut'
                )
            );
	} else {
            $rulebody = <<<EORULE
rule {
        uuGetUserGroupData(*user, *zone);
}
EORULE;
            $rule = new ProdsRule(
                $this->rodsuser->getRodsAccount(),
                $rulebody,
                array(
                    '*user' => $this->rodsuser->getUserInfo()['name'],
                    '*zone' => $this->rodsuser->getUserInfo()['zone']
                ),
                array(
                    'ruleExecOut'
                )
            );
	}

        $result = $rule->execute();
        return json_decode($result['ruleExecOut']);
    }

    protected function _getCategories() {
        if (isset($this->_categories)) {
            return $this->_categories;
        } else {
            $ruleBody = <<<EORULE
rule {
	uuGroupGetCategories(*categoriesList);
	uuJoin(',', *categoriesList, *categories);
}
EORULE;
            $rule = new ProdsRule(
                $this->rodsuser->getRodsAccount(),
                $ruleBody,
                array(),
                array(
                    '*categories'
                )
            );
            $result = $rule->execute();
            return $this->_categories = explodeProperly(',', $result['*categories']);
        }
    }

    protected function _getSubcategories($category) {
        $categories = $this->_getCategories();
        if (!in_array($category, $categories))
            return array();

        if (isset($this->_subcategories[$category])) {
            return $this->_subcategories[$category];
        } else {
            $ruleBody = <<<EORULE
rule {
	uuGroupGetSubcategories(*category, *subcategoriesList);
	uuJoin(',', *subcategoriesList, *subcategories);
}
EORULE;
            $rule = new ProdsRule(
                $this->rodsuser->getRodsAccount(),
                $ruleBody,
                array(
                    '*category' => $category
                ),
                array(
                    '*subcategories'
                )
            );
            $result = $rule->execute();
            return $this->_subcategories[$category] = explodeProperly(',', $result['*subcategories']);
        }
    }

    protected function _findUsers($query) {
        $ruleBody = <<<EORULE
rule {
	uuFindUsers(*query, *userList);
	uuJoin(',', *userList, *users);
}
EORULE;
        $rule = new ProdsRule(
            $this->rodsuser->getRodsAccount(),
            $ruleBody,
            array(
                '*query' => $query,
            ),
            array(
                '*users',
            )
        );
        $result = $rule->execute();
        return explodeProperly(',', $result['*users']);
    }

    protected function _getGroupHierarchy() {
        $groups = $this->_getGroupData();
        $hierarchy = array();

        foreach ($groups as $group) {
            $group = (array) $group;
            // Check YoDa (sub)category
            if (!empty($group['category']) && !empty($group['subcategory'])) {
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
                    'description'         => (!empty($group['description'])         ? $group['description']         : null),
                    'data_classification' => (!empty($group['data_classification']) ? $group['data_classification'] : null),
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
                        return !(!empty($query) && strstr($val, $query) === FALSE);
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
                            return !(!empty($query) && strstr($val, $query) === FALSE);
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
        $ruleBody = <<<EORULE
rule {
	uuGroupAdd(*groupName, *category, *subcategory, *description, *dataClassification, *statusInt, *message);
	*status = str(*statusInt);
}
EORULE;
        $rule = new ProdsRule(
            $this->rodsuser->getRodsAccount(),
            $ruleBody,
            array(
                '*groupName'           => $this->input->post('group_name'),
                '*category'            => $this->input->post('group_category'),
                '*subcategory'         => $this->input->post('group_subcategory'),
                '*description'         => $this->input->post('group_description'),
                '*dataClassification'  => in_array('group_data_classification', array_keys($this->input->post()))
                                          ? $this->input->post('group_data_classification')
                                          : '', // Only research and intake groups will have a data classification parameter.
            ),
            array(
                '*status',
                '*message',
            )
        );
        $result = $rule->execute();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => (int)$result['*status'],
                'message' =>      $result['*message'],
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
            $ruleBody = <<<EORULE
rule {
	uuGroupModify(*groupName, *property, *value, *statusInt, *message);
	*status = str(*statusInt);
}
EORULE;
            $rule = new ProdsRule(
                $this->rodsuser->getRodsAccount(),
                $ruleBody,
                array(
                    '*groupName' => $this->input->post('group_name'),
                    '*property'  => $property,
                    '*value'     => $value,
                ),
                array(
                    '*status',
                    '*message',
                )
            );
            $result = $rule->execute();

            if ($result['*status'] > 0)
                break;
        }

        if (!count($toSet))
            $result = array(
                '*status'  => 0,
                '*message' => '',
            );

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => (int)$result['*status'],
                'message' =>      $result['*message'],
            )));
    }

    public function groupDelete() {
        $ruleBody = <<<EORULE
rule {
	uuGroupRemove(*groupName, *statusInt, *message);
	*status = str(*statusInt);
}
EORULE;
        $rule = new ProdsRule(
            $this->rodsuser->getRodsAccount(),
            $ruleBody,
            array(
                '*groupName' => $this->input->post('group_name'),
            ),
            array(
                '*status',
                '*message',
            )
        );
        $result = $rule->execute();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => (int)$result['*status'],
                'message' =>      $result['*message'],
            )));
    }

    public function userCreate() {
        $ruleBody = <<<EORULE
rule {
	uuGroupUserAdd(*groupName, *userName, *statusInt, *message);
	*status = str(*statusInt);
}
EORULE;
        $rule = new ProdsRule(
            $this->rodsuser->getRodsAccount(),
            $ruleBody,
            array(
                '*groupName' => $this->input->post('group_name'),
                '*userName'  => $this->input->post('user_name'),
            ),
            array(
                '*status',
                '*message',
            )
        );
        $result = $rule->execute();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => (int)$result['*status'],
                'message' =>      $result['*message'],
            )));
    }

    public function userUpdate() {
        $ruleBody = <<<EORULE
rule {
	uuGroupUserChangeRole(*groupName, *userName, *newRole, *statusInt, *message);
	*status = str(*statusInt);
}
EORULE;
        $rule = new ProdsRule(
            $this->rodsuser->getRodsAccount(),
            $ruleBody,
            array(
                '*groupName' => $this->input->post('group_name'),
                '*userName'  => $this->input->post('user_name'),
                '*newRole'   => $this->input->post('new_role'),
            ),
            array(
                '*status',
                '*message',
            )
        );
        $result = $rule->execute();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => (int)$result['*status'],
                'message' =>      $result['*message'],
            )));
    }

    public function userDelete() {
        $ruleBody = <<<EORULE
rule {
	uuGroupUserRemove(*groupName, *userName, *statusInt, *message);
	*status = str(*statusInt);
}
EORULE;
        $rule = new ProdsRule(
            $this->rodsuser->getRodsAccount(),
            $ruleBody,
            array(
                '*groupName' => $this->input->post('group_name'),
                '*userName'  => $this->input->post('user_name'),
            ),
            array(
                '*status',
                '*message',
            )
        );
        $result = $rule->execute();

        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode(array(
                'status'  => (int)$result['*status'],
                'message' =>      $result['*message'],
            )));
    }

    public function index() {
        $viewParams = array(
            'styleIncludes' => array('css/group-manager.css'),
            'scriptIncludes' => array('js/group-manager.js'),
            'activeModule'   => 'group-manager',
            'groupHierarchy'  => $this->_getGroupHierarchy(),
            'userType'        => $this->rodsuser->getUserInfo()['type'],
            'userZone'        => $this->rodsuser->getUserInfo()['zone'],
        );

        loadView('group-manager_index', $viewParams);
    }

    public function __construct() {
        parent::__construct();
    }
}
