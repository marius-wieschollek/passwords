<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 02:31
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
            return $this->getDefaultFavicon($this->domain, $this->size)->getContent();
        };

        return $result;
    }
}