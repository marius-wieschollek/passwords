<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Services\FileCacheService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;

/**
 * Class AdminSettingsController
 *
 * @package OCA\Passwords\Controller
 */
class AdminSettingsController extends Controller {

    /**
     * @var IConfig
     */
    protected $config;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * AdminSettingsController constructor.
     *
     * @param string           $appName
     * @param IRequest         $request
     * @param IConfig          $config
     * @param FileCacheService $fileCacheService
     */
    public function __construct($appName, IRequest $request, IConfig $config, FileCacheService $fileCacheService) {
        parent::__construct($appName, $request);
        $this->config           = $config;
        $this->fileCacheService = $fileCacheService;
    }

    /**
     * @param string $key
     * @param        $value
     *
     * @return JSONResponse
     */
    public function set(string $key, $value): JSONResponse {

        if($value === 'true') $value = true;
        if($value === 'false') $value = false;

        $this->config->setAppValue(Application::APP_NAME, $key, $value);

        return new JSONResponse(['status' => 'ok']);
    }

    /**
     * @param string $key
     *
     * @return JSONResponse
     */
    public function cache(string $key): JSONResponse {
        $this->fileCacheService->clearCache($key);

        return new JSONResponse(['status' => 'ok']);
    }
}