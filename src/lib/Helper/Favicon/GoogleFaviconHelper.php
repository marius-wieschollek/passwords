<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Exception\Favicon\FaviconRequestException;
use OCA\Passwords\Exception\Favicon\UnexpectedResponseCodeException;
use OCA\Passwords\Services\HelperService;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Throwable;

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
    protected string $prefix = HelperService::FAVICON_GOOGLE;

    /**
     * @var string
     */
    protected string $domain;

    /**
     * @param string $domain
     *
     * @return array
     */
    protected function getRequestData(string $domain): array {
        $this->domain = $domain;

        return [
            "https://www.google.com/s2/favicons?domain={$domain}",
            []
        ];
    }

    /**
     * @param string $uri
     * @param array  $options
     *
     * @return string
     * @throws FaviconRequestException
     * @throws UnexpectedResponseCodeException
     * @throws NotFoundException
     * @throws NotPermittedException
     * @throws Throwable
     */
    protected function executeRequest(string $uri, array $options): string {
        $result = parent::executeRequest($uri, $options);

        if(md5($result) === self::DEFAULT_ICON_MD5) {
            return $this->getDefaultFavicon($this->domain)->getContent();
        }

        return $result;
    }

}