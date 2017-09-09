<ul>
	<input id="lock_btn" class="button icon-lock nav-btn" type="button" value="<?php p($l->t("Lock app")); ?>">
	<li id="list_active" class="with-counter active">
		<a href="#"><?php p($l->t("Active passwords")); ?></a>
		<div class="app-navigation-entry-utils">
			<ul>
				<li class="app-navigation-entry-utils-counter menu_passwords_active"></li>
			</ul>
		</div>
	</li>
	<li id="list_trash" class="with-counter">
		<a href="#"><?php p($l->t("Trash bin")); ?></a>
		<div class="app-navigation-entry-utils">
			<ul>
				<li class="app-navigation-entry-utils-counter menu_passwords_trashbin"></li>
			</ul>
		</div>
	</li>

	<div id="PasswordsTableSearch">
		<hr>
		<input id="search_text" type="text" placeholder="<?php p($l->t("Search for")); ?>..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"><br>
		<input id="search_clear" type="button" value="<?php p($l->t("Clear")); ?>">
		<hr>
	</div>

	<li id="list_category">
		<a href="#"><?php p($l->t("Filter")); ?>:</a>
		<div class="app-navigation-entry-utils nav-cat-counter">
			<ul>
				<li id="nav_category_list" class="app-navigation-entry-utils-counter"></li>
			</ul>
		</div>
	</li>

	<div id="nav_buttons">
		<button id="add_new" class="button icon-add nav-btn" type="button"><?php p($l->t("Add new password")); ?></button>
		<button id="editCategories" class="button icon-category nav-btn" type="button"><?php p($l->t("Edit categories")); ?></button>
		<button id="trashAll" class="button icon-delete nav-btn" type="button"><?php p($l->t("Move all to trash")); ?></button>
	</div>

	<div id="add_password_div">
		<div>
			<img src="<?php p(\OC::$server->getURLGenerator()->imagePath('passwords', 'blank.svg')); ?>" class="icon-link">
			<input type="text" id="new_address" placeholder="<?php p($l->t("Full URL (optional)")); ?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
		</div>
		<div>
			<img src="<?php p(\OC::$server->getURLGenerator()->imagePath('passwords', 'blank.svg')); ?>" class="icon-rename">
			<textarea id="new_notes" placeholder="<?php p($l->t("Notes (optional)")); ?>"></textarea>
		</div>
		<div>
			<img src="<?php p(\OC::$server->getURLGenerator()->imagePath('passwords', 'blank.svg')); ?>" class="icon-category">
			<div id="new_category"></div>
		</div>
		<div>
			<img src="<?php p(\OC::$server->getURLGenerator()->imagePath('passwords', 'blank.svg')); ?>" class="icon-share" id="websiteimg">
			<input type="text" id="new_website" placeholder="<?php p($l->t("site.com or Name Inc.")); ?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
		</div>
		<div>
			<img src="<?php p(\OC::$server->getURLGenerator()->imagePath('passwords', 'blank.svg')); ?>" class="icon-user">
			<input type="text" id="new_username" placeholder="<?php p($l->t("Login name or e-mail")); ?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
		</div>
		<div>
			<img src="<?php p(\OC::$server->getURLGenerator()->imagePath('passwords', 'blank.svg')); ?>" class="icon-password">
			<input type="text" id="new_password" class="password_field" placeholder="<?php p($l->t("Password")); ?>" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
		</div>
		<div id="generate-password">
			<p id="generate_strength"></p>
			<div id="generate_passwordtools">
				<input class="checkbox" type="checkbox" id="gen_lower" checked>
				<label for="gen_lower"><?php p($l->t("Lowercase characters")); ?></label>
				<br>
				<input class="checkbox" type="checkbox" id="gen_upper" checked>
				<label for="gen_upper"><?php p($l->t("Uppercase characters")); ?></label>
				<br>
				<input class="checkbox" type="checkbox" id="gen_numbers" checked>
				<label for="gen_numbers"><?php p($l->t("Numbers")); ?></label>
				<br>
				<input class="checkbox" type="checkbox" id="gen_special" checked>
				<label for="gen_special"><?php p($l->t("Punctuation marks")); ?></label>
				<br>
				<label>
					<input type="text" id="gen_length" value="30"> <?php p($l->t("characters")); ?>
				</label>
				<br>
			</div>
			<input id="new_generate" type="button" class="button" value="<?php p($l->t("Generate password")); ?>">
		</div>
	</div>

</ul>
