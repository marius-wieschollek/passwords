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
     * @return JSONResponse
     * @throws \OCA\Passwords\Exception\ApiException
     */
    public function get(): JSONResponse {
        $params = $this->request->getParams();
        unset($params['_route']);

        if(empty($params)) throw new ApiException('Invalid Key', 400);

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
     * @throws PreConditionNotMetException
     */
    public function set(): JSONResponse {
        $params = $this->request->getParams();
        unset($params['_route']);

        if(empty($params)) throw new ApiException('Invalid Key', 400);

        $settings = [];
        foreach($params as $key => $value) {
            $this->settings->set($key, $value);
            $settings[ $key ] = $value;
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
     * @throws ApiException
     */
    public function list(array $scopes = null): JSONResponse {
        return $this->createJsonResponse(
            $this->settings->listSettings($scopes)
        );
    }

    /**
     * @CORS
     * @NoCSRFRequired
     * @NoAdminRequired
     *
     * @return JSONResponse
     * @throws ApiException
     * @throws PreConditionNotMetException
     */
    public function reset(): JSONResponse {
        $params = $this->request->getParams();
        unset($params['_route']);

        $settings = [];
        if(empty($params)) throw new ApiException('Invalid Key', 400);
        foreach($params as $key) {
            $settings[ $key ] = $this->settings->reset($key);
        }

        return $this->createJsonResponse($settings);
    }
}