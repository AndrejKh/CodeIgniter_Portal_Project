<?php

$categories = array(
	'uu-studies' => array(
		'youthcohort' => array(
			'grp-some-group1' => array(
				'description' => 'Some group description 1',
				'users' => array(
					'mgr1-2' => array(
						'isManager' => true,
					),
					'usr1-1' => array(
						'isManager' => false,
					),
					'chrisdm' => array(
						'isManager' => true,
					),
					'mgr1-1' => array(
						'isManager' => true,
					),
					'usr1-2' => array(
						'isManager' => false,
					),
				),
			),
			'grp-some-group2' => array(
				'description' => 'Some group description 2',
				'users' => array(
					'mgr2-1' => array(
						'isManager' => true,
					),
					'mgr2-2' => array(
						'isManager' => true,
					),
					'usr2-1' => array(
						'isManager' => false,
					),
					'usr2-2' => array(
						'isManager' => false,
					),
				),
			),
			'grp-some-group3' => array(
				'description' => 'Some group description 3',
				'users' => array(
					'mgr3-1' => array(
						'isManager' => true,
					),
					'mgr3-2' => array(
						'isManager' => true,
					),
					'usr3-1' => array(
						'isManager' => false,
					),
					'usr3-2' => array(
						'isManager' => false,
					),
				),
			),
		),
		'vijfmaanden' => array(
			'grp-some-group4' => array(
				'description' => 'Some group description 4',
				'users' => array(
					'mgr4-1' => array(
						'isManager' => true,
					),
					'mgr4-2' => array(
						'isManager' => true,
					),
					'usr4-1' => array(
						'isManager' => false,
					),
					'usr4-2' => array(
						'isManager' => false,
					),
				),
			),
		),
	),
	'food-studies' => array(
		'pizza' => array(
			'grp-some-group5' => array(
				'description' => 'Some group description 5',
				'users' => array(
					'mgr5-1' => array(
						'isManager' => true,
					),
					'mgr5-2' => array(
						'isManager' => true,
					),
					'usr5-1' => array(
						'isManager' => false,
					),
					'usr5-2' => array(
						'isManager' => false,
					),
				),
			),
		),
	),
	'system' => array(
		'privileges' => array(
			'priv-groupadd' => array(
				'description' => 'Allows users to create user groups',
				'users' => array(
					'someone@somewhere.nl' => array(
						'isManager' => true,
					),
					'chrisdm' => array(
						'isManager' => false,
					),
				),
			),
		),
	),
);

?>
<script>
// {{{

$(function(){
	"use strict";

	window.YodaPortal = window.YodaPortal || {
		extend: function(path, namespace) {
			(function extendPart(root, name, namespace) {
				var parts = name.split('.');
				if (parts.length > 1) {
					var dir = parts.shift();

					if (root.hasOwnProperty(dir) && typeof(root[dir]) !== 'object' || Array.isArray(root[dir]))
						delete root[dir];

					if (!root.hasOwnProperty(dir))
						root[dir] = { };

					extendPart(root[dir], parts.join('.'), namespace);
				} else {
					// Replace members of different types.
					if (
						root.hasOwnProperty(name)
						&& (
							typeof(root[name]) !== typeof(namespace)
							|| Array.isArray(root[name]) !== Array.isArray(namespace)
						)
					)
						delete root[name];

					if (root.hasOwnProperty(name) && typeof(namespace) === 'object') {
						if (Array.isArray(namespace)) {
							root[name] = root[name].concat(namespace);
						} else {
							for (var property in namespace) {
								if (root[name].hasOwnProperty(property)) {
									extendPart(root[name], property, namespace[property]);
								} else {
									root[name][property] = namespace[property];
								}
							}
						}
					} else {
						root[name] = namespace;
					}
				}
			})(this, path, namespace);

			return this;
		}
	};

	window.YodaPortal.extend('user', {
		userName: '<?=$user['userName']?>',
	});

	YodaPortal.extend('storage', {
		prefix: 'yoda-portal.group-manager',
		session: {
			get:    function(key)        { return sessionStorage.getItem(   YodaPortal.storage.prefix + '.' + key);        },
			set:    function(key, value) { return sessionStorage.setItem(   YodaPortal.storage.prefix + '.' + key, value); },
			remove: function(key)        {        sessionStorage.removeItem(YodaPortal.storage.prefix + '.' + key);        }
		},
		local: {
			get:    function(key)        { return   localStorage.getItem(   YodaPortal.storage.prefix + '.' + key);        },
			set:    function(key, value) { return   localStorage.setItem(   YodaPortal.storage.prefix + '.' + key, value); },
			remove: function(key)        {          localStorage.removeItem(YodaPortal.storage.prefix + '.' + key);        }
		}
	});

	YodaPortal.extend('escapeQuotes', function(str) {
		return str.replace(/\\/g, '\\\\').replace(/("|')/g, '\\$1');
	});
});

$(function() {
	"use strict";

	var categories = <?= json_encode($categories) ?>;

	var groups = (function(categories) {
		var groups = [];
		for (var categoryName in categories)
			for (var subcategoryName in categories[categoryName])
				for (var groupName in categories[categoryName][subcategoryName])
					groups[groupName] = {
						category:    categoryName,
						subcategory: subcategoryName,
						name:        groupName,
						description: categories[categoryName][subcategoryName][groupName].description,
						users:       categories[categoryName][subcategoryName][groupName].users
					};
		return groups;
	})(categories);

	/**
	 * \brief Check if a user is a member of the given group.
	 *
	 * \param groupName
	 * \param userName
	 *
	 * \return
	 */
	function isMember(groupName, userName) {
		return groups[groupName].users.hasOwnProperty(userName);
	}

	/**
	 * \brief Check if a user is a manager in the given group.
	 *
	 * \param groupName
	 * \param userName
	 *
	 * \return
	 */
	function isManager(groupName, userName) {
		return isMember(groupName, userName) && groups[groupName].users[userName].isManager;
	}

	/**
	 * \brief Unfold the category belonging to the given group in the group list.
	 *
	 * \param groupName
	 */
	function unfoldToGroup(groupName) {
		var $groupList = $('#group-list');

		var $group = $groupList.find('.group[data-name="' + groupName + '"]');
		$group.parents('.category').children('a.name').removeClass('collapsed');
		$group.parents('.category').children('ul.category-ul').removeClass('hidden');
		$group.parents('.category').children('ul.category-ul').collapse('show');
	}

	/**
	 * \brief Select the given group in the group list.
	 *
	 * \param groupName
	 */
	function selectGroup(groupName) {
		var group = groups[groupName];

		var $groupList = $('#group-list');
		var $group     = $groupList.find('.group[data-name="' + groupName + '"]');
		var $oldGroup  = $groupList.find('.selected');

		if ($group.is($oldGroup))
			return;

		$oldGroup.removeClass('selected');
		unfoldToGroup(groupName);
		$group.addClass('selected');
		YodaPortal.storage.session.set('selected-group', groupName);

		// Build the group properties panel.
		(function(){
			var $groupProperties = $('#group-properties');

			$groupProperties.find('.placeholder-text').addClass('hidden');
			$groupProperties.find('form').removeClass('hidden');

			var userIsManager = isManager(groupName, YodaPortal.user.userName);

			$groupProperties.find('#f-group-update-category')
				.select2('data', { id: group.category, text: group.category })
				.select2('readonly', !userIsManager);
			$groupProperties.find('#f-group-update-subcategory')
				.select2('data', { id: group.subcategory, text: group.subcategory })
				.select2('readonly', !userIsManager);
			$groupProperties.find('#f-group-update-name').siblings('.input-group-addon')
				.html(function() {
					var matches = groupName.match(/^(grp-|priv-)/, '');
					return matches
						? matches[1]
						: '&nbsp;&nbsp;';
				});
			$groupProperties.find('#f-group-update-name')
				.val(groupName.replace(/^(grp-|priv-)/, ''))
				.prop('readonly', true);
			$groupProperties.find('#f-group-update-description')
				.val(group.description)
				.prop('readonly', !userIsManager);
			$groupProperties.find('#f-group-update-submit')
				.toggleClass('hidden', !userIsManager);
		})();

		// Build the user list panel.
		(function(){
			var users = groups[groupName].users;

			var $userList = $('#user-list');
			$userList.find('.list-group-item.user').remove();

			Object.keys(users).slice().sort(function(a, b) {
				function cmp(a, b) {
					return a < b
						? -1
						: a > b
							? 1
							: 0;
				}
				// Sort managers to the top of the list.
				return users[a].isManager
					? users[b].isManager
						? cmp(a, b) // Sort alphabetically.
						: -1
					: users[b].isManager
						? 1
						: cmp(a, b);

			}).forEach(function(userName, i){
				// Loop through the sorted user list and generate the #userList element.
				var user = users[userName];

				var $user = $('<li class="list-group-item selectable user">');
				$user.attr('id', 'user-' + i);
				$user.addClass(user.isManager ? 'manager' : 'regular');
				$user.attr('data-name', userName);
				if (userName === YodaPortal.user.userName)
					$user.removeClass('selectable')
					     .addClass('self')
					     .attr('title', 'You cannot change your own role or remove yourself from this group.');

				$user.html(
					'<i class="glyphicon'
					+ (
						user.isManager
						? ' glyphicon-tower'
						: ' glyphicon-user'
					)
					+ '"></i> '
					+ userName
				);

				$userList.append($user);
			});

			// Move the user creation item to the bottom of the list.
			var $userCreateItem = $userList.find('.item-user-create');
			$userCreateItem.appendTo($userList);
			$userCreateItem.toggleClass('hidden', !isManager(groupName, YodaPortal.user.userName));

			$userList.find('#f-user-create-group').val(groupName);

			$userList.removeClass('hidden');

			var $userPanel = $('.panel.users');
			$userPanel.find('.panel-body:has(.placeholder-text)').addClass('hidden');

			// Fix bad bootstrap borders caused by hidden elements.
			$userPanel.find('.panel-heading').css({ borderBottom: 'none' });
			$userPanel.find('.panel-footer').css( { borderTop:    ''     });

			$userPanel.find('.create-button').removeClass('disabled');
			$userPanel.find('.update-button, .delete-button').addClass('disabled');
		})();

		//$($groupList.parents('.panel')[0]).find('.delete-button').removeClass('disabled');
	}

	/**
	 * \brief Deselects the selected group, if any.
	 */
	function deselectGroup() {
		deselectUser();

		var $groupList = $('#group-list');
		$groupList.find('.selected').removeClass('selected');

		var $groupProperties = $('#group-properties');
		$groupProperties.find('.placeholder-text').removeClass('hidden');
		$groupProperties.find('form').addClass('hidden');

		var $userPanel = $('.panel.users');
		$userPanel.find('.panel-body:has(.placeholder-text)').removeClass('hidden');
		$userPanel.find('#user-list').addClass('hidden');

		// Fix bad bootstrap borders caused by hidden elements.
		$userPanel.find('.panel-heading').css({ borderBottom: ''               });
		$userPanel.find('.panel-footer').css( { borderTop:    '1px solid #ddd' });

		YodaPortal.storage.session.remove('selected-group');
	}

	/**
	 * \brief Select the given user in the user list.
	 *
	 * \param groupName
	 */
	function selectUser(userName) {
		deselectUser();

		var $userList = $('#user-list');

		var $user    = $userList.find('.user[data-name="' + userName + '"]');
		var $oldUser = $userList.find('.selected');

		if ($user.is($oldUser))
			return;

		$userList.find('.selected').removeClass('selected');
		$user.addClass('selected');

		if (isManager($('#group-list .selected.group').attr('data-name'), YodaPortal.user.userName)) {
			var $userPanel = $('.panel.users');
			$userPanel.find('.update-button, .delete-button').removeClass('disabled');
		}
	}

	/**
	 * \brief Deselects the selected user, if any.
	 */
	function deselectUser() {
		var $userList = $('#user-list');
		$userList.find('.selected').removeClass('selected');
		$('.panel.users').find('.update-button, .delete-button').addClass('disabled');
	}

	/**
	 * \brief Turn certain inputs into select2 inputs.
	 */
	function selectifyInputs(sel) {
		$(sel).filter('.selectify-category').select2({
			query: function(query) {
				var data = { results: [] };

				$.ajax({
					url:      '/group-manager/get-categories',
					type:     'get',
					dataType: 'json',
					data:     { query: query.term }
				}).done(function(categories) {
					var inputMatches = false;
					for (var i=0; i<categories.length; i++) {
						data.results.push({
							id:   categories[i],
							text: categories[i]
						});
						if (query.term === categories[i])
							inputMatches = true;
					}
					if (!inputMatches && query.term.length)
						data.results.unshift({
							id:   query.term,
							text: query.term
						});
					query.callback(data);
				}).fail(function() {
					console.log('Error: Could not get a list of categories.');
				});
			}
		}).on('open', function() {
			$(this).select2('val', '');
		}).on('change', function() {
			$($(this).attr('data-subcategory')).select2('data', '');
		});

		$(sel).filter('.selectify-subcategory').select2({
			query: function(query) {
				var data = { results: [] };

				$.ajax({
					url:      '/group-manager/get-subcategories',
					type:     'get',
					dataType: 'json',
					data:     { category: $($(this.element).attr('data-category')).val(), query: query.term }
				}).done(function(subcategories) {
					var inputMatches = false;
					for (var i=0; i<subcategories.length; i++) {
						data.results.push({
							id:   subcategories[i],
							text: subcategories[i]
						});
						if (query.term === subcategories[i])
							inputMatches = true;
					}
					if (!inputMatches && query.term.length)
						data.results.unshift({
							id:   query.term,
							text: query.term
						});
					query.callback(data);
				}).fail(function() {
					console.log('Error: Could not get a list of subcategories.');
				});
			}
		}).on('open', function() {
			$(this).select2('val', '');
		});

		$(sel).filter('.selectify-user-name').select2({
			query: function(query) {
				var data = { results: [] };

				var $el = $(this.element);

				$.ajax({
					url:      '/group-manager/get-users',
					type:     'get',
					dataType: 'json',
					data:     { query: query.term }
				}).done(function(users) {
					var inputMatches = false;
					for (var i=0; i<users.length; i++) {
						// Exclude users already in the group.
						if (!groups[$($el.attr('data-group')).val()].users.hasOwnProperty(users[i])) {
							data.results.push({
								id:   users[i],
								text: users[i]
							});
							if (query.term === users[i])
								inputMatches = true;
						}
					}
					if (!inputMatches && query.term.length)
						data.results.unshift({
							id:   query.term,
							text: query.term
						});
					query.callback(data);
				}).fail(function() {
					console.log('Error: Could not get a list of users.');
				});
			}
		}).on('open', function() {
			$(this).select2('val', ''); // XXX
		});
	}

	var $groupList = $('#group-list');
	$groupList.on('click', '.list-group-item.selectable.group', function() {
		if ($(this).is($groupList.find('.selected')))
			deselectGroup();
		else
			selectGroup($(this).attr('data-name'));
	});

	$('.panel.groups').on('click', '.create-button', function() {
		deselectGroup();
	});

	selectifyInputs($('.selectify-category, .selectify-subcategory, .selectify-user-name'));

	var $userList = $('#user-list');
	$userList.on('click', '.list-group-item.selectable.user', function() {
		if ($(this).is($userList.find('.selected')))
			deselectUser();
		else
			selectUser($(this).attr('data-name'));
	});

	$userList.on('click', '.list-group-item:has(.placeholder-text)', function() {
		deselectUser();
		$(this).find('.placeholder-text').addClass('hidden');
		$(this).find('form').removeClass('hidden');
		$(this).find('form').find('#f-user-create-name').select2('open');
	});

	$('#f-user-create-name').on('select2-close', function() {
		if ($(this).val().length === 0) {
			$(this).parents('form').addClass('hidden');
			$(this).parents('.list-group-item').find('.placeholder-text').removeClass('hidden');
		}
	});

	$('#modal-group-create').on('shown.bs.modal', function() {
		$('#f-group-create-name').focus();
	});

	$('#group-list-search').on('keyup', function() {
		// FIXME: Bootstrap's Collapse plugin is a pain to work with.
		//        Hiding / showing collapsible elements doesn't work correctly.
		return;

		/*
		$groupList  = $('#group-list');

		var $categories   = $groupList.find('.category');
		var $collapsibles = $categories.children('ul');
		var $groups       = $groupList.find('.group');

		var quotedVal = YodaPortal.escapeQuotes($(this).val());

		$collapsibles.css('transition', 'none');
		//$collapsibles.collapse('hide');
		$collapsibles.addClass('hidden');

		if (quotedVal.length) {
			var $matches = $groups.filter('[data-name*="' + quotedVal + '"]');
			$matches.each(function() { unfoldToGroup($(this).attr('data-name')); });
		} else {
			//$categories.children('ul').collapse('hide');
			//$categories.children('ul:not(.in)').addClass('collapse');
			//$categories.children('a.name:not(.collapsed)').addClass('collapsed');

			var $selected = $groups.filter('.selected');
			if ($selected.length)
				unfoldToGroup($selected.attr('data-name'));
		}
		 */
	});
	$('#user-list-search').on('keyup', function() {
		var $users  = $('.panel.users .user');

		if ($(this).val().length) {
			//var quotedVal = $(this).val().replace(/\\/g, '\\\\').replace(/("|')/g, '\\$1');
			var quotedVal = YodaPortal.escapeQuotes($(this).val());
			$users.filter('.filtered[data-name*="' + quotedVal + '"]').removeClass('filtered');
			$users.filter(':not(.filtered):not([data-name*="' + quotedVal + '"])').addClass('filtered');
		} else {
			$users.removeClass('filtered');
		}
	});

	var selectedGroup = YodaPortal.storage.session.get('selected-group');

	if (selectedGroup !== null && groups.hasOwnProperty(selectedGroup)) {
		selectGroup(selectedGroup);
	} else {
		var $categoryEls = $('#group-list .category');
		if ($categoryEls.length === 1) {
			// When the user can only access a single category, unfold it automatically.
			unfoldToGroup($categoryEls.find('.group').attr('data-name'));
		}
	}
});

// }}}
</script>

<style>
/* {{{ */

.panel-heading .input-group-sm {
	margin: -7px; /* Search box */
}
.panel-footer  .input-group-sm {
	margin: -6px; /* Search box */
}

i.form-control-feedback.glyphicon {
	color: #aaa;
}

.panel > .list-group .list-group .list-group-item {
	border-top:    1px solid #ddd;
	border-bottom: 1px solid #ddd;
}

.panel > .list-group .list-group {
	margin-top: 0;
	margin-bottom: 0;
}
.panel > .list-group .list-group .list-group .list-group-item {
	padding-left: 30px;
}


.list-group-item.selectable {
	cursor: pointer;
	transition: background-color 100ms;
}
.list-group-item.selectable.selected {
	background-color: #337ab7;
	color: #fff;
	box-shadow: 0 0 4px rgba(26,61,92,0.2) inset;
}
.list-group-item.selectable:not(.selected):hover {
	background-color: #eee;
}

.panel > .list-group .list-group-item.category,
.panel > .list-group .list-group-item.subcategory {
	padding-left: 0;
	padding-right: 0;
	border: none;
}

.panel > .list-group .list-group-item.subcategory > .list-group > .list-group-item:last-child {
	border-bottom: 1px solid #ddd;
}

.panel > .list-group .list-group-item.subcategory:last-child > .list-group > .list-group-item:last-child {
	border-bottom: none;
}

.panel > .list-group li.category > .name,
.panel > .list-group li.subcategory > .name {
	padding-left:  15px;
	padding-right: 15px;
	padding-bottom: 10px;
}

.panel > .list-group li.category > .name {
	font-weight: bold;
}
.panel > .list-group li.subcategory > .name {
	padding-left: 30px;
	font-weight: bold;
}

#group-list {
	/*
	min-height: 320px;
	max-height: 320px;
	*/
	overflow: auto;
}

#group-list a {
	display: block;
}

#group-list .list-group,
#group-list .list-group .list-group-item {
	border-radius: 0;
}

#group-list + .panel-footer {
	border-top: 1px solid #ddd
}

#user-list .user .glyphicon {
	padding-right: 8px;
}

#user-list .self {
	/*background-color: #ffe;*/
	color: #999;
	cursor: default;
}

#user-list .self:after {
	content: ' (you)';
}

#user-list .list-group-item #f-user-create {
	margin: -5px -5px;
}

.placeholder-text {
	font-style: italic;
	color: #aaa;
}

.list-group-item.filtered {
	display: none;
}

/* }}} */
</style>

<h1>Group Manager</h1>

<!--
<pre>
<?php var_dump($groups); ?>
</pre>
-->

<div class="row">
	<div class="col-md-5">
		<div class="panel panel-default groups">
			<div class="panel-heading clearfix">
				<h3 class="panel-title pull-left">Yoda groups</h3>
				<div class="input-group-sm has-feedback pull-right">
					<input class="form-control input-sm" id="group-list-search" type="text" placeholder="Search groups" />
					<i class="glyphicon glyphicon-search form-control-feedback"></i>
				</div>
			</div>
			<ul class="list-group" id="group-list">
<?php
	$i = 0;
	$j = 0;
	$k = 0;

	ksort($categories);
	foreach ($categories as $category => $subcategories) {
?>
	<li class="list-group-item category" id="category-<?=$i?>" data-name="<?=$category?>">
	<a class="name collapsed" data-toggle="collapse" data-parent="#category-<?=$i?>" href="#category-<?=$i?>-ul"><?=$category?></a>
		<ul class="list-group collapse category-ul" id="category-<?=$i?>-ul">
<?php
		ksort($subcategories);
		foreach ($subcategories as $subcategory => $groups) {
?>
	<li class="list-group-item subcategory" data-name="<?=$subcategory?>"><div class="name"><?=$subcategory?></div>
	<ul class="list-group subcategory-ul">
<?php
			ksort($groups);
			foreach ($groups as $group => $members) {
?>
				<li class="list-group-item selectable group" id="group-<?=$k?>" data-name="<?=$group?>"><?=$group?></li>
<?php
				$k++;
			}
?>
	</li>
	</ul>
<?php
			$j++;
		}
?>
	</li>
	</ul>
<?php
		$i++;
	}
?>
			</ul>
			<div class="panel-footer clearfix">
				<div class="input-group-sm pull-left">
					<a class="btn btn-sm btn-danger disabled delete-button hidden">Remove group</a>
				</div>
				<div class="input-group-sm pull-right">
					<a class="btn btn-sm btn-primary create-button" data-toggle="modal" data-target="#modal-group-create">Add group</a>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-7">
		<div class="panel panel-default properties">
			<div class="panel-heading">
				<h3 class="panel-title">Group properties</h3>
			</div>
			<div class="panel-body" id="group-properties">
				<p class="placeholder-text">
					Please select a group.
				</p>
				<form action="<?=base_url('group-manager/group-update')?>" method="POST" class="form-horizontal hidden" id="f-group-update">
					<div class="form-group">
						<label class="col-sm-4 control-label" for="f-group-update-category">Category</label>
						<div class="col-sm-8">
							<input name="group_category" id="f-group-update-category" class="form-control selectify-category" type="hidden" placeholder="Select one or enter a new name" required data-subcategory="#f-group-update-subcategory" />
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-4 control-label" for="f-group-update-subcategory">Subcategory</label>
						<div class="col-sm-8">
							<input name="group_subcategory" id="f-group-update-subcategory" class="form-control selectify-subcategory" type="hidden" placeholder="Select one or enter a new name" required data-category="#f-group-update-category" />
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-4 control-label" for="f-group-update-name">Name</label>
						<div class="col-sm-8">
							<div class="input-group">
								<div class="input-group-addon">grp-</div>
								<input name="group_name" id="f-group-update-name" class="form-control" type="text" pattern="^[a-z0-9\-]+$"	required oninvalid="setCustomValidity(\'Please enter only lowercase letters, numbers, and hyphens (-).\')" onchange="setCustomValidity(\'\')" readonly />
							</div>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-4 control-label" for="f-group-update-description">Description</label>
						<div class="col-sm-8">
							<input name="group_description" id="f-group-update-description" class="form-control" type="text" pattern="^[a-z0-9\-]+$" required oninvalid="setCustomValidity(\'Please enter only lowercase letters, numbers, and hyphens (-).\')" onchange="setCustomValidity(\'\')" placeholder="Enter a short description for this group" />
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-4 col-sm-8">
							<input id="f-group-update-submit" class="btn btn-primary" type="submit" value="Update" />
						</div>
					</div>
				</form>
			</div>
		</div>
		<div class="panel panel-default users">
			<div class="panel-heading clearfix">
				<h3 class="panel-title pull-left">Groups members</h3>
				<div class="input-group-sm has-feedback pull-right">
					<input class="form-control input-sm" id="user-list-search" type="text" placeholder="Search users" />
					<i class="glyphicon glyphicon-search form-control-feedback"></i>
				</div>
			</div>
			<div class="panel-body">
				<p class="placeholder-text">
					Please select a group.
				</p>
			</div>
			<ul class="list-group hidden" id="user-list">
				<li class="list-group-item item-user-create">
					<span class="placeholder-text">
						Click here to add a new user to this group
					</span>
					<form action="<?=base_url('group-manager/user-create')?>" method="POST" class="form-inline hidden" id="f-user-create">
						<input name="group_name" id="f-user-create-group" type="hidden" />
						<div class="input-group" style="width: 100%;">
							<input name="user_name" id="f-user-create-name" class="form-control input-sm selectify-user-name" type="hidden" pattern="^[a-z0-9\-]+$" required oninvalid="setCustomValidity('Please enter only lowercase letters, numbers, and hyphens (-).')" onchange="setCustomValidity('')" placeholder="Enter a username" data-group="#f-user-create-group" />
							<div class="input-group-btn">
								<input id="f-user-create-submit" class="btn btn-primary btn-block btn-sm" type="submit" value="Add" />
							</div>
						</div>
					</form>
				</li>
			</ul>
			<div class="panel-footer clearfix" style="border-top: 1px solid #ddd;">
				<div class="input-group-sm pull-left">
					<a class="btn btn-sm btn-primary disabled update-button">Change role</a>
					<a class="btn btn-sm btn-danger disabled delete-button">Remove</a>
				</div>
			</div>
		</div>
	</div>
</div>


<div class="modal fade" id="modal-group-create" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
				<h4 class="modal-title" id="myModalLabel">Create a group</h4>
			</div>
			<div class="modal-body">
				<form class="form-horizontal" id="f-group-create" action="<?=base_url('group-manager/group-create')?>" method="POST">
					<div class="form-group">
						<label class="col-sm-4 control-label" for="f-group-create-category">Category</label>
						<div class="col-sm-8">
							<input name="group_category" id="f-group-create-category" class="form-control selectify-category" type="hidden" placeholder="Select one or enter a new name" required data-subcategory="#f-group-create-subcategory" />
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-4 control-label" for="f-group-create-subcategory">Subcategory</label>
						<div class="col-sm-8">
							<input name="group_subcategory" id="f-group-create-subcategory" class="form-control selectify-subcategory" type="hidden" placeholder="Select one or enter a new name" required data-category="#f-group-create-category" />
						</div>
					</div>
					<hr />
					<div class="form-group">
						<label class="col-sm-4 control-label" for="f-group-create-name">Group name</label>
						<div class="col-sm-8">
							<div class="input-group">
								<div class="input-group-addon">
									grp-
								</div>
								<input name="group_name" id="f-group-create-name" class="form-control" type="text" pattern="^[a-z0-9\-]+$"  required oninvalid="setCustomValidity('Please enter only lowercase letters, numbers, and hyphens (-).')" onchange="setCustomValidity('')" />
							</div>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-4 control-label" for="f-group-create-description">Group description</label>
						<div class="col-sm-8">
							<input name="group_description" id="f-group-create-description" class="form-control" type="text" placeholder="Enter a short description" />
						</div>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-4 col-sm-8">
							<input id="f-group-create-submit" class="btn btn-primary" type="submit" value="Add group" />
							<button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>



<div class="row hidden">
	<div class="col-md-6">
		<div class="panel panel-default">
			<div class="panel-heading">
				<h3 class="panel-title">Add a group</h3>
			</div>
			<div class="panel-body">
				<p>
					<!-- Yada yada -->
				</p>
			</div>
		</div>
	</div>
</div>
