<?php
/*
 * @copyright 2024 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Controller\Api;

use Exception;
use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Services\UserSettingsService;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
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
    protected UserSettingsService $settings;

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
     * @return JSONResponse
     * @throws Exception
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
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
     * @return JSONResponse
     * @throws ApiException
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
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
     * @param array|null $scopes
     *
     * @return JSONResponse
     * @throws Exception
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function list(array $scopes = null): JSONResponse {
        return $this->createJsonResponse(
            $this->settings->list($scopes)
        );
    }

    /**
     * @return JSONResponse
     * @throws Exception
     */
    #[CORS]
    #[NoCSRFRequired]
    #[NoAdminRequired]
    public function reset(): JSONResponse {
        $params = $this->getParameterArray();
        if(empty($params)) return $this->createJsonResponse([]);

        $settings = [];
        foreach($params as $key) {
            $settings[ $key ] = $this->settings->reset($key);
        }

        return $this->createJsonResponse($settings);
    }

    /**
     * @return array
     */
    protected function getParameterArray(): array {
        $params = parent::getParameterArray();
        if(array_key_exists('action', $params)) unset($params['action']);

        return $params;
    }
}