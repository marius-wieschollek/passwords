<div id="passwordSidebar">
	<h3><?php print_unescaped($l->t("Password info")); ?></h3>
	<button id="sidebarClose">X</button>
	<input type="text" id="sidebarRow">
	<p class="" id="sidebarWebsiteHeader"><strong><?php print_unescaped($l->t("Website or company")); ?>:</strong></p>
	<p class="" id="sidebarWebsite">???</p>
	<p class="" id="sidebarAddressHeader"><strong><?php print_unescaped($l->t("Full URL")); ?>:</strong></p>
	<p class="" id="sidebarAddress">???</p>
	<p class="" id="sidebarUsernameHeader"><strong><?php print_unescaped($l->t("Login name")); ?>:</strong></p>
	<p class="" id="sidebarUsername">???</p>

	<p class="leftCol"><?php print_unescaped($l->t("Length")); ?>:</p><p class="rightCol" id="sidebarLength">???</p>
	<p class="leftCol"><?php print_unescaped($l->t("Strength")); ?>:</p><p class="rightCol" id="sidebarStrength">???</p>
	<p class="leftCol">a-z:</p><p class="rightCol" id="sidebarLower">???</p>
	<p class="leftCol">A-Z:</p><p class="rightCol" id="sidebarUpper">???</p>
	<p class="leftCol">0-9:</p><p class="rightCol" id="sidebarNumber">???</p>
	<p class="leftCol">!@#:</p><p class="rightCol" id="sidebarSpecial">???</p>
	<p class="leftCol"><?php print_unescaped($l->t("Last changed")); ?>:</p><p class="rightCol" id="sidebarChanged">???</p>
	<br>
	<p><?php print_unescaped($l->t("Category")); ?>:</p>
	<textarea id="sidebarCategories" disabled></textarea> 
	<p><?php print_unescaped($l->t("Notes")); ?>:</p>
	<textarea id="sidebarNotes" disabled></textarea>
</div>
