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

use Exception;
use GuzzleHttp\Exception\ClientException;
use OCA\Passwords\Exception\Favicon\FaviconRequestException;
use OCA\Passwords\Exception\Favicon\UnexpectedResponseCodeException;
use OCA\Passwords\Services\HelperService;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use Throwable;

/**
 * Class DuckDuckGoProvider
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class DuckDuckGoProvider extends AbstractFaviconProvider {

    /**
     * @var string
     */
    protected string $prefix = HelperService::FAVICON_DUCK_DUCK_GO;

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
            "https://icons.duckduckgo.com/ip2/{$domain}.ico",
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
        $request = $this->createRequest();
        try {
            $response = $request->get($uri, $options);
        } catch(ClientException $e) {
            if($e->getResponse()->getStatusCode() === 404) {
                return $this->getDefaultFavicon($this->domain)->getContent();
            }

            throw new UnexpectedResponseCodeException($e->getResponse()->getStatusCode(), $e);
        } catch(Exception $e) {
            throw new FaviconRequestException($e);
        }

        if($response->getStatusCode() === 200) {
            return $response->getBody();
        }

        throw new UnexpectedResponseCodeException($response->getStatusCode());
    }
}