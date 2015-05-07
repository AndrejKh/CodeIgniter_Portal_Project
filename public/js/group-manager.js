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

		/**
		 * \brief If the amount of visible groups is higher than or equal to this value,
		 *        categories in the group list will be folded on page load.
		 */
		CATEGORY_FOLD_THRESHOLD: 8,

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
			return groupName in this.groups && userName in this.groups[groupName].members;
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
		 * \brief Try to check if a user is a manager in the given category.
		 *
		 * Returns false if the current user does not have access to the given category.
		 *
		 * \param categoryName
		 * \param userName
		 *
		 * \return
		 */
		isManagerInCategory: function(categoryName, userName) {
			var that = this;
			try {
				var category = this.groupHierarchy[categoryName];
				return Object.keys(category).some(function(subcategoryName) {
					return Object.keys(category[subcategoryName]).some(function(groupName) {
						return that.isManager(groupName, userName);
					});
				});
			} catch (ex) {
				// The category is probably not visible to us.
				return false;
			}
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

			this.deselectGroup();

			this.unfoldToGroup(groupName);

			$oldGroup.removeClass('active');
			$group.addClass('active');
			YodaPortal.storage.session.set('selected-group', groupName);

			var that = this;

			// Build the group properties panel {{{

			(function(){
				var $groupProperties = $('#group-properties');

				$groupProperties.find('.placeholder-text').addClass('hidden');
				$groupProperties.find('form').removeClass('hidden');

				var userIsManager = that.isManager(groupName, YodaPortal.user.username);

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
					.prop('readonly', true)
					.attr('title', 'Group names cannot be changed')
					.attr('data-prefix', function() {
						var matches = groupName.match(/^(grp-|priv-)/, '');
						return matches
							? matches[1]
							: '';
					});
				$groupProperties.find('#f-group-update-description')
					.val(group.description)
					.prop('readonly', !userIsManager);
				$groupProperties.find('#f-group-update-submit')
					.toggleClass('hidden', !userIsManager);
			})();

			// }}}
			// Build the user list panel {{{

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
					if (userName === YodaPortal.user.username)
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
						+ '" title="'
						+ (
							user.isManager
							? 'Manager'
							: 'Regular user'
						)
						+ '"></i> '
						+ userName
					);

					$userList.append($user);
				});

				// Move the user creation item to the bottom of the list.
				var $userCreateItem = $userList.find('.item-user-create');
				$userCreateItem.appendTo($userList);
				$userCreateItem.toggleClass('hidden', !that.isManager(groupName, YodaPortal.user.username));

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

			// }}}
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
			$userPanel.find('#user-list-search').val('');
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
			var $userList = $('#user-list');

			var $user    = $userList.find('.user[data-name="' + userName + '"]');
			var $oldUser = $userList.find('.active');

			if ($user.is($oldUser))
				return;

			this.deselectUser();

			$userList.find('.active').removeClass('active');
			$user.addClass('active');

			if (this.isManager($('#group-list .active.group').attr('data-name'), YodaPortal.user.username)) {
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
		 * \brief Turn certain inputs into select2 inputs with autocompletion.
		 */
		selectifyInputs: function(sel) {
			var that = this;

			// Category fields {{{

			$(sel).filter('.selectify-category').each(function() {
				var $el = $(this);

				$el.attr(
					'placeholder',
					that.isMember('priv-category-add', YodaPortal.user.username)
						? 'Select one or enter a new name'
						: 'Select a category'
				);

				$el.select2({
					ajax: {
						quietMillis: 200,
						url:      YodaPortal.baseUrl + 'group-manager/get-categories',
						type:     'get',
						dataType: 'json',
						data: function (term, page) {
							return { query: term };
						},
						results: function (categories) {
							var results = [];
							var query   = $el.data('select2').search.val();
							var inputMatches = false;

							categories.forEach(function(category) {
								if (query === category)
									inputMatches = true;

								if (that.isManagerInCategory(category, YodaPortal.user.username))
									results.push({
										id:   category,
										text: category,
									});
								else if (inputMatches)
									// Only show a (disabled) category the user doesn't have access to
									// if they type its exact name.
									results.push({
										id:       category,
										text:     category,
										disabled: true,
									});
							});
							if (
								  !inputMatches
								&& query.length
								&& that.isMember('priv-category-add', YodaPortal.user.username)
							) {
								results.push({
									id:     query,
									text:   query,
									exists: false
								});
							}

							return { results: results };
						},
					},
					formatResult: function(result, $container, query, escaper) {
						return escaper(result.text)
							+ (
								'exists' in result && !result.exists
								? ' <span class="grey">(create)</span>'
								: ''
							);
					},
					initSelection: function($el, callback) {
						callback({ id: $el.val(), text: $el.val() });
					},
				}).on('open', function() {
					$(this).select2('val', '');
				}).on('change', function() {
					$($(this).attr('data-subcategory')).select2('val', '');
				});
			});

			// }}}
			// Subcategory fields {{{

			$(sel).filter('.selectify-subcategory').each(function() {
				var $el = $(this);

				$el.select2({
					ajax: {
						quietMillis: 200,
						url:      YodaPortal.baseUrl + 'group-manager/get-subcategories',
						type:     'get',
						dataType: 'json',
						data: function (term, page) {
							return {
								category: $($el.attr('data-category')).val(),
								query: term
							};
						},
						results: function (subcategories) {
							var results = [];
							var query   = $el.data('select2').search.val();
							var inputMatches = false;

							subcategories.forEach(function(subcategory) {
								results.push({
									id:   subcategory,
									text: subcategory
								});
								if (query === subcategory)
									inputMatches = true;
							});
							if (!inputMatches && query.length)
								results.push({
									id:   query,
									text: query,
									exists: false
								});

							return { results: results };
						},
					},
					formatResult: function(result, $container, query, escaper) {
						return escaper(result.text)
							+ (
								'exists' in result && !result.exists
								? ' <span class="grey">(create)</span>'
								: ''
							);
					},
					initSelection: function($el, callback) {
						callback({ id: $el.val(), text: $el.val() });
					},
				}).on('open', function() {
					$(this).select2('val', '');
				});
			});

			// }}}
			// Username fields {{{

			$(sel).filter('.selectify-user-name').each(function() {
				var $el = $(this);

				$el.select2({
					allowClear:  true,
					openOnEnter: false,
					minimumInputLength: 3,
					ajax: {
						quietMillis: 400,
						url:      YodaPortal.baseUrl + 'group-manager/get-users',
						type:     'get',
						dataType: 'json',
						data: function (term, page) {
							return {
								query: term
							};
						},
						results: function (users) {
							var query   = $el.data('select2').search.val();
							var results = [];
							var inputMatches = false;

							users.forEach(function(userName) {
								// Exclude users already in the group.
								if (!(userName in that.groups[$($el.attr('data-group')).val()].members))
									results.push({
										id:   userName,
										text: userName
									});
								if (query === userName)
									inputMatches = true;
							});

							if (!inputMatches && query.length)
								results.push({
									id:   query,
									text: query,
									exists: false
								});

							return { results: results };
						},
					},
					formatResult: function(result, $container, query, escaper) {
						return escaper(result.text)
							+ (
								'exists' in result && !result.exists
								? ' <span class="grey">(create)</span>'
								: ''
							);
					},
					initSelection: function($el, callback) {
						callback({ id: $el.val(), text: $el.val() });
					},
				}).on('open', function() {
					$(this).select2('val', '');
				});
			});

			// }}}
		},

		/**
		 * \brief Group create / update form submission handler.
		 *
		 * `this` is assumed to be the groupManager object, not the form element
		 * that was submitted.
		 *
		 * \param el the form element
		 * \param e  a submit event
		 */
		onSubmitGroupCreateOrUpdate: function(el, e) {
			e.preventDefault();

			var action =
				$(el).attr('id') === 'f-group-create'
				? 'create' : 'update';

			var newProperties = {
				name:          $(el).find('#f-group-'+action+'-name'     ).attr('data-prefix')
							 + $(el).find('#f-group-'+action+'-name'     ).val(),
				description: $(el).find('#f-group-'+action+'-description').val(),
				category:    $(el).find('#f-group-'+action+'-category'   ).val(),
				subcategory: $(el).find('#f-group-'+action+'-subcategory').val(),
			};

			if (newProperties.category === '' || newProperties.subcategory === '') {
				alert('Please select a category and subcategory.');
				return;
			} else if (
				// Validate input, in case HTML5 validation did not work.
				// Also needed for the select2 inputs.
				[newProperties.category, newProperties.subcategory, newProperties.description]
					.some(function(item) {
					return !item.match(/^[a-zA-Z0-9,.()_ -]*$/);
				})
			) {
				alert('The (sub)category name and group description fields may only contain letters a-z, numbers, spaces, comma\'s, periods, parentheses, underscores (_) and hyphens (-).');
				return;
			}

			var postData = {
				group_name:        newProperties.name,
				group_description: newProperties.description,
				group_category:    newProperties.category,
				group_subcategory: newProperties.subcategory,
			};

			if (action === 'update') {
				var selectedGroup = this.groups[$($('#group-list .group.active')[0]).attr('data-name')];
				['description', 'category', 'subcategory'].forEach(function(item) {
					// Filter out fields that have not changed.
					if (selectedGroup[item] === newProperties[item])
						delete postData['group_' + item];
				});
			}

			$.ajax({
				url:      $(el).attr('action'),
				type:     'post',
				dataType: 'json',
				data:     postData
			}).done(function(result) {
				if ('status' in result)
					console.log('Group '+action+' completed with status ' + result.status);
				if ('status' in result && result.status === 0) {
					// OK! Make sure the newly added group is selected after reloading the page.
					YodaPortal.storage.session.set('selected-group', postData.group_name);

					// And give the user some feedback.
					YodaPortal.storage.session.set('messages',
						YodaPortal.storage.session.get('messages', []).concat({
							type:    'success',
							message: action === 'create'
									 ? 'Created group ' + postData.group_name + '.'
									 : 'Updated '       + postData.group_name + ' group properties.'
						})
					);

					$(window).on('beforeunload', function() {
						$(window).scrollTop(0);
					});
					window.location.reload(true);
				} else {
					// Something went wrong.
					if ('message' in result)
						alert(result.message);
					else
						alert(
							  "Error: Could not "+action+" group due to an internal error.\n"
							+ "Please contact a Yoda administrator"
						);
				}
			}).fail(function() {
				alert("Error: Could not create group due to an internal error.\nPlease contact a Yoda administrator");
			});
		},

		/**
		 * \brief User add form submission handler.
		 *
		 * Adds a user to the selected group.
		 *
		 * `this` is assumed to be the groupManager object, not the form element
		 * that was submitted.
		 *
		 * \param el the form element
		 * \param e  a submit event
		 */
		onSubmitUserCreate: function(el, e) {
			e.preventDefault();

			var groupName = $(el).find('#f-user-create-group').val();
			var  userName = $(el).find('#f-user-create-name' ).val();

			if (!userName.match(/^([a-z]+|[a-z0-9_.-]+@[a-z0-9_.-]+)$/)) {
				alert('Please enter either an e-mail address or a name consisting only of lowercase letters.');
				return;
			}

			var that = this;

			$.ajax({
				url:      $(el).attr('action'),
				type:     'post',
				dataType: 'json',
				data: {
					group_name: groupName,
					 user_name: userName,
				},
			}).done(function(result) {
				if ('status' in result)
					console.log('User add completed with status ' + result.status);
				if ('status' in result && result.status === 0) {
					that.groups[groupName].members[userName] = {
						isManager: false
					};

					$(el).find('#f-user-create-name').select2('val', '');
					$(el).addClass('hidden');
					$(el).parents('.list-group-item').find('.placeholder-text').removeClass('hidden');

					that.deselectGroup();
					that.selectGroup(groupName);
					that.selectUser(userName);
				} else {
					// Something went wrong. :(
					if ('message' in result)
						alert(result.message);
					else
						alert(
							  "Error: Could not add a user due to an internal error.\n"
							+ "Please contact a Yoda administrator"
						);
				}
			}).fail(function() {
				alert("Error: Could not add a user due to an internal error.\nPlease contact a Yoda administrator");
			});
		},

		/**
		 * \brief Remove the confirmation step for removing users from groups.
		 */
		removeUserDeleteConfirmationModal: function() {
			var that = this;
			$('.users.panel .delete-button').on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				that.onClickUserDelete(this);
			});
		},

		/**
		 * \brief Handle a change role button click event.
		 *
		 * `this` is assumed to be the groupManager object, not the form element
		 * that was submitted.
		 *
		 * \param el
		 * \param e
		 */
		onClickUserUpdate: function(el, e) {
			var that = this;

			var groupName = $('#group-list .group.active').attr('data-name');
			var  userName = $('#user-list   .user.active').attr('data-name');

			$('#user-list .user.active')
				.addClass('update-pending disabled')
				.attr('title', 'Update pending');
			this.deselectUser();

			$.ajax({
				url:      $(el).attr('data-action'),
				type:     'post',
				dataType: 'json',
				data: {
					group_name: groupName,
					 user_name: userName,
					new_role:
						// Toggle.
						that.groups[groupName].members[userName].isManager
						? 'user' : 'manager'
				},
			}).done(function(result) {
				if ('status' in result)
					console.log('User update completed with status ' + result.status);
				if ('status' in result && result.status === 0) {
					that.groups[groupName].members[userName].isManager
						= !that.groups[groupName].members[userName].isManager;

					// Force-regenerate the user list.
					that.deselectGroup();
					that.selectGroup(groupName);

					// Give a visual hint that the user was updated.
					$('#user-list .user[data-name="' + userName + '"]').addClass('blink-once');
				} else {
					// Something went wrong. :(

					$('#user-list .user.update-pending[data-name="' + userName + '"]')
						.removeClass('update-pending disabled')
						.attr('title', '');

					if ('message' in result)
						alert(result.message);
					else
						alert(
							  "Error: Could not change the role for the selected user due to an internal error.\n"
							+ "Please contact a Yoda administrator"
						);
				}
			}).fail(function() {
				alert("Error: Could not change the role for the selected user due to an internal error.\nPlease contact a Yoda administrator");
			});
		},

		/**
		 * \brief Handle a user delete button click event.
		 *
		 * `this` is assumed to be the groupManager object, not the form element
		 * that was submitted.
		 */
		onClickUserDelete: function(el) {
			if ($('#f-user-delete-no-confirm').prop('checked')) {
				YodaPortal.storage.session.set('confirm-user-delete', true);
				this.removeUserDeleteConfirmationModal();
			}

			var groupName = $('#group-list .group.active').attr('data-name');
			var  userName = $('#user-list   .user.active').attr('data-name');

			$('#user-list .user.active')
				.addClass('delete-pending disabled')
				.attr('title', 'Removal pending');
			this.deselectUser();

			var that = this;

			$.ajax({
				url:      $(el).attr('data-action'),
				type:     'post',
				dataType: 'json',
				data: {
					group_name: groupName,
					 user_name: userName,
				},
			}).done(function(result) {
				if ('status' in result)
					console.log('User remove completed with status ' + result.status);
				if ('status' in result && result.status === 0) {
					delete that.groups[groupName].members[userName];

					// Force-regenerate the user list.
					that.deselectGroup();
					that.selectGroup(groupName);
				} else {
					// Something went wrong. :(

					// Re-enable user list entry.
					$('#user-list .user.delete-pending[data-name="' + userName + '"]').removeClass('delete-pending disabled').attr('title', '');

					if ('message' in result)
						alert(result.message);
					else
						alert(
							  "Error: Could not remove the selected user from the group due to an internal error.\n"
							+ "Please contact a Yoda administrator"
						);
				}
			}).fail(function() {
				alert("Error: Could not remove the selected user from the group due to an internal error.\nPlease contact a Yoda administrator");
			});
		},

		/**
		 * \brief Initialize the group manager module.
		 *
		 * The structure of the groupHierarchy parameter is as follows:
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
				// Create a flat group map based on the hierarchy object.
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

			// Attach event handlers {{{
			// Group list {{{

			$groupList.on('show.bs.collapse', function(e) {
				$(e.target).parent('.category').find('.triangle')
					.removeClass('glyphicon-triangle-right')
					   .addClass('glyphicon-triangle-bottom');
			});
			$groupList.on('hide.bs.collapse', function(e) {
				$(e.target).parent('.category').find('.triangle')
					.removeClass('glyphicon-triangle-bottom')
					   .addClass('glyphicon-triangle-right');
			});

			$groupList.on('click', 'a.group', function() {
				if ($(this).is($groupList.find('.active')))
					that.deselectGroup();
				else
					that.selectGroup($(this).attr('data-name'));
			});

			// Group list search.
			$('#group-list-search').on('keyup', function() {
				// TODO: Figure out how to correctly hide / show collapsible Bootstrap elements.
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

			// Group creation {{{

			$('#modal-group-create').on('show.bs.modal', function() {
				$('#f-group-create-name')       .val('').attr('data-prefix', 'grp-');
				$('#f-group-create-description').val('');
				var $selectedGroup = $('#group-list .group.active');
				if ($selectedGroup.length) {
					var groupName = $($selectedGroup[0]).attr('data-name');
					// Fill in the (sub)category of the currently selected group.
					$('#f-group-create-category')   .select2('val', that.groups[groupName].category);
					$('#f-group-create-subcategory').select2('val', that.groups[groupName].subcategory);
				} else {
					$('#f-group-create-category')   .select2('val', '');
					$('#f-group-create-subcategory').select2('val', '');
				}
			});
			$('#modal-group-create').on('shown.bs.modal', function() {
				// Auto-focus group name in group add dialog.
				$('#f-group-create-name').focus();
			});

			// Group creation / update.
			$('#f-group-create, #f-group-update').on('submit', function(e) {
				that.onSubmitGroupCreateOrUpdate(this, e);
			});

			// }}}
			// }}}
			// User list {{{

			var $userList = $('#user-list');
			$userList.on('click', 'a.user:not(.disabled)', function() {
				if ($(this).is($userList.find('.active')))
					that.deselectUser();
				else
					that.selectUser($(this).attr('data-name'));
			});

			$userList.on('click', '.list-group-item:has(.placeholder-text:not(.hidden))', function() {
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

			// Adding users to groups.
			$('#f-user-create').on('submit', function(e) {
				that.onSubmitUserCreate(this, e);
			});

			// Changing user roles.
			$('.users.panel .update-button').on('click', function(e) {
				that.onClickUserUpdate(this, e);
			});

			// Remove users from groups.
			$('#modal-user-delete .confirm').on('click', function(e) {
				that.onClickUserDelete($('.users.panel .delete-button')[0]);
				$('#modal-user-delete').modal('hide');
			});

			$('#modal-user-delete').on('show.bs.modal', function() {
				var groupName = $('#group-list .group.active').attr('data-name');
				var  userName = $('#user-list  .user.active').attr('data-name');
				$(this).find('.group').text(groupName);
				$(this).find('.user').text(userName);
			});

			if (YodaPortal.storage.session.get('confirm-user-delete', false))
				this.removeUserDeleteConfirmationModal();

			$('#f-user-create').on('keypress', '.select2-chosen', function(e) {
				// NOTE: This requires a patched select2.js where a key event is
				// not killEvent()ed when openOnEnter is false.

				if (e.which === 13) {
					// On 'Enter'.
					$(this).submit();
					e.stopPropagation();
				}
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

			// }}}
			// }}}

			this.selectifyInputs('.selectify-category, .selectify-subcategory, .selectify-user-name');

			if (this.isMember('priv-group-add', YodaPortal.user.username)) {
				var $groupPanel = $('.panel.groups');
				$groupPanel.find('.create-button').removeClass('disabled');
			}

			// Indicate which groups are managed by this user.
			for (var groupName in this.groups) {
				if (this.isManager(groupName, YodaPortal.user.username))
					$('#group-list .group[data-name="' + groupName + '"]').append(
						'<span class="pull-right glyphicon glyphicon-tower" title="You manage this group"></span>'
					);
			}

			var selectedGroup = YodaPortal.storage.session.get('selected-group');
			if (selectedGroup !== null && selectedGroup in this.groups) {
				// Automatically select the last selected group within this session (bound to this tab).
				this.selectGroup(selectedGroup);
			}

			if (Object.keys(this.groups).length < this.CATEGORY_FOLD_THRESHOLD) {
				// Unfold all categories containing non-priv groups if the user has access to less than
				// CATEGORY_FOLD_THRESHOLD groups.
				for (groupName in this.groups)
					if (!groupName.match(/^priv-/))
						this.unfoldToGroup(groupName);
			} else {
				// When the user can only access a single category, unfold it automatically.
				var $categoryEls = $('#group-list .category');
				if ($categoryEls.length === 1)
					this.unfoldToGroup($categoryEls.find('.group').attr('data-name'));
			}
		},

	});
});
