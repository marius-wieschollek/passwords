<?php
namespace OCA\Passwords\Controller;

use \OCP\IRequest;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\ContentSecurityPolicy;

class PageController extends Controller {

	public function __construct($AppName, IRequest $request) {
		parent::__construct($AppName, $request);
	}

	/**
	* @NoAdminRequired
	* @NoCSRFRequired
	*/
	public function index() {
		//if (version_compare(\OC_Util::getHumanVersion(), '8.1', '>=')) {
		if (substr(\OC_Util::getHumanVersion(), 0, 3) != '8.0') {
			// OC >= 8.1 (OC9 too)
			$response = new TemplateResponse('passwords', 'main');
			$csp = new ContentSecurityPolicy();
			$csp->addAllowedImageDomain('https://icons.duckduckgo.com');
			$csp->addAllowedImageDomain('https://www.google.com');
			$csp->addAllowedObjectDomain('\'self\''); // for clipboard function
			$response->setContentSecurityPolicy($csp);
			return $response;
		} else {
			// OC =< 8.0.4
			return new TemplateResponse('passwords', 'main');
		}
	}

}
