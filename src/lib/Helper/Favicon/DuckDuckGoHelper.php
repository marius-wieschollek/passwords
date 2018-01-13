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

    /** @var string */
    protected $domain;

    /** @var int */
    protected $size = 32;

    /**
     * @param string $domain
     * @param int    $size
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain, int $size): string {
        $this->domain = $domain;
        $this->size   = $size;

        return "https://icons.duckduckgo.com/ip2/{$domain}.ico";
    }

    /**
     * @param string $url
     *
     * @return mixed|string
     * @throws \Throwable
     */
    protected function getHttpRequest(string $url) {
        $result = parent::getHttpRequest($url);

        if(!$result) return $this->getDefaultFavicon($this->domain, $this->size)->getContent();

        $data = @gzdecode($result);
        if($data) return $data;

        return $result;
    }
}