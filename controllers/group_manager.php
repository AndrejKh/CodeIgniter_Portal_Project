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

	protected function _getUserGroups() {
		if (isset($this->_groups)) {
			return $this->_groups;
		} else {
			if ($this->rodsuser->getUserInfo()['type'] == 'rodsadmin') {
				$ruleBody = <<<EORULE
rule {
	uuGetAllGroups(*groupList);
	uuJoin(',', *groupList, *groups);
}
EORULE;
			} else {
				$ruleBody = <<<EORULE
rule {
	uuGroupMemberships(*user, *groupList);
	uuJoin(',', *groupList, *groups);
}
EORULE;
			}
			$rule = new ProdsRule(
				$this->rodsuser->getRodsAccount(),
				$ruleBody,
				array(
					'*user' => $this->rodsuser->getUsername()
				),
				array(
					'*groups'
				)
			);
			$result = $rule->execute();
			return $this->_groups = explodeProperly(',', $result['*groups']);
		}
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

	protected function _getGroupMembers($groupName) {
		$ruleBody = <<<EORULE
rule {
	uuGroupGetMembers(*groupName, true, true, *memberList);
	uuJoin(',', *memberList, *members);
}
EORULE;
		$rule = new ProdsRule(
			$this->rodsuser->getRodsAccount(),
			$ruleBody,
			array(
				'*groupName' => $groupName,
			),
			array(
				'*members',
			)
		);
		$result = $rule->execute();
		$members = array();
		foreach (explodeProperly(',', $result['*members']) as $memberString) {
			list($type, $name) = explode(':', $memberString);
			$types = array('r' => 'reader',
			               'n' => 'normal',
			               'm' => 'manager');
			$members[$name] = array('access' => $types[$type]);
		}
		return $members;
	}

	protected function _getGroupProperties($groupName) {
		$ruleBody = <<<EORULE
rule {
	uuGroupGetCategory(*groupName, *category, *subcategory);
	uuGroupGetDescription(*groupName, *description);
}
EORULE;
		$rule = new ProdsRule(
			$this->rodsuser->getRodsAccount(),
			$ruleBody,
			array(
				'*groupName' => $groupName,
			),
			array(
				'*category',
				'*subcategory',
				'*description',
			)
		);
		$result = $rule->execute();

		return array(
			'category'    => $result['*category'],
			'subcategory' => $result['*subcategory'],
			'description' => $result['*description'],
		);
	}

	protected function _getGroupHierarchy() {
		$groups = $this->_getUserGroups();

		$hierarchy = array();

		foreach ($groups as $groupName) {
			$properties = $this->_getGroupProperties($groupName);
			if (!empty($properties['category']) && !empty($properties['subcategory'])) {
				$hierarchy[$properties['category']][$properties['subcategory']][$groupName] = array(
					'description' => $properties['description'],
					'members'     => $this->_getGroupMembers($groupName),
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
				array_values(
					array_filter($this->_findUsers($query), function($val) use($query) {
						return !(!empty($query) && strstr($val, $query) === FALSE);
					})
				)
			));
	}

	public function groupCreate() {
		$ruleBody = <<<EORULE
rule {
	uuGroupAdd(*groupName, *category, *subcategory, *description, *statusInt, *message);
	*status = str(*statusInt);
}
EORULE;
		$rule = new ProdsRule(
			$this->rodsuser->getRodsAccount(),
			$ruleBody,
			array(
				'*groupName'   => $this->input->post('group_name'),
				'*category'    => $this->input->post('group_category'),
				'*subcategory' => $this->input->post('group_subcategory'),
				'*description' => $this->input->post('group_description'),
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
		foreach (array('description', 'category', 'subcategory') as $property) {
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

		$this->load->view('common-start', array(
			 'styleIncludes' => array('css/group-manager.css'),
			'scriptIncludes' => array('js/group-manager.js'),
			'activeModule'   => 'group-manager',
			'user' => array(
				'username' => $this->rodsuser->getUsername(),
			),
		));
		$this->load->view('group-manager_index', array(
			'groupHierarchy'  => $this->_getGroupHierarchy(),
			'userType'        => $this->rodsuser->getUserInfo()['type'],
			'userZone'        => $this->rodsuser->getUserInfo()['zone'],
		));
		$this->load->view('common-end');
	}

	public function __construct() {
		parent::__construct();
	}
}
