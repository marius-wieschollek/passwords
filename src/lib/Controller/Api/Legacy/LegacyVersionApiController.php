<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.01.18
 * Time: 10:47
 */

namespace Controller\Api\Legacy;

use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\JSONResponse;

/**
 * Class LegacyVersionApiController
 *
 * @package Controller\Api\Legacy
 */
class LegacyVersionApiController extends ApiController {

    const LEGACY_API_VERSION = 21;

    /**
     * @NoCSRFRequired
     * @NoAdminRequired
     */
    public function index(): JSONResponse {
        return new JSONResponse(self::LEGACY_API_VERSION);
    }
}