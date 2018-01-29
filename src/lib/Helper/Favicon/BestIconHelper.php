<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 00:27
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Services\HelperService;

/**
 * Class BetterIdeaHelper
 */
class BestIconHelper extends AbstractFaviconHelper {

    const BESTICON_DEFAULT_URL = 'https://besticon-demo.herokuapp.com/icon';

    /**
     * @var string
     */
    protected $prefix = HelperService::FAVICON_BESTICON;

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain, int $size): string {
        $fallbackColor = substr($this->fallbackIconGenerator->stringToColor($domain), 1);

        return self::BESTICON_DEFAULT_URL."?size=16..128..256&fallback_icon_color={$fallbackColor}&url={$domain}";
    }
}