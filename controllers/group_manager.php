<?php

class Group_Manager extends CI_Controller {

	protected $_groups;        /// `[ group... ]`.
	protected $_categories;    /// `[ category... ]`.
	protected $_subcategories; /// `category => [ subcategory... ]...`.

	protected function _getUserGroups() {
		if (isset($this->_groups)) {
			return $this->_groups;
		} else {
			$ruleBody = <<<EORULE
rule {
	uuGroupMemberships(*user, *groups);
}
EORULE;
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
			return $this->_groups = explode(',', $result['*groups']);
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
			return $this->_categories = explode(',', $result['*categories']);
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
			return $this->_subcategories[$category] = explode(',', $result['*subcategories']);
		}
	}

	protected function _getUsers() {
		if (isset($this->_categories)) {
			return $this->_categories;
		} else {
			$ruleBody = <<<EORULE
rule {
	uuGetUsers(*userList);
	uuJoin(',', *userList, *users);
}
EORULE;
			$rule = new ProdsRule(
				$this->rodsuser->getRodsAccount(),
				$ruleBody,
				array(),
				array(
					'*users'
				)
			);
			$result = $rule->execute();
			return $this->_categories = explode(',', $result['*users']);
		}
	}

	protected function _findUsers($query) {
		if (isset($this->_categories)) {
			return $this->_categories;
		} else {
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
			return $this->_categories = explode(',', $result['*users']);
		}
	}

	protected function _getGroupMembers($groupName) {
		$ruleBody = <<<EORULE
rule {
	uuGroupGetMembers(*groupName, *memberList);
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
		return explode(',', $result['*members']);
	}

	protected function _getGroupProperties($groupName) {
		$ruleBody = <<<EORULE
rule {
	uuGroupGetCategory(*groupName, *category, *subcategory);
	uuGroupGetDescription(*groupName, *description);
	uuGroupGetManagers(*groupName, *managerList);
	uuJoin(',', *managerList, *managers);
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
				'*managers',
			)
		);
		$result = $rule->execute();

		return array(
			'category'    => $result['*category'],
			'subcategory' => $result['*subcategory'],
			'description' => $result['*description'],
			'managers'    => explode(',', $result['*managers']),
		);
	}

	protected function _getGroupHierarchy() {
		$groups = $this->_getUserGroups();

		$hierarchy = array();

		foreach ($groups as $groupName) {
			$properties = $this->_getGroupProperties($groupName);
			if (!empty($properties['category']) && !empty($properties['subcategory'])) {

				$members = array();
				foreach ($this->_getGroupMembers($groupName) as $member)
					// If only PHP's array_map worked properly with maps...
					$members[$member] = array(
						'isManager' => in_array($member, $properties['managers'])
					);

				$hierarchy[$properties['category']][$properties['subcategory']][$groupName] = array(
					'description' => $properties['description'],
					'members'     => $members,
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
					//array_filter($this->_getUsers(), function($val) use($query) {
					array_filter($this->_findUsers($query), function($val) use($query) {
						return !(!empty($query) && strstr($val, $query) === FALSE);
					})
				)
			));
	}

	public function groupCreate() {
		$ruleBody = <<<EORULE
rule {
	uuGroupAdd(*groupName, *statusInt, *message);
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

		// It seems we can't be sure that this rule is executed AFTER the group
		// has been created and the user has been added to it.
		// Wait 200ms to give iRODS some time to process the previous rule.
		usleep(200000);

		$ruleBody = <<<EORULE
rule {
	uuGroupModify(*groupName, "category",    *category, *statusInt, *message);
	if (*statusInt == 0) {
		uuGroupModify(*groupName, "subcategory", *subcategory, *statusInt, *message);
		if (*statusInt == 0) {
			uuGroupModify(*groupName, "description", *description, *statusInt, *message);
		}
	}
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
		$categories = $this->_getCategories();
		$groups = $this->_getUserGroups();

		$this->load->view('common-start', array(
			 'style_includes' => array('css/group-manager.css'),
			'script_includes' => array('js/group-manager.js'),
			'active_module'   => 'group-manager',
			'user' => array(
				'username' => $this->rodsuser->getUsername(),
			),
		));
		$this->load->view('group-manager_index', [
			'groupHierarchy' => $this->_getGroupHierarchy(),
		]);
		$this->load->view('common-end');
	}

	public function __construct() {
		parent::__construct();
		$this->load->library('prods');
		$this->load->library('session');
		$this->load->model('rodsuser');

		if (
			   $this->session->userdata('username') !== false
			&& $this->session->userdata('password') !== false
		) {
			$this->rodsuser->getRodsAccount(
				$this->session->userdata('username'),
				$this->session->userdata('password')
			);
		}
		if (!$this->rodsuser->isLoggedIn())
			redirect('user/login');
	}
}
