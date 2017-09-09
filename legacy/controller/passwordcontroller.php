<?php
namespace OCA\Passwords\Controller;

use \OCP\IRequest;
use \OCP\AppFramework\Http\DataResponse;
use \OCP\AppFramework\Controller;

use OCA\Passwords\Service\PasswordService;

class PasswordController extends Controller {

	private $service;
	private $userId;

	use Errors;

	public function __construct($AppName, IRequest $request, PasswordService $service, $UserId){
		parent::__construct($AppName, $request);
		$this->service = $service;
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 */
	public function index() {
		return new DataResponse($this->service->findAll($this->userId));
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function show($id) {
		return $this->handleNotFound(function () use ($id) {
			return $this->service->find($id, $this->userId);
		});
	}

	/**
	 * @NoAdminRequired
	 */
	public function create($website, $pass, $loginname, $address, $notes, $category, $deleted) {
		return $this->service->create($website, $pass, $loginname, $address, $notes, $category, $deleted, $this->userId);
	}

	/**
	 * @NoAdminRequired
	 */	
	public function sendmail() {
		$kind = $_POST['kind'];
		$website = $_POST['website'];
		$sharewith = $_POST['sharewith'];
		$domain = $_POST['domain'];
		$fullurl = $_POST['fullurl'];
		$instancename = $_POST['instancename'];
		return $this->handleNotFound(function () use ($kind, $website, $sharewith, $domain, $fullurl, $instancename) {
			return $this->service->sendmail($kind, $website, $sharewith, $domain, $fullurl, $instancename, $this->userId);
		});
	}

	/**
	 * @NoAdminRequired
	 */	
	public function update($id, $website, $pass, $loginname, $address, $notes, $sharewith, $category, $deleted, $datechanged) {
		return $this->handleNotFound(function () use ($id, $website, $pass, $loginname, $address, $notes, $sharewith, $category, $deleted, $datechanged) {
			return $this->service->update($id, $website, $pass, $loginname, $address, $notes, $sharewith, $category, $deleted, $datechanged, $this->userId);
		});
	}

	/**
	 * @NoAdminRequired
	 */
	public function destroy($id) {
		return $this->handleNotFound(function () use ($id) {
			return $this->service->delete($id, $this->userId);
		});
	}

}
