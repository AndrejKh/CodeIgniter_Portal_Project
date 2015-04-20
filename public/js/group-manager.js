/**
 * \file
 * \brief     Yoda Group Manager frontend.
 * \author    Chris Smeele
 * \copyright Copyright (c) 2015, Utrecht university. All rights reserved
 * \license   GPLv3, see LICENSE
 */

"use strict";

$(function() {
	YodaPortal.extend('groupManager', {

		groupHierarchy: null, ///< A group hierarchy object. See YodaPortal.groupManager.load().
		groups:         null, ///< A list of group objects with member information. See YodaPortal.groupManager.load().

		/**
		 * \brief Check if a user is a member of the given group.
		 *
		 * \param groupName
		 * \param userName
		 *
		 * \return
		 */
		isMember: function(groupName, userName) {
			return this.groups[groupName].members.hasOwnProperty(userName);
		},

		/**
		 * \brief Check if a user is a manager in the given group.
		 *
		 * \param groupName
		 * \param userName
		 *
		 * \return
		 */
		isManager: function(groupName, userName) {
			return this.isMember(groupName, userName) && this.groups[groupName].members[userName].isManager;
		},

		/**
		 * \brief Unfold the category belonging to the given group in the group list.
		 *
		 * \param groupName
		 */
		unfoldToGroup: function(groupName) {
			var $groupList = $('#group-list');

			var $group = $groupList.find('.group[data-name="' + groupName + '"]');
			$group.parents('.category').children('a.name').removeClass('collapsed');
			$group.parents('.category').children('.category-ul').removeClass('hidden');
			$group.parents('.category').children('.category-ul').collapse('show');
		},

		/**
		 * \brief Select the given group in the group list.
		 *
		 * \param groupName
		 */
		selectGroup: function(groupName) {
			var group = this.groups[groupName];

			var $groupList = $('#group-list');
			var $group     = $groupList.find('.group[data-name="' + groupName + '"]');
			var $oldGroup  = $groupList.find('.active');

			if ($group.is($oldGroup))
				return;

			this.unfoldToGroup(groupName);

			$oldGroup.removeClass('active');
			$group.addClass('active');
			YodaPortal.storage.session.set('selected-group', groupName);

			var that = this;

			// Build the group properties panel.
			(function(){
				var $groupProperties = $('#group-properties');

				$groupProperties.find('.placeholder-text').addClass('hidden');
				$groupProperties.find('form').removeClass('hidden');

				var userIsManager = that.isManager(groupName, YodaPortal.user.userName);

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
				var users = that.groups[groupName].members;

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

					var $user = $('<a class="list-group-item user">');
					$user.attr('id', 'user-' + i);
					$user.addClass(user.isManager ? 'manager' : 'regular');
					$user.attr('data-name', userName);
					if (userName === YodaPortal.user.userName)
						$user.addClass('disabled')
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
				$userCreateItem.toggleClass('hidden', !that.isManager(groupName, YodaPortal.user.userName));

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
		},

		/**
		 * \brief Deselects the selected group, if any.
		 */
		deselectGroup: function() {
			this.deselectUser();

			var $groupList = $('#group-list');
			$groupList.find('.active').removeClass('active');

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
		},

		/**
		 * \brief Select the given user in the user list.
		 *
		 * \param groupName
		 */
		selectUser: function(userName) {
			this.deselectUser();

			var $userList = $('#user-list');

			var $user    = $userList.find('.user[data-name="' + userName + '"]');
			var $oldUser = $userList.find('.active');

			if ($user.is($oldUser))
				return;

			$userList.find('.active').removeClass('active');
			$user.addClass('active');

			if (this.isManager($('#group-list .active.group').attr('data-name'), YodaPortal.user.userName)) {
				var $userPanel = $('.panel.users');
				$userPanel.find('.update-button, .delete-button').removeClass('disabled');
			}
		},

		/**
		 * \brief Deselects the selected user, if any.
		 */
		deselectUser: function() {
			var $userList = $('#user-list');
			$userList.find('.active').removeClass('active');
			$('.panel.users').find('.update-button, .delete-button').addClass('disabled');
		},

		/**
		 * \brief Turn certain inputs into select2 inputs.
		 */
		selectifyInputs: function(sel) {
			var that = this;

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
							if (!that.groups[$($el.attr('data-group')).val()].members.hasOwnProperty(users[i])) {
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
		},

		/**
		 * \brief Initialize the group manager module.
		 *
		 * The structure of the gruopHierarchy parameter is as follows:
		 *
		 *     {
		 *       'CATEGORY_NAME': {
		 *         'SUBCATEGORY_NAME': {
		 *           'GROUP_NAME': {
		 *             'description': 'GROUP_DESCRIPTION',
		 *             'members': {
		 *               'USER_NAME': {
		 *                 'isManager': BOOLEAN
		 *               }, ...
		 *             }
		 *           }, ...
		 *         }, ...
		 *       }, ...
		 *     }
		 *
		 * \param groupHierarchy An object representing the category / group hierarchy visible to the user.
		 *
		 * \todo Generate the group list in JS just like the user list.
		 */
		load: function(groupHierarchy) {
			this.groupHierarchy = groupHierarchy;
			this.groups = (function(hier) {
				var groups = { };
				for (var categoryName in hier)
					for (var subcategoryName in hier[categoryName])
						for (var groupName in hier[categoryName][subcategoryName])
							groups[groupName] = {
								category:    categoryName,
								subcategory: subcategoryName,
								name:        groupName,
								description: hier[categoryName][subcategoryName][groupName].description,
								members:     hier[categoryName][subcategoryName][groupName].members
							};
				return groups;
			})(this.groupHierarchy);

			var that = this;
			var $groupList = $('#group-list');
			$groupList.on('click', 'a.group', function() {
				if ($(this).is($groupList.find('.active')))
					that.deselectGroup();
				else
					that.selectGroup($(this).attr('data-name'));
			});

			$('.panel.groups').on('click', '.create-button', function() {
				that.deselectGroup();
			});

			this.selectifyInputs('.selectify-category, .selectify-subcategory, .selectify-user-name');

			var $userList = $('#user-list');
			$userList.on('click', 'a.user:not(.disabled)', function() {
				if ($(this).is($userList.find('.active')))
					that.deselectUser();
				else
					that.selectUser($(this).attr('data-name'));
			});

			$userList.on('click', '.list-group-item:has(.placeholder-text)', function() {
				// Show the user add form.
				that.deselectUser();
				$(this).find('.placeholder-text').addClass('hidden');
				$(this).find('form').removeClass('hidden');
				$(this).find('form').find('#f-user-create-name').select2('open');
			});

			$('#f-user-create-name').on('select2-close', function() {
				// Remove the new user name input on unfocus if nothing was entered.
				if ($(this).val().length === 0) {
					$(this).parents('form').addClass('hidden');
					$(this).parents('.list-group-item').find('.placeholder-text').removeClass('hidden');
				}
			});

			$('#modal-group-create').on('shown.bs.modal', function() {
				// Auto-focus group name in group add dialog.
				$('#f-group-create-name').focus();
			});

			// Group list search.
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

					var $selected = $groups.filter('.active');
					if ($selected.length)
						that.unfoldToGroup($selected.attr('data-name'));
				}
				 */
			});

			// User list search.
			$('#user-list-search').on('keyup', function() {
				var $users  = $('.panel.users .user');

				if ($(this).val().length) {
					var quotedVal = YodaPortal.escapeQuotes($(this).val());
					$users.filter('.filtered[data-name*="' + quotedVal + '"]').removeClass('filtered');
					$users.filter(':not(.filtered):not([data-name*="' + quotedVal + '"])').addClass('filtered');
				} else {
					$users.removeClass('filtered');
				}
			});

			// Indicate which groups are managed by this user.
			for (var groupName in this.groups) {
				if (this.isManager(groupName, YodaPortal.user.userName))
					$('#group-list .group[data-name="' + groupName + '"]').append(
						'<span class="pull-right glyphicon glyphicon-tower" title="You manage this group"></span>'
					);
			}

			var selectedGroup = YodaPortal.storage.session.get('selected-group');
			if (selectedGroup !== null && this.groups.hasOwnProperty(selectedGroup)) {
				// Automatically select the last selected group within this session (bound to this tab).
				this.selectGroup(selectedGroup);
			} else {
				// When the user can only access a single category, unfold it automatically.
				var $categoryEls = $('#group-list .category');
				if ($categoryEls.length === 1)
					this.unfoldToGroup($categoryEls.find('.group').attr('data-name'));
			}
		},

	});
});
