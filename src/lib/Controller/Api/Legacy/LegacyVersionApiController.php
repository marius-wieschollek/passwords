<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api\Legacy;

use OCA\Passwords\AppInfo\Application;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class LegacyVersionApiController
 *
 * @package Controller\Api\Legacy
 */
class LegacyVersionApiController extends ApiController {

    const LEGACY_API_VERSION = 21;

    /**
     * LegacyVersionApiController constructor.
     *
     * @param IRequest $request
     */
    public function __construct(IRequest $request) {
        parent::__construct(
            Application::APP_NAME,
            $request,
            'GET',
            'Authorization, Content-Type, Accept',
            1728000
        );
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function index(): JSONResponse {
        return new JSONResponse(self::LEGACY_API_VERSION);
    }
}