<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller\Api;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\UserSettingsService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class SettingsApiController
 *
 * @package OCA\Passwords\Controller\Api
 */
class SettingsApiController extends AbstractApiController {

    /**
     * @var UserSettingsService
     */
    protected $settings;

    /**
     * SettingsApiController constructor.
     *
     * @param IRequest            $request
     * @param UserSettingsService $settings
     */
    public function __construct(IRequest $request, UserSettingsService $settings) {
        parent::__construct($request);
        $this->settings = $settings;
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     * @throws \Exception
     */
    public function get(): JSONResponse {
        $params = $this->getParameterArray();
        if(empty($params)) return $this->createJsonResponse([]);

        $settings = [];
        foreach($params as $key) {
            $settings[ $key ] = $this->settings->get($key);
        }

        return $this->createJsonResponse($settings);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     * @throws ApiException
     */
    public function set(): JSONResponse {
        $params = $this->getParameterArray();
        if(empty($params)) return $this->createJsonResponse([]);

        $settings = [];
        foreach($params as $key => $value) {
            $settings[ $key ] = $this->settings->set($key, $value);
        }

        return $this->createJsonResponse($settings);
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @param array|null $scopes
     *
     * @return JSONResponse
     * @throws \Exception
     */
    public function list(array $scopes = null): JSONResponse {
        return $this->createJsonResponse(
            $this->settings->list($scopes)
        );
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     * @throws \Exception
     */
    public function reset(): JSONResponse {
        $params = $this->getParameterArray();
        if(empty($params)) return $this->createJsonResponse([]);

        $settings = [];
        foreach($params as $key) {
            $settings[ $key ] = $this->settings->reset($key);
        }

        return $this->createJsonResponse($settings);
    }
}