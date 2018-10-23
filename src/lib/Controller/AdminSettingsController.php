<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Controller;

use OCA\Passwords\AppInfo\Application;
use OCA\Passwords\Helper\Favicon\BestIconHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Class AdminSettingsController
 *
 * @package OCA\Passwords\Controller
 */
class AdminSettingsController extends Controller {

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var FileCacheService
     */
    protected $fileCacheService;

    /**
     * AdminSettingsController constructor.
     *
     * @param string               $appName
     * @param IRequest             $request
     * @param ConfigurationService $config
     * @param FileCacheService     $fileCacheService
     */
    public function __construct($appName, IRequest $request, ConfigurationService $config, FileCacheService $fileCacheService) {
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

        if($key === 'backup/files/maximum' && $value < 0) $value = '';

        if($value === '') {
            $this->config->deleteAppValue($key);
        } else {
            $this->config->setAppValue($key, $value);
        }

        if($key === 'nightly_updates') $this->setNightlyStatus($value);

        return new JSONResponse(['status' => 'ok']);
    }

    /**
     * @param string $key
     *
     * @return JSONResponse
     */
    public function cache(string $key): JSONResponse {
        $this->fileCacheService->clearCache($key);

        if(
            $this->fileCacheService::FAVICON_CACHE == $key &&
            $this->config->getAppValue('service/favicon') === HelperService::FAVICON_BESTICON &&
            $this->config->getAppValue(BestIconHelper::BESTICON_CONFIG_KEY, BestIconHelper::BESTICON_DEFAULT_URL) === BestIconHelper::BESTICON_DEFAULT_URL
        ) {
            return new JSONResponse(['status' => 'error'], 400);
        }

        return new JSONResponse(['status' => 'ok']);
    }

    /**
     * @param $enabled
     */
    protected function setNightlyStatus($enabled): void {
        $nightlyApps = $this->config->getSystemValue('allowNightlyUpdates', []);

        if($enabled) {
            if(!in_array(Application::APP_NAME, $nightlyApps)) $nightlyApps[] = Application::APP_NAME;
        } else {
            $index = array_search(Application::APP_NAME, $nightlyApps);
            if($index !== FALSE) unset($nightlyApps[$index]);
        }

        $this->config->setSystemValue('allowNightlyUpdates', $nightlyApps);
    }
}