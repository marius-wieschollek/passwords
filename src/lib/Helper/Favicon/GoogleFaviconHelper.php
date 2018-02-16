<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Services\HelperService;

/**
 * Class GoogleFaviconHelper
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class GoogleFaviconHelper extends AbstractFaviconHelper {
    const DEFAULT_ICON_MD5 = '3ca64f83fdcf25135d87e08af65e68c9';

    /**
     * @var string
     */
    protected $prefix = HelperService::FAVICON_GOOGLE;

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

        return 'https://www.google.com/s2/favicons?domain='.$domain;
    }

    /**
     * @param string $url
     *
     * @return mixed|null|\OCP\Files\SimpleFS\ISimpleFile|string
     * @throws \Throwable
     */
    protected function getHttpRequest(string $url): string {
        $result = parent::getHttpRequest($url);

        if(md5($result) === self::DEFAULT_ICON_MD5) {
            return $this->getDefaultFavicon($this->domain)->getContent();
        };

        return $result;
    }
}