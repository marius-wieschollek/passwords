<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 13.02.18
 * Time: 21:58
 */

namespace OCA\Passwords\Controller\Api;

use OCP\AppFramework\Http\JSONResponse;

/**
 * Class SettingsApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class SettingsApiController extends AbstractApiController {

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $key
     *
     * @return JSONResponse
     */
    public function get(string $key): JSONResponse {

        return $this->createJsonResponse($key);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $key
     * @param null   $value
     *
     * @return JSONResponse
     */
    public function set(string $key, $value = null): JSONResponse {

        return $this->createJsonResponse(['status' => 'ok']);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $scope
     *
     * @return JSONResponse
     */
    public function list(string $scope = null): JSONResponse {

        return $this->createJsonResponse([]);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $key
     *
     * @return JSONResponse
     */
    public function reset(string $key): JSONResponse {

        return $this->createJsonResponse(['key' => $key, 'value' => 'value']);
    }
}