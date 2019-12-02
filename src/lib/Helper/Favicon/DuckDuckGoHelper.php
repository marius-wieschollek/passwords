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
     * @return string
     * @throws UnexpectedResponseCodeException
     * @throws FaviconRequestException
     * @throws \Throwable
     */
    protected function executeRequest(string $uri, array $options): string {
        $request = $this->createRequest();
        try {
            $response = $request->get($uri, $options);
        } catch(\Exception $e) {
            throw new FaviconRequestException($e);
        }

        if($response->getStatusCode() === 404) {
            return $this->getDefaultFavicon($this->domain)->getContent();
        }

        if($response->getStatusCode() === 200) {
            return $response->getBody();
        }

        throw new UnexpectedResponseCodeException($response->getStatusCode());
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