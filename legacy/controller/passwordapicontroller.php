<?php
namespace OCA\Passwords\Controller;

use \OCP\IRequest;
use \OCP\AppFramework\Http\DataResponse;
use \OCP\AppFramework\ApiController;

use OCA\Passwords\Service\PasswordService;

class PasswordApiController extends ApiController {

	private $service;
	private $userId;

	use Errors;

	public function __construct($AppName, IRequest $request, PasswordService $service, $UserId) {
		// allow getting passwords and editing/saving them
		parent::__construct(
			$AppName,
			$request,
			'GET, POST, DELETE, PUT, PATCH',
			'Authorization, Content-Type, Accept',
			86400);
		$this->service = $service;
		$this->userId = $UserId;
	}

	/**
	 * @CORS
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
	public function index() {
		return new DataResponse($this->service->findAll($this->userId, true));
	}

	/**
	 * @CORS
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param int $id
	 */
	public function show($id) {
		return $this->handleNotFound(function () use ($id) {
			return $this->service->find($id, $this->userId);
		});
	}

	/**
	 * @CORS
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param string $loginname
	 * @param string $website
	 */
	public function create($website, $pass, $loginname, $address, $notes, $category, $deleted) {
		return $this->service->create($website, $pass, $loginname, $address, $notes, $category, $deleted, $this->userId);
	}

	/**
	 * @CORS
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string $loginname
	 * @param string $website
	 */
	public function update($id, $website, $pass, $loginname, $address, $notes, $sharewith, $category, $deleted, $datechanged) {
		return $this->handleNotFound(function () use ($id, $website, $pass, $loginname, $address, $notes, $sharewith, $category, $deleted, $datechanged) {
			return $this->service->update($id, $website, $pass, $loginname, $address, $notes, $sharewith, $category, $deleted, $datechanged, $this->userId);
		});
	}

	/**
	 * @CORS
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 *
	 * @param int $id
	 */
	public function destroy($id) {
		return $this->handleNotFound(function () use ($id) {
			return $this->service->delete($id, $this->userId);
		});
	}

}
