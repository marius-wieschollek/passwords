<?php
	$auth_type = OC::$server->getConfig()->getUserValue(OC::$server->getUserSession()->getUser()->getUID(), 'passwords', 'extra_auth_type', 'owncloud');
	$instancename = $theme->getName();
	$passwordsname = $l->t("Passwords");
	$passwordsversion = OC::$server->getConfig()->getAppValue('passwords', 'installed_version', '');

	switch ($auth_type) {
		case 'master':
			$auth_str = $l->t('Master password');
			break;
		case 'owncloud':
		default:
			// this will set the text to 'NextCloud password' on NextCloud
			$auth_str = preg_replace('/owncloud/i', $theme->getName(), $l->t('ownCloud password'));
			break;
	}
?>
<div id="auth_div">
	<h2><?php p($l->t('Authenticate')); ?></h2>
	<h3><?php p($auth_str); ?>:</h3>
	<form id="auth_form">
		<input id="auth_pass" type="password" placeholder="<?php p($l->t('Password')); ?>" auth-type="<?php p($auth_type); ?>"><br>
		<p id="invalid_auth"><?php p($l->t('This password is invalid. Please try again.')); ?></p>
		<input class="button primary" type="submit" id="auth_btn" value="<?php p($l->t('Authenticate')); ?>">
		<p><?php p($l->t('You need to authenticate using your password') . '. ' . $l->t('This can be changed in this app, after you have successfully authenticated')); ?>.</p>
	</form>
</div>
<div id="auth_footer">
	<p id="githubref"><a href="https://github.com/marius-wieschollek/passwords" target="_blank"><?php p($instancename . ' ' . $passwordsname) ?></a> - <?php p($l->t('Version')); ?> <?php p($passwordsversion) ?></p>
</div>
