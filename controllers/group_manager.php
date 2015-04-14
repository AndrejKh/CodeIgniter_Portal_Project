<?php

class Group_Manager extends CI_Controller {

	protected $_account;
	protected $_groups;        /// `[ group... ]`.
	protected $_categories;    /// `[ category... ]`.
	protected $_subcategories; /// `category => [ subcategory... ]...`.

	protected function _getAccount() {
		if (isset($this->_account))
			return $this->_account;
		else
			return $this->_account = new RODSAccount($this->_getIrodsHost(), $this->_getIrodsPort(), $this->_getUserName(), $this->_getPassword());
	}

	protected function _getIrodsHost() {
		return 'pax-vm-uu';
	}
	protected function _getIrodsPort() {
		return 1247;
	}
	protected function _getUserName() {
		return 'chrisdm';
	}
	protected function _getPassword() {
		return 'chris';
	}
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
				$this->_getAccount(),
				$ruleBody,
				array(
					'*user' => $this->_getUserName()
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
				$this->_getAccount(),
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
				$this->_getAccount(),
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
				$this->_getAccount(),
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
				$this->_getAccount(),
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

		// TODO: Use query string in rule call for efficiency.

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

	public function groupAdd() {
		redirect('group-manager');
	}

	public function index() {
		$categories = $this->_getCategories();
		$groups = $this->_getUserGroups();

		$this->load->view('common-start', [
			 'style_includes' => ['group-manager.css'],
			'script_includes' => ['group-manager.js'],
		]);
		$this->load->view('common-header', [
			'active_module' => 'group-manager',
		]);
		$this->load->view('group-manager_index', [
			'user'   => array(
				'userName' => $this->_getUserName(),
			),
			'groups' => $groups,
		]);
		$this->load->view('common-footer');
		$this->load->view('common-end');
	}

	public function __construct() {
		parent::__construct();
		$this->load->library('prods');
	}
}
