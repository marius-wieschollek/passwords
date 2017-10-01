<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 01:46
 */

namespace OCA\Passwords\Helper\PageShot;

use OCA\Passwords\Services\HelperService;
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
    protected $prefix = HelperService::PAGESHOT_SCREEN_SHOT_LAYER;

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    protected function getPageShotUrl(string $domain, string $view): string {
        $apiKey = $this->config->getAppValue('service/pageshot/ssl/key');

        if($view === PageShotService::VIEWPORT_DESKTOP) {
            return "http://api.screenshotlayer.com/api/capture?access_key={$apiKey}&viewport={$this::VIEWPORT_DESKTOP}&width=720&fullpage=1&url=http://{$domain}";
        }

        return "http://api.screenshotlayer.com/api/capture?access_key={$apiKey}&viewport={$this::VIEWPORT_MOBILE}&width=720&fullpage=1&url=http://{$domain}";
    }
}