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
use OCA\Passwords\Exception\Favicon\FaviconRequestException;
use OCA\Passwords\Exception\Favicon\UnexpectedResponseCodeException;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;
use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class FaviconGrabberProvider
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class FaviconGrabberProvider extends AbstractFaviconProvider {

    const FAVICON_GRABBER_URL = 'https://favicongrabber.com';

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $config;

    /**
     * @var string
     */
    protected string $prefix = HelperService::FAVICON_FAVICON_GRABBER;

    /**
     * @var string
     */
    protected string $domain;

    /**
     * FaviconGrabberProvider constructor.
     *
     * @param LoggerInterface      $logger
     * @param ConfigurationService $config
     * @param HelperService        $helperService
     * @param IClientService       $requestService
     * @param FileCacheService     $fileCacheService
     */
    public function __construct(
        LoggerInterface       $logger,
        ConfigurationService $config,
        HelperService $helperService,
        IClientService $requestService,
        FileCacheService $fileCacheService
    ) {
        $this->config = $config;
        parent::__construct($helperService, $logger, $fileCacheService, $requestService);
    }

    /**
     * @param string $domain
     *
     * @return array
     */
    protected function getRequestData(string $domain): array {
        $this->domain = $domain;

        return [
            self::FAVICON_GRABBER_URL.'/api/grab/'.$domain,
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
     * @throws Exception
     * @throws Throwable
     */
    protected function executeRequest(string $uri, array $options): string {
        $response = parent::executeRequest($uri, $options);
        $json     = json_decode($response, true);

        $result = $this->analyzeApiResponse($json);
        if($result === null) {
            return $this->getDefaultFavicon($this->domain)->getContent();
        }

        return $result;
    }

    /**
     * @param array $json
     *
     * @return string
     * @throws Exception
     */
    protected function analyzeApiResponse(array $json): ?string {
        if(isset($json['error'])) throw new Exception("Favicongrabber said: {$json['error']} ({$this->domain})");

        $iconData   = null;
        $sizeOffset = null;
        foreach($json['icons'] as $icon) {
            [$iconData, $sizeOffset] = $this->analyzeApiIcon($icon, $iconData, $sizeOffset);
        }

        return $iconData;
    }

    /**
     * @param $icon
     * @param $iconData
     * @param $sizeOffset
     *
     * @return array
     */
    protected function analyzeApiIcon($icon, $iconData, $sizeOffset): array {
        $info = pathinfo($icon['src']);
        if(!isset($info['extension'])) return [$iconData, $sizeOffset];
        $ext = $info['extension'];
        if(!in_array($ext, ['png', 'ico', 'gif', 'jpg', 'jpeg'])) return [$iconData, $sizeOffset];

        if($iconData === null) {
            $iconData = $this->loadIcon($icon['src']);
        } else if(isset($icon['sizes'])) {
            $size = explode('x', $icon['sizes'])[0];
            if(!is_numeric($size)) return [$iconData, $sizeOffset];

            $offset = abs(256 - $size);
            if($offset < $sizeOffset || $sizeOffset === null) {
                $sizeOffset = $offset;
                $iconData   = $this->loadIcon($icon['src'], $iconData);
            }
        }

        return [$iconData, $sizeOffset];
    }

    /**
     * @param string      $url
     * @param string|null $data
     *
     * @return null|string
     */
    protected function loadIcon(string $url, string $data = null): ?string {
        $request = $this->requestService->newClient();
        try {
            $response = $request->get($url, ['idn_conversion' => true]);
        } catch(Exception $e) {
            return $data;
        }

        $mime = $response->getHeader('content-type');
        if(substr($mime, 0, 5) !== 'image') return $data;

        $iconData = $response->getBody();

        return empty($iconData) ? $data:$iconData;
    }
}