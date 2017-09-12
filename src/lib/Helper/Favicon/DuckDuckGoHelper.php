<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 02:34
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Services\HelperService;

/**
 * Class DuckDuckGoHelper
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class DuckDuckGoHelper extends AbstractFaviconHelper {

    /**
     * @var string
     */
    protected $prefix = HelperService::FAVICON_DUCK_DUCK_GO;

    /**
     * @param string $domain
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain): string {
        return "https://icons.duckduckgo.com/ip2/{$domain}.ico";
    }

    /**
     * @param string $url
     *
     * @return mixed|string
     */
    protected function getHttpRequest(string $url) {
        $result = parent::getHttpRequest($url);

        $data = @gzdecode($result);
        if($data) return $data;

        return $result;
    }
}