<script>
$(function() {
    Yoda.groupManager.load(<?php echo json_encode($groupHierarchy); ?>,
                           <?php echo json_encode($userType) ?>,
                           <?php echo json_encode($userZone) ?>);
});
</script>

<h1>Group Manager</h1>

<div class="row">
	<div class="col-md-5">
		<div class="card groups">
			<div class="card-header">
				Yoda groups
				<div class="input-group-sm has-feedback float-right hidden">
					<!-- TODO: Search groups. -->
					<input class="form-control form-control-sm" id="group-list-search" type="text" placeholder="Search groups" />
				</div>
			</div>
			<div class="list-group" id="group-list">
<?php
	$i = 0;
	$j = 0;
	$k = 0;

	ksort($groupHierarchy);
	foreach ($groupHierarchy as $category => $subcategories) {
?>
	<div class="list-group-item category" id="category-<?php echo $i?>" data-name="<?php echo htmlentities($category); ?>">
		<a class="name collapsed" data-toggle="collapse" data-parent="#category-<?php echo $i?>" href="#category-<?php echo $i?>-ul">
			<i class="fa fa-caret-right triangle" aria-hidden="true"></i> <?php echo htmlentities($category); ?>
		</a>
		<div class="list-group collapse category-ul" id="category-<?php echo $i?>-ul">
<?php
		ksort($subcategories);
		foreach ($subcategories as $subcategory => $groups) {
?>
			<div class="list-group-item subcategory" data-name="<?php echo htmlentities($subcategory); ?>">
				<a class="name collapsed" data-toggle="collapse" data-parent="#subcategory-<?php echo $j?>" href="#subcategory-<?php echo $j?>-ul">
					<i class="fa fa-caret-right triangle" aria-hidden="true"></i> <?php echo htmlentities($subcategory); ?>
				</a>

				<div class="list-group collapse subcategory-ul" id="subcategory-<?php echo $j?>-ul">
<?php
			ksort($groups);
			foreach ($groups as $group => $properties) {
?>
					<a class="list-group-item list-group-item-action group" id="group-<?php echo $k?>" data-name="<?php echo htmlentities($group); ?>">
						<?php echo htmlentities($group); ?>
					</a>
<?php
				$k++;
			}
?>
				</div>
			</div>
<?php
			$j++;
		}
?>
		</div>
	</div>
<?php
		$i++;
	}
?>

			</div>
			<div class="card-footer">
				<div class="input-group-sm float-left">
					<a class="btn btn-sm btn-danger disabled delete-button" data-action="<?php echo base_url('group-manager/group-delete')?>" data-toggle="modal" data-target="#modal-group-delete">Remove group</a>
				</div>
				<div class="input-group-sm float-right">
					<a class="btn btn-sm btn-primary create-button disabled" data-toggle="modal" data-target="#modal-group-create">Add group</a>
				</div>
			</div>
		</div>
	</div>
	<div class="col-md-7">
		<div class="card  properties">
			<div class="card-header">Group properties</div>
			<div class="card-body" id="group-properties">
				<p class="placeholder-text">
					Please select a group.
				</p>
				<form action="<?php echo base_url('group-manager/group-update')?>" method="POST" class=" hidden" id="f-group-update">
					<div class="form-group row">
						<label class="col-sm-4 col-form-label" for="f-group-update-category">Category</label>
						<div class="col-sm-8">
							<input name="group_category" id="f-group-update-category" class="form-control selectify-category" type="hidden" placeholder="Select one or enter a new name" required data-subcategory="#f-group-update-subcategory" />
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-4 col-form-label" for="f-group-update-subcategory">Subcategory</label>
						<div class="col-sm-8">
							<input name="group_subcategory" id="f-group-update-subcategory" class="form-control selectify-subcategory" type="hidden" placeholder="Select one or enter a new name" required data-category="#f-group-update-category" />
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-4 col-form-label" for="f-group-update-name">Group name</label>
						<div class="col-sm-8">
							<div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text" id="inputGroupPrepend">grp-</span>
                                </div>
								<input name="group_name" id="f-group-update-name" class="form-control" type="text" pattern="^([a-z0-9]|[a-z0-9][a-z0-9-]*[a-z0-9])$" required oninvalid="setCustomValidity('Please enter only lowercase letters, numbers, and hyphens (-). The group name may not start or end with a hyphen.')" onchange="setCustomValidity('')" />
							</div>
						</div>
					</div>
					<div class="form-group row data-classification">
						<label class="col-sm-4 col-form-label" for="f-group-update-data-classification">Data classification</label>
						<div class="col-sm-8">
							<select name="group_data_classification" id="f-group-update-data-classification" class="selectify-data-classification">
								<option value="unspecified" class="unspecified-option">Unspecified</option>
								<option value="public">Public</option>
								<option value="basic">Basic</option>
								<option value="sensitive">Sensitive</option>
								<option value="critical">Critical</option>
							</select>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-4 col-form-label" for="f-group-update-description">Group description</label>
						<div class="col-sm-8">
							<input name="group_description" id="f-group-update-description" class="form-control" type="text" placeholder="Enter a short description" pattern="^[a-zA-Z0-9,.()_ -]*$" oninvalid="setCustomValidity('Please enter only letters a-z, numbers, spaces, comma\'s, periods, parentheses, underscores (_) and hyphens (-).')" onchange="setCustomValidity('')" />
						</div>
					</div>
					<div class="form-group row">
						<div class="offset-sm-4 col-sm-8">
							<input id="f-group-update-submit" class="btn btn-primary" type="submit" value="Update" />
						</div>
					</div>
				</form>
			</div>
		</div>
		<div class="card users">
			<div class="card-header">Group members</h3>
				<div class="input-group-sm has-feedback float-right">
					<input class="form-control form-control-sm mt-1" id="user-list-search" type="text" placeholder="Search users" />
				</div>
			</div>
			<div class="card-body">
				<p class="placeholder-text">
					Please select a group.
				</p>
			</div>
			<div class="list-group" id="user-list">
				<div class="list-group-item item-user-create" hidden>
					<a class="user-create-text" href="#" onclick="return false;">
						Click here to add a new user to this group
					</a>
					<form action="<?php echo base_url('group-manager/user-create')?>" method="POST" class="form-inline" id="f-user-create" hidden>
						<input name="group_name" id="f-user-create-group" type="hidden" />
						<div class="input-group" style="width: 100%;">
							<input name="user_name" id="f-user-create-name" class="form-control form-control-sm selectify-user-name" type="hidden" required placeholder="Enter a username" data-group="#f-user-create-group" />
							<div class="input-group-btn">
								<input id="f-user-create-submit" class="btn btn-primary btn-block btn-sm" type="submit" value="Add" />
							</div>
						</div>
					</form>
				</div>
			</div>
			<div class="card-footer">
				<div class="input-group-sm float-left">
					Change role:
                    <a class="btn btn-sm btn-primary disabled update-button promote-button" data-action="<?php echo base_url('group-manager/user-update')?>" title="Promote the selected user">&uarr;<i class="fa fa-user-circle-o" aria-hidden="true"></i></a>
                    <a class="btn btn-sm btn-primary disabled update-button demote-button" data-action="<?php echo base_url('group-manager/user-update')?>" title="Demote the selected user">&darr;<i class="fa fa-eye" aria-hidden="true"></i></i></a>

					<a class="btn btn-sm btn-danger disabled delete-button" data-action="<?php echo base_url('group-manager/user-delete')?>" data-toggle="modal" data-target="#modal-user-delete" title="Remove the selected user from this group">Remove user</a>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-group-create" tabindex="-1" role="dialog">
	<div class="modal-dialog">
		<div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Create a group</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
			<div class="modal-body">
				<form class="form-horizontal" id="f-group-create" action="<?php echo base_url('group-manager/group-create')?>" method="POST">
					<div class="form-group row">
						<label class="col-sm-4 form-control-label" for="f-group-create-category">Category</label>
						<div class="col-sm-8">
							<input name="group_category" id="f-group-create-category" class="form-control selectify-category" type="hidden" placeholder="Select one or enter a new name" required data-subcategory="#f-group-create-subcategory" />
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-4 form-control-label" for="f-group-create-subcategory">Subcategory</label>
						<div class="col-sm-8">
							<input name="group_subcategory" id="f-group-create-subcategory" class="form-control selectify-subcategory" type="hidden" placeholder="Select one or enter a new name" required data-category="#f-group-create-category" />
						</div>
					</div>
					<hr />
					<div class="form-group row">
						<label class="col-sm-4 form-control-label" for="f-group-create-name">Group name</label>
						<div class="col-sm-8">
							<div class="input-group">

								<div class="input-group-btn" id="f-group-create-prefix-div" title="Choose a group type">
									<button type="button" id="f-group-create-prefix-button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span class="text">research-&nbsp;</span><span class="caret"></span></button>
									<ul class="dropdown-menu">
										<li id="f-group-create-prefix-grp"><a href="#" data-value="grp-">grp-&nbsp;</a></li>
										<li id="f-group-create-prefix-datamanager"><a href="#" data-value="datamanager-">datamanager-&nbsp;</a></li>
										<li><a href="#" data-value="research-">research-&nbsp;</a></li>
										<li><a href="#" data-value="intake-">intake-&nbsp;</a></li>
									</ul>
								</div>
								<input name="group_name" id="f-group-create-name" class="form-control" type="text" pattern="^([a-z0-9]|[a-z0-9][a-z0-9-]*[a-z0-9])$" required oninvalid="setCustomValidity('Please enter only lowercase letters, numbers, and hyphens (-). The group name may not start or end with a hyphen.')" onchange="setCustomValidity('')" />
							</div>
						</div>
					</div>
					<div class="form-group row data-classification">
						<label class="col-sm-4 form-control-label" for="f-group-create-data-classification">Data classification</label>
						<div class="col-sm-8">
							<select name="group_data_classification" id="f-group-create-data-classification" class="selectify-data-classification">
								<option value="unspecified" class="unspecified-option">Unspecified</option>
								<option value="public">Public</option>
								<option value="basic">Basic</option>
								<option value="sensitive">Sensitive</option>
								<option value="critical">Critical</option>
							</select>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-sm-4 form-control-label" for="f-group-create-description">Group description</label>
						<div class="col-sm-8">
							<input name="group_description" id="f-group-create-description" class="form-control" type="text" placeholder="Enter a short description" pattern="^[a-zA-Z0-9,.()_ -]*$" oninvalid="setCustomValidity('Please enter only letters a-z, numbers, spaces, comma\'s, periods, parentheses, underscores (_) and hyphens (-).')" onchange="setCustomValidity('')" />
						</div>
					</div>
					<div class="form-group row">
						<div class="offset-sm-4 col-sm-8">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <input id="f-group-create-submit" class="btn btn-primary" type="submit" value="Add group" />
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal-group-delete" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm group removal</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
          <p>Are you sure you want to remove <strong class="group"></strong>?</p>
          <p>Please make sure that the group's directory is empty before continuing.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button id="f-group-delete" type="button" class="btn btn-danger confirm">Remove</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-user-delete" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Confirm user removal</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
			<div class="modal-body">
				<p>Are you sure you want to remove <strong class="user"></strong> from group <strong class="group"></strong>?</p>
			</div>
			<div class="modal-footer">
				<div class="input-group float-left">
					<div class="checkbox">
						<label for="f-user-delete-no-confirm">
							<input id="f-user-delete-no-confirm" type="checkbox" /> Don't ask again during this session.
						</label>
					</div>
				</div>

                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button id="f-user-delete" type="button" class="btn btn-danger confirm">Remove</button>
			</div>
		</div>
	</div>
</div>
