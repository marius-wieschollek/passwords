<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\SettingsService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\PreConditionNotMetException;

/**
 * Class SettingsApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class SettingsApiController extends AbstractApiController {

    /**
     * @var SettingsService
     */
    protected $settings;

    /**
     * SettingsApiController constructor.
     *
     * @param IRequest        $request
     * @param SettingsService $settings
     */
    public function __construct(IRequest $request, SettingsService $settings) {
        parent::__construct($request);
        $this->settings = $settings;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param string $key
     *
     * @return JSONResponse
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function get(string $key): JSONResponse {
        return $this->createJsonResponse($this->settings->get($key));
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
     * @throws ApiException
     * @throws PreConditionNotMetException
     */
    public function set(string $key, $value = null): JSONResponse {
        $this->createJsonResponse(['status' => 'ok']);
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