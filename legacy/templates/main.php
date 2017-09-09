<?php

	function isSecure() {
		$url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

		if (false !== strpos($url,'d=1')) {
			\OCP\Util::writeLog('passwords', 'Passwords app accessed without secure connection.', \OCP\Util::WARN);
			return true;
		}

		// test if at least one is true in:
		// (1) header, (2) port number, (3/4) config.php setting, (5) admin setting
		return
		(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
		|| $_SERVER['SERVER_PORT'] == 443
		|| \OC::$server->getConfig()->getSystemValue('overwriteprotocol', '') == 'https'
		|| \OC::$server->getConfig()->getSystemValue('forcessl', '')
		|| substr(\OC::$server->getConfig()->getSystemValue('overwrite.cli.url', 'http:'), 0, 5) == 'https'
		|| \OC::$server->getConfig()->getAppValue('passwords', 'https_check', 'true') == 'false';
	};

	style('passwords', 'style');
	style('passwords', 'spectrum'); // colour picker
	script('passwords', 'sha512'); // hash function

	// check if secure (https)
	if (isSecure()) {

		$auth_type = \OC::$server->getConfig()->getUserValue(\OC::$server->getUserSession()->getUser()->getUID(), 'passwords', 'extra_auth_type', 'owncloud');
		
		// kill ownCloud/NextCloud auth when user_saml is enabled, it won't work. Master pw DOES work.
		// https://github.com/fcturner/passwords/issues/263
		$user_saml = \OC::$server->getConfig()->getAppValue('user_saml', 'enabled', 'no');
		if ($auth_type == 'owncloud' AND $user_saml == 'yes') {
			$auth_type = 'none';
		}

		$auth_cookie = '';
		if (isset($_COOKIE["oc_passwords_auth"])) {
			$auth_cookie = $_COOKIE["oc_passwords_auth"];
		}
		
		if (($auth_type == 'owncloud' OR $auth_type == 'master') AND $auth_cookie != hash('sha512', \OC::$server->getUserSession()->getUser()->getUID())) { 

			script('passwords', 'auth'); ?>
			
			<div id="app">
				<div id="app-content">
					<div id="app-content-wrapper">
						<?php print_unescaped($this->inc('part.authenticate')); ?>
					</div>
				</div>
			</div>

		<?php } else { 

			script('passwords', 'handlebars');
			script('passwords', 'passwords');
			script('passwords', 'sorttable');
			script('passwords', 'spectrum'); // colour picker
			script('passwords', 'clipboard.min'); // clipboard function

			?>

			<div id="app">
				<div id="app-navigation">
					<?php print_unescaped($this->inc('part.navigation')); ?>
					<?php print_unescaped($this->inc('part.settings')); ?>
				</div>

				<div id="app-content">
					<div id="app-content-wrapper">
						<?php print_unescaped($this->inc('part.content')); ?>
					</div>
					<div id="app-sidebar-wrapper">
						<?php print_unescaped($this->inc('part.sidebar')); ?>
					</div>
				</div>
			</div>
	<?php } ?>

<?php } else {
	\OCP\Util::writeLog('passwords', 'Passwords app blocked; no secure connection.', \OCP\Util::ERROR);
?>

	<div id="app">
		<div id="app-content">
			<div id="app-content-wrapper">
				<?php print_unescaped($this->inc('part.blocked')); ?>
			</div>
		</div>
	</div>

<?php } ?>
