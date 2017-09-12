<?php
/**
 * Created by PhpStorm.
 * User: marius
 * Date: 10.09.17
 * Time: 15:23
 */

namespace OCA\Passwords\Helper\PageShot;

use OCA\Passwords\Exception\ApiException;
use OCA\Passwords\Helper\HttpRequestHelper;
use OCA\Passwords\Services\ConfigurationService;
use OCA\Passwords\Services\FileCacheService;
use OCA\Passwords\Services\PageShotService;

/**
 * Class ScreenShotApiHelper
 *
 * @package OCA\Passwords\Helper\PageShot
 */
class ScreenShotApiHelper extends AbstractPageShotHelper {
    const SERVICE_PROCESSING_TIMEOUT = 60;

    /**
     * @var string
     */
    protected $prefix = 'ssa';

    /**
     * @var string
     */
    protected $domain = '';

    /**
     * @var string
     */
    protected $viewport = '';

    /**
     * @var ConfigurationService
     */
    protected $config;

    /**
     * ScreenShotLayerHelper constructor.
     *
     * @param FileCacheService     $fileCacheService
     * @param ConfigurationService $config
     */
    public function __construct(FileCacheService $fileCacheService, ConfigurationService $config) {
        parent::__construct($fileCacheService);
        $this->config = $config;
    }

    /**
     * @param string $url
     *
     * @return mixed
     * @throws ApiException
     */
    protected function getHttpRequest(string $url) {
        $request = $this->getAuthorizedRequest($url);
        $request->setJson($this->getServiceOptions());

        $image = json_decode($request->sendWithRetry(), true);

        if($image['status'] !== 'accepted' && $image['status'] !== 'ready') {
            \OC::$server->getLogger()->error('screenshotapi.io service refused request');

            return null;
        }

        $seconds = 0;
        while ($seconds < self::SERVICE_PROCESSING_TIMEOUT) {
            $request = $this->getAuthorizedRequest('https://api.screenshotapi.io/retrieve?key='.$image['key']);
            $check   = json_decode($request->sendWithRetry(1), true);

            if($check['status'] === 'ready') {
                $load = new HttpRequestHelper($check['imageUrl']);

                return $load->sendWithRetry();
            } else if($check['status'] === 'error' || isset($check['error'])) {
                $message = isset($check['msg']) ? $check['msg']:$check['message'];
                \OC::$server->getLogger()->error($message);

                return null;
            }

            sleep(1);
            $seconds++;
        }

        \OC::$server->getLogger()->error('screenshotapi.io service did not complete within 60 seconds');

        return null;
    }

    /**
     * @param string $url
     *
     * @return HttpRequestHelper
     */
    protected function getAuthorizedRequest(string $url): HttpRequestHelper {
        $request = new HttpRequestHelper($url);

        return $request->setHeader($this->getServiceAuth())->setAcceptResponseCodes([]);
    }

    /**
     * @return array
     */
    protected function getServiceOptions(): array {
        return [

            'url'         => $this->domain,
            'viewport'    => $this->viewport,
            'fullpage'    => true,
            'javascript'  => true,
            'webdriver'   => 'firefox',
            'waitSeconds' => 2,
            'fresh'       => false
        ];
    }

    /**
     * @return array
     */
    protected function getServiceAuth(): array {
        $apiKey = $this->config->getAppValue('service/pageshot/ssa/key');

        return ['apikey' => $apiKey];
    }

    /**
     * @param string $domain
     * @param string $view
     *
     * @return string
     */
    protected function getPageShotUrl(string $domain, string $view): string {
        $this->domain   = 'http://'.$domain;
        $this->viewport = self::VIEWPORT_MOBILE;

        if($view === PageShotService::VIEWPORT_DESKTOP) {
            $this->viewport = self::VIEWPORT_DESKTOP;
        }

        return 'https://api.screenshotapi.io/capture';
    }
}