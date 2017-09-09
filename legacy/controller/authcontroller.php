<?php

namespace OCA\Passwords\Controller;

use \OCA\Passwords\Service\AuthService;
use \OCP\IRequest;
use \OCP\AppFramework\Controller;

use \OCP\AppFramework\Http\DataResponse;

class AuthController extends Controller {

	private $authService;
	private $userId;

	use errors;

	public function __construct($AppName, IRequest $request, AuthService $authService, $UserId) {
		parent::__construct($AppName, $request, $UserId);
		$this->authService = $authService;
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 */
	public function checkauth() {

		$pass = $_POST['password'];
		$type = $_POST['authtype'];

		return $this->handleNotFound(function () use ($pass, $type) {
			return $this->authService->checkauth($pass, $type);
		});
	}
}
