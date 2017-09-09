<?php
$days_orange = \OC::$server->getConfig()->getAppValue('passwords', 'days_orange', '150');
$days_red = \OC::$server->getConfig()->getAppValue('passwords', 'days_red', '365');
$version = \OC::$server->getConfig()->getAppValue('passwords', 'installed_version', '');
?>
<div class="icon-loading" id="loading">
	<p id="loading_text"><?php p($l->t("Decrypting passwords")); ?>...</p>
</div>

<div id="section_update">
	<div id="update_box">
		<h2><?php p($l->t("Update required")); ?></h2>
		<p id="update_p_start"><?php p($l->t("%s has been updated to version %s, which requires an update of your passwords table. Do not close this window during the update.", array($theme->getName() . ' ' . $l->t("Passwords"), $version))); ?></p>
		<input type="button" id="update_start_btn" value="<?php p($l->t("Update")); ?>">
		<div id="update_progress">
			<input id="update_progress_active"/>
			<input id="update_progress_total"/>
			<p id="update_progress_text">0 / 0</p>
			<div id="update_progress_bar">
				<div id="update_progress_done"></div>
			</div>
		</div>
		<div id="update_done">
			<p id="update_p_done"><b><?php p($l->t("Update done. This page must now reload.")); ?></b></p>
			<input type="button" id="update_done_btn" value="<?php p($l->t("Reload")); ?>">
		</div>
	</div>
	<div id="update_overlay"></div>
</div>
<div id="section_table">
	<div id="cleartrashbin">
		<input type="button" id="delete_trashbin" value="<?php p($l->t("Delete all items in trash bin")); ?>">
	</div>

	<div id="commands_popup">
		<input type="button" id="btn_edit" value="<?php p(strtolower($l->t("Edit"))); ?>">
		<input type="button" id="btn_view" value="<?php p(strtolower($l->t("View"))); ?>">
		<input type="button" id="btn_copy" value="<?php p(strtolower($l->t("Copy"))); ?>">
		<input type="button" id="btn_share" value="<?php p(strtolower($l->t("Share"))); ?>">
		<input type="button" id="btn_clone" value="<?php p(strtolower($l->t("Clone"))); ?>">
		<input type="text" id="cmd_id">
		<input type="text" id="cmd_type">
		<input type="text" id="cmd_value">
		<input type="text" id="cmd_website">
		<input type="text" id="cmd_address">
		<input type="text" id="cmd_loginname">
		<input type="text" id="cmd_pass">
		<input type="text" id="cmd_notes">
		<input type="text" id="cmd_sharedwith">
		<input type="text" id="cmd_category">
	</div>
	<div id="zeroclipboard_copied">
		<p id="copied_text"><?php p($l->t("Value copied to clipboard")); ?></p>
	</div>

	<div id="ShareUsersTable">
		<table id="ShareUsersTableContent"></table>
		<select id="ShareUsers"></select>
	</div>

	<div id="PasswordsTable">

		<table id="PasswordsTableTestOld">
			<!-- backwards compatibility for version prior to v17, will be used for update to new database form -->
		</table>

		<table id="PasswordsTableContent" class="sortable">
			<tr>
				<th id="column_website" class="sorttable_alpha"><?php p($l->t("Website or company")); ?></th>
				<th id="column_username" class="sorttable_alpha"><?php p($l->t("Login name")); ?></th>
				<th id="column_password" class="sorttable_alpha"><?php p($l->t("Password")); ?></th>
				<th id="column_strength"><?php p($l->t("Strength")); ?></th>
				<th id="column_datechanged"><?php p($l->t("Last changed")); ?></th>
				<th id="column_category"><?php p($l->t("Category")); ?></th>
				<th id="column_notes"></th>
				<th id="column_info"></th>
				<th id="column_share"></th>
				<th id="column_delete"></th>
			</tr>

		</table>

		<br>
		<p align="center"><?php print_unescaped($l->t("Click on a <b>column head</b> to sort the column.")); ?></p>
		<p align="center"><?php print_unescaped($l->t("Click on a <b>user name</b> or a <b>password</b> to be able to copy it to the clipboard.")); ?></p>
		<p align="center"><?php print_unescaped($l->t("Click on a <b>website</b> to open it in a new tab.")); ?></p>
		<br>
		<p align="center"><?php print_unescaped($l->t("The <b>password date</b> becomes <orange>orange after %s days</orange> and <red>red after %s days</red>.", array($days_orange, $days_red))); ?></p>
		<p align="center"><?php print_unescaped($l->t("The <b>strength value</b> is interpreted as") . " <red>" . strtolower($l->t("Weak")) .  "</red> (0-7), <orange>" . strtolower($l->t("Moderate")) . "</orange> (8-14) " . $l->t("or") . " <green>" . strtolower($l->t("Strong")) . "</green> (>= 15)."); ?></p>
		<br>

	</div>

	<script id="template-passwords-serialize" type="text/x-handlebars-template">
		{{#each passwords}}
			"website" : "{{ website }}", "pass" : "{{ pass }}", {{#if properties}}{{ properties }}{{/if}}, "deleted" : "{{ deleted }}", "id" : "{{ id }}", "user_id" : "{{ user_id }}"<br>
		{{/each}}
	</script>

	<script id="template-passwords-old" type="text/x-handlebars-template">

		{{#each passwords}}
			<tr>
				<td>{{ website }}</td>
				<td>{{ loginname }}</td>
				<td>{{ pass }}</td>
				<td>{{ properties }}</td>
				<td>{{ creation_date }}</td>
				<td>{{ id }}</td>
				<td>{{ user_id }}</td>
				<td>{{ address }}</td>
				<td>{{ notes }}</td>
				<td>{{ deleted }}</td>
			</tr>
		{{/each}}
		
	</script>

	<div id="emptycontent">
		<div class="icon-passwords"></div>
		<h2><?php p($l->t("No passwords yet")); ?></h2>
		<p><?php p($l->t("Create some new passwords!")); ?></p> 
	</div>
	<div id="emptytrashbin">
		<div class="icon-delete"></div>
		<h2><?php p($l->t("Empty trash bin")); ?></h2>
		<p><?php p($l->t("Deleted passwords will be shown here!")); ?></p> 
	</div>
</div>

<div id="section_categories">
	<input type="button" id="back_to_passwords" value="<< <?php p($l->t("Back to passwords")); ?>">
	<br>
	<h2><?php p($l->t("Create a new category")); ?></h2>
	<input id="cat_name" type="text" value="" placeholder="<?php p($l->t("Name of category")); ?>...">
	<input id="colorpicker" type="color" name="color">
	<input id="cat_colour" type="text" value="#eeeeee">
	<input type="button" id="cat_add" value="<?php p($l->t("Add category")); ?>">
	<h2><?php p($l->t("List of categories")); ?></h2>
	<div id="CategoriesTable">
		<table id="CategoriesTableContent"></table>
	</div>

	<script id="template-categories" type="text/x-handlebars-template">

		{{#each categories}}
			<tr>
				<td></td>
				<td class="hide_always catTable_id">{{ id }}</td>
				<td class="hide_always catTable_userid">{{ user_id }}</td>
				<td class="hide_always catTable_name">{{ category_name }}</td>
				<td class="hide_always catTable_bg">{{ category_colour }}</td>
				<td class="icon-delete"></td>
			</tr>
		{{/each}}
		
	</script>

	<div id="emptycategories">
		<p><?php p($l->t("No categories yet")); ?>.</p> 
	</div>

</div>

<div id="idleTimer">
	<p id="countSec"></p>
	<p id="explnSec"><?php p($l->t("You will be logged off automatically when this countdown reaches 0")); ?>.</p>
	<div id="outerRing"></div>
</div>
