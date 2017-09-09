<?php

namespace OCA\Passwords\Controller;

use \OCA\Passwords\Service\SettingsService;
use \OCP\IRequest;
use \OCP\AppFramework\Controller;

use \OCP\AppFramework\Http\DataResponse;

class SettingsController extends Controller {

	private $settingsService;
	private $userId;

	use errors;

	public function __construct($AppName, IRequest $request, SettingsService $settingsService, $UserId) {
		parent::__construct($AppName, $request, $UserId);
		$this->settingsService = $settingsService;
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 */
	public function get(){
		return $this->handleNotFound(function () {
			return $this->settingsService->get();
		});
	}

	public function getKey($key){
		return $this->handleNotFound(function () {
			return [$this->settingsService->getKey($key)];
		});
	}

	/**
	 * @NoAdminRequired
	 */
	public function set($setting, $value){
		if ($setting == 'auth_timer' && $value < 3) {
			// will else never work; the auth will keep coming
			$value = 3;
		}
		return $this->handleNotFound(function () use ($setting, $value) {
			return $this->settingsService->set($setting, $value);
		});
	}

	public function setadmin($setting, $value, $admin1, $admin2){
		return $this->handleNotFound(function () use ($setting, $value, $admin1, $admin2) {
			return $this->settingsService->setadmin($setting, $value, $admin1, $admin2);
		});
	}
}
