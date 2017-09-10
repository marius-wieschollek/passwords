<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 01:46
 */

namespace OCA\Passwords\Helper\PageShot;

use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\PageShotService;

/**
 * Class ScreenShotLayerHelper
 *
 * @package OCA\Passwords\Helper\PageShot
 */
class ScreenShotLayerHelper extends AbstractPageShotHelper {

    /**
     * @var string
     */
    protected $prefix = 'sl';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * ScreenShotLayerHelper constructor.
     *
     * @param FileCacheService     $fileCacheService
     * @param ConfigurationService $config
     */
    public function __construct(FileCacheService $fileCacheService, ConfigurationService $config) {
        parent::__construct($fileCacheService);
        $this->config = $config;
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    protected function getPageShotUrl(string $domain, string $view): string {
        $apiKey = $this->config->getAppValue('service/pageshot/screenshotlayer/apiKey');

        if($view === PageShotService::VIEWPORT_DESKTOP) {
            return "http://api.screenshotlayer.com/api/capture?access_key={$apiKey}&viewport=1280x720&width=720&fullpage=1&url=http://{$domain}";
        }

        return "http://api.screenshotlayer.com/api/capture?access_key={$apiKey}&viewport=360x640&width=720&fullpage=1&url=http://{$domain}";
    }
}