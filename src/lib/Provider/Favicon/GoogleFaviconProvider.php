<?php
/*
 * @copyright 2023 Passwords App
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the Passwords App
 * created by Marius David Wieschollek.
 */

namespace OCA\Passwords\Provider\Favicon;

use OCA\Passwords\Exception\Favicon\FaviconRequestException;
use OCA\Passwords\Exception\Favicon\UnexpectedResponseCodeException;
use OCA\Passwords\Services\HelperService;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Throwable;

/**
 * Class GoogleFaviconProvider
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class GoogleFaviconProvider extends AbstractFaviconProvider {
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
            "https://www.google.com/s2/favicons?domain={$domain}&sz=256",
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
