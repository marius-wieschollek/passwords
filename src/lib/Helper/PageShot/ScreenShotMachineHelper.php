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
class ScreenShotMachineHelper extends AbstractPageShotHelper {

    /**
     * @var string
     */
    protected $prefix = HelperService::PAGESHOT_SCREEN_SHOT_MACHINE;

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    protected function getPageShotUrl(string $domain, string $view): string {
        $apiKey = $this->config->getAppValue('service/pageshot/ssm/key');

        if($view === PageShotService::VIEWPORT_DESKTOP) {
            return "http://api.screenshotmachine.com/?key={$apiKey}&dimension={$this::WIDTH_DESKTOP}xfull&device=desktop&format=jpg&url=http://{$domain}";
        }

        return "http://api.screenshotmachine.com/?key={$apiKey}&dimension={$this::WIDTH_MOBILE}xfull&device=phone&format=jpg&url=http://{$domain}";
    }
}