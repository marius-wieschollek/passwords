<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Helper\Favicon;

use OCA\Passwords\Helper\Http\RequestHelper;
use OCA\Passwords\Helper\Icon\FallbackIconGenerator;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\HelperService;

/**
 * Class FaviconGrabberHelper
 *
 * @package OCA\Passwords\Helper\Favicon
 */
class FaviconGrabberHelper extends AbstractFaviconHelper {

    const FAVICON_GRABBER_URL = 'http://favicongrabber.com';
    const API_WAIT_TIME       = 1;

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * @var string
     */
    protected $prefix = HelperService::FAVICON_FAVICON_GRABBER;

    /**
     * BestIconHelper constructor.
     *
     * @param ConfigurationService  $config
     * @param HelperService         $helperService
     * @param FileCacheService      $fileCacheService
     * @param FallbackIconGenerator $fallbackIconGenerator
     *
     * @throws \OCP\AppFramework\QueryException
     */
    public function __construct(
        ConfigurationService $config,
        HelperService $helperService,
        FileCacheService $fileCacheService,
        FallbackIconGenerator $fallbackIconGenerator
    ) {
        $this->config = $config;
        parent::__construct($helperService, $fileCacheService, $fallbackIconGenerator);
    }

    /**
     * @param string $domain
     *
     * @return string
     * @throws \Exception
     * @throws \Throwable
     */
    protected function getFaviconData(string $domain): ?string {
        $json = $this->sendApiRequest($domain);
        $icon = $this->analyzeApiResponse($json);

        if($icon === null) return $this->getDefaultFavicon($domain)->getContent();
        return $this->getHttpRequest($icon);
    }

    /**
     * @param string $domain
     *
     * @return array
     */
    protected function sendApiRequest(string $domain): array {
        $this->checkRequestTimeout();
        $request = new RequestHelper();
        $data = $request
            ->setAcceptResponseCodes([200, 400])
            ->setUserAgent(
                'Nextcloud/'.$this->config->getSystemValue('version').
                ' Passwords/'.$this->config->getAppValue('installed_version').
                ' Instance/'.$this->config->getSystemValue('instanceid')
            )->send(self::FAVICON_GRABBER_URL.'/api/grab/'.$domain);
        $this->setLastRequestTime();

        return json_decode($data, true);
    }

    /**
     * @param array $json
     *
     * @return null|string
     * @throws \Exception
     * @throws \Throwable
     */
    protected function analyzeApiResponse(array $json): ?string {
        if(isset($json['error'])) throw new \Exception("Favicongrabber said: {$json['error']}");

        $iconUrl = null;
        $sizeOffset = null;
        foreach($json['icons'] as $icon) {
            if($iconUrl === null) {
                $iconUrl = $icon['src'];
            } else if(isset($icon['sizes']) && $icon['sizes'] !== 'any') {
                $size = explode($icon['sizes'], 'x')[0];
                $offset = abs(256 - $size);
                if($offset < $sizeOffset || $sizeOffset === null) {
                    $sizeOffset = $offset;
                    $iconUrl = $icon['src'];
                }
            }
        }

        return $iconUrl;
    }

    /**
     *
     */
    protected function checkRequestTimeout(): void {
        $lastRequest = $this->config->getAppValue('security/fg/api/request', 0);
        if(time()-$lastRequest < self::API_WAIT_TIME) {
            sleep(self::API_WAIT_TIME);
        }
    }

    /**
     *
     */
    protected function setLastRequestTime(): void {
        $this->config->setAppValue('security/fg/api/request', time());
    }
}