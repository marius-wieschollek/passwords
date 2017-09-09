<?php
// get installed version
$thisVersion = OC::$server->getConfig()->getAppValue('passwords', 'installed_version', '');

// check if tracking new version is allowed
$checkVersion = OC::$server->getConfig()->getAppValue('passwords', 'check_version', 'false') == 'true';
if ($checkVersion) {
	// get latest master version
	$doc = new DOMDocument();
	$doc->load('https://raw.githubusercontent.com/marius-wieschollek/passwords/stable/appinfo/info.xml');
	$githubVersion = $doc->getElementsByTagName("info")->item(0)->getElementsByTagName("version")->item(0)->nodeValue;
}

$app_path = OC::$server->getConfig()->getAppValue('passwords', 'app_path', OC::$SERVERROOT . '/apps');

?>
<div class="section" id="passwords-admin">
	<div 
		id="password-settings" 
		root-folder="<?php p(OC::$SERVERROOT) ?>"
		app-path="<?php p($app_path) ?>"
		instance-name="<?php p($theme->getName()) ?>" >
	</div>
	<h2><?php p($l->t('Passwords')); ?></h2>

	<div>
		<h3><?php p($l->t('Version')); ?></h3>
		
		<p><?php p($l->t('Installed') . ': v' . $thisVersion); ?></p>
		
		<?php
		if ($checkVersion) {
			if (version_compare($thisVersion, $githubVersion) == -1) { ?>
				<p>
				<strong><?php p($l->t('Available') . ': v' . $githubVersion); ?></strong>
				</p>
				<br>
				<a href="https://github.com/marius-wieschollek/passwords/archive/master.zip" class="button"><?php p($l->t('Download %s', 'ZIP')); ?></a>
				<a href="https://github.com/marius-wieschollek/passwords/archive/master.tar.gz" class="button"><?php p($l->t('Download %s', 'TAR')); ?></a>
				<br>
				<br>
				<a href="https://github.com/marius-wieschollek/passwords/blob/master/CHANGELOG.md" class="button" target="_blank"><?php p($l->t('List of changes since %s', 'v' . $thisVersion)); ?></a>
				<a href="https://github.com/marius-wieschollek/passwords/releases" class="button" target="_blank"><?php p($l->t('View all releases')); ?></a>
				<a href="https://github.com/marius-wieschollek/passwords" class="button" target="_blank"><?php p($l->t('Visit %s', 'GitHub')); ?></a>
				<br>
				<br>
				<p><?php p($l->t('Or update with CLI')); ?>:</p>
				<p class="gitcode">sudo rm -rf <?php p($app_path); ?>/passwords</p>
				<p class="gitcode">sudo git clone https://github.com/marius-wieschollek/passwords.git <?php p($app_path); ?>/passwords</p>
				<p class="gitcode">sudo -u <?php p(posix_getpwuid(fileowner(OC::$SERVERROOT . '/config/config.php'))['name']) ?> php <?php p(OC::$SERVERROOT); ?>/occ upgrade</p>
			<?php } else { ?>
				<p><?php p($l->t('The latest version is already installed')); ?></p>

				<input class="checkbox" type="checkbox" id="check_version">
				<label for="check_version"><?php p($l->t('Check for new versions here (requires reload of this page)')); ?></label>

			<?php } ?>
		<?php } else {
			?>

			<input class="checkbox" type="checkbox" id="check_version">
			<label for="check_version"><?php p($l->t('Check for new versions here (requires reload of this page)')); ?></label>

			<?php
		} ?>
	</div>
	
	<?php
	// Block extra auth when using user_saml, so let admin know:
	$user_saml = OC::$server->getConfig()->getAppValue('user_saml', 'enabled', 'no');
	if ($user_saml == 'yes') { ?>
		<div>
			<h3><?php p($l->t('Extra authentication')); ?></h3>
			<p><strong><?php p($l->t('Extra authentication is disabled for all users with their own %s password, since you are using user_saml authentication.', $theme->getName())); ?></strong></p>
		</div>
	<?php } ?>
	
	<div>
		<h3><?php p($l->t('App location')); ?></h3>
		<label> 
			<?php p($l->t('App location')); ?>: <input type="text" id="app_path" value=""> /passwords
			<p class="descr">
			<em><?php p($l->t('Change this to support other app folders')); ?>.<br>
				<?php p($l->t('An invalid folder name will break the app and these settings too! The value is saved to the database table %s', "`oc_appconfig`.`configkey` = 'app_path'")); ?>.</em>
		</p>
		</label>
	</div>

	<div>
		<h3><?php p($l->t('Security')); ?></h3>

		<label>
		<input class="checkbox" type="checkbox" id="https_check">
		<label for="https_check"><?php p($l->t('Block app when not connected to %s using a secured connection', array($theme->getName()))); ?></label>
		<p class="descr">
			<em class="https_warning"><?php p($l->t('Turning this off is HIGHLY DISCOURAGED')); ?>.</em>
		</p>

		<input class="checkbox" type="checkbox" id="backup_allowed">
		<label for="backup_allowed"><?php p($l->t('Allow users to download a backup as an unencrypted, plain text file')); ?></label>

		<br>

		<input class="checkbox" type="checkbox" id="disable_contextmenu">
		<label for="disable_contextmenu"><?php p($l->t('Disable browsers context menu')); ?></label>
		<p class="descr">
			<em><?php p($l->t('This will make it harder for users to use the functions of the browsers context menu, but it may really be annoying to some users')); ?>.</em>
		</p>

		<label for="masterresetid"><?php p($l->t('Reset master password for user')); ?> (user ID):</label>
		<input type="text" id="masterresetid">
		<button id="masterreset" class="button"><?php p($l->t('Reset')); ?></button>

	</div>

	<div>
		<h3><?php p($l->t('Website icons')); ?></h3>
		<input class="checkbox" type="checkbox" id="icons_allowed">
		<label for="icons_allowed"><?php p($l->t('Allow website icons')); ?></label>
		<p class="descr">
			<em><?php p($l->t('This will help users finding a website they are looking for in their list and it looks rather nice too, but it will send your IP address to another server')); ?>.</em>
		</p>
		<div>
			<input class="radio" type="radio" id="ddg_value" name="icons_service_grp">
			<label for="ddg_value"><?php p($l->t('Use DuckDuckGo')); ?></label>
			<br>
			<input class="radio" type="radio" id="ggl_value" name="icons_service_grp"> 
			<label for="ggl_value"><?php p($l->t('Use Google')); ?></label>
			<p class="descr radiotext">
				<?php p($l->t("Google DOES track your moves. Use DuckDuckGo preferably, since they don't")); ?>: <a class="linkDDG" href="http://donttrack.us" target="_blank">http://donttrack.us</a>.
			</p>
		</div>
	</div>

	<div>
		<h3><?php p($l->t('Colour of password date')); ?></h3>
		<label> 
			<green><?php p($l->t('Green') . ': 0 ' . $l->t('to')); ?> <input type="text" id="days_orange" class="fieldDays" value=""> <?php p($l->t('days')); ?></green>
		</label>
		<br>
		<label> 
			<orange id="daysOrange"></orange> <input type="text" id="days_red" class="fieldDays" value=""> <orange><?php p($l->t('days')); ?></orange>
		</label>
		<br>
		<label>
			<red id="daysRed"></red>
		</label>
	</div>

	<span class="msg-passwords"></span>

</div>
