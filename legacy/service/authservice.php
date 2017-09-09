<?php
namespace OCA\Passwords\Service;

use OCP\IConfig;

class AuthService {

	private $userId;
	private $auth;
	private $appName;

	public function __construct($UserId, IConfig $auth, $AppName) {
		$this->userId = $UserId;
		$this->auth = $auth;
		$this->appName = $AppName;
	}

	public function checkauth($pass, $type) {

		$result = false;

		if ($type == 'owncloud') {
			// support for LDAP
			if (\OC::$server->getUserSession()->getUser()->getBackendClassName() == 'LDAP') {
				$username = \OC::$server->getUserSession()->getLoginName();
			} else {
				$username = $this->userId;
			}
			$result = (\OC::$server->getUserManager()->checkPassword($username, $pass) != false);	
			// on fail, OC will add an entry to the log, so clarify that:
			if ($result == false) {
				\OCP\Util::writeLog('passwords', "Authentication failed: '" . $username . "'", \OCP\Util::WARN);
			}
		}
		
		if ($type == 'master') {
			$master = \OC::$server->getConfig()->getUserValue($this->userId, 'passwords', 'master_password', '0');
			$result = ($master == hash('sha512', $pass));
		}
		
		if ($result == true) {
			return 'pass';
		} else {
			return 'fail';
		}
	}
}
