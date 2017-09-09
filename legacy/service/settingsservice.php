<?php
namespace OCA\Passwords\Service;

use OCP\IConfig;

class SettingsService {

	private $userId;
	private $settings;
	private $appName;

	public function __construct($UserId, IConfig $settings, $AppName) {
		$this->userId = $UserId;
		$this->settings = $settings;
		$this->appName = $AppName;
	}

	/**
	 * get the current settings
	 *
	 * @return array
	 */
	public function get() {
		$settings = array(
				// admin settings
				'app_path' => (string)$this->settings->getAppValue($this->appName, 'app_path', \OC::$SERVERROOT.'/apps'),
				'backup_allowed' => (string)$this->settings->getAppValue($this->appName, 'backup_allowed', 'false'),
				'check_version' => (string)$this->settings->getAppValue($this->appName, 'check_version', 'false'),
				'days_orange' => (string)$this->settings->getAppValue($this->appName, 'days_orange', '150'),
				'days_red' => (string)$this->settings->getAppValue($this->appName, 'days_red', '365'),
				'disable_contextmenu' => (string)$this->settings->getAppValue($this->appName, 'disable_contextmenu', 'false'),
				'https_check' => (string)$this->settings->getAppValue($this->appName, 'https_check', 'true'),
				'icons_allowed' => (string)$this->settings->getAppValue($this->appName, 'icons_allowed', 'true'),
				'icons_service' => (string)$this->settings->getAppValue($this->appName, 'icons_service', 'ddg'),
				// user settings
				'auth_timer' => (string)$this->settings->getUserValue($this->userId, $this->appName, 'auth_timer', '300'),
				'extra_auth_type' => (string)$this->settings->getUserValue($this->userId, $this->appName, 'extra_auth_type', 'owncloud'),
				'hide_attributes' => (string)$this->settings->getUserValue($this->userId, $this->appName, 'hide_attributes', 'false'),
				'hide_passwords' => (string)$this->settings->getUserValue($this->userId, $this->appName, 'hide_passwords', 'true'),
				'hide_usernames' => (string)$this->settings->getUserValue($this->userId, $this->appName, 'hide_usernames', 'false'),
				'master_password' => (string)$this->settings->getUserValue($this->userId, $this->appName, 'master_password', '0'),
				'icons_show' => (string)$this->settings->getUserValue($this->userId, $this->appName, 'icons_show', 'true'),
				'icons_size' => (string)$this->settings->getUserValue($this->userId, $this->appName, 'icons_size', '32'),
				'show_lockbutton' => (string)$this->settings->getUserValue($this->userId, $this->appName, 'show_lockbutton', 'true'),
				'timer' => (string)$this->settings->getUserValue($this->userId, $this->appName, 'timer', '60')
		);
		return $settings;
	}

	public function getKey($key) {
		return $settings.$key;
	}

	/**
	 * set user setting
	 *
	 * @param $setting
	 * @param $value
	 * @return bool
	 */
	public function set($setting, $value) {
		return $this->settings->setUserValue($this->userId, $this->appName, $setting, $value);
	}

	/**
	 * set admin setting
	 *
	 * @param $setting
	 * @param $value
	 * @param $admin1
	 * @param $admin2
	 * @return bool
	 */
	public function setadmin($setting, $value, $admin1, $admin2) {
		if ($setting == 'resetmaster') {
			$resetMaster = $this->settings->setUserValue($value, $this->appName, 'master_password', '0');
			$resetAuthtype = $this->settings->setUserValue($value, $this->appName, 'extra_auth_type', 'owncloud');
			return ($resetMaster && $resetAuthtype);
		} else {
			return $this->settings->setAppValue($this->appName, $setting, $value);
		}
	}
}
