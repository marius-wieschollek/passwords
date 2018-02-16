<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
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
     * @var string
     */
    protected $domain;

    /**
     * @param string $domain
     *
     * @return string
     */
    protected function getFaviconUrl(string $domain): string {
        $this->domain = $domain;

        return "https://icons.duckduckgo.com/ip2/{$domain}.ico";
    }

    /**
     * @param string $url
     *
     * @return mixed|string
     * @throws \Throwable
     */
    protected function getHttpRequest(string $url): string {
        $result = parent::getHttpRequest($url);

        if(!$result) return $this->getDefaultFavicon($this->domain)->getContent();

        $data = @gzdecode($result);
        if($data) return $data;

        return $result;
    }
}